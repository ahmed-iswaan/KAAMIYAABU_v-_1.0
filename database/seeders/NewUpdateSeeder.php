<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\Directory;
use App\Models\Country;
use App\Models\Island;
use App\Models\Property;
use App\Models\Party;
use App\Models\SubConsite;
use App\Models\EventLog;
use Carbon\Carbon;

class NewUpdateSeeder extends Seeder
{
    /**
     * Update directories from database/seeders/data/electiondatalistmale.json
     * - Upsert by ID card number
     * - Overwrite fields when changed; log changes
     * - Merge phone numbers (keep old, add new unique)
     * - Create property under the resolved island when missing
     */
    public function run(): void
    {
        $file = database_path('seeders/data/electiondatalistmale.json');
        if (!File::exists($file)) {
            $this->command->error("JSON file missing: {$file}");
            return;
        }

        $rows = json_decode(File::get($file), true);
        if (!is_array($rows)) { $this->command->error('electiondatalistmale.json not valid JSON array.'); return; }

        $maldivesId = Country::where('name', 'Maldives')->value('id');
        if (!$maldivesId) { $this->command->error('Country Maldives missing.'); return; }

        // Preload islands with strict mapping for Malé, Hulhumalé', Villimalé
        $islandIndex = [];
        foreach (Island::all(['id','name']) as $i) {
            $base = mb_strtolower($i->name);
            $normalized = str_replace('é', 'e', $base);
            $islandIndex[$base] = $i->id;
            $islandIndex[$normalized] = $i->id;
        }

        // Explicitly handle real DB names: "Hulhumalé'", "Malé", "Villimalé"
        $islandMaleId = $islandIndex['malé']
            ?? $islandIndex["male'"]
            ?? $islandIndex['male']
            ?? null;

        $islandHulhumaleId = $islandIndex["hulhumalé'"]
            ?? $islandIndex['hulhumalé']
            ?? $islandIndex["hulhumale'"]
            ?? $islandIndex['hulhumale']
            ?? null;

        $islandVilimaleId = $islandIndex['villimalé']
            ?? $islandIndex['villimale']
            ?? $islandIndex['vilimale']
            ?? null;

        // Preload parties & subconsite (party may not exist in this dataset)
        $partyMap = Party::all()->mapWithKeys(fn($p)=>[strtoupper(trim($p->short_name)) => $p->id])->toArray();
        $subConsiteMap = SubConsite::all()->mapWithKeys(fn($s)=>[strtoupper(trim($s->code))=>$s->id])->toArray();

        $created = 0; $updated = 0; $skipped = 0; $phoneMerged = 0; $processed = 0; $total = count($rows);

        foreach ($rows as $r) {
            $processed++;
            $nid = trim((string)($r['Full ID'] ?? $r['Id #'] ?? $r['id_card'] ?? ''));
            if ($nid === '') { $skipped++; continue; }
            $name = trim((string)($r['Name'] ?? $r['name'] ?? ''));
            if ($name === '') { $skipped++; continue; }

            // SERIAL (nullable) - only update when JSON has a non-empty value
            $serialRaw = $r['SERIAL'] ?? $r['serial'] ?? null;
            $serial = null;
            if ($serialRaw !== null) {
                $serial = trim((string) $serialRaw);
                if ($serial === '') { $serial = null; }
            }

            // Gender
            $genderRaw = strtoupper(trim((string)($r['Sex'] ?? $r['GENDER'] ?? $r['gender'] ?? '')));
            $gender = $genderRaw === 'MALE' || $genderRaw === 'M' ? 'male' : ($genderRaw === 'FEMALE' || $genderRaw === 'F' ? 'female' : 'other');

            // Party
            $partyShort = strtoupper(trim($r['PARTY'] ?? $r['party'] ?? ''));
            $partyId = $partyShort !== '' ? ($partyMap[$partyShort] ?? null) : null;

            // SubConsite
            $subCode = strtoupper(trim((string)($r['Consit'] ?? $r['CODE'] ?? $r['sub_code'] ?? '')));
            $subConsiteId = $subCode !== '' ? ($subConsiteMap[$subCode] ?? null) : null;
            $hasDatasetSubConsite = $subCode !== '' && !empty($subConsiteId);

            // Island mapping by Island field first; fallback to prior heuristics
            $islandNameRaw = trim((string)($r['Island'] ?? $r['ISLAND'] ?? ''));
            $islandId = $this->resolveIslandIdFromDatasetName($islandNameRaw, $islandIndex, $islandMaleId, $islandHulhumaleId, $islandVilimaleId);

            // Also normalize stored island name for lookups (no DB write here, used only to resolve ID)
            if (!$islandId) {
                // Fallback to Mauru code-based mapping (kept for compatibility)
                $isMaleCodes = ['T02','T03','T04','T05','T06','T07','T08','T09','T10','T11','T12','T14','T15'];
                $isHulhumaleCodes = ['T01','T16','T17'];
                $isVilimaleCodes = ['T13'];
                if (in_array($subCode, $isHulhumaleCodes, true)) {
                    $islandId = $islandHulhumaleId;
                } elseif (in_array($subCode, $isMaleCodes, true)) {
                    $islandId = $islandMaleId;
                } elseif (in_array($subCode, $isVilimaleCodes, true)) {
                    $islandId = $islandVilimaleId;
                }
            }

            // Always set current island from resolved island
            $currentIslandId = $islandId;

            // DOB
            $dobRaw = trim((string)($r['DOB'] ?? $r['D.O.B'] ?? $r['dob'] ?? ''));
            $dob = null;
            if ($dobRaw !== '' && strtoupper($dobRaw) !== '#VALUE!') {
                $dob = $this->parseDob($dobRaw);
            }

            // Phones
            $phonesNew = array_values(array_filter([
                $this->normalizePhone((string)($r['phone 1'] ?? '')),
                $this->normalizePhone((string)($r['phone 2'] ?? '')),
            ]));

            $existing = Directory::where('id_card_number', $nid)->first();
            $mergedPhones = $existing && is_array($existing->phones) ? collect($existing->phones) : collect();
            $beforePhones = $mergedPhones->implode(',');
            foreach ($phonesNew as $ph) { if(!$mergedPhones->contains($ph) && $ph!=='0') { $mergedPhones->push($ph); } }
            $afterPhones = $mergedPhones->implode(',');
            $phonesChanged = $beforePhones !== $afterPhones;

            // Properties strictly from ADDRESS, same for current
            $addressRaw = trim((string)($r['Address'] ?? $r['ADDRESS'] ?? $r['address'] ?? ''));
            [$permPropertyId, $permStreet] = $this->resolveOrCreateProperty($addressRaw, $islandId);
            [$currPropertyId, $currStreet] = $this->resolveOrCreateProperty($addressRaw, $currentIslandId);

            $payload = [
                'name' => $name,
                'gender' => $gender,
                // Set serial if provided, otherwise keep existing value
                'serial' => $serial ?? ($existing?->serial),
                'date_of_birth' => $dob ?: ($existing?->date_of_birth),
                'phones' => $mergedPhones->values()->all(),
                'country_id' => $maldivesId,
                'island_id' => $islandId,
                'properties_id' => $permPropertyId,
                'street_address' => $permStreet ?: ($existing?->street_address),
                'current_country_id' => $maldivesId,
                'current_island_id' => $currentIslandId,
                'current_properties_id' => $currPropertyId,
                'current_street_address' => $currStreet ?: ($existing?->current_street_address),
                'party_id' => $partyId ?? ($existing?->party_id),
                // If Consit code provided and found in DB, always overwrite.
                'sub_consite_id' => $hasDatasetSubConsite ? $subConsiteId : ($existing?->sub_consite_id),
                'status' => $existing?->status ?? 'Active',
            ];

            if ($existing) {
                $original = $existing->getOriginal();
                $existing->fill($payload)->save();
                $updated++;
                $changes = [];
                foreach ($payload as $k=>$v) {
                    if ($k==='phones') continue;
                    if (($original[$k] ?? null) != $existing->$k) { $changes[$k] = ['from'=>($original[$k] ?? null),'to'=>$existing->$k]; }
                }
                if ($phonesChanged) { $changes['phones'] = ['from'=>$beforePhones,'to'=>$afterPhones]; $phoneMerged++; }
                if (!empty($changes)) {
                    EventLog::create([
                        'user_id' => auth()->id() ?? null,
                        'event_type' => 'directory_updated',
                        'event_tab' => 'directory',
                        'event_entry_id' => $existing->id,
                        'description' => 'Directory updated via NewUpdateSeeder 04/02/2026',
                        'event_data' => $changes,
                        'ip_address' => request()->ip() ?? null,
                    ]);
                }
            } else {
                $payload['id'] = (string) Str::uuid();
                $payload['id_card_number'] = $nid;
                $dir = Directory::create($payload);
                $created++;
                EventLog::create([
                    'user_id' => auth()->id() ?? null,
                    'event_type' => 'directory_created',
                    'event_tab' => 'directory',
                    'event_entry_id' => $dir->id,
                    'description' => 'Directory created via NewUpdateSeeder 04/02/2026',
                    'event_data' => [
                        'id_card_number' => $nid,
                        'name' => $name,
                        'phones' => $phonesNew,
                    ],
                    'ip_address' => request()->ip() ?? null,
                ]);
            }

            if ($processed % 1000 === 0) {
                $this->command->info("Processed {$processed}/{$total} ...");
            }
        }

        $this->command->info("✅ Update complete. Total: {$total} | Created: {$created} | Updated: {$updated} | Skipped: {$skipped} | Phones merged: {$phoneMerged}");
    }

