<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rc = new ReflectionClass(App\Livewire\CallCenter\CallCenter::class);

echo $rc->hasMethod('loadCallcenterFormForSelected') ? "has loadCallcenterFormForSelected\n" : "missing loadCallcenterFormForSelected\n";
echo $rc->hasMethod('loadCallCenterFormForSelected') ? "has loadCallCenterFormForSelected\n" : "missing loadCallCenterFormForSelected\n";
