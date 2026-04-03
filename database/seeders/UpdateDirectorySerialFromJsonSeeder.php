<?php

namespace Database\Seeders;

use App\Models\Directory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class UpdateDirectorySerialFromJsonSeeder extends Seeder
{
    /**
     * Update directories.serial by matching directories.id_card_number with JSON rows.
     * JSON file: database/seeders/data/serial.json
     * Format: [{"nid":"A034125","serial":"1"}, ...]
     */
    public function run(): void
    {
        $file = database_path('seeders/data/serial.json');

        if (!File::exists($file)) {
            $this->command?->error("JSON file missing: {$file}");
            return;
        }

        $rows = json_decode(File::get($file), true);
        if (!is_array($rows)) {
            $this->command?->error('serial.json is not a valid JSON array.');
            return;
        }

        $updated = 0;
        $notFound = 0;
        $skipped = 0;
        $processed = 0;

        foreach ($rows as $r) {
            $processed++;

            $nid = strtoupper(trim((string)($r['nid'] ?? '')));
            $serialRaw = $r['serial'] ?? null;
            $serial = $serialRaw === null ? '' : trim((string) $serialRaw);

            if ($nid === '' || $serial === '') {
                $skipped++;
                continue;
            }

            $dir = Directory::query()
                ->where('id_card_number', $nid)
                ->first(['id', 'id_card_number', 'serial']);

            if (!$dir) {
                $notFound++;
                continue;
            }

            // Only update if changed
            if ((string) $dir->serial !== (string) $serial) {
                $dir->serial = $serial;
                $dir->save();
                $updated++;
            }
        }

        $this->command?->info("Serial update complete. processed={$processed}, updated={$updated}, not_found={$notFound}, skipped={$skipped}");
    }
}
