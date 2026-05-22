<?php

namespace App\Services;

use App\Models\Alternative;
use App\Models\Criteria;
use App\Models\Score;
use App\Models\SubmissionScore;

class COCOSOService
{
    public function calculateRanking($weights, $criteria = null, $submissionId = null)
    {
        if ($criteria === null) {
            $criteria = Criteria::where('submission_id', $submissionId)->orderBy('id')->get();
        }

        $alternatives = Alternative::where('submission_id', $submissionId)->get();

        if ($criteria->isEmpty() || $alternatives->isEmpty()) {
            return [];
        }

        if ($submissionId) {
            $decisionMatrix = $this->getDecisionMatrixFromSubmission($submissionId, $criteria, $alternatives);
        } else {
            $decisionMatrix = $this->getDecisionMatrix($criteria, $alternatives);
        }

        $normalizedMatrix = $this->normalizeMatrix($decisionMatrix, $criteria);

        $si = [];
        $pi = [];

        // Ekstraksi bobot kriteria
        $extractedWeights = [];
        foreach ($criteria as $j => $crit) {
            $weight = 0;
            foreach ($weights as $wKey => $wVal) {
                if (is_object($wVal) && isset($wVal->id) && $wVal->id == $crit->id) { $weight = (float)($wVal->weight ?? 0); break; }
                elseif (is_array($wVal) && isset($wVal['id']) && $wVal['id'] == $crit->id) { $weight = (float)($wVal['weight'] ?? 0); break; }
            }
            if ($weight == 0) {
                if (isset($weights[$crit->id])) { $weight = (float)(is_array($weights[$crit->id]) ? ($weights[$crit->id]['weight'] ?? 0) : $weights[$crit->id]); }
                elseif (isset($weights[$j])) { $weight = (float)(is_array($weights[$j]) ? ($weights[$j]['weight'] ?? $weights[$j] ?? 0) : $weights[$j]); }
            }
            if ($weight > 1) { $weight = $weight / 100; }
            $extractedWeights[$j] = $weight;
        }

        foreach ($alternatives as $i => $alt) {
            $siSum = 0;
            $piProduct = 1.0;

            foreach ($criteria as $j => $crit) {
                $val = $normalizedMatrix[$i][$j];
                $weight = $extractedWeights[$j];

                $siSum += ($val * $weight);
                $safeVal = $val <= 0 ? 1.0 : $val; 
                $piProduct *= pow($safeVal, $weight);
            }
            $si[$i] = $siSum;
            $pi[$i] = $piProduct;
        }

        $minSi = min($si);
        $maxSi = max($si);
        $minPi = min($pi);
        $maxPi = max($pi);

        $sumSiPi = 0;
        foreach ($si as $idx => $s) {
            $sumSiPi += $s + $pi[$idx];
        }

        $results = [];

        foreach ($alternatives as $i => $alt) {
            // Standar CoCoSo (Yazdani et al. 2019)
            // k_a = (S_i + P_i) / Σ(S_i + P_i)
            // k_b = S_i/min(S) + P_i/min(P)
            // k_c = 0.5*(S_i/max(S)) + 0.5*(P_i/max(P))
            $kia = $sumSiPi > 0 ? ($si[$i] + $pi[$i]) / $sumSiPi : 0;
            $kib = ($minSi != 0 ? $si[$i] / $minSi : 0) + ($minPi != 0 ? $pi[$i] / $minPi : 0);
            $kic = 0.5 * ($maxSi != 0 ? $si[$i] / $maxSi : 0) + 0.5 * ($maxPi != 0 ? $pi[$i] / $maxPi : 0);

            $sumK = $kia + $kib + $kic;
            $prodK = $kia * $kib * $kic;
            $qi_raw = ($sumK / 3) + pow($prodK, 1 / 3);

            $results[] = [
                'alternative' => $alt,
                'name' => $alt->name,
                'si' => round($si[$i], 2),
                'pi' => round($pi[$i], 2),
                'ka' => $kia,
                'kb' => $kib,
                'kc' => $kic,
                'qi' => round($qi_raw, 3),
                'debug_raw_scores' => $decisionMatrix[$i] ?? [],
                'debug_weight' => $extractedWeights,
                'debug_normalized' => $normalizedMatrix[$i] ?? []
            ];
        }

        // Urutkan dari nilai preferensi tertinggi ke terendah
        usort($results, fn ($a, $b) => $b['qi'] <=> $a['qi']);

        return $results;
    }

    private function getDecisionMatrixFromSubmission($submissionId, $criteria, $alternatives)
    {
        $matrix = [];
        foreach ($alternatives as $i => $alt) {
            foreach ($criteria as $j => $crit) {
                $score = SubmissionScore::where('submission_id', $submissionId)
                    ->where('alternative_id', $alt->id)
                    ->where('criteria_id', $crit->id)
                    ->first();
                $matrix[$i][$j] = $score ? (float) $score->value : 0;
            }
        }
        return $matrix;
    }

    private function getDecisionMatrix($criteria, $alternatives)
    {
        $matrix = [];
        foreach ($alternatives as $i => $alt) {
            foreach ($criteria as $j => $crit) {
                $score = Score::where('alternative_id', $alt->id)
                    ->where('criteria_id', $crit->id)
                    ->first();
                $matrix[$i][$j] = $score ? (float) $score->value : 0;
            }
        }
        return $matrix;
    }

    private function normalizeMatrix($matrix, $criteria)
    {
        $normalized = [];
        $colMax = [];
        $colMin = [];

        $m = count($matrix);
        $n = count($criteria);

        for ($j = 0; $j < $n; $j++) {
            $col = array_column($matrix, $j);
            $colMax[$j] = max($col);
            $colMin[$j] = min($col);
        }

        for ($i = 0; $i < $m; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $val = $matrix[$i][$j];
                $max = $colMax[$j];
                $min = $colMin[$j];
                $range = $max - $min;

                if ($range == 0) {
                    $normalized[$i][$j] = 1.0;
                    continue;
                }

                if ($criteria[$j]->type === 'benefit') {
                    $normalized[$i][$j] = ($val - $min) / $range;
                } else {
                    $normalized[$i][$j] = ($max - $val) / $range;
                }
            }
        }

        return $normalized;
    }
}