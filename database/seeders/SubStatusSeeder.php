<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubStatus;

class SubStatusSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name'=>'First Attempt','description'=>'Initial contact attempt','active'=>true],
            ['name'=>'Second Attempt','description'=>'Second contact attempt','active'=>true],
            ['name'=>'No Answer','description'=>'Could not reach directory','active'=>true],
            ['name'=>'Rescheduled','description'=>'Follow up scheduled','active'=>true],
            ['name'=>'Escalated','description'=>'Escalated to supervisor','active'=>true],
        ];
        foreach($items as $i){
            SubStatus::firstOrCreate(['name'=>$i['name']], [
                'description'=>$i['description'],
                'active'=>$i['active'],
            ]);
        }
    }
}
