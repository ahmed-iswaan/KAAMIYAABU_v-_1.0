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
                    // Authoritative list (code -> correct name)
                    ['code' => 'T01', 'name' => 'Hulhumaale Dhekunu Dhaairaa'],
                    ['code' => 'T16', 'name' => 'Hulhumaale Medhu Dhaairaa'],
                    ['code' => 'T17', 'name' => 'Hulhumaale Uthuru Dhaairaa'],

                    ['code' => 'T02', 'name' => 'Medhu Henveiru Dhaairaa'],
                    ['code' => 'T03', 'name' => 'Henveiru Dhekunu Dhaairaa'],
                    ['code' => 'T04', 'name' => 'Henveiru Uthuru Dhaairaa'],
                    ['code' => 'T14', 'name' => 'Henveiru Hulhangu Dhaairaa'],

                    ['code' => 'T05', 'name' => 'Galolhu Uthuru Dhaairaa'],
                    ['code' => 'T06', 'name' => 'Galolhu Dhekunu Dhaairaa'],

                    ['code' => 'T07', 'name' => 'Mahchangolhi Uthuru Dhaairaa'],
                    ['code' => 'T08', 'name' => 'Mahchangolhi Dhekunu Dhaairaa'],
                    ['code' => 'T15', 'name' => 'Mahchangolhi Medhu Dhaairaa'],

                    ['code' => 'T09', 'name' => 'Maafannu Uthuru Dhaairaa'],
                    ['code' => 'T10', 'name' => 'Maafannu Hulhangu Dhaairaa'],
                    ['code' => 'T11', 'name' => 'Maafannu Medhu Dhaairaa'],
                    ['code' => 'T12', 'name' => 'Maafannu Dhekunu Dhaairaa'],

                    ['code' => 'T13', 'name' => 'Villimale Dhaairaa'],
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
                // Ensure the seed is idempotent and also corrects wrong names if they exist.
                $row = SubConsite::where('code', $sub['code'])->first();

                if (!$row) {
                    SubConsite::create([
                        'id' => (string) Str::uuid(),
                        'consite_id' => $consite->id,
                        'code' => $sub['code'],
                        'name' => $sub['name'],
                        'status' => 'Active',
                    ]);
                    continue;
                }

                $row->consite_id = $row->consite_id ?: $consite->id;
                $row->name = $sub['name'];
                $row->status = $row->status ?: 'Active';
                $row->save();
            }
        }
    }
}
