<?php

namespace Database\Seeders;

use App\Models\Directory;
use App\Models\VotingBox;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class AssignVotingBoxesFromJsonSeeder extends Seeder
{
    /**
     * Assign voting boxes to directories from database/seeders/data/box.json.
     *
     * Expected JSON format:
     * [
     *   {"Final Box": "322 Hulhumale Dhekunu 1", "ID Cards": "A034125"},
     *   ...
     * ]
     */
    public function run(): void
    {
        $file = database_path('seeders/data/box.json');
        if (! File::exists($file)) {
            $this->command->error("JSON file missing: {$file}");
            return;
        }

        $rows = json_decode(File::get($file), true);
        if (! is_array($rows)) {
            $this->command->error('box.json is not a valid JSON array.');
            return;
        }

        // Preload existing voting boxes (case-insensitive)
        $boxMap = VotingBox::query()
            ->get(['id', 'name'])
            ->mapWithKeys(fn ($b) => [mb_strtolower(trim($b->name)) => $b->id])
            ->toArray();

        $processed = 0;
        $assigned = 0;
        $createdBoxes = 0;
        $notFound = [];

        foreach ($rows as $r) {
            $processed++;

            $boxName = trim((string) ($r['Final Box'] ?? $r['final_box'] ?? $r['box'] ?? ''));
            $idCardRaw = (string) ($r['ID Cards'] ?? $r['ID Card'] ?? $r['id_card'] ?? $r['id_card_number'] ?? '');
            $idCard = strtoupper(trim(preg_replace('/\s+/', '', $idCardRaw)));

            if ($boxName === '' || $idCard === '') {
                continue;
            }

            $boxKey = mb_strtolower($boxName);
            $boxId = $boxMap[$boxKey] ?? null;
            if (! $boxId) {
                $box = VotingBox::query()->create(['name' => $boxName]);
                $boxId = $box->id;
                $boxMap[$boxKey] = $boxId;
                $createdBoxes++;
            }

            $dir = Directory::query()->where('id_card_number', $idCard)->first(['id', 'id_card_number', 'voting_box_id']);
            if (! $dir) {
                $notFound[$idCard] = true;
                continue;
            }

            if ((string) $dir->voting_box_id !== (string) $boxId) {
                $dir->voting_box_id = $boxId;
                $dir->save();
                $assigned++;
            }

            if ($processed % 5000 === 0) {
                $this->command->info("Processed {$processed} ...");
            }
        }

        if (! empty($notFound)) {
            $notFoundList = array_values(array_keys($notFound));
            sort($notFoundList);

            File::put(
                database_path('seeders/data/box-not-found.json'),
                json_encode($notFoundList, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            $this->command->warn('Not found ID cards: '.count($notFoundList));
            $this->command->warn('Saved to: database/seeders/data/box-not-found.json');
        }

        $this->command->info("✅ Done. Processed: {$processed}. Boxes created: {$createdBoxes}. Directories assigned/updated: {$assigned}.");
    }
}
