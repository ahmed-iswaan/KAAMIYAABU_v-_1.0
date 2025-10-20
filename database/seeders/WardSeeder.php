<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Island;
use App\Models\Wards;

class WardSeeder extends Seeder
{
    public function run(): void
    {
        // Target island: Malé
        $maleIsland = Island::where('name', "Male'")->orWhere('name', 'Malé')->first();
        if (!$maleIsland) {
            $this->command?->warn("Island Male' / Malé not found. Skipping ward seeding.");
            return;
        }

        $wards = [
            'Henveiru',
            'Galolhu',
            'Maafannu',
            'Macchangoalhi',
        ];

        foreach ($wards as $name) {
            Wards::firstOrCreate(
                ['island_id' => $maleIsland->id, 'name' => $name],
                [
                    'id' => (string) Str::uuid(),
                    'status' => 'Active',
                ]
            );
        }

        $this->command?->info('✅ WardSeeder: Malé wards seeded.');
    }
}
