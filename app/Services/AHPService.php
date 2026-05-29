<?php

namespace App\Services;

use App\Models\Comparison;
use App\Models\Criteria;
use App\Models\SubmissionComparison;

class AHPService
{
    // Tetap gunakan 10 digit untuk akurasi perhitungan internal
    private function round_custom($num, $digits = 10)
    {
        return round($num, $digits);
    }

    public function calculateWeights($submissionId = null)
{
    $criteria = Criteria::where('submission_id', $submissionId)->orderBy('id')->get();
    $n = $criteria->count();
    if ($n === 0) { return []; }

    // 1. Hitung dengan angka teliti (jangan dibulatkan dulu agar CI tidak negatif)
    if ($submissionId) {
        $matrix = $this->getPairwiseMatrixFromSubmission($submissionId, $criteria);
    } else {
        $matrix = $this->getPairwiseMatrix($criteria);
    }

    $normalizedMatrix = $this->normalizeMatrix($matrix, $n);
    $vektor = [];
    for ($i = 0; $i < $n; $i++) {
        $sumVector = 0;
        foreach ($normalizedMatrix[$i] as $val) $sumVector += $val;
        $vektor[$i] = $sumVector;
    }
    $weights = $this->calculateEigenvector($normalizedMatrix, $n);
    $consistencyResult = $this->calculateConsistencyRatio($matrix, $weights, $n);

    // 2. Kumpulkan bobot kriteria (bulatkan ke 2 desimal di sini)
    $weightedCriteria = [];
    foreach ($criteria as $index => $crit) {
        $weight = $weights[$index] ?? 0;
        $weightRounded2 = round($weight, 2);
        if (! $submissionId) {
            $crit->update(['weight' => $weightRounded2]);
        }
        $weightedCriteria[$crit->id] = [
            'criteria_id' => $crit->id,
            'name' => $crit->name,
            'weight' => $weight,
        ];
    }

    // 3. Bulatkan Matriks HANYA untuk tampilan tabel hijau
    $displayMatrix = [];
    foreach ($matrix as $i => $row) {
        foreach ($row as $j => $val) {
            $displayMatrix[$i][$j] = round($val, 2);
        }
    }

    // 4. Return hasil: Hitungan asli tetap akurat, tapi yang dikirim ke View sudah bulat
    return [
        'weightsRaw' => $weights,
        'weightsIndexed' => array_map(fn($w) => round($w, 4), $weights),
        'weights' => $weightedCriteria,
        'cr' => round($consistencyResult['cr'], 2),
        'ri' => $consistencyResult['ri'],
        'ci' => round($consistencyResult['ci'], 2),
        'lambdaMax' => round($consistencyResult['lambdaMax'], 2),
        'eigenValues' => $consistencyResult['eigenValues'],
        'vektor' => $vektor,
        'matrix' => $displayMatrix, // Matriks ini yang akan tampil di tabel hijau (Sudah 2 digit!)
        'normalizedMatrix' => $normalizedMatrix,
        'criteria' => $criteria->values()->all(),
    ];
}

    
    // ... (Gunakan fungsi getPairwiseMatrix, normalizeMatrix, calculateEigenvector, calculateConsistencyRatio yang asli/awal tadi)



    private function getPairwiseMatrixFromSubmission($submissionId, $criteria)
    {
        $matrix = [];
        $criteriaIds = $criteria->pluck('id')->toArray();
        $n = count($criteriaIds);

        for ($i = 0; $i < $n; $i++) {
            $id1 = $criteriaIds[$i];
            for ($j = 0; $j < $n; $j++) {
                $id2 = $criteriaIds[$j];

                if ($id1 == $id2) {
                    $matrix[$j][$i] = 1.0;
                } else {
                    $comparison = SubmissionComparison::where('submission_id', $submissionId)
                        ->where('criteria_id_1', $id1)
                        ->where('criteria_id_2', $id2)
                        ->first();

                    if ($comparison) {
                        $matrix[$j][$i] = $this->round_custom((float) $comparison->value);
                    } else {
                        $reverse = SubmissionComparison::where('submission_id', $submissionId)
                            ->where('criteria_id_1', $id2)
                            ->where('criteria_id_2', $id1)
                            ->first();

                        if ($reverse && $reverse->value != 0) {
                            $matrix[$j][$i] = $this->round_custom(1.0 / (float) $reverse->value);
                        } else {
                            $matrix[$j][$i] = 1.0;
                        }
                    }
                }
            }
        }

        return $matrix;
    }

