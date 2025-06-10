<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DirectorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lookup IDs
        $companyTypeId     = DB::table('directory_types')->where('slug','company')->value('id');
        $instituteTypeId   = DB::table('directory_types')->where('slug','institute')->value('id');
        $individualTypeId  = DB::table('directory_types')->where('slug','individual')->value('id');
        $clubTypeId        = DB::table('directory_types')->where('slug','club')->value('id');

        $regRegTypeId      = DB::table('registration_types')->where('slug','registration')->value('id');
        $regPassportTypeId = DB::table('registration_types')->where('slug','passport')->value('id');
        $regIdCardTypeId   = DB::table('registration_types')->where('slug','id_card')->value('id');

        $countryId = DB::table('countries')->value('id');
        $islandId  = DB::table('islands')->value('id');

        $now = now();

        // 30 dummy entries
        $entries = [
            // Companies
            ['name'=>'Acme Corporation','description'=>'Leading gadgets provider.','type'=>$companyTypeId,'regType'=>$regRegTypeId,'regNo'=>'ACME-001','contact'=>'Wile E. Coyote','phone'=>'+9607000001','email'=>'contact@acme.example.com','website'=>'https://acme.example.com','locType'=>'inland'],
            ['name'=>'Oceanview Real Estate','description'=>'Prime waterfront properties.','type'=>$companyTypeId,'regType'=>$regRegTypeId,'regNo'=>'OVRE-002','contact'=>'Sarah Rocks','phone'=>'+9607000005','email'=>'info@oceanview.example.com','website'=>'https://oceanview.example.com','locType'=>'inland'],
            ['name'=>'Coral Reef Consulting','description'=>'Environmental consulting services.','type'=>$companyTypeId,'regType'=>$regRegTypeId,'regNo'=>'CRC-003','contact'=>'Dr. Coraline','phone'=>'+9607000009','email'=>'consult@coralreef.example.com','website'=>'https://coralreef.example.com','locType'=>'inland'],
            ['name'=>'Sunrise Hospitality','description'=>'Resort management company.','type'=>$companyTypeId,'regType'=>$regRegTypeId,'regNo'=>'SUNH-004','contact'=>'Ali Karim','phone'=>'+9607000013','email'=>'hello@sunrise.example.com','website'=>'https://sunrise.example.com','locType'=>'inland'],
            ['name'=>'Tropical Foods Ltd','description'=>'Local produce exporter.','type'=>$companyTypeId,'regType'=>$regRegTypeId,'regNo'=>'TROP-005','contact'=>'Emily Palm','phone'=>'+9607000017','email'=>'export@tropical.example.com','website'=>'https://tropical.example.com','locType'=>'inland'],
            ['name'=>'Island Construction Co','description'=>'Infrastructure development firm.','type'=>$companyTypeId,'regType'=>$regRegTypeId,'regNo'=>'ICC-006','contact'=>'John Builder','phone'=>'+9607000021','email'=>'build@islandcc.example.com','website'=>'https://islandcc.example.com','locType'=>'inland'],
            ['name'=>'Local Artisans Guild','description'=>'Crafts and handlooms cooperative.','type'=>$companyTypeId,'regType'=>$regRegTypeId,'regNo'=>'LAG-007','contact'=>'Nina Craft','phone'=>'+9607000025','email'=>'guild@artisans.example.com','website'=>'https://artisans.example.com','locType'=>'inland'],
            ['name'=>'IT Solutions Maldives','description'=>'Tech services and support.','type'=>$companyTypeId,'regType'=>$regRegTypeId,'regNo'=>'ITSM-008','contact'=>'Kevin Tech','phone'=>'+9607000029','email'=>'support@itsolutions.example.com','website'=>'https://itsolutions.example.com','locType'=>'inland'],

            // Institutes
            ['name'=>'Maldives Institute of Technology','description'=>'Premier technical institute.','type'=>$instituteTypeId,'regType'=>$regRegTypeId,'regNo'=>'MIT-2025','contact'=>'Dr. Aishath Ahmed','phone'=>'+9607000002','email'=>'info@mit.mv','website'=>'https://mit.mv','locType'=>'inland'],
            ['name'=>'Atoll Research Institute','description'=>'Marine and climate research.','type'=>$instituteTypeId,'regType'=>$regRegTypeId,'regNo'=>'ARI-100','contact'=>'Prof. Oceanus','phone'=>'+9607000006','email'=>'contact@ari.example.com','website'=>'https://ari.example.com','locType'=>'inland'],
            ['name'=>'Pilots Training Institute','description'=>'Aviation training programs.','type'=>$instituteTypeId,'regType'=>$regRegTypeId,'regNo'=>'PTI-101','contact'=>'Captain Sky','phone'=>'+9607000010','email'=>'train@pti.example.com','website'=>'https://pti.example.com','locType'=>'inland'],
            ['name'=>'Marine Biology Institute','description'=>'Coastal ecosystem studies.','type'=>$instituteTypeId,'regType'=>$regRegTypeId,'regNo'=>'MBI-102','contact'=>'Dr. Bluefin','phone'=>'+9607000014','email'=>'study@mbi.example.com','website'=>'https://mbi.example.com','locType'=>'inland'],
            ['name'=>'Language Learning Institute','description'=>'Maltese and English courses.','type'=>$instituteTypeId,'regType'=>$regRegTypeId,'regNo'=>'LLI-103','contact'=>'Ms. Lingua','phone'=>'+9607000018','email'=>'learn@lli.example.com','website'=>'https://lli.example.com','locType'=>'inland'],
            ['name'=>'Renewable Energy Institute','description'=>'Solar and wind research.','type'=>$instituteTypeId,'regType'=>$regRegTypeId,'regNo'=>'REI-104','contact'=>'Dr. Sunwind','phone'=>'+9607000022','email'=>'energy@rei.example.com','website'=>'https://rei.example.com','locType'=>'inland'],
            ['name'=>'Culinary Arts Institute','description'=>'Professional chef training.','type'=>$instituteTypeId,'regType'=>$regRegTypeId,'regNo'=>'CAI-105','contact'=>'Chef Gordon','phone'=>'+9607000026','email'=>'cook@cai.example.com','website'=>'https://cai.example.com','locType'=>'inland'],
            ['name'=>'Veterinary Science Institute','description'=>'Animal health and welfare.','type'=>$instituteTypeId,'regType'=>$regRegTypeId,'regNo'=>'VSI-106','contact'=>'Dr. Paw','phone'=>'+9607000030','email'=>'vet@vsi.example.com','website'=>'https://vsi.example.com','locType'=>'inland'],

            // Individuals
            ['name'=>'John Doe','description'=>'Software developer.','type'=>$individualTypeId,'regType'=>$regIdCardTypeId,'regNo'=>'ID-987654321','gender'=>'male','date_of_birth'=>'1990-05-10','contact'=>null,'phone'=>'+9607000003','email'=>'john.doe@example.com','website'=>null,'locType'=>'inland'],
            ['name'=>'Jane Smith','description'=>'Digital marketer.','type'=>$individualTypeId,'regType'=>$regPassportTypeId,'regNo'=>'PPT-556677','gender'=>'female','date_of_birth'=>'1988-11-23','contact'=>null,'phone'=>'+9607000007','email'=>'jane.smith@example.com','website'=>null,'locType'=>'inland'],
            ['name'=>'Alex Ahmed','description'=>'Graphic designer.','type'=>$individualTypeId,'regType'=>$regIdCardTypeId,'regNo'=>'ID-112233445','gender'=>'male','date_of_birth'=>'1992-08-15','contact'=>null,'phone'=>'+9607000011','email'=>'alex.ahmed@example.com','website'=>null,'locType'=>'inland'],
            ['name'=>'Maria Hassan','description'=>'Financial advisor.','type'=>$individualTypeId,'regType'=>$regPassportTypeId,'regNo'=>'PPT-334455','gender'=>'female','date_of_birth'=>'1985-02-28','contact'=>null,'phone'=>'+9607000015','email'=>'maria.hassan@example.com','website'=>null,'locType'=>'inland'],
            ['name'=>'David Lee','description'=>'Fitness coach.','type'=>$individualTypeId,'regType'=>$regIdCardTypeId,'regNo'=>'ID-223344556','gender'=>'male','date_of_birth'=>'1991-07-07','contact'=>null,'phone'=>'+9607000019','email'=>'david.lee@example.com','website'=>null,'locType'=>'inland'],
            ['name'=>'Sarah Ali','description'=>'Event planner.','type'=>$individualTypeId,'regType'=>$regPassportTypeId,'regNo'=>'PPT-445566','gender'=>'female','date_of_birth'=>'1993-03-19','contact'=>null,'phone'=>'+9607000023','email'=>'sarah.ali@example.com','website'=>null,'locType'=>'inland'],
            ['name'=>'Michael Khan','description'=>'Photographer.','type'=>$individualTypeId,'regType'=>$regIdCardTypeId,'regNo'=>'ID-556677889','gender'=>'male','date_of_birth'=>'1987-12-05','contact'=>null,'phone'=>'+9607000027','email'=>'michael.khan@example.com','website'=>null,'locType'=>'inland'],

            // Clubs
            ['name'=>'Sunset Runners Club','description'=>'Community running club.','type'=>$clubTypeId,'regType'=>$regRegTypeId,'regNo'=>'SRC-2023','contact'=>'Ali Hassan','phone'=>'+9607000004','email'=>'hello@sunsetrunners.com','website'=>'https://sunsetrunners.com','locType'=>'outer_islander'],
            ['name'=>'Paradise Yacht Club','description'=>'Luxury sailing events.','type'=>$clubTypeId,'regType'=>$regRegTypeId,'regNo'=>'PYC-207','contact'=>'Captain Sail','phone'=>'+9607000008','email'=>'info@pyc.example.com','website'=>'https://pyc.example.com','locType'=>'outer_islander'],
            ['name'=>'Dolphin Swimming Club','description'=>'Competitive swimming team.','type'=>$clubTypeId,'regType'=>$regRegTypeId,'regNo'=>'DSC-308','contact'=>'Coach Aqua','phone'=>'+9607000012','email'=>'swim@dolphins.example.com','website'=>'https://dolphins.example.com','locType'=>'outer_islander'],
            ['name'=>'Beach Volleyball Club','description'=>'Outdoor sport enthusiasts.','type'=>$clubTypeId,'regType'=>$regRegTypeId,'regNo'=>'BVC-409','contact'=>'Spike Master','phone'=>'+9607000016','email'=>'play@bvc.example.com','website'=>'https://bvc.example.com','locType'=>'outer_islander'],
            ['name'=>'Sailing Adventures Club','description'=>'Open-water sailing experiences.','type'=>$clubTypeId,'regType'=>$regRegTypeId,'regNo'=>'SAC-510','contact'=>'Wind Rider','phone'=>'+9607000020','email'=>'join@sac.example.com','website'=>'https://sac.example.com','locType'=>'outer_islander'],
            ['name'=>'Football Fanatics Club','description'=>'Amateur football league.','type'=>$clubTypeId,'regType'=>$regRegTypeId,'regNo'=>'FFC-611','contact'=>'Goal Keeper','phone'=>'+9607000024','email'=>'kick@ffc.example.com','website'=>'https://ffc.example.com','locType'=>'outer_islander'],
            ['name'=>'Fishing Enthusiasts Club','description'=>'Deep-sea fishing group.','type'=>$clubTypeId,'regType'=>$regRegTypeId,'regNo'=>'FEC-712','contact'=>'Captain Hook','phone'=>'+9607000028','email'=>'fish@fec.example.com','website'=>'https://fec.example.com','locType'=>'outer_islander'],
        ];

        // Prepare and insert
        $data = array_map(function($e) use ($now, $countryId, $islandId) {
            return [
                'id'                   => Str::uuid()->toString(),
                'name'                 => $e['name'],
                'description'          => $e['description'],
                'profile_picture'      => null,
                'directory_type_id'    => $e['type'],
                'registration_type_id' => $e['regType'],
                'registration_number'  => $e['regNo'],
                'gender'               => $e['gender'] ?? null,
                'date_of_birth'        => $e['date_of_birth'] ?? null,
                'contact_person'       => $e['contact'] ?? null,
                'phone'                => $e['phone'],
                'email'                => $e['email'],
                'website'              => $e['website'] ?? null,
                'country_id'           => $countryId,
                'island_id'            => $islandId,
                'address'              => $e['address'] ?? 'Sample address',
                'location_type'        => $e['locType'],
                'created_at'           => $now,
                'updated_at'           => $now,
            ];
        }, $entries);

        DB::table('directories')->insert($data);
    }
}
