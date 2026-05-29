<?php

namespace App\Http\Controllers;

use App\Models\KuesionerGroup;
use App\Models\Kuesioner;
use App\Services\AHPService;
use App\Services\COCOSOService;
use Illuminate\Http\Request;

class KuesionerGroupController extends Controller
{
    protected $ahpService;
    protected $cocosoService;

    public function __construct(AHPService $ahpService, COCOSOService $cocosoService)
    {
        $this->ahpService = $ahpService;
        $this->cocosoService = $cocosoService;
    }

    public function create()
    {
        return view('pages.kuisioner.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_projek' => 'required|string|max:255',
        ]);

        $data = [
            'nama_projek' => $request->nama_projek,
            'semua_jawaban' => [],
            'status' => 'pending',
        ];

        $kuesioner = KuesionerGroup::create($data);

        return redirect()->route('kuisioner.form', $kuesioner->id)
            ->with('success', 'Projek kuesioner dibuat! Silakan isi form di bawah.');
    }

    public function showForm($id)
    {
        $kuesioner = KuesionerGroup::findOrFail($id);
        $respondenIndex = count($kuesioner->semua_jawaban);
        
        return view('pages.kuisioner.form', compact('kuesioner', 'respondenIndex'));
    }

    public function submitJawaban(Request $request, $id)
    {
        $kuesioner = KuesionerGroup::findOrFail($id);
        
        $request->validate([
            'nama_responden' => 'required|string|max:255',
            'usia' => 'required|integer|min:1|max:120',
            'kriteria.*' => 'required|numeric|min:1|max:9',
            'varietas.*' => 'required|numeric|min:1|max:100',
        ]);

        $kriteriaValues = [];
        for ($i = 0; $i < 5; $i++) {
            for ($j = 0; $j < 5; $j++) {
                $val = $request->kriteria[$i][$j] ?? 1;
                $kriteriaValues[$i][$j] = (double)$val;
            }
        }

        $varietasValues = [];
        for ($i = 0; $i < 5; $i++) {
            for ($j = 0; $j < 5; $j++) {
                $val = $request->varietas[$i][$j] ?? 50;
                $varietasValues[$i][$j] = (double)$val;
            }
        }

        $kuesionerIndividual = Kuesioner::create([
            'nama_responden' => $request->nama_responden,
            'usia' => $request->usia,
            'status' => 'pending',
        ]);

        $kriteriaNames = ['Waktu Panen', 'Mobilitas Tanah', 'Ketersediaan Air', 'Kemudahan Perawatan', 'Hasil Pertanian'];
        for ($i = 0; $i < 5; $i++) {
            for ($j = 0; $j < 5; $j++) {
                KuesionerComparison::create([
                    'kuesioner_id' => $kuesionerIndividual->id,
                    'kriteria_from' => $kriteriaNames[$i],
                    'kriteria_to' => $kriteriaNames[$j],
                    'value' => $kriteriaValues[$i][$j],
                ]);
            }
        }

        $varietasNames = ['A1 Golden Aroma', 'A2 Red Velvet', 'A3 Long Staple', 'A4 Premium', 'A5 Classic'];
        $criteriaNames = ['Waktu Panen', 'Mobilitas Tanah', 'Ketersediaan Air', 'Kemudahan Perawatan', 'Hasil Pertanian'];
        for ($i = 0; $i < 5; $i++) {
            for ($j = 0; $j < 5; $j++) {
                KuesionerScore::create([
                    'kuesioner_id' => $kuesionerIndividual->id,
                    'varietas' => $varietasNames[$i],
                    'kriteria' => $criteriaNames[$j],
                    'score' => $varietasValues[$i][$j],
                ]);
            }
        }

        $jawaban = [
            'nama' => $request->nama_responden,
            'usia' => $request->usia,
            'kriteria' => $kriteriaValues,
            'varietas' => $varietasValues,
        ];

        $semuaJawaban = $kuesioner->semua_jawaban;
        $semuaJawaban[] = $jawaban;
        
        $kuesioner->update([
            'semua_jawaban' => $semuaJawaban,
        ]);

        return back()->with('success', 'Jawaban untuk ' . $request->nama_responden . ' berhasil disimpan!');
    }

    public function adminIndex()
    {
        $groups = KuesionerGroup::latest()->get();
        return view('pages.admin.kuesioner.index', compact('groups'));
    }

    public function adminShow($id)
    {
        $group = KuesionerGroup::findOrFail($id);
        return view('pages.admin.kuesioner.show', compact('group'));
    }

    public function eksekusi(Request $request, $id)
    {
        $group = KuesionerGroup::findOrFail($id);
        $semuaOrang = $group->semua_jawaban;
        
        $totalResponden = count($semuaOrang);
        if ($totalResponden === 0) {
            return back()->with('error', 'Belum ada responden!');
        }

        $rataRataKriteria = $this->hitungRataRataKriteria($semuaOrang, $totalResponden);
        $rataRataVarietas = $this->hitungRataRataVarietas($semuaOrang, $totalResponden);

        $hasilAHP = $this->hitungAHP($rataRataKriteria);
        $hasilCoCoSo = $this->hitungCoCoSo($rataRataVarietas, $hasilAHP);

        $group->update([
            'status' => 'processed',
            'hasil_akhir_json' => [
                'ranking' => $hasilCoCoSo,
                'weights' => $hasilAHP['weights'],
                'cr' => $hasilAHP['cr'],
                'calculated_at' => now()->toIso8601String(),
            ],
        ]);

        return back()->with('success', 'Eksekusi berhasil! Hasil perhitungan tersimpan.');
    }

    public function adminDashboard()
    {
        $kuesioners = Kuesioner::latest()->get();
        return view('pages.admin.kuesioner.dashboard', compact('kuesioners'));
    }

    public function eksekusiTerpilih(Request $request)
    {
        $ids = $request->input('kuesioner_ids');
        if (empty($ids)) {
            return redirect()->route('admin.kuesioner.dashboard')->with('error', 'Silakan pilih minimal satu data kuesioner terlebih dahulu!');
        }

        $kuesionerTerpilih = Kuesioner::with(['comparisons', 'scores'])->whereIn('id', $ids)->get();
        $totalResponden = $kuesionerTerpilih->count();

        $totalKriteria = [];
        $totalVarietas = [];

        foreach ($kuesionerTerpilih as $k) {
            foreach ($k->comparisons as $comp) {
                $key = $comp->kriteria_from . '_' . $comp->kriteria_to;
                $totalKriteria[$key] = ($totalKriteria[$key] ?? 0) + (double)$comp->value;
            }

            foreach ($k->scores as $score) {
                $key = $score->varietas . '_' . $score->kriteria;
                $totalVarietas[$key] = ($totalVarietas[$key] ?? 0) + (double)$score->score;
            }
        }

        $kriteriaKeys = ['Waktu Panen', 'Mobilitas Tanah', 'Ketersediaan Air', 'Kemudahan Perawatan', 'Hasil Pertanian'];
        $rataRataKriteria = [];
        foreach ($kriteriaKeys as $i => $kriteriaFrom) {
            foreach ($kriteriaKeys as $j => $kriteriaTo) {
                $key = $kriteriaFrom . '_' . $kriteriaTo;
                if (isset($totalKriteria[$key])) {
                    $rataRataKriteria[$i][$j] = $totalKriteria[$key] / $totalResponden;
                }
            }
        }

        $varietasKeys = ['A1 Golden Aroma', 'A2 Red Velvet', 'A3 Long Staple', 'A4 Premium', 'A5 Classic'];
        $rataRataVarietas = [];
        foreach ($varietasKeys as $varietas) {
            foreach ($kriteriaKeys as $kriteria) {
                $key = $varietas . '_' . $kriteria;
                if (isset($totalVarietas[$key])) {
                    $rataRataVarietas[$varietas][$kriteria] = $totalVarietas[$key] / $totalResponden;
                }
            }
        }

        $hasilAHP = $this->hitungAHPDariArray($rataRataKriteria);
        $hasilCoCoSo = $this->hitungCoCoSoDariArray($rataRataVarietas, $hasilAHP);

        Kuesioner::whereIn('id', $ids)->update([
            'status' => 'processed',
            'hasil_json' => [
                'ranking' => $hasilCoCoSo,
                'weights' => $hasilAHP['weights'],
                'cr' => $hasilAHP['cr'],
                'calculated_at' => now()->toIso8601String(),
            ],
        ]);

        return redirect()->route('admin.kuesioner.dashboard')->with('success', 'Eksekusi berhasil! ' . $totalResponden . ' data telah diproses.');
    }

    private function hitungRataRataKriteria($semuaOrang, $totalResponden)
    {
        $rataRataKriteria = [];

        foreach ($semuaOrang as $orang) {
            foreach ($orang['kriteria'] as $idx => $baris) {
                foreach ($baris as $j => $nilai) {
                    $key = "q" . ($idx + 1) . "_" . ($j + 1);
                    if (!isset($rataRataKriteria[$key])) {
                        $rataRataKriteria[$key] = 0;
                    }
                    $rataRataKriteria[$key] += (double)$nilai;
                }
            }
        }

        foreach ($rataRataKriteria as $key => $totalNilai) {
            $rataRataKriteria[$key] = $totalNilai / $totalResponden;
        }

        return $rataRataKriteria;
    }

    private function hitungRataRataVarietas($semuaOrang, $totalResponden)
    {
        $rataRataVarietas = [];

        foreach ($semuaOrang as $orang) {
            foreach ($orang['varietas'] as $idx => $skorList) {
                foreach ($skorList as $critIdx => $skor) {
                    $key = "v" . ($idx + 1) . "_c" . ($critIdx + 1);
                    if (!isset($rataRataVarietas[$key])) {
                        $rataRataVarietas[$key] = 0;
                    }
                    $rataRataVarietas[$key] += (double)$skor;
                }
            }
        }

        foreach ($rataRataVarietas as $key => $totalSkor) {
            $rataRataVarietas[$key] = $totalSkor / $totalResponden;
        }

        return $rataRataVarietas;
    }

    private function hitungAHP($rataRataKriteria)
    {
        $n = 5;
        $kriteriaKeys = ['q1', 'q2', 'q3', 'q4', 'q5'];
        
        $matrix = [];
        foreach ($kriteriaKeys as $i) {
            foreach ($kriteriaKeys as $j) {
                $matrix[$i][$j] = ($i === $j) ? 1.0 : 0.0;
            }
        }

        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                if ($i !== $j && $matrix[$kriteriaKeys[$i]][$kriteriaKeys[$j]] === 0.0) {
                    $val1 = $rataRataKriteria[$kriteriaKeys[$i]] ?? 0;
                    $val2 = $rataRataKriteria[$kriteriaKeys[$j]] ?? 0;
                    $val = ($val2 != 0) ? $val1 / $val2 : 1;
                    $matrix[$kriteriaKeys[$i]][$kriteriaKeys[$j]] = $val;
                    $matrix[$kriteriaKeys[$j]][$kriteriaKeys[$i]] = ($val != 0) ? 1 / $val : 1;
                }
            }
        }

        $columnSums = [];
        foreach ($kriteriaKeys as $j) {
            $sum = 0;
            foreach ($kriteriaKeys as $i) {
                $sum += $matrix[$kriteriaKeys[$i]][$j];
            }
            $columnSums[$j] = $sum;
        }

        $weights = [];
        foreach ($kriteriaKeys as $i) {
            $rowSum = 0;
            foreach ($kriteriaKeys as $j) {
                $rowSum += $columnSums[$j] != 0 ? $matrix[$kriteriaKeys[$i]][$j] / $columnSums[$j] : 0;
            }
            $weights[$i] = $rowSum / $n;
        }

        $lambdaMax = 0;
        foreach ($kriteriaKeys as $i) {
            $rowSumProduct = 0;
            foreach ($kriteriaKeys as $j) {
                $rowSumProduct += $matrix[$kriteriaKeys[$i]][$j] * $weights[$j];
            }
            $lambdaMax += $weights[$kriteriaKeys[$i]] != 0 ? $rowSumProduct / $weights[$kriteriaKeys[$i]] : 0;
        }
        $lambdaMax = $lambdaMax / $n;

        $ci = ($n > 1) ? ($lambdaMax - $n) / ($n - 1) : 0;
        $ri = [1 => 0, 2 => 0, 3 => 0.58, 4 => 0.90, 5 => 1.12][$n] ?? 1.12;
        $cr = ($ri > 0) ? $ci / $ri : 0;

        $weightsList = [];
        $kriteriaNames = ['Waktu Panen', 'Mobilitas Tanah', 'Ketersediaan Air', 'Kemudahan Perawatan', 'Hasil Pertanian'];
        foreach ($kriteriaKeys as $idx => $key) {
            $weightsList[] = [
                'name' => $kriteriaNames[$idx],
                'weight' => round($weights[$key], 4),
            ];
        }

        return [
            'weights' => $weightsList,
            'cr' => round($cr, 4),
            'ci' => round($ci, 4),
            'ri' => $ri,
            'n_criteria' => $n,
        ];
    }

    private function hitungAHPDariArray($rataRataKriteria)
    {
        $n = 5;
        $kriteriaKeys = ['c1', 'c2', 'c3', 'c4', 'c5'];
        
        $matrix = [];
        foreach ($kriteriaKeys as $i) {
            foreach ($kriteriaKeys as $j) {
                $matrix[$i][$j] = ($i === $j) ? 1.0 : 0.0;
            }
        }

        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                if ($i !== $j && $matrix[$kriteriaKeys[$i]][$kriteriaKeys[$j]] === 0.0) {
                    $val = isset($rataRataKriteria[$i][$j]) ? $rataRataKriteria[$i][$j] : 1;
                    $matrix[$kriteriaKeys[$i]][$kriteriaKeys[$j]] = $val;
                    $matrix[$kriteriaKeys[$j]][$kriteriaKeys[$i]] = ($val != 0) ? 1 / $val : 1;
                }
            }
        }

        $columnSums = [];
        foreach ($kriteriaKeys as $j) {
            $sum = 0;
            foreach ($kriteriaKeys as $i) {
                $sum += $matrix[$kriteriaKeys[$i]][$j];
            }
            $columnSums[$j] = $sum;
        }

        $weights = [];
        foreach ($kriteriaKeys as $i) {
            $rowSum = 0;
            foreach ($kriteriaKeys as $j) {
                $rowSum += $columnSums[$j] != 0 ? $matrix[$kriteriaKeys[$i]][$j] / $columnSums[$j] : 0;
            }
            $weights[$i] = $rowSum / $n;
        }

        $lambdaMax = 0;
        foreach ($kriteriaKeys as $i) {
            $rowSumProduct = 0;
            foreach ($kriteriaKeys as $j) {
                $rowSumProduct += $matrix[$kriteriaKeys[$i]][$j] * $weights[$j];
            }
            $lambdaMax += $weights[$kriteriaKeys[$i]] != 0 ? $rowSumProduct / $weights[$kriteriaKeys[$i]] : 0;
        }
        $lambdaMax = $lambdaMax / $n;

        $ci = ($n > 1) ? ($lambdaMax - $n) / ($n - 1) : 0;
        $ri = [1 => 0, 2 => 0, 3 => 0.58, 4 => 0.90, 5 => 1.12][$n] ?? 1.12;
        $cr = ($ri > 0) ? $ci / $ri : 0;

        $weightsList = [];
        $kriteriaNames = ['Waktu Panen', 'Mobilitas Tanah', 'Ketersediaan Air', 'Kemudahan Perawatan', 'Hasil Pertanian'];
        foreach ($kriteriaKeys as $idx => $key) {
            $weightsList[] = [
                'name' => $kriteriaNames[$idx],
                'weight' => round($weights[$key], 4),
            ];
        }

        return [
            'weights' => $weightsList,
            'cr' => round($cr, 4),
            'ci' => round($ci, 4),
            'ri' => $ri,
            'n_criteria' => $n,
        ];
    }

    private function hitungCoCoSo($rataRataVarietas, $ahpWeights)
    {
        $varietasKeys = ['v1', 'v2', 'v3', 'v4', 'v5'];
        $kriteriaKeys = ['c1', 'c2', 'c3', 'c4', 'c5'];
        
        $weightsIndexed = [];
        foreach ($ahpWeights as $idx => $w) {
            $weightsIndexed[$kriteriaKeys[$idx]] = $w['weight'];
        }

        $scores = [];
        foreach ($varietasKeys as $vk) {
            foreach ($kriteriaKeys as $ck) {
                $key = $vk . "_c" . (strpos($ck, 'c') !== false ? substr($ck, 1) : $ck);
                $scores[$vk][$ck] = $rataRataVarietas[$key] ?? 0;
            }
        }

        $minMax = [];
        foreach ($kriteriaKeys as $ck) {
            $colScores = [];
            foreach ($varietasKeys as $vk) {
                $colScores[] = $scores[$vk][$ck];
            }
            $minMax[$ck]['max'] = max($colScores);
            $minMax[$ck]['min'] = min($colScores);
        }

        $siValues = [];
        $piValues = [];

        foreach ($varietasKeys as $vk) {
            $si = 0;
            $pi = 1;
            foreach ($kriteriaKeys as $ck) {
                $actual = $scores[$vk][$ck];
                $max = $minMax[$ck]['max'];
                $min = $minMax[$ck]['min'];
                $denominator = ($max - $min) == 0 ? 1 : ($max - $min);
                $r_ij = ($actual - $min) / $denominator;
                $w_j = $weightsIndexed[$ck];
                $si += $w_j * $r_ij;
                $pi *= pow($r_ij > 0 ? $r_ij : 0.0001, $w_j);
            }
            $siValues[$vk] = $si;
            $piValues[$vk] = $pi;
        }

        $minS = min($siValues);
        $maxS = max($siValues);
        $minP = min($piValues);
        $maxP = max($piValues);

        $rankingResult = [];
        $varietasNames = ['A1 Golden Aroma', 'A2 Red Velvet', 'A3 Long Staple', 'A4 Premium', 'A5 Classic'];
        
        foreach ($varietasKeys as $idx => $vk) {
            $si = $siValues[$vk];
            $pi = $piValues[$vk];

            $denomS = ($maxS - $minS) == 0 ? 1 : ($maxS - $minS);
            $denomP = ($maxP - $minP) == 0 ? 1 : ($maxP - $minP);

            $ka = (($si - $minS) / $denomS) + (($pi - $minP) / $denomP);
            $kb = ($minS != 0 && $minP != 0) ? ($si / $minS) + ($pi / $minP) : 0;
            $kc = (($si + $pi) / 2) / (($maxS + $maxP) / 2);
            $qi = pow($ka * $kb * $kc, 1/3) + (($ka + $kb + $kc) / 3);

            $rankingResult[] = [
                'name' => $varietasNames[$idx],
                'si' => round($si, 4),
                'pi' => round($pi, 4),
                'ka' => round($ka, 2),
                'kb' => round($kb, 2),
                'kc' => round($kc, 2),
                'qi' => round($qi, 2),
            ];
        }

        usort($rankingResult, fn($a, $b) => $b['qi'] <=> $a['qi']);
        foreach ($rankingResult as $idx => $data) {
            $rankingResult[$idx]['rank'] = $idx + 1;
        }

        return $rankingResult;
    }

    private function hitungCoCoSoDariArray($rataRataVarietas, $ahpWeights)
    {
        $varietasKeys = ['A1 Golden Aroma', 'A2 Red Velvet', 'A3 Long Staple', 'A4 Premium', 'A5 Classic'];
        $kriteriaKeys = ['Waktu Panen', 'Mobilitas Tanah', 'Ketersediaan Air', 'Kemudahan Perawatan', 'Hasil Pertanian'];
        
        $weightsIndexed = [];
        foreach ($ahpWeights as $idx => $w) {
            $weightsIndexed[$kriteriaKeys[$idx]] = $w['weight'];
        }

        $scores = [];
        foreach ($varietasKeys as $varietas) {
            foreach ($kriteriaKeys as $kriteria) {
                $scores[$varietas][$kriteria] = $rataRataVarietas[$varietas][$kriteria] ?? 0;
            }
        }

        $minMax = [];
        foreach ($kriteriaKeys as $ck) {
            $colScores = [];
            foreach ($varietasKeys as $vk) {
                $colScores[] = $scores[$vk][$ck];
            }
            $minMax[$ck]['max'] = max($colScores);
            $minMax[$ck]['min'] = min($colScores);
        }

        $siValues = [];
        $piValues = [];

        foreach ($varietasKeys as $varietas) {
            $si = 0;
            $pi = 1;
            foreach ($kriteriaKeys as $kriteria) {
                $actual = $scores[$varietas][$kriteria];
                $max = $minMax[$kriteria]['max'];
                $min = $minMax[$kriteria]['min'];
                $denominator = ($max - $min) == 0 ? 1 : ($max - $min);
                $r_ij = ($actual - $min) / $denominator;
                $w_j = $weightsIndexed[$kriteria];
                $si += $w_j * $r_ij;
                $pi *= pow($r_ij > 0 ? $r_ij : 0.0001, $w_j);
            }
            $siValues[$varietas] = $si;
            $piValues[$varietas] = $pi;
        }

        $minS = min($siValues);
        $maxS = max($siValues);
        $minP = min($piValues);
        $maxP = max($piValues);

        $rankingResult = [];
        
        foreach ($varietasKeys as $idx => $varietas) {
            $si = $siValues[$varietas];
            $pi = $piValues[$varietas];

            $denomS = ($maxS - $minS) == 0 ? 1 : ($maxS - $minS);
            $denomP = ($maxP - $minP) == 0 ? 1 : ($maxP - $minP);

            $ka = (($si - $minS) / $denomS) + (($pi - $minP) / $denomP);
            $kb = ($minS != 0 && $minP != 0) ? ($si / $minS) + ($pi / $minP) : 0;
            $kc = (($si + $pi) / 2) / (($maxS + $maxP) / 2);
            $qi = pow($ka * $kb * $kc, 1/3) + (($ka + $kb + $kc) / 3);

            $rankingResult[] = [
                'name' => $varietas,
                'si' => round($si, 4),
                'pi' => round($pi, 4),
                'ka' => round($ka, 2),
                'kb' => round($kb, 2),
                'kc' => round($kc, 2),
                'qi' => round($qi, 2),
            ];
        }

        usort($rankingResult, fn($a, $b) => $b['qi'] <=> $a['qi']);
        foreach ($rankingResult as $idx => $data) {
            $rankingResult[$idx]['rank'] = $idx + 1;
        }

        return $rankingResult;
    }
}