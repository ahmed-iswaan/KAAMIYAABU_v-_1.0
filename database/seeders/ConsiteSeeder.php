<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Consite;
use App\Models\SubConsite;

class ConsiteSeeder extends Seeder
{
    public function run(): void
    {
        // Only Malé City Council and its wards
        $data = [
            [
                'code' => 'CITY-MLE', 'name' => 'Malé City', 'subs' => [
                    ['code' => 'T01', 'name' => 'Hulhumale Dhaaira'],
                    ['code' => 'T02', 'name' => 'Henveiru Uthuru Dhaaira'],
                    ['code' => 'T03', 'name' => 'Medhu Henveiru Dhaaira'],
                    ['code' => 'T04', 'name' => 'Henveiru Dhekunu Dhaaira'],
                    ['code' => 'T05', 'name' => 'Henveiru Hulhangu Dhaaira'],
                    ['code' => 'T06', 'name' => 'Galolhu Uthuru Dhaaira'],
                    ['code' => 'T07', 'name' => 'Galolhu Medhu Dhaaira'],
                    ['code' => 'T08', 'name' => 'Galolhu Dhekunu Dhaaira'],
                    ['code' => 'T09', 'name' => 'Galolhu Hulhangu Dhaaira'],
                    ['code' => 'T10', 'name' => 'Mahchangoalhi Uthuru Dhaaira'],
                    ['code' => 'T11', 'name' => 'Mahchangoalhi Medhu Dhaaira'],
                    ['code' => 'T12', 'name' => 'Mahchangoalhi Dhekunu Dhaaira'],
                    ['code' => 'T13', 'name' => 'Maafannu Medhu Dhaaira'],
                    ['code' => 'T14', 'name' => 'Maafannu Uthuru Dhaaira'],
                    ['code' => 'T15', 'name' => 'Maafannu Hulhangu Dhaaira'],
                    ['code' => 'T16', 'name' => 'Maafannu Hulhangu Dhaaira'],
                    ['code' => 'T18', 'name' => 'Vilimale Dhaaira'],               
                ]
            ]
        ];

        foreach ($data as $consiteData) {
            $consite = Consite::firstOrCreate(
                ['code' => $consiteData['code']],
                [
                    'id' => (string) Str::uuid(),
                    'name' => $consiteData['name'],
                    'status' => 'Active',
                ]
            );

            foreach ($consiteData['subs'] as $sub) {
                SubConsite::firstOrCreate(
                    ['code' => $sub['code']],
                    [
                        'id' => (string) Str::uuid(),
                        'consite_id' => $consite->id,
                        'name' => $sub['name'],
                        'status' => 'Active',
                    ]
                );
            }
        }
    }
}
