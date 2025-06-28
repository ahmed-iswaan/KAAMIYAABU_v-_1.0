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
            DirectoryTypeSeeder::class,
            RegistrationTypeSeeder::class,
            PropertyTypesSeeder::class,
            InvoiceCategorySeeder::class,
            PropertySeeder::class,
            DirectoriesTableSeeder::class,
            WasteCollectionPriceListTranslatedSeeder::class,
            VehicleSeeder::class,
            WasteTypeSeeder::class,
        ]);
    }
}
