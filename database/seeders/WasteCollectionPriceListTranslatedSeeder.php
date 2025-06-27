<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WasteCollectionPriceList;
use Illuminate\Support\Str;

class WasteCollectionPriceListTranslatedSeeder extends Seeder
{
    public function run()
    {
        $items = [
            [
                'id' => Str::uuid(),
                'name' => 'Household waste collection (residential houses)',
                'description' => 'Standard waste pickup for local households.',
                'status' => 'active',
                'amount' => 200.00,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Guesthouse waste collection',
                'description' => 'Waste collection for inhabited guesthouses.',
                'status' => 'active',
                'amount' => 150.00,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Uninhabited flats/apartments',
                'description' => 'Waste collection from unoccupied residential units.',
                'status' => 'active',
                'amount' => 150.00,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Commercial establishments',
                'description' => 'Shops, offices, and general businesses.',
                'status' => 'active',
                'amount' => 500.00,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Import warehouses & cafés/restaurants',
                'description' => 'Import-related businesses and food establishments.',
                'status' => 'active',
                'amount' => 500.00,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Clinical/hazardous waste facilities',
                'description' => 'Hospitals, clinics, or toxic waste handlers.',
                'status' => 'active',
                'amount' => 350.00,
            ],
        ];

        foreach ($items as $item) {
            WasteCollectionPriceList::updateOrCreate(
                ['name' => $item['name']],
                $item
            );
        }
    }
}

