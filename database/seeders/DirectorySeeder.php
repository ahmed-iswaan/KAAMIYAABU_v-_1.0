<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DirectorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countryId = DB::table('countries')->value('id');
        $islandId  = DB::table('islands')->value('id');
        $propertyId = DB::table('properties')->value('id');
        $partyId = DB::table('parties')->value('id');
        $subConsiteId = DB::table('sub_consites')->value('id');

        $now = now();

        $entries = [
            ['name'=>'John Doe','description'=>'Sample individual','id_card'=>'A123456','gender'=>'male','dob'=>'1990-05-10','phones'=>['+9607000001','+9607900001'],'email'=>'john.doe@example.com'],
            ['name'=>'Jane Smith','description'=>'Marketing specialist','id_card'=>'A987654','gender'=>'female','dob'=>'1988-11-23','phones'=>['+9607000002'],'email'=>'jane.smith@example.com'],
            ['name'=>'Ali Ahmed','description'=>'Community volunteer','id_card'=>'A555444','gender'=>'male','dob'=>'1992-08-15','phones'=>['+9607000003','+9607600003'],'email'=>'ali.ahmed@example.com'],
            ['name'=>'Sara Ibrahim','description'=>'Designer','id_card'=>'A222111','gender'=>'female','dob'=>'1995-02-28','phones'=>['+9607000004'],'email'=>'sara.ibrahim@example.com'],
            ['name'=>'Mohamed Rasheed','description'=>'Engineer','id_card'=>'A333222','gender'=>'male','dob'=>'1987-07-07','phones'=>['+9607000005'],'email'=>'mohamed.rasheed@example.com'],
        ];

        $data = array_map(function($e) use ($now,$countryId,$islandId,$propertyId,$partyId,$subConsiteId){
            return [
                'id' => Str::uuid()->toString(),
                'name' => $e['name'],
                'description' => $e['description'],
                'profile_picture' => null,
                'id_card_number' => $e['id_card'],
                'gender' => $e['gender'],
                'date_of_birth' => $e['dob'],
                'death_date' => null,
                'phones' => json_encode($e['phones']),
                'email' => $e['email'],
                'website' => null,
                'country_id' => $countryId,
                'island_id' => $islandId,
                'address' => 'Permanent Address',
                'street_address' => 'Street 1',
                'properties_id' => $propertyId,
                'current_country_id' => $countryId,
                'current_island_id' => $islandId,
                'current_address' => 'Current Address',
                'current_street_address' => 'Current Street 1',
                'current_properties_id' => $propertyId,
                'party_id' => $partyId,
                'sub_consite_id' => $subConsiteId,
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $entries);

        DB::table('directories')->insert($data);
    }
}
