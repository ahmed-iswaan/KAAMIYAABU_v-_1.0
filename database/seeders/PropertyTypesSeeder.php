<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PropertyTypes;

class PropertyTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            $types = [
            'Residential',
            'Guest Houses',
            'Commercial',
            'Industrial',
            'Agricultural',
            'Retail',
            'Office',
            'Mixed-Use',
            'Hospitality',
            'Institutional',
            'Recreational',
            'Vacant Land',
            'Special Purpose',
            'Condominium',
            'Townhouse',
        ];

        foreach ($types as $name) {
            PropertyTypes::firstOrCreate(['name' => $name]);
        }
    }
}
