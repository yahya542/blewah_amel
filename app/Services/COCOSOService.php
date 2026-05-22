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

        // Si (Weighted Sum) and Pi (Weighted Product)
        $si = [];
        $pi = [];

        foreach ($alternatives as $i => $alt) {
            $siSum = 0;
            $piProduct = 1.0;

            foreach ($criteria as $j => $crit) {
                $val = $normalizedMatrix[$i][$j];

                // Get weight
                $weight = 0;
                if (isset($weights[$crit->id]) && isset($weights[$crit->id]['weight'])) {
                    $weight = (float) $weights[$crit->id]['weight'];
                } elseif (is_array($weights) && isset($weights[$j])) {
                    $weight = (float) ($weights[$j]['weight'] ?? $weights[$j] ?? 0);
                }

                // Si = Σ (w_j * r_ij)
                $siSum = $this->round_custom($siSum + $this->round_custom($val * $weight));

                // Pi = Π (r_ij ^ w_j)
                $piProduct = $this->round_custom($piProduct * pow(($val == 0 ? 0.0001 : $val), $weight));
            }
            $si[$i] = $siSum;
            $pi[$i] = $piProduct;
        }

        $minSi = min($si);
        $maxSi = max($si);
        $minPi = min($pi);
        $maxPi = max($pi);
        $sumSi = 0;
        foreach ($si as $s) {
            $sumSi = $this->round_custom($sumSi + $s);
        }
        $sumPi = 0;
        foreach ($pi as $p) {
            $sumPi = $this->round_custom($sumPi + $p);
        }

        $results = [];
        foreach ($alternatives as $i => $alt) {
            // 1. Ka (Sesuai perhitungan di Excel kamu: (Si+Pi)/(MaxSi+MaxPi) + 0.5)
            // Ini yang menghasilkan A1 = 1.31 dan A3 = 1.50
            $ka = (($si[$i] + $pi[$i]) / ($maxSi + $maxPi)) + 0.5;

            // 2. Kb (Sesuai perhitungan di Excel kamu: (Si/MinSi) + (Pi/MinPi))
            // Ini yang menghasilkan A1 = 2.96 dan A3 = 3.66
            $kb = ($minSi != 0 && $minPi != 0) ? ($si[$i] / $minSi) + ($pi[$i] / $minPi) : 0;

            // 3. Kc (Sesuai perhitungan di Excel kamu: (0.5*Si + 0.5*Pi) / (0.5*MaxSi + 0.5*MaxPi))
            // Ini yang menghasilkan A1 = 0.81 dan A3 = 1.00
            $lambda = 0.5;
            $kc = (($lambda * $si[$i]) + ((1 - $lambda) * $pi[$i])) / (($lambda * $maxSi) + ((1 - $lambda) * $maxPi));

            // 4. Qi (RUMUS YANG KAMU KETIK DARI EXCEL)
            // K = (PRODUCT(Ka,Kb,Kc)^(1/3) + (1/3) * SUM(Ka,Kb,Kc))
            $product = $ka * $kb * $kc;
            $sumK = $ka + $kb + $kc;

            $qi_raw = pow($product, 1 / 3) + ((1 / 3) * $sumK);

            // PEMBULATAN 2 ANGKA DI BELAKANG KOMA (Hasil Akhir Saja)
            $qi = round($qi_raw, 2);

            $results[] = [
                'alternative' => $alt,
                'name' => $alt->name,
                'si' => round($si[$i], 4),
                'pi' => round($pi[$i], 4),
                'ka' => round($ka, 2),
                'kb' => round($kb, 2),
                'kc' => round($kc, 2),
                'qi' => $qi, // Ini yang tampil: 3.16, 3.82, dll
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

        // Ambil Nilai Max dan Min per Kriteria
        for ($j = 0; $j < $n; $j++) {
            $col = array_column($matrix, $j);
            $colMax[$j] = max($col);
            $colMin[$j] = min($col);
        }

        // Proses Normalisasi Sesuai Rumus Excel
        for ($i = 0; $i < $m; $i++) { // Tambahkan loop baris ini
            for ($j = 0; $j < $n; $j++) {
                $val = $matrix[$i][$j];
                $max = $colMax[$j];
                $min = $colMin[$j];

                if ($criteria[$j]->type === 'benefit') {
                    // rij = x_ij / max(x_j)
                    $normalized[$i][$j] = ($max != 0) ? $this->round_custom($val / $max) : 0;
                } else {
                    // rij = min(x_j) / x_ij
                    $normalized[$i][$j] = ($val != 0) ? $this->round_custom($min / $val) : 0;
                }
            }
        }

        return $normalized;
    }
}
