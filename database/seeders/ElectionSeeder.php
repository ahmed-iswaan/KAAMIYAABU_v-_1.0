<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Election;
use Illuminate\Support\Str;

class ElectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Avoid duplicate seeding by checking existing name
        if (Election::where('name', 'Male City Council')->exists()) {
            return;
        }

        Election::create([
            'id' => (string) Str::uuid(),
            'name' => 'Male City Council',
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->startOfYear()->addMonths(2)->toDateString(),
            'status' => 'Upcoming', // or Active / Completed depending on your logic
        ]);
    }
}
