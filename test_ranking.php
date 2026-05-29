<?php
$c = \App\Models\Criteria::orderBy('id')->get();
$a = \App\Models\Alternative::orderBy('id')->get();
$s = new \App\Services\COCOSOService();
$ahp = new \App\Services\AHPService();
$weights = $ahp->calculateWeights()['weightsRaw'];

echo "Weights: " . json_encode($weights) . "\n";
echo "Pi: \n";
foreach ($a as $i => $alt) {
    $piProduct = 1.0;
    foreach ($c as $j => $crit) {
        $val = 0.5; // Simulate normalized val
        $weight = 0;
        if (isset($weights[$crit->id]) && isset($weights[$crit->id]['weight'])) {
            $weight = (float) $weights[$crit->id]['weight'];
        } elseif (is_array($weights) && isset($weights[$j])) {
            $weight = (float) ($weights[$j]['weight'] ?? $weights[$j] ?? 0);
        }
        $piProduct = round($piProduct * pow(($val == 0 ? 0.0001 : $val), $weight), 10);
    }
    echo "Pi[$i] = $piProduct\n";
}
