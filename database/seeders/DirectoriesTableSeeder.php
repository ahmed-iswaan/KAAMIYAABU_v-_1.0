<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Directory;
use App\Models\Island;
use App\Models\Property;
use App\Models\Country;
use App\Models\Party;
use App\Models\SubConsite;
use App\Models\Wards;

class DirectoriesTableSeeder extends Seeder
{
    /**
     * Run the directory people seeds using directorydata.json (array JSON).
     */
    public function run(): void
    {
        $file = database_path('seeders/data/directorydata.json');
        if (! File::exists($file)) {
            $this->command->error("JSON file missing: {$file}");
            return;
        }

        $maldivesId = Country::where('name', 'Maldives')->value('id');
        if (!$maldivesId) { $this->command->error('Country Maldives missing.'); return; }

        // Preload islands we intend to map (accent-insensitive)
        $islands = Island::all();
        $islandIndex = [];
        foreach ($islands as $i) {
            $base = mb_strtolower($i->name);
            $islandIndex[$base] = $i->id;
            if (str_contains($base, 'é')) { // add accentless variant
                $islandIndex[str_replace('é','e',$base)] = $i->id;
            }
        }
        // Derive common ids
        $islandHulhumaleId = $islandIndex['hulhumale'] ?? $islandIndex['hulhumalé'] ?? null;
        if(!$islandHulhumaleId){
            // fallback: first key that starts with hulhuma
            foreach ($islandIndex as $k=>$v) { if (str_starts_with($k,'hulhuma')) { $islandHulhumaleId = $v; break; } }
        }
        $islandVilimaleId  = $islandIndex['vilimale']  ?? $islandIndex['villimale'] ?? null;
        $islandMaleId      = $islandIndex['malé'] ?? $islandIndex["male'"] ?? $islandIndex['male'] ?? null;
        if(!$islandHulhumaleId){ $this->command->warn('Island Hulhumale not found; rows mapped there will have null island.'); }

        $json = File::get($file);
        $rows = json_decode($json, true);
        if (! is_array($rows)) { $this->command->error('directorydata.json not valid JSON array.'); return; }

        $total = count($rows);
        $processed = 0; $created = 0; $updated = 0; $skipped = 0;

        // Preload parties by short_name (trimmed) map => id
        $partyMap = Party::all()->mapWithKeys(fn($p)=>[strtoupper(trim($p->short_name)) => $p->id])->toArray();
        // Preload sub consites by code map => id
        $subConsiteMap = SubConsite::all()->mapWithKeys(fn($s)=>[strtoupper(trim($s->code))=>$s->id])->toArray();
        // Preload properties by number/register_number and name (case-insensitive)
        $propertyMap = Property::all()->flatMap(function($prop){
            $keys = [];
            if($prop->number) $keys['NUM:'.strtoupper(trim($prop->number))] = $prop->id;
            if($prop->register_number) $keys['REG:'.strtoupper(trim($prop->register_number))] = $prop->id;
            if($prop->name) $keys['NAME:'.strtoupper(trim($prop->name))] = $prop->id;
            return $keys;
        })->toArray();
        // Preload wards (Malé wards) for detection (convert to array to satisfy type-hint)
        $wards = Wards::all();
        $wardPatterns = $wards->mapWithKeys(fn($w)=>[mb_strtolower($w->name)=>$w->id])->toArray();

        // Counters
        $missingSubConsite = 0; $missingParty = 0; $dhaairaUnmappedIsland = 0; $dhaairaMappedMale = 0; $dhaairaMappedHulhumale = 0; $dhaairaMappedVilimale = 0; $wardMatched = 0; $wardNotMatched = 0;
        $missingPermPropertySamples = []; $missingCurrPropertySamples = [];

        foreach ($rows as $r) {
            $processed++;
            $nid = trim($r['NID'] ?? '');
            if ($nid === '') { $skipped++; continue; }
            $name = trim($r['NAME'] ?? '');
            if ($name === '') { $skipped++; continue; }

            $genderRaw = strtoupper(trim($r['Sex'] ?? ''));
            $gender = $genderRaw === 'M' ? 'male' : ($genderRaw === 'F' ? 'female' : 'other');

            $partyShort = strtoupper(trim($r['PARTY'] ?? ''));
            $partyId = $partyShort !== '' ? ($partyMap[$partyShort] ?? null) : null;
            if ($partyShort !== '' && !$partyId) { $missingParty++; }

            $subCode = strtoupper(trim($r['CONSIT'] ?? ''));
            $subConsiteId = $subCode !== '' ? ($subConsiteMap[$subCode] ?? null) : null;
            if ($subCode !== '' && !$subConsiteId) { $missingSubConsite++; }

            $dhaaira = trim($r['DHAAIRA'] ?? '');
            [$targetIslandId, $wardIdDetected] = $this->resolveIslandAndWard($dhaaira, $islandMaleId, $islandHulhumaleId, $islandVilimaleId, $wardPatterns);
            if (!$targetIslandId) { $dhaairaUnmappedIsland++; }
            else if ($targetIslandId === $islandMaleId) { $dhaairaMappedMale++; }
            else if ($targetIslandId === $islandHulhumaleId) { $dhaairaMappedHulhumale++; }
            else if ($targetIslandId === $islandVilimaleId) { $dhaairaMappedVilimale++; }
            if ($wardIdDetected) { $wardMatched++; } else { $wardNotMatched++; }

            // Extract property references from ADDRESS / ADDRESS2
            [$permPropertyId, $permStreet, $permCandidate] = $this->extractPropertyInfo($r['ADDRESS'] ?? '', $propertyMap);
            [$currPropertyId, $currStreet, $currCandidate] = $this->extractPropertyInfo($r['ADDRESS2'] ?? '', $propertyMap);

            if(!$permPropertyId && $permCandidate && count($missingPermPropertySamples) < 25){ $missingPermPropertySamples[] = $permCandidate; }
            if(!$currPropertyId && $currCandidate && count($missingCurrPropertySamples) < 25){ $missingCurrPropertySamples[] = $currCandidate; }

            // Phones (as before)
            $phones = $this->parsePhones($r['MOBILE'] ?? '');

            // Fallback island: if property belongs to a known island different from detected? (Skipping heavy cross-check for now.)
            $islandId = $targetIslandId ?? $islandHulhumaleId ?? $islandMaleId; // prefer detected; fallback to Hulhumale then Male
            $currentIslandId = $islandId; // Assuming same for current

            $existing = Directory::where('id_card_number', $nid)->first();
            $payload = [
                'name' => $name,
                'description' => null,
                'profile_picture' => $existing?->profile_picture,
                'gender' => $gender,
                'date_of_birth' => null,
                'death_date' => null,
                'phones' => $phones,
                'email' => $existing?->email,
                'website' => $existing?->website,
                'country_id' => $maldivesId,
                'island_id' => $islandId,
                'properties_id' => $permPropertyId,
                'address' => null,
                'street_address' => $permStreet,
                'current_country_id' => $maldivesId,
                'current_island_id' => $currentIslandId,
                'current_properties_id' => $currPropertyId,
                'current_address' => null,
                'current_street_address' => $currStreet,
                'party_id' => $partyId,
                'sub_consite_id' => $subConsiteId,
                'status' => 'Active',
            ];

            if ($existing) {
                $existing->fill($payload)->save();
                $updated++;
            } else {
                $payload['id'] = (string) Str::uuid();
                $payload['id_card_number'] = $nid;
                Directory::create($payload);
                $created++;
            }

            if ($processed % 500 === 0) {
                $this->command->info("Processed {$processed}/{$total} ...");
            }
        }

        $this->command->info("✅ Import complete. Total: {$total} | Created: {$created} | Updated: {$updated} | Skipped: {$skipped}");
        $this->command->warn("Mapping stats: missingSubConsite={$missingSubConsite}, missingParty={$missingParty}, dhaairaUnmappedIsland={$dhaairaUnmappedIsland}, wardMatched={$wardMatched}, wardNotMatched={$wardNotMatched}");
        $this->command->line("Island hits: Male={$dhaairaMappedMale}, Hulhumale={$dhaairaMappedHulhumale}, Vilimale={$dhaairaMappedVilimale}");
        if(count($missingPermPropertySamples)){
            $this->command->warn('Permanent property candidates not matched (sample): '.implode(' | ', $missingPermPropertySamples));
        }
        if(count($missingCurrPropertySamples)){
            $this->command->warn('Current property candidates not matched (sample): '.implode(' | ', $missingCurrPropertySamples));
        }
    }

