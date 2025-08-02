<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;

class ClientSeeder extends Seeder
{
    public function run()
    {
        Client::updateOrCreate(
            ['name' => 'Test Client'], // Search condition
            [
                'secret' => Hash::make('test-secret-123'),
                'domain' => '127.0.0.1',
            ]
        );
    }
}
