<?php

$path = __DIR__ . '/../resources/views/livewire/call-center/call-center-beta-details.blade.php';
$s = file_get_contents($path);

preg_match_all('/@(if|endif|forelse|endforelse|foreach|endforeach|else|elseif)\b/', $s, $m);
$c = [];
foreach ($m[1] as $t) {
    $c[$t] = ($c[$t] ?? 0) + 1;
}
ksort($c);
print_r($c);
