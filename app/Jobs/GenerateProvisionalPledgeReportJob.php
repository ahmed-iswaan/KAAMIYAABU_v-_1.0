<?php

namespace App\Jobs;

use App\Models\Directory;
use App\Models\Election;
use App\Models\GeneratedReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateProvisionalPledgeReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Hard limits to keep memory usage predictable on small servers
    private const DIR_CHUNK_SIZE = 25;

    public function __construct(public string $reportId)
    {
    }

    public function handle(): void
    {
        /** @var GeneratedReport|null $report */
        $report = GeneratedReport::query()->find($this->reportId);
        if (! $report) {
            return;
        }

        $report->status = GeneratedReport::STATUS_RUNNING;
        $report->started_at = now();
        $report->error = null;
        $report->save();

        try {
            $params = (array) ($report->params ?? []);
            $electionId = (string) ($params['election_id'] ?? '');
            $allowedSubconsiteIds = (array) ($params['allowed_sub_consite_ids'] ?? []);
            $filterSubConsiteId = $params['filter_sub_consite_id'] ?? null;
            $search = (string) ($params['search'] ?? '');

            if ($electionId === '') {
                throw new \RuntimeException('Election is required');
            }

            // IMPORTANT: stream to local filesystem to keep memory usage low
            $reportDisk = $report->disk ?: 'local';
            if ($reportDisk !== 'local') {
                // Fallback: force local to avoid memory-heavy buffering for non-local disks.
                $reportDisk = 'local';
            }

            $baseQuery = Directory::query()
                ->where('directories.status', 'Active')
                ->when(!empty($allowedSubconsiteIds), fn($q) => $q->whereIn('directories.sub_consite_id', $allowedSubconsiteIds))
                ->whereIn('directories.sub_consite_id', function ($sub) use ($electionId) {
                    $sub->select('sub_consite_id')
                        ->from('participants')
                        ->where('election_id', $electionId);
                })
                ->leftJoin('sub_consites', 'sub_consites.id', '=', 'directories.sub_consite_id')
                ->leftJoin('voter_pledges as vp_final', function ($join) use ($electionId) {
                    $join->on('vp_final.directory_id', '=', 'directories.id')
                        ->where('vp_final.election_id', '=', $electionId)
                        ->where('vp_final.type', '=', \App\Models\VoterPledge::TYPE_FINAL);
                })
                ->select([
                    'directories.id',
                    'directories.name',
                    'directories.id_card_number',
                    'directories.phones',
                    'directories.street_address',
                    'directories.address',
                    'sub_consites.code as sub_consite_code',
                    'sub_consites.name as sub_consite_name',
                    'vp_final.status as final_pledge_status',
                ]);

            if ($filterSubConsiteId) {
                $baseQuery->where('directories.sub_consite_id', $filterSubConsiteId);
            }

            if ($search !== '') {
                $term = '%'.$search.'%';
                $baseQuery->where(function ($qq) use ($term) {
                    $qq->where('directories.name', 'like', $term)
                        ->orWhere('directories.email', 'like', $term)
                        ->orWhere('directories.id_card_number', 'like', $term)
                        ->orWhere('directories.street_address', 'like', $term)
                        ->orWhere('directories.address', 'like', $term);
                });
            }

            // Determine max number of provisional pledges per directory
            $dirIdSub = (clone $baseQuery)->select('directories.id');
            $maxPledges = (int) DB::query()
                ->fromSub(
                    DB::table('voter_provisional_user_pledges as vpp2')
                        ->joinSub($dirIdSub, 'd2', function ($join) {
                            $join->on('d2.id', '=', 'vpp2.directory_id');
                        })
                        ->where('vpp2.election_id', $electionId)
                        ->selectRaw('vpp2.directory_id, COUNT(*) as cnt')
                        ->groupBy('vpp2.directory_id'),
                    'x'
                )
                ->selectRaw('COALESCE(MAX(cnt), 0) as max_cnt')
                ->value('max_cnt');

            $maxPledges = max(0, min($maxPledges, 25));

            $filename = 'provisional-pledges-election-'.$electionId.'-pivot-'.$maxPledges.'-'.now()->format('Ymd_His').'.csv';
            $relativePath = 'reports/'.$report->user_id.'/'.now()->format('Y-m-d').'/'.$report->id.'.csv';

            $fullPath = storage_path('app/'.ltrim($relativePath, '/'));
            if (! is_dir(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0775, true);
            }

            $out = fopen($fullPath, 'w');
            if (! $out) {
                throw new \RuntimeException('Failed to open report output file for writing');
            }

            // UTF-8 BOM
            fwrite($out, "\xEF\xBB\xBF");

            $header = [
                'Name',
                'ID Card',
                'Phones',
                'Permanent Address',
                'SubConsite Code',
                'SubConsite Name',
                'Final Pledge',
            ];
            for ($i = 1; $i <= $maxPledges; $i++) {
                $header[] = "Provisional Pledge {$i}";
                $header[] = "Provisional Pledge {$i} By";
                $header[] = "Provisional Pledge {$i} Updated At";
            }
            fputcsv($out, $header);

            $baseQuery->orderBy('directories.id')
                ->chunkById(self::DIR_CHUNK_SIZE, function ($rows) use ($out, $maxPledges, $electionId) {
                    $dirIds = $rows->pluck('id')->map(fn($v) => (string) $v)->all();

                    // Load only the most recent N provisional pledges per directory to avoid large per-chunk spikes.
                    // MySQL 8+ supports window functions; if not available, fall back to the previous behavior.
                    $pledgesByDirectory = [];
                    try {
                        $placeholders = implode(',', array_fill(0, count($dirIds), '?'));
                        $bindings = array_merge([$electionId], $dirIds, [$maxPledges]);

                        $sql = "
                            SELECT directory_id, status, updated_at, user_id
                            FROM (
                                SELECT vpp.directory_id,
                                       vpp.status,
                                       vpp.updated_at,
                                       vpp.user_id,
                                       ROW_NUMBER() OVER (PARTITION BY vpp.directory_id ORDER BY vpp.updated_at DESC) AS rn
                                FROM voter_provisional_user_pledges vpp
                                WHERE vpp.election_id = ?
                                  AND vpp.directory_id IN ($placeholders)
                            ) t
                            WHERE t.rn <= ?
                            ORDER BY t.directory_id ASC, t.updated_at DESC
                        ";

                        $rowsP = DB::select($sql, $bindings);
                        foreach ($rowsP as $pp) {
                            $did = (string) $pp->directory_id;
                            $pledgesByDirectory[$did][] = $pp;
                        }
                    } catch (Throwable $e) {
                        // Fallback: may use more memory, but still bounded by DIR_CHUNK_SIZE.
                        $pledges = DB::table('voter_provisional_user_pledges as vpp')
                            ->where('vpp.election_id', $electionId)
                            ->whereIn('vpp.directory_id', $dirIds)
                            ->orderBy('vpp.updated_at', 'desc')
                            ->get([
                                'vpp.directory_id',
                                'vpp.status',
                                'vpp.updated_at',
                                'vpp.user_id',
                            ]);

                        foreach ($pledges as $pp) {
                            $did = (string) $pp->directory_id;
                            if (!isset($pledgesByDirectory[$did])) {
                                $pledgesByDirectory[$did] = [];
                            }
                            // Keep only the first N in-memory (query is already DESC)
                            if (count($pledgesByDirectory[$did]) < $maxPledges) {
                                $pledgesByDirectory[$did][] = $pp;
                            }
                        }
                    }

                    foreach ($rows as $r) {
                        $phones = $r->phones;
                        if (is_array($phones)) {
                            $phones = implode(', ', array_filter($phones));
                        } elseif (is_string($phones)) {
                            $decoded = json_decode($phones, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $phones = implode(', ', array_filter($decoded));
                            }
                        }

                        $p = collect($pledgesByDirectory[(string) $r->id] ?? []);

                        $cells = [];
                        for ($i = 0; $i < $maxPledges; $i++) {
                            $row = $p->get($i);
                            $cells[] = $row ? (strtolower(trim((string) ($row->status ?? ''))) ?: 'pending') : '';
                            $cells[] = $row ? ((string) ($row->user_id ?? '')) : '';
                            $cells[] = $row ? ($row->updated_at ?? '') : '';
                        }

                        $final = strtolower((string) ($r->final_pledge_status ?? ''));
                        if ($final === '') $final = 'pending';

                        fputcsv($out, array_merge([
                            $r->name,
                            $r->id_card_number,
                            $phones,
                            $r->street_address,
                            $r->sub_consite_code,
                            $r->sub_consite_name,
                            $final,
                        ], $cells));
                    }

                    // Flush file buffers periodically
                    if (function_exists('fflush')) {
                        @fflush($out);
                    }
                });

            fclose($out);

            $report->status = GeneratedReport::STATUS_COMPLETED;
            $report->filename = $filename;
            $report->disk = $reportDisk;
            $report->path = $relativePath;
            $report->finished_at = now();
            $report->save();
        } catch (Throwable $e) {
            $report->status = GeneratedReport::STATUS_FAILED;
            $report->error = $e->getMessage();
            $report->finished_at = now();
            $report->save();

            throw $e;
        }
    }
}
