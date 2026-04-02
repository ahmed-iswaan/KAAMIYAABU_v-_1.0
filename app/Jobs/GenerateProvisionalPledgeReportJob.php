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
            $path = 'reports/'.$report->user_id.'/'.now()->format('Y-m-d').'/'.$report->id.'.csv';

            Storage::disk($report->disk)->put($path, '');
            $stream = Storage::disk($report->disk)->readStream($path);
            if (is_resource($stream)) {
                fclose($stream);
            }

            // Write CSV using temp stream then store (keeps storage drivers compatible)
            $tmp = fopen('php://temp', 'w+');
            fprintf($tmp, chr(0xEF).chr(0xBB).chr(0xBF));

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
            fputcsv($tmp, $header);

            $baseQuery->orderBy('directories.id')
                ->chunk(250, function ($rows) use ($tmp, $maxPledges, $electionId) {
                    $dirIds = $rows->pluck('id')->map(fn($v) => (string) $v)->all();

                    $pledges = DB::table('voter_provisional_user_pledges as vpp')
                        ->leftJoin('users', 'users.id', '=', 'vpp.user_id')
                        ->where('vpp.election_id', $electionId)
                        ->whereIn('vpp.directory_id', $dirIds)
                        ->orderBy('vpp.updated_at', 'desc')
                        ->get([
                            'vpp.directory_id',
                            'vpp.status',
                            'vpp.updated_at',
                            'users.name as pledge_by',
                        ])
                        ->groupBy('directory_id');

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

                        $p = ($pledges->get($r->id) ?? collect())->values();

                        $cells = [];
                        for ($i = 0; $i < $maxPledges; $i++) {
                            $row = $p->get($i);
                            $cells[] = $row ? (strtolower($row->status ?? '') ?: 'pending') : '';
                            $cells[] = $row ? ($row->pledge_by ?? '') : '';
                            $cells[] = $row ? ($row->updated_at ?? '') : '';
                        }

                        $final = strtolower((string) ($r->final_pledge_status ?? ''));
                        if ($final === '') $final = 'pending';

                        fputcsv($tmp, array_merge([
                            $r->name,
                            $r->id_card_number,
                            $phones,
                            $r->street_address,
                            $r->sub_consite_code,
                            $r->sub_consite_name,
                            $final,
                        ], $cells));
                    }
                });

            rewind($tmp);
            $csvContent = stream_get_contents($tmp);
            fclose($tmp);

            Storage::disk($report->disk)->put($path, $csvContent ?? '');

            $report->status = GeneratedReport::STATUS_COMPLETED;
            $report->filename = $filename;
            $report->path = $path;
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
