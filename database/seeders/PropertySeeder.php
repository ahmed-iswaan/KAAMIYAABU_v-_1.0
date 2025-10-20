<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Island;
use App\Models\PropertyTypes;
use App\Models\Wards;
use App\Models\Property;

class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds importing from data/Property.json
     */
    public function run(): void
    {
        $path = database_path('seeders/data/Property.json');
        if (!File::exists($path)) {
            $this->command?->error("Property.json not found at {$path}");
            return;
        }

        $data = json_decode(File::get($path), true);
        if (!is_array($data)) {
            $this->command?->error('Property.json invalid JSON array.');
            return;
        }

        // Ensure primary property type
        $propertyType = PropertyTypes::firstOrCreate(['name' => 'Residential']);
        $propertyTypeId = $propertyType->id;

        // Preload islands (case-insensitive map)
        $islandMap = Island::all()->mapWithKeys(function($i){
            $base = mb_strtolower($i->name);
            $keys = [$base => $i->id];
            // Add accentless variant automatically (handles Villimalé -> villimale, Malé -> male, etc.)
            if (str_contains($base, 'é')) {
                $keys[str_replace('é','e',$base)] = $i->id;
            }
            // normalize variants for Hulhumale / Hulhumalé
            if (str_starts_with($base, 'hulhuma')) {
                $keys['hulhumale'] = $i->id; $keys['hulhumalé'] = $i->id;
            }
            // Special handling for Malé variants / spellings
            if (in_array($base, ["malé","male'","male"])) {
                $keys['male'] = $i->id; $keys["male'"] = $i->id; $keys['malé'] = $i->id; $keys['male city'] = $i->id; $keys['male city '] = $i->id;
            }
            return $keys;
        })->toArray();

        // Preload wards keyed by island_id + lower(name)
        $wardMap = Wards::all()->mapWithKeys(fn($w)=>[$w->island_id.'|'.mb_strtolower($w->name) => $w->id])->toArray();

        // Existing properties (before this run)
        $existing = Property::all();
        $existingByNumber = $existing->mapWithKeys(fn($p)=>[$p->island_id.'|'.mb_strtolower((string)$p->number) => $p->id])->toArray();
        $existingByName = $existing->mapWithKeys(fn($p)=>[$p->island_id.'|'.mb_strtolower($p->name) => $p->id])->toArray();

        // In-run seen keys to prevent duplicate inserts that violate composite indexes
        $seenNumberKeys = [];
        $seenNameKeys = [];
        $incrementSuffixCounters = []; // track numeric suffix attempts per (island|baseNumber)

        $insert = []; $created = 0; $updated = 0; $skipped = 0; $processed = 0;
        $skipEmptyName = 0; $skipIsland = 0; $sampleMissingIslands = [];
        // New duplicate skip counters
        $skipDuplicateNumber = 0; $skipDuplicateName = 0;

        foreach ($data as $row) {
            $processed++;
            $rawName = trim($row['name'] ?? '');
            if ($rawName === '') { $skipped++; $skipEmptyName++; continue; }

            $number = null;
            if (preg_match('/^(\d{2,})/', $rawName, $m)) { $number = $m[1]; }

            $islandField = trim($row['island'] ?? '');
            $islandNameKey = mb_strtolower(str_replace(['é'], ['e'], $islandField)); // normalize accent
            $islandId = $islandMap[$islandNameKey] ?? null;
            if (!$islandId) {
                $skipped++; $skipIsland++;
                if (count($sampleMissingIslands) < 10) { $sampleMissingIslands[] = $islandField; }
                continue;
            }

            $wardName = mb_strtolower(trim($row['wards'] ?? ''));
            $wardId = $wardName !== '' ? ($wardMap[$islandId.'|'.$wardName] ?? null) : null;

            $keyNumber = $number ? ($islandId.'|'.mb_strtolower($number)) : null;
            $keyName = $islandId.'|'.mb_strtolower($rawName);
            $existingId = $keyNumber && isset($existingByNumber[$keyNumber]) ? $existingByNumber[$keyNumber] : ($existingByName[$keyName] ?? null);

            // Prevent duplicate new inserts within this run (composite unique safety)
            if (!$existingId) {
                if ($keyNumber && isset($seenNumberKeys[$keyNumber])) {
                    // Attempt to refine duplicate base number instead of skipping
                    $baseNumber = $number; // original extracted digits
                    $refinedNumber = null;

                    // 1. Try to extract a unit pattern like 2-06, 4-10, 5-11, 3-08 etc.
                    if (preg_match('/\b(\d{1,2}-\d{2})\b/', $rawName, $um)) {
                        $candidate = $baseNumber.'-'.$um[1]; // e.g. 12-2-06
                        $candidateKey = $islandId.'|'.mb_strtolower($candidate);
                        if (!isset($existingByNumber[$candidateKey]) && !isset($seenNumberKeys[$candidateKey])) {
                            $refinedNumber = $candidate;
                        }
                    }

                    // 2. Fallback: append incremental numeric suffix (12-2, 12-3, ...)
                    if (!$refinedNumber) {
                        $counterKey = $islandId.'|'.$baseNumber;
                        $incrementSuffixCounters[$counterKey] = ($incrementSuffixCounters[$counterKey] ?? 1) + 1; // start at 2 for first duplicate
                        // Try a few successive numbers to avoid collisions
                        for ($attempt = 0; $attempt < 10 && !$refinedNumber; $attempt++) {
                            $suffixNum = $incrementSuffixCounters[$counterKey] + $attempt;
                            $candidate = $baseNumber.'-'.$suffixNum; // e.g. 12-2
                            $candidateKey = $islandId.'|'.mb_strtolower($candidate);
                            if (!isset($existingByNumber[$candidateKey]) && !isset($seenNumberKeys[$candidateKey])) {
                                $refinedNumber = $candidate;
                            }
                        }
                    }

                    if ($refinedNumber) {
                        $number = $refinedNumber; // adopt refined number
                        $keyNumber = $islandId.'|'.mb_strtolower($number);
                        // continue with normal insert flow (do NOT increment skip counter)
                    } else {
                        // Could not find a unique refined number => skip
                        $skipped++; $skipDuplicateNumber++; continue;
                    }
                }
                if (isset($seenNameKeys[$keyName])) { $skipped++; $skipDuplicateName++; continue; }
            }

            $payload = [
                'name' => $rawName,
                'register_number' => $number ? 'REG-'.$number : null,
                'number' => $number,
                'property_type_id' => $propertyTypeId,
                'latitude' => null,
                'longitude' => null,
                'square_feet' => null,
                'island_id' => $islandId,
                'ward_id' => $wardId,
                'status' => 'Active',
                'updated_at' => now(),
            ];

            if ($existingId) {
                Property::where('id', $existingId)->update($payload);
                $updated++;
            } else {
                $payload['id'] = (string) Str::uuid();
                $payload['created_at'] = now();
                $insert[] = $payload;
                $created++;
                if ($keyNumber) { $seenNumberKeys[$keyNumber] = true; }
                $seenNameKeys[$keyName] = true;
            }

            if ($processed % 1000 === 0) {
                $this->command?->info("Processed {$processed} records...");
            }
        }

        if (!empty($insert)) {
            foreach (array_chunk($insert, 1000) as $chunk) {
                DB::table('properties')->insert($chunk);
            }
        }

        $this->command?->info("✅ Property import complete. Created: {$created} Updated: {$updated} Skipped: {$skipped}");
        $this->command?->warn("Skip breakdown => Empty name: {$skipEmptyName}, Island not found: {$skipIsland}, Duplicate number (run): {$skipDuplicateNumber}, Duplicate name (run): {$skipDuplicateName}");
        if ($skipIsland > 0) {
            $this->command?->line('Sample missing island values: '.implode(', ', $sampleMissingIslands));
        }
    }
}

