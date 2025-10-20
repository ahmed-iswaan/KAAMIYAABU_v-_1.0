<?php
// Quick script to list duplicate property numbers per island based on the seeder JSON

require __DIR__.'/../vendor/autoload.php';

$path = __DIR__.'/../database/seeders/data/Property.json';
if (! file_exists($path)) {
    echo "Property.json not found at $path\n"; exit(1);
}

$data = json_decode(file_get_contents($path), true);
if (! is_array($data)) { echo "Invalid JSON file\n"; exit(1); }

$normIsland = function($v){ return strtolower(str_replace('é','e', trim($v ?? ''))); };

$groups = collect($data)->map(function($r) use ($normIsland){
    $name = trim($r['name'] ?? '');
    if ($name === '' || !preg_match('/^(\d{2,})/', $name, $m)) { return null; }
    return [
        'island_key' => $normIsland($r['island'] ?? ''),
        'number' => $m[1],
        'full_name' => $name,
    ];
})->filter()->groupBy(fn($r)=> $r['island_key'].'|'.$r['number'])
  ->filter(fn($g)=> $g->count() > 1);

if ($groups->isEmpty()) {
    echo "No duplicate numbers found.\n"; exit(0);
}

echo "Duplicate property numbers (grouped)\n";
foreach ($groups as $key => $group) {
    $island = $group[0]['island_key'];
    $number = $group[0]['number'];
    echo "Island: $island | Number: $number | Count: ".count($group)."\n";
    foreach ($group as $row) {
        echo "  - ".$row['full_name']."\n";
    }
    echo "\n";
}
