<?php
$ahpService = app(\App\Services\AHPService::class);
$cocosoService = app(\App\Services\COCOSOService::class);
$criteria = \App\Models\Criteria::whereNull('submission_id')->orderBy('id')->get();
$alternatives = \App\Models\Alternative::orderBy('id')->get();
$method = new ReflectionMethod($cocosoService, 'getDecisionMatrix');
$method->setAccessible(true);
$matrix = $method->invoke($cocosoService, $criteria, $alternatives);
$method2 = new ReflectionMethod($cocosoService, 'normalizeMatrix');
$method2->setAccessible(true);
$normalized = $method2->invoke($cocosoService, $matrix, $criteria);

echo "Decision Matrix:\n" . json_encode($matrix) . "\n\n";
echo "Normalized:\n" . json_encode($normalized) . "\n\n";
