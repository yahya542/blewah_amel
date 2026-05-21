<?php
$si = [0.79, 0.63, 0.98, 0.71, 0.57];
$pi = [0.78, 0.62, 0.97, 0.71, 0.55];
$maxSi = max($si); $minSi = min($si);
$maxPi = max($pi); $minPi = min($pi);

foreach ($si as $i => $s) {
    $p = $pi[$i];
    $ka = (($s + $p) / ($maxSi + $maxPi)) + 0.5; // From their comment
    $kb = ($s / $minSi) + ($p / $minPi);
    $kc = ((0.5 * $s) + (0.5 * $p)) / ((0.5 * $maxSi) + (0.5 * $maxPi));

    $product = $ka * $kb * $kc;
    $sumK = $ka + $kb + $kc;
    $qi = pow($product, 1/3) + (1/3 * $sumK);
    echo "K" . ($i+1) . " = " . round($qi, 3) . "\n";
}
