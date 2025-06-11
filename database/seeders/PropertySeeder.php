<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Island; // Import the Island model
use App\Models\PropertyTypes; // Import the PropertyTypes model

class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define common data for all properties
        $latitude = 2.9476522332702;
        $longitude = 73.58470916748;

        // Fetch island_id for 'Mulah'
        // Ensure that an island with the name 'Mulah' exists in your 'islands' table.
        $island = Island::where('name', 'Mulah')->first();
        $islandId = $island ? $island->id : null; // Use the fetched ID or null if not found

        // Fetch property_type_id. Assuming a 'Residential' property type exists.
        // You might need to adjust 'Residential' to a name that exists in your 'property_types' table,
        // or create a default one if it doesn't.
        $propertyType = PropertyTypes::where('name', 'Residential')->first();
        if (!$propertyType) {
            // If 'Residential' doesn't exist, create it or pick another default
            $propertyType = PropertyTypes::first(); // Fallback to any existing property type
            if (!$propertyType) {
                // If no property types exist at all, you might want to create one here
                // For example: $propertyType = PropertyTypes::create(['name' => 'Residential']);
                // Or log an error and return if no property types can be found/created.
                 $this->command->error('No property types found. Please seed property_types table first or create a "Residential" type.');
                 return;
            }
        }
        $propertyTypeId = $propertyType->id;


        // Unique H.No to Name mapping based on your provided list
        // and addressing potential conflicts for the 'number' unique field.
        $propertiesData = [
            '17' => 'ASURUMAAGE',
            '21' => 'ATHAMAAGE',
            '34' => 'AAGE',
            '36' => 'AHIMAAGE',
            '37' => 'AHIMAAGE', // 'FINIHIYAAGE' is ignored for H.No 37 to maintain uniqueness of 'number'
            '54' => 'ALAMAAGE',
            '73' => 'AARAAMUGE', // 'HANDHUVAREEGE' is ignored for H.No 73
            '77' => 'ASRAFEEGE',
            '90' => 'ATHAMAAVILAA', // 'RANKOKAAVILA' is ignored for H.No 90
            '98' => 'AKIRIVALHUGE',
            '104' => 'ALIVAAGE',
            '125' => 'AHIYAA',
            '368' => 'SILENT HIL',
            '144' => 'AAVELI',
            '156' => 'AABAADHU',
            '161' => 'AAVAAZ',
            '168' => 'AAKAAS',
            '178' => 'ALAMAAVILAA',
            '274' => 'AAINA',
            '255' => 'ARROWVILLA',
            '226' => 'AHMADEEAABAADHU',
            '65' => 'AHMADHEE MANZIL',
            '311' => 'AASMAN',
            '262' => 'AZUM',
            '299' => 'AAVINA',
            '12' => 'BAHAARUGE',
            '237' => 'BAHAAREEVILAA',
            '41' => 'BILIMAGUMAAGE',
            '57' => 'BOKARUMAAGE',
            '86' => 'BLUE BEAM',
            '119' => 'BOAGANVILAA',
            '123' => 'BULAASAA',
            '137' => 'BEACH HOUSE', // 'FAHIVAA' is ignored for H.No 137
            '247' => 'BINAA',
            '147' => 'BINMAA',
            '218' => 'BLUEVILLA',
            '296' => 'BUILDINGVILLA',
            '304' => 'BEACH HEAVEN',
            '6' => 'CHANBEYLEEGE',
            '23' => 'CHAANDHANEEGE',
            '78' => 'CHAANDHANEEVILA',
            '1' => 'DHUVEYDHAARUGE',
            '25' => 'DHEBUDHAARUGE', // 'DEBUDHAARUGE' (typo) is ignored for H.No 25
            '275' => 'FROST',
            '50' => 'DHOONIGE',
            '52' => 'DHILAASAAGE',
            '56' => 'DHETHANDIMAAGE',
            '62' => 'DHABUGE',
            '63' => 'DHOORES',
            '173' => 'DHILSAADHUGE',
            '176' => 'DHINASHA',
            '252' => 'DAYLIGHT',
            '346' => 'DREAMROSE',
            '411' => 'EAGLE RAY', // 'KIRIYAA VILLA' is ignored for H.No 411
            '24' => 'EVENINGPARIS',
            '30' => 'FEHIGE',
            '223' => 'FINIHAN\'DHUVARU',
            '38' => 'FENFIYAAZGE',
            '43' => 'FALHOMAAGE',
            '294' => 'FALAK',
            '45' => 'FEYRUMAAGE',
            '55' => 'FEEROAZUGE',
            '58' => 'FALHOAGE',
            '64' => 'FINIVAAGE',
            '88' => 'FINIFENMAAGE',
            '101' => 'FUNAMAAGE',
            '267' => 'FRUIT GARDEN',
            '114' => 'FEMOARAAGE',
            '134' => 'FASSIYA',
            '136' => 'FAHIVAA',
            '140' => 'FEHITHARI',
            '155' => 'FEHIVILAAGE',
            '164' => 'FEHIALI',
            '174' => 'FEHIVINA',
            '222' => 'FEHIVINAMAAGE',
            '232' => 'FOREST',
            '207' => 'FOTHIVELI',
            '282' => 'FAIRSTAR',
            '280' => 'FAZAA',
            '239' => 'FEHIVAADHEE',
            '271' => 'FITHUROANUVILLA',
            '265' => 'FULLMOON', // 'MORNINGVILLA' is ignored for H.No 265
            '26' => 'GUMREEGE',
            '39' => 'GOMASHIMAAGE',
            '42' => 'GULISTHAANUGE',
            '49' => 'GULBAKAAGE',
            '288' => 'GULBAKAAMANZIL',
            '69' => 'GREEN VILLA', // 'GREEN VILAA' (typo) is ignored for H.No 69
            '102' => 'GULZAARUGE',
            '111' => 'GANDHAKOALHIMAAGE', // 'GANDHAKOLHIMAAGE' (typo) is ignored for H.No 111
            '146' => 'GANDUVARU',
            '413' => 'HUDHUVILLA',
            '7' => 'HUSNOOVILAA',
            '183' => 'HUSNUAAVILAA',
            '16' => 'HUSNUGE',
            '210' => 'HUSNUVADHEE',
            '70' => 'HANDHAAN',
            '72' => 'HANDHUVAREEGE',
            '281' => 'HASTHEE',
            '295' => 'HUDHUVELI',
            '85' => 'HITHILAAGE',
            '93' => 'HUVANDHUMAAGE',
            '107' => 'HEENAAMAAGE',
            '115' => 'HULHANGUGE',
            '127' => 'HAVEEREEGE',
            '162' => 'HIMAALIYAA',
            '165' => 'HIJUREE',
            '249' => 'HIKIVELI',
            '221' => 'HIYAAVAHI',
            '177' => 'HASEENAMANZIL',
            '251' => 'HAVAASA',
            '193' => 'HAVAA',
            '185' => 'HAVEYLI',
            '289' => 'HAPPYDAWN',
            '301' => 'HAPPYNAAS',
            '28' => 'IRUMATHEEGE',
            '48' => 'IRUDHEYMAAGE',
            '53' => 'IRAMAAGE',
            '118' => 'IRAMAA VILLA',
            '128' => 'INASAAHOUSE',
            '10' => 'JANBUROALMAAGE',
            '15' => 'JANBUGE',
            '33' => 'JAVAAHIRGE',
            '105' => 'JANBUGASDHOSHUGE',
            '109' => 'JANAVAREEGE',
            '150' => 'JANBUMAA',
            '145' => 'JAZEERA',
            '248' => 'JIFTI',
            '22' => 'KANMATHEEGE',
            '29' => 'KAAMINEEGE',
            '51' => 'KEERANMAAGE',
            '75' => 'KINKIRIMAAGE',
            '87' => 'KARANKAAGE',
            '91' => 'KIRIYAA VILLA',
            '96' => 'KINBIDHOOGE',
            '97' => 'KARANKAA VILLA',
            '117' => 'KOAZEEVILLA',
            '121' => 'KETHI',
            '126' => 'KANEERUVILLA',
            '154' => 'KURIBOSHI',
            '191' => 'KOKAAHANDHUVARU',
            '306' => 'KEEMAA',
            '338' => 'KAVAL',
            '516' => 'LAMSAA HOUSE',
            '182' => 'LEMON',
            '44' => 'LILYMAAGE',
            '166' => 'LEGOONA',
            '179' => 'LAKE',
            '464' => 'MAAVEL',
            '391' => 'MAAVEHI',
            '266' => 'MAHEENA',
            '229' => 'MALA',
            '230' => 'MALA', // 'SHAYA' is ignored for H.No 230
            '5' => 'MUNIYAAGE',
            '8' => 'MATHARASGE',
            '13' => 'MAAFOLHEYGE',
            '19' => 'MADHAARUGE',
            '244' => 'MAAVEYO',
            '40' => 'MIRUSMAAGE',
            '67' => 'MINIVANASSEYRI',
            '71' => 'MUSHTHAREEGE',
            '79' => 'MOONIMAAGE',
            '187' => 'MOONIMAAVILLA',
            '94' => 'MANADHOOGE',
            '106' => 'MIRIHIMAAGE',
            '131' => 'MANZIL',
            '151' => 'MAAFUSHEEVILA',
            '152' => 'MEENAAZ',
            '171' => 'MULAHMA',
            '175' => 'MAAKOANI',
            '219' => 'MAAOLHU',
            '228' => 'MOONLIGHTVILLA',
            '264' => 'MORNINGVILLA',
            '231' => 'MUNIYAVILA',
            '233' => 'MEHEL',
            '285' => 'MAAKAREEVILLA',
            '283' => 'MAAVINA',
            '292' => 'MIUMAN',
            '246' => 'MARIYAAZ',
            '312' => 'MAAHIYA',
            '339' => 'MARVELOUS',
            '272' => 'MARINEVILLA', // 'MERINEVILLA' (typo) is ignored for H.No 272
            '352' => 'MALAK',
            '31' => 'NOOMARAAGE',
            '47' => 'NOORANGULEYGE',
            '76' => 'NOORAANEEGE',
            '108' => 'NEWSTAR',
            '112' => 'NOORAANEEVILLA',
            '113' => 'NAARES',
            '124' => 'NOOVILAAGE',
            '130' => 'NOOMAAGE',
            '143' => 'NEWFLOWER',
            '157' => 'NIGHTROSE',
            '158' => 'NOOKOKAGE',
            '159' => 'NASEEMEEVILA',
            '167' => 'NOOALI',
            '216' => 'NIVAIDHOSHUGE',
            '257' => 'NIYAAMA',
            '242' => 'NEELVILLA',
            '284' => 'NEW LIN',
            '293' => 'NEW MOON',
            '61' => 'OCEANLEAD',
            '81' => 'ORCHID VILAA',
            '220' => 'ORCHIDMAAGE',
            '35' => 'PENZEEMAGE',
            '208' => 'PINK GARDEN',
            '59' => 'RAANBAAGE',
            '254' => 'REYSHAM',
            '424' => 'RAISING STAR',
            '82' => 'ROANUGE',
            '89' => 'RANKOKAAVILA',
            '95' => 'RANKOKAAGE',
            '18' => 'ROASHANEEGE',
            '133' => 'RIVELI',
            '142' => 'RIHIVELI',
            '287' => 'REDROSE',
            '184' => 'ROASAN',
            '215' => 'RABEEU',
            '305' => 'REEF',
            '388' => 'SAAVAN',
            '300' => 'SHAYAN',
            '14' => 'SEVENSTAR',
            '27' => 'SOSUNVILLA',
            '186' => 'SHAHIL',
            '74' => 'SHEHENAZMANZIL',
            '100' => 'SOSUNGE',
            '103' => 'SABUNAMEEGE',
            '110' => 'SNOWROSE',
            '141' => 'SHAZVEEN',
            '153' => 'SITHARAVADHEE',
            '160' => 'SOAMAAVILLA',
            '172' => 'SOAMAA',
            '211' => 'SHAAH',
            '213' => 'SWEET DREAM',
            '227' => 'SAIMA',
            '353' => 'SAISH',
            '32' => 'SUNLIGHT',
            '4' => 'THOATHAAGE',
            '46' => 'THIBUROAZUGE',
            '84' => 'THIYARAMAAGE',
            '122' => 'THEMA',
            '132' => 'THEESREEMANZIL',
            '425' => 'TWIN FLOWER',
            '3' => 'UNIMAAGE',
            '99' => 'UDUVILAGE',
            '135' => 'UDHARES',
            '180' => 'UMMEEDHU',
            '261' => 'URAHA',
            '2' => 'VAIMATHEEGE',
            '9' => 'VAADHEEGE',
            '83' => 'VAIJEHEYGE',
            '92' => 'VEENUSVILLA',
            '116' => 'VELIVARU',
            '209' => 'VEYOVILLA',
            '461' => 'VILLA',
            '400' => 'VILLA DELIGHT',
            '325' => 'VILLAGE',
            '129' => 'VINARES',
            '138' => 'VEESUNHOUSE',
            '149' => 'VEEVARU',
            '236' => 'VEESANVILLA',
            '298' => 'VIDHUVARU',
            '341' => 'WATER VILLA',
            '214' => 'WHITE LILLY',
            '66' => 'WESTSIDE',
            '250' => 'WESTBEACH',
            '11' => 'ZIYAARAIHDHOSHUGE',
            '68' => 'ZEENIYAAMAGE',
            '277' => 'ZIURIK',
            '139' => 'ZAMAANEEHOUSE',
            '364' => 'ZUHUDHAMANZIL',
            '365' => 'ZUHUDHA MANZIL',
            '268' => 'ZENITH',
            '148' => 'ZAMEEN',
        ];

        // Ensure islandId and propertyTypeId are not null before inserting
        if (is_null($islandId) || is_null($propertyTypeId)) {
            $this->command->error('Island or Property Type not found. Cannot seed properties.');
            return;
        }

        $insertData = [];
        foreach ($propertiesData as $number => $name) {
            $insertData[] = [
                'id' => Str::uuid(),
                'name' => $name,
                'register_number' => 'REG-' . $number, // Dummy registration number
                'number' => (string) $number, // Ensure it's a string for the 'number' column
                'property_type_id' => $propertyTypeId,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'square_feet' => null, // Left null as per your schema and no specific value provided
                'island_id' => $islandId,
                'ward_id' => null, // Left null as per your schema and no specific value provided
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('properties')->insert($insertData);
    }
}

