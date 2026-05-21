<?php
$weights = [0.052, 0.058, 0.134, 0.131, 0.247, 0.375]; // Sum = 0.997

// Simulate uniform random matrix
for($a=0; $a<1000; $a++) {
    $r = [
        mt_rand(0,100)/100,
        mt_rand(0,100)/100,
        mt_rand(0,100)/100,
        mt_rand(0,100)/100,
        mt_rand(0,100)/100,
        mt_rand(0,100)/100
    ];
    $s = 0;
    $p = 1.0;
    foreach($weights as $j => $w) {
        $v = $r[$j];
        $s += $w * $v;
        $p *= pow($v ?: 0.0001, $w);
    }
    if(round($s, 2) == 0.79 && round($p, 2) == 0.78) {
        echo "Found! r = " . json_encode($r) . "\n";
        break;
    }
}
