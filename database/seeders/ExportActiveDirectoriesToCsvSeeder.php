<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExportActiveDirectoriesToCsvSeeder extends Seeder
{
    /**
     * Export all active directories (current active election) with address details into a CSV file.
     *
     * Output: storage/app/exports/active_directories_YYYY-mm-dd_His.csv
     */
    public function run(): void
    {
        $dirPath = storage_path('app/exports');
        if (!is_dir($dirPath) && !@mkdir($dirPath, 0775, true) && !is_dir($dirPath)) {
            throw new \RuntimeException('Unable to create exports directory: ' . $dirPath);
        }

        $file = $dirPath . DIRECTORY_SEPARATOR . 'active_directories_' . now()->format('Y-m-d_His') . '.csv';

        $out = fopen($file, 'w');
        if ($out === false) {
            throw new \RuntimeException('Unable to open file for writing: ' . $file);
        }

        // UTF-8 BOM for Excel
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, [
            'Directory ID',
            'Directory Name',
            'NID Number',
            'Serial',
            'Block',
            'Party',
            'Gender',
            'Date Of Birth',
            'Phones',
            'SubConsite',
            'Voting Box',
            'Permanent Address',
            'Current Address',
            'Property (Permanent)',
            'Property (Current)',
        ]);

        $exported = 0;

        // Export active directories based on directories.status
        // (No election filtering)
        DB::table('directories as d')
            ->leftJoin('sub_consites as sc', 'sc.id', '=', 'd.sub_consite_id')
            ->leftJoin('voting_boxes as vb', 'vb.id', '=', 'd.voting_box_id')
            ->leftJoin('properties as p', 'p.id', '=', 'd.properties_id')
            ->leftJoin('properties as cp', 'cp.id', '=', 'd.current_properties_id')
            ->leftJoin('parties as pa', 'pa.id', '=', 'd.party_id')
            ->where('d.status', 'active')
            ->select([
                'd.id',
                'd.name',
                'd.id_card_number',
                'd.serial',
                'd.block',
                'd.gender',
                'd.date_of_birth',
                'd.phones',
                'sc.code as sub_consite_code',
                'sc.name as sub_consite_name',
                'vb.name as voting_box_name',
                'd.address',
                'd.current_address',
                'p.name as property_name',
                'cp.name as current_property_name',
                'pa.name as party_name',
            ])
            ->orderBy('d.id')
            ->chunkById(1000, function ($rows) use ($out, &$exported) {
                foreach ($rows as $r) {
                    // phones may be json array/string
                    $phones = '';
                    if (is_array($r->phones)) {
                        $phones = implode(', ', array_filter($r->phones));
                    } elseif (is_string($r->phones)) {
                        $decoded = json_decode($r->phones, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $phones = implode(', ', array_filter($decoded));
                        } else {
                            $phones = $r->phones;
                        }
                    }

                    $sub = trim((string)($r->sub_consite_code ?? ''));
                    if (!empty($r->sub_consite_name)) {
                        $sub = $sub ? ($sub . ' - ' . $r->sub_consite_name) : (string)$r->sub_consite_name;
                    }

                    $vbox = (string)($r->voting_box_name ?? '');

                    $dob = '';
                    if (!empty($r->date_of_birth)) {
                        // date_of_birth may come as string or DateTime
                        $dob = is_string($r->date_of_birth)
                            ? substr($r->date_of_birth, 0, 10)
                            : (method_exists($r->date_of_birth, 'format') ? $r->date_of_birth->format('Y-m-d') : (string)$r->date_of_birth);
                    }

                    // IMPORTANT: directories.id is UUID (string). Do not cast to int.
                    fputcsv($out, [
                        (string) $r->id,
                        (string) ($r->name ?? ''),
                        (string) ($r->id_card_number ?? ''),
                        (string) ($r->serial ?? ''),
                        (string) ($r->block ?? ''),
                        (string) ($r->party_name ?? ''),
                        (string) ($r->gender ?? ''),
                        (string) $dob,
                        (string) $phones,
                        (string) $sub,
                        (string) $vbox,
                        (string) ($r->address ?? ''),
                        (string) ($r->current_address ?? ''),
                        (string) ($r->property_name ?? ''),
                        (string) ($r->current_property_name ?? ''),
                    ]);

                    $exported++;
                }
            }, 'd.id');

        fclose($out);

        // Print where it went (shows when running seeder via artisan)
        $this->command?->info('CSV exported: ' . $file);
        $this->command?->info('Rows exported: ' . $exported);
    }
}
