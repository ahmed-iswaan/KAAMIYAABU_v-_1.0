<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegistrationTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insert default registration types
        DB::table('registration_types')->insert([
            [
                'id'         => Str::uuid()->toString(),
                'name'       => 'ID Card',
                'slug'       => 'id_card',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id'         => Str::uuid()->toString(),
                'name'       => 'Passport',
                'slug'       => 'passport',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id'         => Str::uuid()->toString(),
                'name'       => 'Registration',
                'slug'       => 'registration',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
