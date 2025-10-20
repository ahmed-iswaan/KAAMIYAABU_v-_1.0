<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Atoll;

class AtollSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
     // List of Maldives administrative atolls with their assigned codes
        $atolls = [
            ['name' => 'Male\' City',     'code' => 'MLE'],
            ['name' => 'Haa Alif',        'code' => 'HA'],
            ['name' => 'Haa Dhaalu',      'code' => 'HDH'],
            ['name' => 'Shaviyani',       'code' => 'SH'],
            ['name' => 'Noonu',           'code' => 'N'],
            ['name' => 'Raa',             'code' => 'R'],
            ['name' => 'Baa',             'code' => 'B'],
            ['name' => 'Lhaviyani',       'code' => 'Lh'],
            ['name' => 'Kaafu',           'code' => 'K'],
            ['name' => 'Alifu Alifu',     'code' => 'AA'],
            ['name' => 'Alifu Dhaalu',    'code' => 'ADH'],
            ['name' => 'Vaavu',           'code' => 'V'],
            ['name' => 'Meemu',           'code' => 'M'],
            ['name' => 'Faafu',           'code' => 'F'],
            ['name' => 'Dhaalu',          'code' => 'DH'],
            ['name' => 'Thaa',            'code' => 'TH'],
            ['name' => 'Laamu',           'code' => 'L'],
            ['name' => 'Gaafu Alifu',     'code' => 'GA'],
            ['name' => 'Gaafu Dhaalu',    'code' => 'GDH'],
            ['name' => 'Gnaviyani',       'code' => 'GN'],
            ['name' => 'Seenu',           'code' => 'S'],
            ['name' => 'Ihavandhippolhu', 'code' => 'IH'],
            ['name' => 'Thiladhunmathi',  'code' => 'THM'],
        ];

        foreach ($atolls as $data) {
            Atoll::updateOrCreate(
                ['name' => $data['name']],
                ['code' => $data['code']]
            );
        }
    }
}
