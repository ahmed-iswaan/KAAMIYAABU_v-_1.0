<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionTableSeeder::class,
            CreateAdminUserSeeder::class,
            CountrySeeder::class,
            AtollSeeder::class,
            IslandSeeder::class,
            WardSeeder::class,
            PartySeeder::class,
            ConsiteSeeder::class,
            RegistrationTypeSeeder::class,
            PropertyTypesSeeder::class,
            PropertySeeder::class,
            ElectionSeeder::class,
            ParticipantSeeder::class,
            OpinionAndRequestTypeSeeder::class,
            MayorAndPartyChangeRequestTypeSeeder::class,
            DirectoriesTableSeeder::class,
        ]);
    }
}
