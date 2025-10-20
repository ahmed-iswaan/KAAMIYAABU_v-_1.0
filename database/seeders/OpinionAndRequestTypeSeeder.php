<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OpinionType;
use App\Models\RequestType;

class OpinionAndRequestTypeSeeder extends Seeder
{
    public function run(): void
    {
        $opinions = [
            'Call Center 1','Call Center 2','Door To Door','SMS Follow Up','Field Visit'
        ];
        foreach($opinions as $o){
            OpinionType::firstOrCreate(['name'=>$o],['description'=>null]);
        }

        $requests = [
            'Financial Aid','Medical Assistance','Job Placement','Housing','Transport','Education Support'
        ];
        foreach($requests as $r){
            RequestType::firstOrCreate(['name'=>$r],['description'=>null]);
        }
    }
}
