<?php

/**
 * Debug users performance counts vs daily breakdown.
 *
 * Usage (PowerShell):
 *   php scripts/debug_user_attempts.php --user=1
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Election;

$options = getopt('', ['user::']);
$userId = isset($options['user']) ? (int)$options['user'] : 1;

$e = Election::where('status', Election::STATUS_ACTIVE)->first();
if (!$e) {
    fwrite(STDERR, "No active election found.\n");
    exit(1);
}

echo "Active election: {$e->id}\n";
echo "User: {$userId}\n\n";

$totalAttempts = (int) DB::table('election_directory_call_sub_statuses')
    ->where('election_id', (string)$e->id)
    ->where('updated_by', $userId)
    ->selectRaw('COUNT(DISTINCT CONCAT(directory_id, ":", attempt)) as c')
    ->value('c');

// Attribute each (directory_id,attempt) to the first-seen date for this user
$attemptsFirstSeenByDay = DB::table('election_directory_call_sub_statuses')
    ->where('election_id', (string)$e->id)
    ->where('updated_by', $userId)
    ->selectRaw('DATE(MIN(updated_at)) as d')
    ->selectRaw('COUNT(*) as attempts')
    ->groupBy('directory_id', 'attempt')
    ->get()
    ->groupBy('d')
    ->map(fn($g) => (int)$g->sum('attempts'))
    ->sortKeysDesc();

$sumDailyAttempts = (int) $attemptsFirstSeenByDay->sum();

$totalCompleted = (int) DB::table('election_directory_call_statuses')
    ->where('election_id', (string)$e->id)
    ->where('updated_by', $userId)
    ->where('status', 'completed')
    ->count();

$completedByDay = DB::table('election_directory_call_statuses')
    ->where('election_id', (string)$e->id)
    ->where('updated_by', $userId)
    ->where('status', 'completed')
    ->selectRaw('DATE(COALESCE(completed_at, updated_at)) as d')
    ->selectRaw('COUNT(*) as completed')
    ->groupBy(DB::raw('DATE(COALESCE(completed_at, updated_at))'))
    ->pluck('completed', 'd')
    ->map(fn($v) => (int)$v)
    ->sortKeysDesc();

echo "TOTALS\n";
echo "- Attempts (distinct directory_id+attempt): {$totalAttempts}\n";
echo "- Attempts (sum by first-seen day):        {$sumDailyAttempts}\n";
echo "- Completed:                               {$totalCompleted}\n\n";

if ($totalAttempts !== $sumDailyAttempts) {
    echo "WARNING: attempts totals do NOT match. This implies duplicate attempt rows (same directory+attempt) are being counted in one of the queries, or data violates the unique constraint.\n\n";
}

echo "DAILY (showing all days)\n";
$allDays = $attemptsFirstSeenByDay->keys()->merge($completedByDay->keys())->unique()->sortDesc();
foreach ($allDays as $d) {
    $a = (int)($attemptsFirstSeenByDay[$d] ?? 0);
    $c = (int)($completedByDay[$d] ?? 0);
    echo "{$d}  completed={$c}  attempts={$a}\n";
}

echo "\nNOTE: The dashboard UI currently hides days where completed=0, so you may see attempts drop to 0 on some displayed completed days even when total attempts is larger.\n";
