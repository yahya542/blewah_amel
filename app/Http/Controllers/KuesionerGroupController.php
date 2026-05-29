<?php

namespace App\Http\Controllers;

use App\Models\KuesionerGroup;
use App\Models\Kuesioner;
use App\Models\KuesionerComparison;
use App\Models\KuesionerScore;
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
            'usia'           => 'required|integer|min:1|max:120',
            'kriteria'       => 'required|array',
            'varietas'       => 'required|array',
        ]);

        $n = 6; // 6 kriteria
        $m = 5; // 5 varietas blewah

        // Build full n×n matrix from upper-triangle radio answers
        $kriteriaValues = [];
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                if ($i === $j) {
                    $kriteriaValues[$i][$j] = 1.0;
                } elseif ($i < $j) {
                    $val = (double)($request->kriteria[$i][$j] ?? 1);
                    $kriteriaValues[$i][$j] = $val;
                    $kriteriaValues[$j][$i] = $val != 0 ? 1.0 / $val : 1.0;
                }
            }
        }

        // Likert values: varietas[vIdx][cIdx] => score 1-5
        $varietasValues = [];
        for ($i = 0; $i < $m; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $varietasValues[$i][$j] = (double)($request->varietas[$i][$j] ?? 3);
            }
        }

        $kuesionerIndividual = Kuesioner::create([
            'nama_responden' => $request->nama_responden,
            'usia' => $request->usia,
            'status' => 'pending',
        ]);

        $kriteriaNames = ['Waktu Panen', 'Jumlah Buah', 'Ketahanan Penyakit', 'Kualitas Buah', 'Berat Buah', 'Harga Bibit'];
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                KuesionerComparison::create([
                    'kuesioner_id'  => $kuesionerIndividual->id,
                    'kriteria_from' => $kriteriaNames[$i],
                    'kriteria_to'   => $kriteriaNames[$j],
                    'value'         => $kriteriaValues[$i][$j],
                ]);
            }
        }

        $varietasNames = ['A1 Golden Aroma', 'A2 Varietas Aruna', 'A3 Sweet Net', 'A4 King Blewah', 'A5 Rangipo'];
        for ($i = 0; $i < $m; $i++) {
            for ($j = 0; $j < $n; $j++) {
                KuesionerScore::create([
                    'kuesioner_id' => $kuesionerIndividual->id,
                    'varietas'     => $varietasNames[$i],
                    'kriteria'     => $kriteriaNames[$j],
                    'score'        => $varietasValues[$i][$j],
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

        $hasilAkhirData = [
            'ranking' => $hasilCoCoSo,
            'weights' => $hasilAHP['weights'] ?? [],
            'cr' => $hasilAHP['cr'] ?? 0,
            'total_responden' => $totalResponden,
            'calculated_at' => now()->toIso8601String(),
        ];

        $group->update([
            'status' => 'processed',
            'hasil_akhir_json' => $hasilAkhirData,
        ]);

        return redirect()->route('admin.kuesioner.hasil')->with([
            'success' => 'Kalkulasi grup berhasil dieksekusi!',
            'data_hasil' => $hasilAkhirData
        ]);
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

        $kriteriaRawValues = [];
        $totalVarietas = [];

        foreach ($kuesionerTerpilih as $k) {
            foreach ($k->comparisons as $comp) {
                // Hanya ambil yang kriteria_from != kriteria_to untuk agregasi
                if ($comp->kriteria_from !== $comp->kriteria_to) {
                    $key = $comp->kriteria_from . '_' . $comp->kriteria_to;
                    $kriteriaRawValues[$key][] = (double)$comp->value;
                }
            }

            foreach ($k->scores as $score) {
                $key = $score->varietas . '_' . $score->kriteria;
                $totalVarietas[$key] = ($totalVarietas[$key] ?? 0) + (double)$score->score;
            }
        }

        $kriteriaKeys = ['Waktu Panen', 'Jumlah Buah', 'Ketahanan Penyakit', 'Kualitas Buah', 'Berat Buah', 'Harga Bibit'];
        $rataRataKriteria = []; 
        
        // Agregasi AHP menggunakan Geometric Mean
        foreach ($kriteriaKeys as $i => $kriteriaFrom) {
            foreach ($kriteriaKeys as $j => $kriteriaTo) {
                $key = $kriteriaFrom . '_' . $kriteriaTo;
                if (isset($kriteriaRawValues[$key])) {
                    $vals = $kriteriaRawValues[$key];
                    $product = 1.0;
                    foreach ($vals as $v) { $product *= ($v > 0 ? $v : 1); }
                    $rataRataKriteria[$i][$j] = pow($product, 1 / count($vals));
                }
            }
        }

        // Agregasi CoCoSo menggunakan Arithmetic Mean (Rata-rata biasa)
        $varietasKeys = ['A1 Golden Aroma', 'A2 Varietas Aruna', 'A3 Sweet Net', 'A4 King Blewah', 'A5 Rangipo'];
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

        $hasilAkhirData = [
            'ranking' => $hasilCoCoSo,
            'weights' => $hasilAHP['weights'],
            'cr' => $hasilAHP['cr'],
            'total_responden' => $totalResponden,
            'calculated_at' => now()->toIso8601String(),
        ];

        Kuesioner::whereIn('id', $ids)->update([
            'status' => 'processed',
            'hasil_json' => $hasilAkhirData,
        ]);

        return redirect()->route('admin.kuesioner.hasil')->with([
            'success' => 'Eksekusi berhasil! ' . $totalResponden . ' data telah diproses.',
            'data_hasil' => $hasilAkhirData
        ]);
    }

    public function tampilkanHasil()
    {
        $data = session('data_hasil');
        
        // Jika tidak ada data di session (misal akses langsung), ambil data terakhir dari DB
        if (!$data) {
            $latest = Kuesioner::where('status', 'processed')->whereNotNull('hasil_json')->latest()->first();
            if ($latest) {
                $data = $latest->hasil_json;
            }
        }

        return view('pages.admin.kuesioner.hasil', compact('data'));
    }

    // fitur delete 
    public function destroyTerpilih(Request $request)
{
    $ids = $request->input('kuesioner_ids');
    
    if (empty($ids)) {
        return back()->with('error', 'Silakan pilih minimal satu data kuesioner yang ingin dihapus!');
    }

    // Hapus data relasi terlebih dahulu (jika tidak pakai cascade delete di database)
    KuesionerComparison::whereIn('kuesioner_id', $ids)->delete();
    KuesionerScore::whereIn('kuesioner_id', $ids)->delete();

    // Hapus data utama kuesioner
    Kuesioner::whereIn('id', $ids)->delete();

    return back()->with('success', count($ids) . ' data kuesioner berhasil dihapus.');
}


    private function hitungRataRataKriteria($semuaOrang, $totalResponden)
    {
        $rawValues = [];
        foreach ($semuaOrang as $orang) {
            foreach ($orang['kriteria'] as $i => $row) {
                foreach ($row as $j => $val) {
                    if ($i < $j) {
                        $key = "q" . ($i + 1) . "_" . ($j + 1);
                        $rawValues[$key][] = (double)$val;
                    }
                }
            }
        }

        $rataRataKriteria = [];
        foreach ($rawValues as $key => $vals) {
            $product = 1.0;
            foreach ($vals as $v) { $product *= ($v > 0 ? $v : 1); }
            $rataRataKriteria[$key] = pow($product, 1 / count($vals));
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
        $n = 6; // 6 kriteria blewah
        $kriteriaNames = ['Waktu Panen', 'Jumlah Buah', 'Ketahanan Penyakit', 'Kualitas Buah', 'Berat Buah', 'Harga Bibit'];

        // Build full matrix with Inverted Scale Logic
        $matrix = array_fill(0, $n, array_fill(0, $n, 1.0));
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $key = "q" . ($i + 1) . "_" . ($j + 1);
                $meanValue = $rataRataKriteria[$key] ?? 1.0;
                // Skala: Angka tinggi berarti Kriteria KEDUA (j) lebih penting
                $matrix[$j][$i] = $meanValue;
                $matrix[$i][$j] = $meanValue != 0 ? 1.0 / $meanValue : 1.0;
            }
        }

        // --- METHOD 3: GEOMETRIC MEAN OF ROWS (Most Precise Method) ---
        $rowGeoMeans = [];
        for ($i = 0; $i < $n; $i++) {
            $product = 1.0;
            for ($j = 0; $j < $n; $j++) {
                $product *= $matrix[$i][$j];
            }
            $rowGeoMeans[$i] = pow($product, 1 / $n);
        }

        $sumGeoMeans = array_sum($rowGeoMeans);
        $weights = [];
        for ($i = 0; $i < $n; $i++) {
            $weights[$i] = ($sumGeoMeans != 0) ? $rowGeoMeans[$i] / $sumGeoMeans : 1 / $n;
        }

        // Lambda max
        $lambdaMax = 0;
        for ($i = 0; $i < $n; $i++) {
            $rowSumProduct = 0;
            for ($j = 0; $j < $n; $j++) {
                $rowSumProduct += $matrix[$i][$j] * $weights[$j];
            }
            $lambdaMax += $weights[$i] != 0 ? $rowSumProduct / $weights[$i] : 0;
        }
        $lambdaMax /= $n;

        $ci = ($n > 1) ? ($lambdaMax - $n) / ($n - 1) : 0;
        $ri = [1 => 0, 2 => 0, 3 => 0.58, 4 => 0.90, 5 => 1.12, 6 => 1.00, 7 => 1.32][$n] ?? 1.00;
        $cr = ($ri > 0) ? $ci / $ri : 0;

        $weightsList = [];
        for ($idx = 0; $idx < $n; $idx++) {
            $weightsList[] = [
                'name'   => $kriteriaNames[$idx],
                'weight' => round($weights[$idx], 6),
            ];
        }

        return [
            'weights'    => $weightsList,
            'cr'         => round($cr, 4),
            'ci'         => round($ci, 4),
            'ri'         => $ri,
            'n_criteria' => $n,
        ];
    }

    private function hitungAHPDariArray($rataRataKriteria)
    {
        $n = 6;
        $kriteriaNames = ['Waktu Panen', 'Jumlah Buah', 'Ketahanan Penyakit', 'Kualitas Buah', 'Berat Buah', 'Harga Bibit'];

        // Build Full Matrix with Inverted Scale Logic
        // IF i < j AND mean value V is given, then j (second) is V times as important as i (first).
        $matrix = array_fill(0, $n, array_fill(0, $n, 1.0));
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $meanValue = $rataRataKriteria[$i][$j] ?? 1.0;
                // Skala: Angka tinggi berarti Kriteria KEDUA (j) lebih penting
                $matrix[$j][$i] = $meanValue;
                $matrix[$i][$j] = $meanValue != 0 ? 1.0 / $meanValue : 1.0;
            }
        }

        // --- METHOD 3: GEOMETRIC MEAN OF ROWS (Most Precise Method) ---
        $rowGeoMeans = [];
        for ($i = 0; $i < $n; $i++) {
            $product = 1.0;
            for ($j = 0; $j < $n; $j++) {
                $product *= $matrix[$i][$j];
            }
            $rowGeoMeans[$i] = pow($product, 1 / $n);
        }

        $sumGeoMeans = array_sum($rowGeoMeans);
        $weights = [];
        for ($i = 0; $i < $n; $i++) {
            $weights[$i] = ($sumGeoMeans != 0) ? $rowGeoMeans[$i] / $sumGeoMeans : 1 / $n;
        }

        $lambdaMax = 0;
        for ($i = 0; $i < $n; $i++) {
            $rowSumProduct = 0;
            for ($j = 0; $j < $n; $j++) {
                $rowSumProduct += $matrix[$i][$j] * $weights[$j];
            }
            $lambdaMax += $weights[$i] != 0 ? $rowSumProduct / $weights[$i] : 0;
        }
        $lambdaMax /= $n;

        $ci = ($n > 1) ? ($lambdaMax - $n) / ($n - 1) : 0;
        $ri = [1 => 0, 2 => 0, 3 => 0.58, 4 => 0.90, 5 => 1.12, 6 => 1.00, 7 => 1.32][$n] ?? 1.00;
        $cr = ($ri > 0) ? $ci / $ri : 0;

        $weightsList = [];
        for ($idx = 0; $idx < $n; $idx++) {
            $weightsList[] = [
                'name'   => $kriteriaNames[$idx],
                'weight' => round($weights[$idx], 6),
            ];
        }

        return [
            'weights'    => $weightsList,
            'cr'         => round($cr, 4),
            'ci'         => round($ci, 4),
            'ri'         => $ri,
            'n_criteria' => $n,
        ];
    }

    private function hitungCoCoSo($rataRataVarietas, $ahpWeights)
    {
        $n = 6; $m = 5;
        $varietasNames = ['A1 Golden Aroma', 'A2 Varietas Aruna', 'A3 Sweet Net', 'A4 King Blewah', 'A5 Rangipo'];
        
        // Weights indexed 0..n-1
        $weights = [];
        foreach ($ahpWeights['weights'] as $idx => $w) {
            $weights[$idx] = $w['weight'];
        }

        // Build scores[v][c] 
        $scores = [];
        for ($v = 0; $v < $m; $v++) {
            for ($c = 0; $c < $n; $c++) {
                $key = 'v' . ($v + 1) . '_c' . ($c + 1);
                $scores[$v][$c] = (double)($rataRataVarietas[$key] ?? 1.0); // Default to 1.0 to avoid zero
            }
        }

        // Min/Max per column
        $minMax = [];
        for ($c = 0; $c < $n; $c++) {
            $col = array_column($scores, $c);
            $minMax[$c] = ['min' => min($col), 'max' => max($col)];
        }

        $siValues = []; $piValues = [];
        for ($v = 0; $v < $m; $v++) {
            $si = 0; $pi = 1;
            for ($c = 0; $c < $n; $c++) {
                $val = $scores[$v][$c];
                $min = $minMax[$c]['min'];
                $max = $minMax[$c]['max'];
                
                // Waktu Panen (index 0) dan Harga Bibit (index 5) adalah COST
                if ($c === 0 || $c === 5) {
                    $r = ($val != 0) ? ($min / $val) : 0;
                } else {
                    $r = ($max != 0) ? ($val / $max) : 0;
                }
                
                $w = $weights[$c];
                $si += $w * $r;
                $pi *= pow($r > 0 ? $r : 0.0001, $w);
            }
            $siValues[$v] = $si;
            $piValues[$v] = $pi;
        }

        $minS = min($siValues); $maxS = max($siValues);
        $minP = min($piValues); $maxP = max($piValues);
        
        $rankingResult = [];
        for ($v = 0; $v < $m; $v++) {
            $si = $siValues[$v]; $pi = $piValues[$v];
            
            $ka = ($maxS + $maxP != 0) ? (($si + $pi) / ($maxS + $maxP)) + 0.5 : 0.5;
            $kb = ($minS != 0 && $minP != 0) ? ($si / $minS) + ($pi / $minP) : 0;
            $kc = ($maxS + $maxP != 0) ? (0.5 * $si + 0.5 * $pi) / (0.5 * $maxS + 0.5 * $maxP) : 0;
            
            $qi = pow($ka * $kb * $kc, 1/3) + ((1/3) * ($ka + $kb + $kc));
            
            $rankingResult[] = [
                'name' => $varietasNames[$v],
                'si'   => round($si, 4),
                'pi'   => round($pi, 4),
                'ka'   => round($ka, 2),
                'kb'   => round($kb, 2),
                'kc'   => round($kc, 2),
                'qi'   => round($qi, 3),
            ];
        }
        usort($rankingResult, fn($a, $b) => $b['qi'] <=> $a['qi']);
        return $rankingResult;
    }

    private function hitungCoCoSoDariArray($rataRataVarietas, $ahpWeights)
    {
        $varietasKeys = ['A1 Golden Aroma', 'A2 Varietas Aruna', 'A3 Sweet Net', 'A4 King Blewah', 'A5 Rangipo'];
        $kriteriaKeys = ['Waktu Panen', 'Jumlah Buah', 'Ketahanan Penyakit', 'Kualitas Buah', 'Berat Buah', 'Harga Bibit'];

        $weightsIndexed = [];
        foreach ($ahpWeights['weights'] as $idx => $w) {
            $weightsIndexed[$kriteriaKeys[$idx]] = $w['weight'];
        }

        $scores = [];
        foreach ($varietasKeys as $varietas) {
            foreach ($kriteriaKeys as $kriteria) {
                // Likert 1-5, set default 1.0 to avoid zero
                $scores[$varietas][$kriteria] = (double)($rataRataVarietas[$varietas][$kriteria] ?? 1.0);
            }
        }

        $minMax = [];
        foreach ($kriteriaKeys as $ck) {
            $colScores = array_column(array_map(fn($v) => [$scores[$v][$ck]], $varietasKeys), 0);
            $minMax[$ck] = ['min' => min($colScores), 'max' => max($colScores)];
        }

        $siValues = []; $piValues = [];
        foreach ($varietasKeys as $varietas) {
            $si = 0; $pi = 1;
            foreach ($kriteriaKeys as $idx => $kriteria) {
                $val = $scores[$varietas][$kriteria];
                $min = $minMax[$kriteria]['min'];
                $max = $minMax[$kriteria]['max'];

                // Waktu Panen (index 0) dan Harga Bibit (index 5) adalah COST
                if ($idx === 0 || $idx === 5) {
                    $r = ($val != 0) ? ($min / $val) : 0;
                } else {
                    $r = ($max != 0) ? ($val / $max) : 0;
                }

                $w = $weightsIndexed[$kriteria];
                $si += $w * $r;
                $pi *= pow($r > 0 ? $r : 0.0001, $w);
            }
            $siValues[$varietas] = $si;
            $piValues[$varietas] = $pi;
        }

        $minS = min($siValues); $maxS = max($siValues);
        $minP = min($piValues); $maxP = max($piValues);

        $rankingResult = [];
        foreach ($varietasKeys as $varietas) {
            $si = $siValues[$varietas]; $pi = $piValues[$varietas];
            
            $ka = ($maxS + $maxP != 0) ? (($si + $pi) / ($maxS + $maxP)) + 0.5 : 0.5;
            $kb = ($minS != 0 && $minP != 0) ? ($si / $minS) + ($pi / $minP) : 0;
            $kc = ($maxS + $maxP != 0) ? (0.5 * $si + 0.5 * $pi) / (0.5 * $maxS + 0.5 * $maxP) : 0;
            
            $qi = pow($ka * $kb * $kc, 1/3) + ((1/3) * ($ka + $kb + $kc));
            
            $rankingResult[] = [
                'name' => $varietas,
                'si'   => round($si, 4),
                'pi'   => round($pi, 4),
                'ka'   => round($ka, 2),
                'kb'   => round($kb, 2),
                'kc'   => round($kc, 2),
                'qi'   => round($qi, 3),
            ];
        }
        usort($rankingResult, fn($a, $b) => $b['qi'] <=> $a['qi']);
        return $rankingResult;
    }
}