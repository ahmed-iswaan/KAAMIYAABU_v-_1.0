<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\InvoiceCategory;

class InvoiceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Fine',
            'Garbage',
        ];

        foreach ($categories as $name) {
            InvoiceCategory::create(['name' => $name]);
        }
    }
}