    private function parsePhones(string $raw): array
    {
        if (trim($raw) === '') return [];
        return collect(preg_split('/\|/',$raw))
            ->map(fn($p)=>preg_replace('/[^0-9+]/','', trim($p)))
            ->filter(fn($p)=>$p!=='' && $p!=='0')
            ->unique()
            ->values()
            ->all();
    }

    private function parseDob(?string $raw)
    {
        if (!$raw) return null;
        $raw = trim($raw);
        if ($raw === '' || $raw === '0' || strtoupper($raw) === '#VALUE!') return null;
        $parts = preg_split('/[\/\-]/', $raw);
        if (count($parts) === 3) {
            [$a,$b,$y] = $parts;
            $a = (int)$a; $b = (int)$b; $y = (int)$y;
            $year = $y < 30 ? (2000 + $y) : (1900 + $y);
            if ($a >=1 && $a <=12 && $b >=1 && $b <=31) {
                try { return Carbon::createFromDate($year, $a, $b); } catch(\Exception $e) {}
            }
            if ($b >=1 && $b <=12 && $a >=1 && $a <=31) {
                try { return Carbon::createFromDate($year, $b, $a); } catch(\Exception $e) {}
            }
        }
        foreach (['d/m/Y','m/d/Y','d-m-Y','m-d-Y'] as $fmt) {
            try { return Carbon::createFromFormat($fmt, $raw); } catch(\Exception $e) {}
        }
        return null;
    }

