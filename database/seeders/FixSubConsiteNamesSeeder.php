<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Consite;
use App\Models\SubConsite;

class FixSubConsiteNamesSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure parent consite exists
        $consite = Consite::firstOrCreate(
            ['code' => 'CITY-MLE'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Malé City',
                'status' => 'Active',
            ]
        );

        // Authoritative code -> name map (user provided)
        $map = [
            'T01' => 'Hulhumaale Dhekunu Dhaairaa',
            'T16' => 'Hulhumaale Medhu Dhaairaa',
            'T17' => 'Hulhumaale Uthuru Dhaairaa',

            'T02' => 'Medhu Henveiru Dhaairaa',
            'T03' => 'Henveiru Dhekunu Dhaairaa',
            'T04' => 'Henveiru Uthuru Dhaairaa',
            'T14' => 'Henveiru Hulhangu Dhaairaa',

            'T05' => 'Galolhu Uthuru Dhaairaa',
            'T06' => 'Galolhu Dhekunu Dhaairaa',

            'T07' => 'Mahchangolhi Uthuru Dhaairaa',
            'T08' => 'Mahchangolhi Dhekunu Dhaairaa',
            'T15' => 'Mahchangolhi Medhu Dhaairaa',

            'T09' => 'Maafannu Uthuru Dhaairaa',
            'T10' => 'Maafannu Hulhangu Dhaairaa',
            'T11' => 'Maafannu Medhu Dhaairaa',
            'T12' => 'Maafannu Dhekunu Dhaairaa',

            'T13' => 'Villimale Dhaairaa',
        ];

        foreach ($map as $code => $name) {
            $sub = SubConsite::where('code', $code)->first();

            if (!$sub) {
                // Create missing row if it doesn't exist (keep code unique)
                SubConsite::create([
                    'id' => (string) Str::uuid(),
                    'consite_id' => $consite->id,
                    'code' => $code,
                    'name' => $name,
                    'status' => 'Active',
                ]);
                continue;
            }

            // Update wrong name and ensure consite_id is correct
            $sub->consite_id = $sub->consite_id ?: $consite->id;
            $sub->name = $name;
            $sub->save();
        }

        // If old/incorrect codes exist that should not be used, keep them but disable.
        // Example: old seed contained T18 for Vilimale; correct code is T13.
        $knownCodes = array_keys($map);
        SubConsite::whereNotIn('code', $knownCodes)
            ->where('consite_id', $consite->id)
            ->update(['status' => 'Inactive']);
    }
}
