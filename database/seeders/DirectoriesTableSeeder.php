<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\Directory;
use App\Models\DirectoryType;
use App\Models\RegistrationType;
use App\Models\Island;
use App\Models\Property;
use App\Models\Country;
use Carbon\Carbon;

class DirectoriesTableSeeder extends Seeder
{
    /**
     * Run the directory people seeds.
     */
    public function run(): void
    {
        // 1) Look up the related IDs
        $individualDirectoryType = DirectoryType::firstWhere('name', 'Individual');
        $idCardRegistrationType  = RegistrationType::firstWhere('slug', 'id_card');
        $mulahIsland             = Island::firstWhere('name', 'Mulah');
        $country             = Country::firstWhere('name', 'Maldives');

        if (! $individualDirectoryType || ! $idCardRegistrationType || ! $mulahIsland) {
            $this->command->error(
                'Ensure you have seeded: ' .
                'DirectoryType(name=Individual), ' .
                'RegistrationType(slug=id_card), ' .
                'Island(name=Mulah).'
            );
            return;
        }

        // 2) Path to your JSONL file
        $path = database_path('seeders/data/tableConvert.com_2do92e.jsonl');
        if (! File::exists($path)) {
            $this->command->error("JSONL file missing: {$path}");
            return;
        }

        // 3) Read each non-empty line
        $lines = preg_split('/\R/', File::get($path));
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue; // skip blanks
            }

            $row = json_decode($line, true);
            if (! $row) {
                $this->command->warn("Skipping invalid JSON: {$line}");
                continue;
            }

            // 4) Normalize date-of-birth, support both dd/mm/YYYY and d/m/yy
            $dobString = $row['D.B'] ?? '';
            $dob = null;
            if ($dobString !== '') {
                try {
                    $dob = Carbon::createFromFormat('d/m/Y', $dobString)->toDateString();
                } catch (\Exception $e1) {
                    try {
                        $dob = Carbon::createFromFormat('n/j/y', $dobString)->toDateString();
                    } catch (\Exception $e2) {
                        $this->command->warn(
                            "Bad D.B for “{$row['Name of Person']}”: “{$dobString}”"
                        );
                        $dob = null;
                    }
                }
            }

            // 5) Prepare registration_number, marking duplicates if present or null if not provided
            $rawId = trim($row['ID'] ?? '');
            if ($rawId === '') {
                $regNo = null;
            } else {
                $regNo = Directory::where('registration_number', $rawId)->exists()
                    ? $rawId . ' - Duplicate'
                    : $rawId;
            }

            // 6) Lookup related property by its number (H.No) to set properties_id
            $houseNo = trim($row['H.No'] ?? '');
            $property = $houseNo !== ''
                ? Property::where('number', $houseNo)->first()
                : null;
            $propertiesId = $property?->id;

            // 7) Insert into `directories` table
            Directory::create([
                'id'                    => Str::uuid(),
                'name'                  => $row['Name of Person'],
                'description'           => null,
                'profile_picture'       => null,
                'directory_type_id'     => $individualDirectoryType->id,
                'registration_type_id'  => $idCardRegistrationType->id,
                'registration_number'   => $regNo,
                'gst_number'            => null,
                'gender'                => strtolower($row['Sex']) === 'm' ? 'male' : 'female',
                'date_of_birth'         => $dob,
                'death_date'            => null,
                'phone'                 => null,
                'email'                 => null,
                'website'               => null,
                'country_id'            => $country->id,
                'island_id'             => $mulahIsland->id,
                'properties_id'         => $propertiesId,
                'address'               => null,
                'street_address'        => null,
                'status'                => 'Active',
                'location_type'         => 'inland',
            ]);
        }

        $this->command->info('✅ DirectoriesTableSeeder completed successfully.');
    }
}
