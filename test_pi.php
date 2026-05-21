<?php
$c = \App\Models\Criteria::orderBy('id')->get();
$a = \App\Models\Alternative::orderBy('id')->get();
$s = new \App\Services\COCOSOService();
$ahp = new \App\Services\AHPService();
$w = $ahp->calculateWeights()['weightsRaw'];
echo "WEIGHTS: " . json_encode($w) . "\n";
echo "RESULT: " . json_encode($s->calculateRanking($w, $c, null)) . "\n";
