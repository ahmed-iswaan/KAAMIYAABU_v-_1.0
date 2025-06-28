<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WasteType;
use Illuminate\Support\Str;

class WasteTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => '10kg Plastic',
                'unit' => 'kg',
                'unit_quantity' => 10,
                'index' => 1,
            ],
            [
                'name' => '20kg Paper',
                'unit' => 'kg',
                'unit_quantity' => 20,
                'index' => 2,
            ],
            [
                'name' => '1 Liter Oil',
                'unit' => 'ltr',
                'unit_quantity' => 1,
                'index' => 3,
            ],
            [
                'name' => '5kg Metal',
                'unit' => 'kg',
                'unit_quantity' => 5,
                'index' => 4,
            ],
        ];

        foreach ($types as $type) {
            WasteType::create([
                'id' => Str::uuid(),
                'name' => $type['name'],
                'unit' => $type['unit'],
                'unit_quantity' => $type['unit_quantity'],
                'index' => $type['index'],
                'total_collection' => 0,
            ]);
        }
    }
}
