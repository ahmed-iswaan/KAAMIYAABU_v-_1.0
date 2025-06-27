<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Support\Str;

class VehicleSeeder extends Seeder
{
    public function run()
    {
        // Get random users to assign as drivers (assuming users exist)
        $drivers = User::inRandomOrder()->limit(3)->get();

        $vehicles = [
            [
                'registration_number' => 'MV-TRK-001',
                'model' => 'Isuzu Giga 2020',
                'device_id' => 'TRKDEV001',
            ],
            [
                'registration_number' => 'MV-TRK-002',
                'model' => 'Hino Ranger 2021',
                'device_id' => 'TRKDEV002',
            ],
            [
                'registration_number' => 'MV-TRK-003',
                'model' => 'Fuso Fighter 2019',
                'device_id' => 'TRKDEV003',
            ],
        ];

        foreach ($vehicles as $i => $data) {
            Vehicle::updateOrCreate(
                ['registration_number' => $data['registration_number']],
                [
                    'id' => Str::uuid(),
                    'registration_number' => $data['registration_number'],
                    'model' => $data['model'],
                    'device_id' => $data['device_id'],
                    'driver_id' => $drivers[$i % $drivers->count()]->id, // assign available drivers
                    'status' => 'active',
                ]
            );
        }
    }
}
