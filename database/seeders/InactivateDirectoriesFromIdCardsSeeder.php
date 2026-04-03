<?php

namespace Database\Seeders;

use App\Models\Directory;
use App\Models\EventLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class InactivateDirectoriesFromIdCardsSeeder extends Seeder
{
    /**
     * Inactivate directories matching ID cards from database/seeders/data/inactivelist.json.
     *
     * Expected JSON format:
     * [
     *   {"ID Card": "A342397"},
     *   {"ID Card": "A010708"}
     * ]
     */
    public function run(): void
    {
        $file = database_path('seeders/data/inactivelist.json');
        if (! File::exists($file)) {
            $this->command->error("JSON file missing: {$file}");
            return;
        }

        $rows = json_decode(File::get($file), true);
        if (! is_array($rows)) {
            $this->command->error('inactivelist.json is not a valid JSON array.');
            return;
        }

        $idCards = collect($rows)
            ->map(function ($r) {
                $raw = (string) (($r['ID Card'] ?? $r['ID_CARD'] ?? $r['id_card'] ?? $r['id_card_number'] ?? '') ?? '');
                $raw = preg_replace('/\s+/', '', $raw);
                return strtoupper(trim((string) $raw));
            })
            ->filter(fn ($v) => $v !== '')
            ->unique()
            ->values();

        if ($idCards->isEmpty()) {
            $this->command->warn('No ID cards found in JSON. Nothing to do.');
            return;
        }

        $foundIdCards = Directory::query()
            ->whereIn('id_card_number', $idCards)
            ->pluck('id_card_number')
            ->map(fn ($v) => strtoupper(trim((string) $v)))
            ->unique()
            ->values();

        $notFound = $idCards->diff($foundIdCards)->values();

        $toDeactivate = Directory::query()
            ->whereIn('id_card_number', $idCards)
            ->where('status', '!=', 'Inactive')
            ->count();

        $this->command->info('ID cards in list: '.$idCards->count());
        $this->command->info('Matched in DB: '.$foundIdCards->count());
        $this->command->info('To set Inactive (status != Inactive): '.$toDeactivate);

        $updated = 0;
        Directory::query()
            ->whereIn('id_card_number', $idCards)
            ->where('status', '!=', 'Inactive')
            ->orderBy('id')
            ->chunk(500, function ($chunk) use (&$updated) {
                foreach ($chunk as $dir) {
                    $old = $dir->status;
                    $dir->status = 'Inactive';
                    $dir->save();
                    $updated++;

                    EventLog::create([
                        'user_id' => auth()->id() ?? null,
                        'event_type' => 'directory_status_changed',
                        'event_tab' => 'directory',
                        'event_entry_id' => $dir->id,
                        'description' => 'Directory status set to Inactive (inactivelist.json)',
                        'event_data' => [
                            'field' => 'status',
                            'from' => $old,
                            'to' => 'Inactive',
                            'id_card_number' => $dir->id_card_number,
                            'name' => $dir->name,
                        ],
                        'ip_address' => null,
                    ]);
                }
            });

        if ($notFound->isNotEmpty()) {
            $this->command->warn('Not found ID cards (first 50):');
            foreach ($notFound->take(50) as $idc) {
                $this->command->line(' - '.$idc);
            }
            if ($notFound->count() > 50) {
                $this->command->warn('... and '.($notFound->count() - 50).' more');
            }

            File::put(
                database_path('seeders/data/inactivelist-not-found.json'),
                json_encode($notFound->values()->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
            $this->command->warn('Saved not found list to: database/seeders/data/inactivelist-not-found.json');
        }

        $this->command->info("✅ Done. Marked Inactive: {$updated}");
    }
}
