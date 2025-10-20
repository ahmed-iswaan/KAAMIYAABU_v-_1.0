<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use App\Models\Atoll;
use App\Models\Island;

class IslandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      $path = database_path('seeders/data/atoll-islands-atollsofmaldives.json');
        if (! File::exists($path)) {
            $this->command->error("JSON file not found: {$path}");
            return;
        }

        $json = File::get($path);
        $data = json_decode($json, true);
        if (! is_array($data)) {
            $this->command->error('Invalid JSON format in islands file');
            return;
        }

        $seeded = 0;

        // Support two structures:
        // 1) { "HA": [ {..island..}, ... ], "K": [ ... ] }
        // 2) [ { "atoll": "K", "name": "..." }, ... ]
        $isAssoc = count(array_filter(array_keys($data), 'is_string')) > 0;

        if ($isAssoc) {
            foreach ($data as $outerAtollCode => $islands) {
                if (!is_array($islands)) { continue; }
                foreach ($islands as $item) {
                    $this->seedIslandRecord($item, $outerAtollCode, $seeded);
                }
            }
        } else {
            // Flat list
            foreach ($data as $item) {
                $outerAtollCode = $item['atoll'] ?? null;
                $this->seedIslandRecord($item, $outerAtollCode, $seeded);
            }
        }

        $this->command->info("IslandSeeder: {$seeded} islands seeded with codes and coordinates.");
    }

    private function seedIslandRecord(array $item, ?string $outerAtollCode, int &$seeded): void
    {
        $explicitCode = $item['atoll'] ?? null; // If present inside item it overrides outer key
        $atollCode = strtoupper(trim($explicitCode ?: ($outerAtollCode ?? '')));
        if ($atollCode === '') { return; }

        $atoll = Atoll::where('code', $atollCode)->first();
        if (! $atoll) {
            $this->command?->warn("Atoll with code {$atollCode} not found (island: ".($item['name'] ?? 'unknown').")");
            return;
        }

        $name = $item['name'] ?? null;
        if (! $name) { return; }
        $lat  = $item['latitude'] ?? null;
        $lon  = $item['longitude'] ?? null;

        $islandCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 3));

        Island::updateOrCreate(
            ['atoll_id' => $atoll->id, 'name' => $name],
            [
                'island_code' => $islandCode,
                'latitude'    => $lat,
                'longitude'   => $lon,
            ]
        );
        $seeded++;
    }
}
