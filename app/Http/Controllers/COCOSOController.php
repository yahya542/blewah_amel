<?php

namespace App\Http\Controllers;

use App\Models\Alternative;
use App\Models\Criteria;
use App\Models\Score;
use App\Services\AHPService;
use App\Services\COCOSOService;
use Illuminate\Http\Request;

class COCOSOController extends Controller
{
    protected $cocosoService;
    protected $ahpService;

    public function __construct(COCOSOService $cocosoService, AHPService $ahpService)
    {
        $this->cocosoService = $cocosoService;
        $this->ahpService = $ahpService;
    }

    public function index()
    {
        $criteria = Criteria::whereNull('submission_id')->orderBy('id')->get();
        $alternatives = Alternative::whereNull('submission_id')->get();
        $results = null;
        $error = null;
        $savedScores = [];

        // Load saved scores untuk ditampilkan di halaman input
        $scores = Score::whereHas('alternative', function($q) {
            $q->whereNull('submission_id');
        })->get();
        foreach ($scores as $score) {
            $savedScores[$score->alternative_id][$score->criteria_id] = $score->value;
        }

        if (Score::whereHas('alternative', function($q) {
            $q->whereNull('submission_id');
        })->exists()) {
            
            // Mengambil bobot kriteria hasil simpanan proses AHP
            $weights = $this->ahpService->getWeightsFromCriteria(null);

            // --- PERBAIKAN: Deteksi bobot kriteria yang super aman untuk segala format ---
            $hasWeights = false;
            if (!empty($weights)) {
                foreach ($weights as $wKey => $wVal) {
                    if (is_object($wVal) && isset($wVal->weight) && $wVal->weight > 0) {
                        $hasWeights = true;
                        break;
                    } elseif (is_array($wVal) && isset($wVal['weight']) && $wVal['weight'] > 0) {
                        $hasWeights = true;
                        break;
                    } elseif (is_numeric($wVal) && $wVal > 0) { // Jika flat array [ID => Bobot]
                        $hasWeights = true;
                        break;
                    }
                }
            }

            if (empty($weights)) {
                $error = 'Belum ada data kriteria. Silakan input kriteria terlebih dahulu.';
            } elseif (! $hasWeights) {
                // Jika bobot belum dihitung, jalankan kalkulasi AHP otomatis
                $ahpResults = $this->ahpService->calculateWeights();

                if (empty($ahpResults) || ! isset($ahpResults['weights'])) {
                    $error = 'Perbandingan AHP belum lengkap. Silakan lakukan perbandingan berpasangan di halaman AHP.';
                } elseif ($ahpResults['cr'] >= 0.1) {
                    $error = 'Consistency Ratio (CR) = '.number_format($ahpResults['cr'], 4).' (>= 0.1). Perbandingan AHP tidak konsisten. Silakan perbaiki perbandingan.';
                } else {
                    $weights = $this->ahpService->getWeightsFromCriteria(null);
                    $results = $this->cocosoService->calculateRanking($weights, $criteria);
                }
            } else {
                // Periksa konsistensi matriks AHP sebelum memproses peringkat CoCoSo
                $ahpResults = $this->ahpService->calculateWeights();
                if (isset($ahpResults['cr']) && $ahpResults['cr'] >= 0.1) {
                    $error = 'Consistency Ratio (CR) = '.number_format($ahpResults['cr'], 4).' (>= 0.1). Perbandingan AHP tidak konsisten. Silakan perbaiki perbandingan.';
                } else {
                    $results = $this->cocosoService->calculateRanking($weights, $criteria);
                }
            }
        }

        return view('pages.cocoso.index', compact('criteria', 'alternatives', 'results', 'error', 'savedScores'));
    }

    public function calculate(Request $request)
    {
        $scores = $request->input('score');

        if ($scores) {
            foreach ($scores as $altId => $criteriaScores) {
                foreach ($criteriaScores as $critId => $value) {
                    Score::updateOrCreate(
                        ['alternative_id' => $altId, 'criteria_id' => $critId],
                        ['value' => $value]
                    );
                }
            }
        }

        return redirect()->route('admin.cocoso.index')->with('success', 'Perhitungan CoCoSo berhasil diperbarui');
    }

    /**
     * METHOD RANKING UNTUK HALAMAN DEPAN / HASIL AKHIR
     */
    public function ranking()
    {
        $ahpResults = $this->ahpService->calculateWeights();

        if (empty($ahpResults)) {
            return redirect()->route('admin.cocoso.index')->with('error', 'Belum ada data kriteria. Silakan input kriteria terlebih dahulu.');
        }

        if (! isset($ahpResults['weights'])) {
            return redirect()->route('admin.cocoso.index')->with('error', 'Perbandingan AHP belum lengkap. Silakan lakukan perbandingan berpasangan di halaman AHP.');
        }

        if ($ahpResults['cr'] >= 0.1) {
            return redirect()->route('admin.cocoso.index')->with('error', 'Consistency Ratio (CR) = '.number_format($ahpResults['cr'], 4).' (>= 0.1). Perbandingan tidak konsisten. Silakan perbaiki data perbandingan AHP.');
        }

        $criteria = Criteria::whereNull('submission_id')->orderBy('id')->get();
        
        // Memanggil service dengan format array dari calculateWeights()
        $results = $this->cocosoService->calculateRanking($ahpResults['weights'], $criteria);

        if (auth()->check()) {
            return view('pages.ranking.index', compact('results'));
        }

        return view('awal', compact('results'));
    }
}
