<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DirectoryTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insert default directory types
        DB::table('directory_types')->insert([
            [
                'id'         => Str::uuid()->toString(),
                'name'       => 'Company',
                'slug'       => 'company',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id'         => Str::uuid()->toString(),
                'name'       => 'Institute',
                'slug'       => 'institute',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id'         => Str::uuid()->toString(),
                'name'       => 'Individual',
                'slug'       => 'individual',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id'         => Str::uuid()->toString(),
                'name'       => 'Club',
                'slug'       => 'club',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