    public function getWeightsFromCriteria($submissionId = null)
    {
        $criteria = Criteria::where('submission_id', $submissionId)->orderBy('id')->get();
        $weights = [];

        foreach ($criteria as $crit) {
            $weights[$crit->id] = [
                'criteria_id' => $crit->id,
                'name' => $crit->name,
                'weight' => $this->round_custom((float) $crit->weight),
                'type' => $crit->type,
            ];
        }

        return $weights;
    }

    private function getPairwiseMatrix($criteria)
    {
        $matrix = [];
        $criteriaIds = $criteria->pluck('id')->toArray();
        $n = count($criteriaIds);

        for ($i = 0; $i < $n; $i++) {
            $id1 = $criteriaIds[$i];
            for ($j = 0; $j < $n; $j++) {
                $id2 = $criteriaIds[$j];

                if ($id1 == $id2) {
                    $matrix[$j][$i] = 1.0;
                } else {
                    $comparison = Comparison::where('criteria_id_1', $id1)
                        ->where('criteria_id_2', $id2)
                        ->first();

                    $matrix[$j][$i] = $comparison ? $this->round_custom((float) $comparison->value) : 1.0;
                }
            }
        }

        return $matrix;
    }

    private function normalizeMatrix($matrix, $n)
    {
        $columnSums = array_fill(0, $n, 0);
        for ($j = 0; $j < $n; $j++) {
            for ($i = 0; $i < $n; $i++) {
                $columnSums[$j] = $this->round_custom($columnSums[$j] + $matrix[$i][$j]);
            }
        }

        $normalizedMatrix = [];
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $normalizedMatrix[$i][$j] = $columnSums[$j] != 0 ? $this->round_custom($matrix[$i][$j] / $columnSums[$j]) : 0;
            }
        }

        return $normalizedMatrix;
    }

    private function calculateEigenvector($normalizedMatrix, $n)
    {
        $weights = [];
        for ($i = 0; $i < $n; $i++) {
            $sum = 0;
            for ($j = 0; $j < $n; $j++) {
                $sum = $this->round_custom($sum + $normalizedMatrix[$i][$j]);
            }
            $weights[$i] = $this->round_custom($sum / $n);
        }

        $sum = 0;
        foreach ($weights as $w) {
            $sum = $this->round_custom($sum + $w);
        }

        if ($sum > 0) {
            foreach ($weights as $i => $w) {
                $weights[$i] = $this->round_custom($w / $sum);
            }
        }

        return $weights;
    }

    private function calculateConsistencyRatio($matrix, $weights, $n)
    {
        if ($n <= 2) {
            return ['cr' => 0, 'ri' => 0, 'ci' => 0, 'lambdaMax' => 0, 'eigenValues' => array_fill(0, $n, 0)];
        }

        $columnSums = array_fill(0, $n, 0);
        for ($j = 0; $j < $n; $j++) {
            for ($i = 0; $i < $n; $i++) {
                $columnSums[$j] = $this->round_custom($columnSums[$j] + $matrix[$i][$j]);
            }
        }

        $eigenValues = [];
        $lambdaMax = 0;
        for ($i = 0; $i < $n; $i++) {
            $ev = $this->round_custom($columnSums[$i] * $weights[$i]);
            $eigenValues[$i] = $ev;
            $lambdaMax = $this->round_custom($lambdaMax + $ev);
        }

        $ci = $this->round_custom(($lambdaMax - $n) / ($n - 1));

        $ri = [
            1 => 0.00, 2 => 0.00, 3 => 0.58, 4 => 0.90, 5 => 1.12,
            6 => 1.24, 7 => 1.32, 8 => 1.41, 9 => 1.45, 10 => 1.49,
        ];

        $riValue = isset($ri[$n]) ? $ri[$n] : 1.49;

        $cr = ($riValue > 0) ? $this->round_custom($ci / $riValue) : 0;

        return [
            'cr' => $cr, 
            'ri' => $riValue, 
            'ci' => $ci, 
            'lambdaMax' => $lambdaMax,
            'eigenValues' => $eigenValues
        ];
    }
}