    private function parsePhones(string $raw): array
    {
        if (trim($raw) === '') return [];
        return collect(preg_split('/\|/',$raw))
            ->map(fn($p)=>preg_replace('/[^0-9+]/','', trim($p)))
            ->filter(fn($p)=>$p!=='')
            ->unique()
            ->values()
            ->all();
    }

    private function extractPropertyInfo(string $raw, array $propertyMap): array
    {
        $rawOriginal = $raw;
        $raw = trim($raw);
        if ($raw === '') return [null, null, null];

        $candidates = [];
        // 1. Exact raw uppercase for name matching
        $candidates[] = 'NAME:'.strtoupper($raw);
        // 2. Leading number or number-hyphen patterns (e.g., 10017, 12-2, 12-2-06)
        if (preg_match_all('/\b\d+(?:-\d+){0,2}\b/', $raw, $mAll)) {
            foreach($mAll[0] as $seg){
                $candidates[] = 'NUM:'.strtoupper($seg);
                $candidates[] = 'REG:REG-'.strtoupper($seg); // attempt register number pattern
            }
        }
        // 3. Parenthetical removal variant
        if (preg_match('/^(\d{2,})(?:\s*\(.+\))?/', $raw, $m)) {
            $num = $m[1];
            $candidates[] = 'NUM:'.strtoupper($num);
            $candidates[] = 'REG:REG-'.strtoupper($num);
        }

        $candidates = array_unique($candidates);
        foreach($candidates as $key){
            if(isset($propertyMap[$key])){
                return [$propertyMap[$key], $rawOriginal, $key];
            }
        }
        // Not found, return first meaningful candidate (for logging) if exists
        $firstCandidate = null;
        foreach($candidates as $c){ if(str_starts_with($c,'NUM:') || str_starts_with($c,'NAME:')) { $firstCandidate = $c; break; } }
        return [null, $rawOriginal, $firstCandidate];
    }

    private function resolveIslandAndWard(string $dhaaira, ?string $maleId, ?string $hulhumaleId, ?string $vilimaleId, array $wardPatterns): array
    {
        $lower = mb_strtolower($dhaaira);
        $islandId = null; $wardId = null;
        if ($lower === '') { return [$islandId,$wardId]; }
        if (str_contains($lower, 'hulhumale')) { $islandId = $hulhumaleId; }
        elseif (str_contains($lower, 'vilimale') || str_contains($lower,'villimale')) { $islandId = $vilimaleId; }
        elseif (str_contains($lower, 'henveiru') || str_contains($lower,'galolhu') || str_contains($lower,'maafannu') || str_contains($lower,'machchangoalhi') || str_contains($lower,'mahchangoalhi')) { $islandId = $maleId; }

        // Ward detection: find first ward name contained in phrase (simple substring)
        foreach ($wardPatterns as $wName=>$wId) {
            if ($wName !== '' && str_contains($lower, $wName)) { $wardId = $wId; break; }
        }
        return [$islandId, $wardId];
    }
}
