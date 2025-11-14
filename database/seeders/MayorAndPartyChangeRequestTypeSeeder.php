<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RequestType;

class MayorAndPartyChangeRequestTypeSeeder extends Seeder
{
    public function run(): void
    {
        $requests = [
            ['name' => 'Mayor Call', 'description' => null],
            ['name' => 'Party Change', 'description' => null],
        ];

        foreach ($requests as $r) {
            RequestType::firstOrCreate(['name' => $r['name']], ['description' => $r['description']]);
        }
    }
}
