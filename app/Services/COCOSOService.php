<?php

namespace App\Services;

use App\Models\Alternative;
use App\Models\Criteria;
use App\Models\Score;
use App\Models\SubmissionScore;

class COCOSOService
{
    private function round_custom($num, $digits = 10)
    {
        return round($num, $digits);
    }

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

        // Ekstraksi bobot kriteria terlebih dahulu
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

                $siSum = $this->round_custom($siSum + $this->round_custom($val * $weight));
                $safeVal = $val <= 0 ? 0.0001 : $val;
                $piProduct = $this->round_custom($piProduct * pow($safeVal, $weight));
            }
            $si[$i] = $siSum;
            $pi[$i] = $piProduct;
        }

        $minSi = min($si);
        $maxSi = max($si);
        $minPi = min($pi);
        $maxPi = max($pi);

        // Hitung sum untuk kc sesuai rumus di rumus/alur.md
        $sumSi = array_sum($si);
        $sumPi = array_sum($pi);

        $results = [];
        foreach ($alternatives as $i => $alt) {
            // === RUMUS EXACT SESUAI rumus/alur.md (yang user bandingkan dengan Excel) ===
            // k_a = (Si-min)/(max-min) + (Pi-min)/(max-min)
            $ka = 0;
            if ($maxSi != $minSi) {
                $ka += ($si[$i] - $minSi) / ($maxSi - $minSi);
            }
            if ($maxPi != $minPi) {
                $ka += ($pi[$i] - $minPi) / ($maxPi - $minPi);
            }

            // k_b = Si / min(Si) + Pi / min(Pi)
            $kb = 0;
            if ($minSi != 0) $kb += $si[$i] / $minSi;
            if ($minPi != 0) $kb += $pi[$i] / $minPi;

            // k_c = Si/ΣSi + Pi/ΣPi
            $kc = 0;
            if ($sumSi != 0) $kc += $si[$i] / $sumSi;
            if ($sumPi != 0) $kc += $pi[$i] / $sumPi;

            $product = $ka * $kb * $kc;
            $sumK = $ka + $kb + $kc;
            $qi_raw = pow($product, 1 / 3) + ($sumK / 3);

            $results[] = [
                'alternative' => $alt,
                'name' => $alt->name,
                'si' => $si[$i],
                'pi' => $pi[$i],
                'ka' => $ka,
                'kb' => $kb,
                'kc' => $kc,
                'qi' => round($qi_raw, 3),
                // Sertakan data pelacak agar bisa dibaca di Blade View secara rapi
                'debug_raw_scores' => $decisionMatrix[$i] ?? [],
                'debug_weight' => $extractedWeights,
                'debug_normalized' => $normalizedMatrix[$i] ?? []
            ];
        }

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
                    $normalized[$i][$j] = $this->round_custom(($val - $min) / $range);
                } else {
                    $normalized[$i][$j] = $this->round_custom(($max - $val) / $range);
                }
            }
        }

        return $normalized;
    }
}
