<?php
$ahpService = app(\App\Services\AHPService::class);
$cocosoService = app(\App\Services\COCOSOService::class);

$criteria = \App\Models\Criteria::whereNull('submission_id')->orderBy('id')->get();
$alternatives = \App\Models\Alternative::orderBy('id')->get();

$ahpResults = $ahpService->calculateWeights();

$weights = $ahpResults['weightsRaw'];
$res = $cocosoService->calculateRanking($weights, $criteria, null);
echo json_encode($res, JSON_PRETTY_PRINT);