    private function resolveOrCreateProperty(string $raw, ?string $islandId): array
    {
        $rawOriginal = trim($raw);
        if ($rawOriginal === '' || !$islandId) return [null, null];
        $prop = Property::where('island_id', $islandId)
            ->where(function($q) use($rawOriginal){
                $q->where('name', $rawOriginal)->orWhere('number', $rawOriginal)->orWhere('register_number', $rawOriginal);
            })->first();
        if ($prop) return [$prop->id, $rawOriginal];
        if (preg_match('/^\d[\d-]*$/', $rawOriginal)) {
            $prop = Property::where('island_id',$islandId)->where(function($q) use($rawOriginal){
                $q->where('number',$rawOriginal)->orWhere('register_number',$rawOriginal);
            })->first();
            if ($prop) return [$prop->id, $rawOriginal];
        }
        $defaultTypeId = \App\Models\PropertyTypes::query()->value('id');
        $new = Property::create([
            'id' => (string) Str::uuid(),
            'island_id' => $islandId,
            'name' => $rawOriginal,
            'number' => preg_match('/^\d[\d-]*$/',$rawOriginal) ? $rawOriginal : null,
            'register_number' => null,
            'property_type_id' => $defaultTypeId,
            'status' => 'Active',
        ]);
        return [$new->id, $rawOriginal];
    }

    private function resolveIslandFallback(string $dhaaira, ?string $maleId, ?string $hulhumaleId, ?string $vilimaleId): ?string
    {
        $lower = mb_strtolower($dhaaira);
        $islandId = null;
        if ($lower === '') return $islandId;
        if (str_contains($lower, 'hulhumale')) { $islandId = $hulhumaleId; }
        elseif (str_contains($lower, 'vilimale') || str_contains($lower,'villimale')) { $islandId = $vilimaleId; }
        elseif (str_contains($lower, 'henveiru') || str_contains($lower,'galolhu') || str_contains($lower,'maafannu') || str_contains($lower,'machchangoalhi') || str_contains($lower,'mahchangoalhi')) { $islandId = $maleId; }
        return $islandId;
    }

    private function normalizePhone(string $raw): string
    {
        $p = preg_replace('/[^0-9+]/', '', trim($raw));
        return $p === '0' ? '' : (string) $p;
    }

    private function resolveIslandIdFromDatasetName(
        string $datasetIsland,
        array $islandIndex,
        ?string $maleId,
        ?string $hulhumaleId,
        ?string $vilimaleId
    ): ?string {
        $raw = mb_strtolower(trim($datasetIsland));
        if ($raw === '') return null;

        // Normalize common punctuation/spaces
        $raw = str_replace(['  ', "\t"], ' ', $raw);
        $raw = str_replace('é', 'e', $raw);

        // Handle prefixes like "k." or "k"
        $rawNoAtoll = preg_replace("/^k\.?\s*/", '', $raw);

        // Canonicalize common island variants
        $canonical = $rawNoAtoll;
        $canonical = str_replace(["male'", 'male', 'malé'], 'malé', $canonical);
        $canonical = str_replace(["hulhumale'", 'hulhumale', "hulhumalé'", 'hulhumalé'], "hulhumalé'", $canonical);
        $canonical = str_replace(['vilimale', 'villimale', 'villimalé', 'vilimalé'], 'villimalé', $canonical);

        // Direct index lookup by raw/canonical
        $key1 = mb_strtolower($datasetIsland);
        $key2 = str_replace('é', 'e', $key1);
        $key3 = mb_strtolower($canonical);
        $key4 = str_replace('é', 'e', $key3);

        $found = $islandIndex[$key1] ?? $islandIndex[$key2] ?? $islandIndex[$key3] ?? $islandIndex[$key4] ?? null;
        if ($found) return $found;

        // If still not found, return known IDs based on canonical string
        if (str_contains($canonical, 'malé')) return $maleId;
        if (str_contains($canonical, 'hulhumal')) return $hulhumaleId;
        if (str_contains($canonical, 'vilimal')) return $vilimaleId;

        return null;
    }
}
