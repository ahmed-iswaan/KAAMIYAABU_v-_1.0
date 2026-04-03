<?php

namespace Database\Seeders;

use App\Models\Directory;
use App\Models\VotingBox;
use Illuminate\Database\Seeder;

class RenameVotingBoxHulhumaleUthuruSeeder extends Seeder
{
    /**
     * Move all directories from voting box "414 Hulhumale Uthuru 1 1" to "414 Hulhumale Uthuru 1".
     *
     * - Ensures the target voting box exists (creates if missing)
     * - Updates directories.voting_box_id in bulk
     * - Deletes the old voting box if no directories reference it afterward
     */
    public function run(): void
    {
        $fromName = '414 Hulhumale Uthuru 1 1';
        $toName = '414 Hulhumale Uthuru 1';

        $from = VotingBox::query()->where('name', $fromName)->first(['id', 'name']);
        if (! $from) {
            $this->command?->warn("Source voting box not found: {$fromName}");
            return;
        }

        $to = VotingBox::query()->firstOrCreate(['name' => $toName]);

        $affected = Directory::query()
            ->where('voting_box_id', $from->id)
            ->update(['voting_box_id' => $to->id]);

        // Delete old box after moving directories.
        // If something still references it, fail loudly.
        $stillUsed = Directory::query()->where('voting_box_id', $from->id)->exists();
        if ($stillUsed) {
            throw new \RuntimeException("Old voting box is still referenced, cannot delete: {$fromName}");
        }

        $from->delete();
        $this->command?->info("Deleted old voting box: '{$fromName}'");

        $this->command?->info("Updated {$affected} directories: '{$fromName}' -> '{$toName}'");
    }
}
