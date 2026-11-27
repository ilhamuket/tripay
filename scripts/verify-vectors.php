<?php
// Cocokkan spec/test-vectors.json dengan rumus di library PHP (hash_hmac + hash_equals).
// Jalankan: php scripts/verify-vectors.php
$v = json_decode(file_get_contents(__DIR__ . '/../spec/test-vectors.json'), true);
$fail = 0;

foreach ($v['signature'] as $s) {
    $got = hash_hmac('sha256', $s['merchantCode'] . $s['merchantRef'] . $s['amount'], $s['privateKey']);
    if ($got !== $s['expected']) { $fail++; echo "FAIL signature {$s['merchantRef']}\n"; }
}

foreach ($v['openPayment'] as $o) {
    $got = hash_hmac('sha256', $o['merchantCode'] . $o['method'] . $o['merchantRef'], $o['privateKey']);
    if ($got !== $o['expected']) { $fail++; echo "FAIL openPayment {$o['method']} {$o['merchantRef']}\n"; }
}

foreach ($v['callback'] as $c) {
    $local = hash_hmac('sha256', $c['rawBody'], $c['privateKey']);
    if (hash_equals($local, $c['signature']) !== $c['valid']) { $fail++; echo "FAIL callback {$c['name']}\n"; }
}

$total = count($v['signature']) + count($v['openPayment']) + count($v['callback']);
echo $fail ? "$fail/$total gagal\n" : "PHP: $total/$total vector cocok\n";
exit($fail ? 1 : 0);
