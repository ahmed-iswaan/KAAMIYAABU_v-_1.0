<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Election;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportsController extends Controller
{
    public function usersPerformanceDailyCsv(Request $request): StreamedResponse
    {
        $userId = (int) $request->route('user');
        if ($userId <= 0) {
            abort(422, 'Invalid user');
        }

        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();
        if (!$from || !$to) {
            abort(422, 'from/to required');
        }

        $activeElectionId = Election::query()
            ->where('status', Election::STATUS_ACTIVE)
            ->value('id');
        if (!$activeElectionId) {
            abort(422, 'No active election');
        }

        $userName = DB::table('users')->where('id', $userId)->value('name') ?? ('user_' . $userId);
        $safeName = preg_replace('/[^A-Za-z0-9_\-]+/','_', (string)$userName);
        $filename = 'users_performance_daily_' . $safeName . '_' . now()->format('Y-m-d_His') . '.csv';

        $rows = $this->getUserDailyStatsRows($activeElectionId, $userId);
        $rows = collect($rows)
            ->where('date', '>=', $from)
            ->where('date', '<=', $to)
            ->values();

        return response()->streamDownload(function () use ($rows, $userName) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['User', 'Date', 'Completed', 'Attempts']);

            foreach ($rows as $d) {
                fputcsv($out, [
                    (string)$userName,
                    (string)($d['date'] ?? ''),
                    (int)($d['completed'] ?? 0),
                    (int)($d['attempts'] ?? 0),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function usersPerformanceDailyZip(Request $request): Response
    {
        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();
        if (!$from || !$to) {
            abort(422, 'from/to required');
        }

        $activeElectionId = Election::query()
            ->where('status', Election::STATUS_ACTIVE)
            ->value('id');
        if (!$activeElectionId) {
            abort(422, 'No active election');
        }

        $users = $this->getUsersPerformanceList($activeElectionId);

        // Zip preferred; if missing, output one combined CSV
        if (!class_exists(\ZipArchive::class)) {
            $filename = 'users_performance_daily_all_' . now()->format('Y-m-d_His') . '.csv';
            return response()->streamDownload(function () use ($users, $activeElectionId, $from, $to) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, ['User', 'Date', 'Completed', 'Attempts']);

                foreach ($users as $u) {
                    $userId = (int) $u->id;
                    $userName = (string) $u->name;

                    $rows = collect($this->getUserDailyStatsRows($activeElectionId, $userId))
                        ->where('date', '>=', $from)
                        ->where('date', '<=', $to);

                    foreach ($rows as $d) {
                        fputcsv($out, [$userName, $d['date'], (int)$d['completed'], (int)$d['attempts']]);
                    }
                }

                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'upd_');
        if ($tmp === false) {
            abort(500, 'Unable to create temp file');
        }

        $zipPath = $tmp . '.zip';
        @unlink($zipPath);

        $zip = new \ZipArchive();
        $opened = $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        if ($opened !== true) {
            @unlink($tmp);
            abort(500, 'Unable to create zip');
        }

        foreach ($users as $u) {
            $userId = (int) $u->id;
            $userName = (string) $u->name;
            $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $userName);

            $rows = collect($this->getUserDailyStatsRows($activeElectionId, $userId))
                ->where('date', '>=', $from)
                ->where('date', '<=', $to);

            $fh = fopen('php://temp', 'w+');
            fwrite($fh, "\xEF\xBB\xBF");
            fputcsv($fh, ['User', 'Date', 'Completed', 'Attempts']);
            foreach ($rows as $d) {
                fputcsv($fh, [$userName, $d['date'], (int)$d['completed'], (int)$d['attempts']]);
            }
            rewind($fh);
            $csv = stream_get_contents($fh) ?: '';
            fclose($fh);

            $zip->addFromString($safeName . '_daily.csv', $csv);
        }

        $zip->close();
        @unlink($tmp);

        $filename = 'users_performance_daily_' . now()->format('Y-m-d_His') . '.zip';

        return response()->download($zipPath, $filename, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    private function getUsersPerformanceList(string $activeElectionId)
    {
        $attemptsByUser = DB::table('election_directory_call_sub_statuses as edcss')
            ->where('edcss.election_id', $activeElectionId)
            ->select('edcss.updated_by')
            ->selectRaw('COUNT(DISTINCT CONCAT(edcss.directory_id, ":", edcss.attempt)) as attempts')
            ->groupBy('edcss.updated_by');

        $completedByUser = DB::table('election_directory_call_statuses as edcs')
            ->where('edcs.election_id', $activeElectionId)
            ->where('edcs.status', 'completed')
            ->select('edcs.updated_by')
            ->selectRaw('COUNT(edcs.id) as completed')
            ->groupBy('edcs.updated_by');

        return DB::table('users as u')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('model_has_roles as mhr')
                    ->whereColumn('mhr.model_id', 'u.id')
                    ->where('mhr.model_type', '=', 'App\\Models\\User');
            })
            ->leftJoinSub($attemptsByUser, 'a', fn($j) => $j->on('a.updated_by', '=', 'u.id'))
            ->leftJoinSub($completedByUser, 'c', fn($j) => $j->on('c.updated_by', '=', 'u.id'))
            ->select('u.id', 'u.name')
            ->selectRaw('COALESCE(c.completed, 0) as completed')
            ->selectRaw('COALESCE(a.attempts, 0) as attempts')
            ->orderByRaw('COALESCE(c.completed, 0) DESC')
            ->orderBy('u.name')
            ->get();
    }

    private function getUserDailyStatsRows(string $activeElectionId, int $userId): array
    {
        $attempts = DB::table('election_directory_call_sub_statuses as edcss')
            ->where('edcss.election_id', $activeElectionId)
            ->where('edcss.updated_by', $userId)
            ->selectRaw('DATE(MIN(edcss.updated_at)) as d')
            ->selectRaw('COUNT(*) as attempts')
            ->groupBy('edcss.directory_id', 'edcss.attempt');

        $completed = DB::table('election_directory_call_statuses as edcs')
            ->where('edcs.election_id', $activeElectionId)
            ->where('edcs.updated_by', $userId)
            ->where('edcs.status', 'completed')
            ->selectRaw('DATE(COALESCE(edcs.completed_at, edcs.updated_at)) as d')
            ->selectRaw('COUNT(*) as completed')
            ->groupBy(DB::raw('DATE(COALESCE(edcs.completed_at, edcs.updated_at))'));

        $attemptDates = DB::query()->fromSub(
                DB::table('election_directory_call_sub_statuses as edcss')
                    ->where('edcss.election_id', $activeElectionId)
                    ->where('edcss.updated_by', $userId)
                    ->select('edcss.directory_id', 'edcss.attempt', 'edcss.updated_at'),
                'x'
            )
            ->selectRaw('DISTINCT DATE(MIN(x.updated_at)) as d')
            ->groupBy('x.directory_id', 'x.attempt');

        $completeDates = DB::table('election_directory_call_statuses as edcs')
            ->where('edcs.election_id', $activeElectionId)
            ->where('edcs.updated_by', $userId)
            ->where('edcs.status', 'completed')
            ->selectRaw('DISTINCT DATE(COALESCE(edcs.completed_at, edcs.updated_at)) as d');

        $datesUnion = $attemptDates->union($completeDates);

        $rows = DB::query()->fromSub($datesUnion, 'dd')
            ->leftJoinSub(
                DB::query()->fromSub($attempts, 'att')
                    ->select('att.d')
                    ->selectRaw('SUM(att.attempts) as attempts')
                    ->groupBy('att.d'),
                'a',
                fn($join) => $join->on('a.d', '=', 'dd.d')
            )
            ->leftJoinSub($completed, 'c', fn($join) => $join->on('c.d', '=', 'dd.d'))
            ->select('dd.d')
            ->selectRaw('COALESCE(c.completed, 0) as completed')
            ->selectRaw('COALESCE(a.attempts, 0) as attempts')
            ->orderBy('dd.d', 'desc')
            ->get();

        return $rows->map(fn($r) => [
            'date' => (string) $r->d,
            'completed' => (int) ($r->completed ?? 0),
            'attempts' => (int) ($r->attempts ?? 0),
        ])->toArray();
    }
}
