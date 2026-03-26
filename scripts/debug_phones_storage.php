<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$counts = [
    'null' => DB::selectOne('SELECT COUNT(1) AS c FROM directories WHERE phones IS NULL')->c,
    'empty_string' => DB::selectOne("SELECT COUNT(1) AS c FROM directories WHERE phones = ''")->c,
    'json_empty_array' => DB::selectOne('SELECT COUNT(1) AS c FROM directories WHERE JSON_VALID(phones)=1 AND JSON_LENGTH(phones)=0')->c,
    'json_null' => DB::selectOne('SELECT COUNT(1) AS c FROM directories WHERE JSON_VALID(phones)=1 AND JSON_TYPE(phones) = "NULL"')->c,
    'invalid_json' => DB::selectOne('SELECT COUNT(1) AS c FROM directories WHERE phones IS NOT NULL AND JSON_VALID(phones)=0')->c,
];

foreach ($counts as $k => $v) {
    echo $k . '=' . $v . PHP_EOL;
}

echo PHP_EOL . "Sample phones values:" . PHP_EOL;
$rows = DB::select('SELECT id, phones FROM directories ORDER BY phones IS NULL DESC, id LIMIT 15');
foreach ($rows as $r) {
    $v = $r->phones;
    if (is_string($v) && strlen($v) > 120) {
        $v = substr($v, 0, 120) . '...';
    }
    echo $r->id . ' => ' . ($v === null ? 'NULL' : $v) . PHP_EOL;
}
