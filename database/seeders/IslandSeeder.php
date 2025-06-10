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
        foreach ($data as $atollCode => $islands) {
            $atoll = Atoll::where('code', $atollCode)->first();
            if (! $atoll) {
                $this->command->warn("Atoll with code {$atollCode} not found");
                continue;
            }
            foreach ($islands as $item) {
                $name = $item['name'] ?? null;
                $lat  = $item['latitude'] ?? null;
                $lon  = $item['longitude'] ?? null;
                if (! $name) {
                    continue;
                }
                // Generate island code: first 3 letters uppercase
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

        $this->command->info("IslandSeeder: {$seeded} islands seeded with codes and coordinates.");
    }
}
