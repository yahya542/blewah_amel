<?php

namespace App\Http\Controllers;

use App\Models\Comparison;
use App\Models\Score;
use App\Models\Criteria;
use App\Models\Alternative;
use App\Services\AHPService;
use App\Services\COCOSOService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AHPController extends Controller
{
    protected $ahpService;
    protected $cocosoService;

    public function __construct(AHPService $ahpService, COCOSOService $cocosoService)
    {
        $this->ahpService = $ahpService;
        $this->cocosoService = $cocosoService;
    }

    /**
     * Menampilkan Form Upload dan Hasil Olah Data Terintegrasi
     */
    /**
     * Menampilkan Form Upload dan Hasil Olah Data Terintegrasi
     */
    public function index()
    {
        // 1. Ambil kriteria global milik admin (yang submission_id-nya NULL)
        $criteria = Criteria::whereNull('submission_id')->orderBy('id')->get();
        $alternatives = Alternative::orderBy('id')->get();
        
        $ahpResults = null;
        $cocosoResults = null;

        // Cek data global (tanpa submission)
        if (Comparison::whereHas('criteria1', function($q) {
            $q->whereNull('submission_id');
        })->exists()) {
            $ahpResults = $this->ahpService->calculateWeights();
        }

        if (Score::exists() && $ahpResults && isset($ahpResults['weightsIndexed'])) { 
            $weights = $ahpResults['weightsIndexed']; 
            $cocosoResults = $this->cocosoService->calculateRanking($weights, $criteria, null);
        }

        return view('pages.ahp.index', compact('criteria', 'alternatives', 'ahpResults', 'cocosoResults'));
    }
    /**
     * Proses Utama: Ekstraksi 1 File CSV ke Dua Metode (AHP & COCOSO) sekaligus
     */
    public function combinedCalculate(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt'
        ]);

        $criteria = Criteria::whereNull('submission_id')->orderBy('id')->get();
        $alternatives = Alternative::orderBy('id')->get();

        if ($criteria->count() < 6 || $alternatives->count() < 5) {
            return redirect()->back()->with('error', 'Pastikan sistem memiliki minimal 6 kriteria dan 5 alternatif blewah.');
        }

        // Membaca file CSV
        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        fgetcsv($handle);        // Lewati baris 1 (Header teks kuesioner)
        $row = fgetcsv($handle); // Ambil baris 2 (Data nilai responden JUMA'I)
        fclose($handle);

        if (!$row) {
            return redirect()->back()->with('error', 'File CSV kosong atau tidak memiliki baris data.');
        }

        DB::beginTransaction();
        try {
            // ==========================================
            // BAGIAN I: DATA AHP (Kolom Indeks 3 s/d 17)
            // ==========================================
            $ahpMapping = [
                3  => [$criteria[0]->id, $criteria[1]->id], 4  => [$criteria[0]->id, $criteria[2]->id],
                5  => [$criteria[0]->id, $criteria[3]->id], 6  => [$criteria[0]->id, $criteria[4]->id],
                7  => [$criteria[0]->id, $criteria[5]->id], 8  => [$criteria[1]->id, $criteria[2]->id],
                9  => [$criteria[1]->id, $criteria[3]->id], 10 => [$criteria[1]->id, $criteria[4]->id],
                11 => [$criteria[1]->id, $criteria[5]->id], 12 => [$criteria[2]->id, $criteria[3]->id],
                13 => [$criteria[2]->id, $criteria[4]->id], 14 => [$criteria[2]->id, $criteria[5]->id],
                15 => [$criteria[3]->id, $criteria[4]->id], 16 => [$criteria[3]->id, $criteria[5]->id],
                17 => [$criteria[4]->id, $criteria[5]->id],
            ];

            foreach ($ahpMapping as $colIndex => $pairs) {
                $val = floatval(str_replace(',', '.', $row[$colIndex] ?? 1));
                if ($val > 0) {
                    Comparison::updateOrCreate(
                        ['criteria_id_1' => $pairs[0], 'criteria_id_2' => $pairs[1]],
                        ['value' => $val]
                    );
                    Comparison::updateOrCreate(
                        ['criteria_id_1' => $pairs[1], 'criteria_id_2' => $pairs[0]],
                        ['value' => 1 / $val]
                    );
                }
            }

            // ==========================================
            // BAGIAN II: DATA COCOSO (Kolom Indeks 18 s/d 47)
            // ==========================================
            $csvCol = 18;
            foreach ($criteria as $crit) {
                foreach ($alternatives as $alt) {
                    $scoreVal = floatval(str_replace(',', '.', $row[$csvCol] ?? 0));
                    
                    Score::updateOrCreate(
                        ['alternative_id' => $alt->id, 'criteria_id' => $crit->id],
                        ['value' => $scoreVal]
                    );
                    $csvCol++; 
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mengekstrak struktur file CSV: ' . $e->getMessage());
        }

        return redirect()->route('admin.ahp.index')->with('success', 'Data AHP dan CoCoSo Berhasil Diekstraksi dan Diperbarui dari CSV!');
    }

    /**
     * Mempertahankan fitur hitung manual lama agar tidak merusak flow bawaan sistem
     */
    public function calculate(Request $request)
    {
        $comparisons = $request->input('comparison');

        if ($comparisons) {
            foreach ($comparisons as $id1 => $others) {
                foreach ($others as $id2 => $value) {
                    if ($id1 != $id2 && $value != 0) {
                        Comparison::updateOrCreate(
                            ['criteria_id_1' => $id1, 'criteria_id_2' => $id2],
                            ['value' => $value]
                        );
                        Comparison::updateOrCreate(
                            ['criteria_id_1' => $id2, 'criteria_id_2' => $id1],
                            ['value' => 1 / $value]
                        );
                    }
                }
            }
        }

        return redirect()->route('admin.ahp.index')->with('success', 'Perhitungan AHP manual berhasil diperbarui');
    }
}