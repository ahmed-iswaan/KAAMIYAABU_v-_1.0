<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Party;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;

class PartySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $parties = [
            // Source: Maldives political parties (example set) - adjust logos later if needed
            ['name' => 'Maldivian Democratic Party', 'short_name' => 'MDP'],
            ['name' => 'Progressive Party of Maldives', 'short_name' => 'PPM'],
            ['name' => 'People\'s National Congress', 'short_name' => 'PNC'],
            ['name' => 'People\'s National Front', 'short_name' => 'PNF'],
            ['name' => 'Maldives Third-Way Democrats', 'short_name' => 'MTD'],
            ['name' => 'Jumhooree Party', 'short_name' => 'JP'],
            ['name' => 'Adhaalath Party', 'short_name' => 'AP'],
            ['name' => 'Maldives National Party', 'short_name' => 'MNP'],
            ['name' => 'Maldives Development Alliance', 'short_name' => 'MDA'],
            ['name' => 'The Democrats', 'short_name' => 'DEMS'],
        ];

        foreach ($parties as $p) {
            $party = Party::firstOrCreate(
                ['short_name' => $p['short_name']],
                [
                    'id' => (string) Str::uuid(),
                    'name' => $p['name'],
                    'status' => 'Active',
                ]
            );

            // Attempt to set logo if column exists
            if (Schema::hasColumn($party->getTable(), 'logo')) {
                $short = strtolower($party->short_name);
                $possibleExtensions = ['png','jpg','jpeg','webp'];
                $src = null;
                foreach($possibleExtensions as $ext){
                    $path = database_path('seeders/data/image/'.$short.'.'.$ext);
                    if (file_exists($path)) { $src = $path; break; }
                }
                if($src){
                    $destDir = 'party/logos';
                    $filename = $short.'.'.pathinfo($src, PATHINFO_EXTENSION);
                    if(!Storage::disk('public')->exists($destDir.'/'.$filename)){
                        Storage::disk('public')->put($destDir.'/'.$filename, file_get_contents($src));
                    }
                    if (empty($party->logo) || $party->logo !== $destDir.'/'.$filename) {
                        $party->logo = $destDir.'/'.$filename;
                        $party->save();
                    }
                }
            }
        }
    }
}
