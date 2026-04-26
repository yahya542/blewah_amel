<?php

namespace App\Services;

use App\Models\Comparison;
use App\Models\Criteria;
use App\Models\SubmissionComparison;

class AHPService
{
    private function round_custom($num, $digits = 10)
    {
        return round($num, $digits);
    }

    public function calculateWeights($submissionId = null)
    {
        $criteria = Criteria::where('submission_id', $submissionId)->orderBy('id')->get();
        $n = $criteria->count();
        if ($n === 0) {
            return [];
        }

        if ($submissionId) {
            $matrix = $this->getPairwiseMatrixFromSubmission($submissionId, $criteria);
        } else {
            $matrix = $this->getPairwiseMatrix($criteria);
        }

        $normalizedMatrix = $this->normalizeMatrix($matrix, $n);
        $weights = $this->calculateEigenvector($matrix, $n);

        $consistencyResult = $this->calculateConsistencyRatio($matrix, $weights, $n);
        $cr = $consistencyResult['cr'];
        $riValue = $consistencyResult['ri'];
        $ciValue = $consistencyResult['ci'];
        $lambdaMaxValue = $consistencyResult['lambdaMax'];

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
                'weight' => $weightRounded2,
            ];
        }

        $criteriaArray = [];
        foreach ($criteria as $index => $crit) {
            $criteriaArray[$index] = $crit;
        }

        return [
            'weightsIndexed' => array_map(fn($w) => round($w, 2), $weights),
            'weights' => $weightedCriteria,
            'cr' => $cr,
            'ri' => $riValue,
            'ci' => $ciValue,
            'lambdaMax' => $lambdaMaxValue,
            'matrix' => $matrix,
            'normalizedMatrix' => $normalizedMatrix,
            'criteria' => $criteriaArray,
        ];
    }

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
                        $matrix[$j][$i] = (float) $comparison->value;
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
                'weight' => (float) $crit->weight,
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

                    $matrix[$j][$i] = $comparison ? (float) $comparison->value : 1.0;
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

    private function calculateEigenvector($matrix, $n)
    {
        $weights = [];
        for ($i = 0; $i < $n; $i++) {
            $product = 1.0;
            for ($j = 0; $j < $n; $j++) {
                $product = $this->round_custom($product * $matrix[$i][$j]);
            }
            $weights[$i] = $this->round_custom(pow($product, 1 / $n));
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
            return ['cr' => 0, 'ri' => 0];
        }

        $ax = [];
        for ($i = 0; $i < $n; $i++) {
            $ax[$i] = 0;
            for ($j = 0; $j < $n; $j++) {
                $ax[$i] = $this->round_custom($ax[$i] + $this->round_custom($matrix[$i][$j] * $weights[$j]));
            }
        }

        $lambdaMax = 0;
        for ($i = 0; $i < $n; $i++) {
            if ($weights[$i] != 0) {
                $lambdaMax = $this->round_custom($lambdaMax + $this->round_custom($ax[$i] / $weights[$i]));
            }
        }
        $lambdaMax = $this->round_custom($lambdaMax / $n);

        $ci = $this->round_custom(($lambdaMax - $n) / ($n - 1));

        $ri = [
            1 => 0.00, 2 => 0.00, 3 => 0.58, 4 => 0.90, 5 => 1.12,
            6 => 1.24, 7 => 1.32, 8 => 1.41, 9 => 1.45, 10 => 1.49,
        ];

        $riValue = isset($ri[$n]) ? $ri[$n] : 1.49;

        $cr = ($riValue > 0) ? round($this->round_custom($ci / $riValue), 4) : 0;

        return ['cr' => $cr, 'ri' => $riValue, 'ci' => round($ci, 4), 'lambdaMax' => round($lambdaMax, 4)];
    }
}
