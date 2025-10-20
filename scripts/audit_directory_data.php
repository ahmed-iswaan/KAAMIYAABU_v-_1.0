<?php
// Audit script for directorydata.json
// Run: php scripts/audit_directory_data.php

require __DIR__.'/../vendor/autoload.php';

use Illuminate\Support\Str;

$path = __DIR__.'/../database/seeders/data/directorydata.json';
if (!file_exists($path)) { fwrite(STDERR, "File not found: $path\n" ); exit(1);}   

$rows = json_decode(file_get_contents($path), true);
if (!is_array($rows)) { fwrite(STDERR, "Invalid JSON (expected array).\n"); exit(1);} 

$total = count($rows);
$seenNid = [];
$dupNid = [];
$emptyName = 0; $emptyNid = 0; $emptyParty = 0; $partyTrimmedBlank = 0; $phoneIssues = 0; $propertyPatternNoDigits = 0; $multiPhones = 0; $sameAddressMismatch = 0;
$partyCodesRaw = [];
$consitCodes = [];
$dhaairaValues = [];
$phoneCounts = [];

function normParty($v){ return strtoupper(trim($v)); }
function extractPhones($raw){
    if (trim($raw) === '') return [];
    $parts = preg_split('/[|,]/', $raw);
    $out = [];
    foreach ($parts as $p) {
        $p = preg_replace('/[^0-9+]/','', trim($p));
        if ($p !== '') { $out[$p] = true; }
    }
    return array_keys($out);
}
function extractBaseProperty($addr){
    $addr = trim($addr);
    if ($addr === '') return [null,false];
    if (preg_match('/^(\d{2,})/', $addr, $m)) return [$m[1], true];
    return [null,true]; // true meaning we attempted
}

foreach ($rows as $r) {
    $nid = trim($r['NID'] ?? '');
    $name = trim($r['NAME'] ?? '');
    $partyRaw = $r['PARTY'] ?? '';
    $partyCodesRaw[] = $partyRaw;
    $consitCodes[] = strtoupper(trim($r['CONSIT'] ?? ''));
    $dhaairaValues[] = trim($r['DHAAIRA'] ?? '');

    if ($nid === '') { $emptyNid++; continue; }
    if (isset($seenNid[$nid])) { $dupNid[$nid] = ($dupNid[$nid] ?? 1) + 1; } else { $seenNid[$nid] = 1; }
    if ($name === '') { $emptyName++; }

    $partyTrim = normParty($partyRaw);
    if ($partyRaw === '') { $emptyParty++; }
    if ($partyTrim === '' && $partyRaw !== '') { $partyTrimmedBlank++; }

    $phones = extractPhones($r['MOBILE'] ?? '');
    $countPhones = count($phones);
    if ($countPhones > 1) { $multiPhones++; }
    $phoneCounts[$countPhones] = ($phoneCounts[$countPhones] ?? 0) + 1;

    // Address patterns
    [$base1,$attempt1] = extractBaseProperty($r['ADDRESS'] ?? '');
    [$base2,$attempt2] = extractBaseProperty($r['ADDRESS2'] ?? '');
    if ($attempt1 && $attempt2 && ($r['ADDRESS'] ?? '') === ($r['ADDRESS2'] ?? '') && $base1 === null) {
        $propertyPatternNoDigits++;
    }
    if (($r['ADDRESS'] ?? '') !== ($r['ADDRESS2'] ?? '') && $base1 === $base2 && $base1 !== null) {
        $sameAddressMismatch++;
    }
}

// Aggregate party codes
$partyFreq = [];
foreach ($partyCodesRaw as $p) {
    $k = normParty($p);
    $partyFreq[$k] = ($partyFreq[$k] ?? 0) + 1;
}
arsort($partyFreq);

// CONSIT codes
$consitFreq = [];
foreach ($consitCodes as $c) { if ($c !== '') { $consitFreq[$c] = ($consitFreq[$c] ?? 0) + 1; } }
arsort($consitFreq);

// Dhaaira values
$dhaairaFreq = [];
foreach ($dhaairaValues as $d) { if ($d !== '') { $dhaairaFreq[$d] = ($dhaairaFreq[$d] ?? 0) + 1; } }
arsort($dhaairaFreq);

// Output summary
echo "Directory Data Audit Summary\n";
echo "--------------------------------\n";
printf("Total rows: %d\n", $total);
printf("Empty NID: %d\n", $emptyNid);
printf("Duplicate NIDs (count of NIDs with >1 occurrences): %d\n", count($dupNid));
if ($dupNid) {
    echo " Top duplicate NIDs (first 10):\n";
    $i=0; foreach ($dupNid as $nid=>$cnt){ if ($i++>=10) break; echo "  $nid occurrences: $cnt\n"; }
}
printf("Empty NAME: %d\n", $emptyName);
printf("Empty PARTY raw: %d\n", $emptyParty);
printf("Party raw non-empty but trims to blank: %d\n", $partyTrimmedBlank);

printf("Rows with >1 phone numbers: %d\n", $multiPhones);
ksort($phoneCounts); echo "Phone count distribution:"; foreach($phoneCounts as $c=>$n){ echo " [$c=>$n]"; } echo "\n";

printf("ADDRESS==ADDRESS2 but no leading digits: %d\n", $propertyPatternNoDigits);
printf("Different ADDRESS/ADDRESS2 but same extracted number: %d\n", $sameAddressMismatch);

// Party frequencies
echo "\nParty code frequencies (trimmed, top 15):\n"; $i=0; foreach ($partyFreq as $code=>$cnt){ if($i++>=15) break; $label=$code===''?'(EMPTY)':$code; echo "  $label => $cnt\n"; }

// CONSIT frequencies
echo "\nCONSIT code frequencies (top 15):\n"; $i=0; foreach ($consitFreq as $code=>$cnt){ if($i++>=15) break; echo "  $code => $cnt\n"; }

// Dhaaira frequencies
echo "\nDHAAIRA value frequencies (top 15):\n"; $i=0; foreach ($dhaairaFreq as $val=>$cnt){ if($i++>=15) break; echo "  $val => $cnt\n"; }

echo "\nDone.\n";
