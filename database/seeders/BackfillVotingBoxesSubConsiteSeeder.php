<?php

namespace Database\Seeders;

use App\Models\SubConsite;
use App\Models\VotingBox;
use Illuminate\Database\Seeder;

class BackfillVotingBoxesSubConsiteSeeder extends Seeder
{
    /**
     * Backfill voting_boxes.sub_consite_id based on the voting box name.
     *
     * Rule: if the box name contains any of the ward keywords below,
     * set sub_consite_id to that ward's SubConsite (by code).
     *
     * This is intentionally conservative: it only updates rows where sub_consite_id is NULL.
     */
    public function run(): void
    {
        // Map of SubConsite code => keywords that may appear in the box name
        $map = [
            'T01' => ['hulhumale dhekunu'],
            'T16' => ['hulhumale medhu'],
            'T17' => ['hulhumale uthuru'],

            'T02' => ['medhu henveiru'],
            'T03' => ['henveiru dhekunu'],
            'T04' => ['henveiru uthuru'],
            'T14' => ['henveiru hulhangu'],

            'T05' => ['galolhu uthuru'],
            'T06' => ['galolhu dhekunu'],

            'T07' => ['mahchangolhi uthuru'],
            'T08' => ['mahchangolhi dhekunu'],
            'T15' => ['mahchangolhi medhu'],

            'T09' => ['maafannu uthuru'],
            'T10' => ['maafannu hulhangu'],
            'T11' => ['maafannu medhu'],
            'T12' => ['maafannu dhekunu'],

            'T13' => ['villimale'],
        ];

        $subIdsByCode = SubConsite::query()
            ->whereIn('code', array_keys($map))
            ->pluck('id', 'code')
            ->toArray();

        $missing = array_values(array_diff(array_keys($map), array_keys($subIdsByCode)));
        if (! empty($missing)) {
            $this->command?->error('Missing SubConsite codes in DB: '.implode(', ', $missing));
            $this->command?->error('Run ConsiteSeeder first (it creates T01..T17).');
            return;
        }

        $updated = 0;
        $skipped = 0;

        VotingBox::query()
            ->whereNull('sub_consite_id')
            ->select(['id', 'name', 'sub_consite_id'])
            ->orderBy('name')
            ->chunkById(500, function ($boxes) use (&$updated, &$skipped, $map, $subIdsByCode) {
                foreach ($boxes as $box) {
                    $name = mb_strtolower(trim((string) $box->name));

                    $matchedCode = null;
                    foreach ($map as $code => $keywords) {
                        foreach ($keywords as $kw) {
                            if (str_contains($name, $kw)) {
                                $matchedCode = $code;
                                break 2;
                            }
                        }
                    }

                    if (! $matchedCode) {
                        $skipped++;
                        continue;
                    }

                    $box->sub_consite_id = $subIdsByCode[$matchedCode];
                    $box->save();
                    $updated++;
                }
            });

        $this->command?->info("✅ BackfillVotingBoxesSubConsiteSeeder done. Updated: {$updated}. Skipped (no match): {$skipped}.");
    }
}
