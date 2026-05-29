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
    public function index()
    {
        $criteria = Criteria::whereNull('submission_id')->orderBy('id')->get();
        $alternatives = Alternative::whereNull('submission_id')->orderBy('id')->get();
        
        $ahpResults = null;
        $cocosoResults = null;

        if (Comparison::whereHas('criteria1', function($q) {
            $q->whereNull('submission_id');
        })->exists()) {
            $ahpResults = $this->ahpService->calculateWeights();
        }

        if (Score::exists() && $ahpResults && isset($ahpResults['weights'])) { 
            $weights = $ahpResults['weights']; 
            $cocosoResults = $this->cocosoService->calculateRanking($weights, $criteria, null);
        }

        return view('pages.ahp.index', compact('criteria', 'alternatives', 'ahpResults', 'cocosoResults'));
    }

    /**
     * Proses Utama: Ekstraksi 1 File CSV ke Dua Metode (AHP & COCOSO) sekaligus
     * Diperbaiki: auto-detect delimiter (, atau ;), filter konsisten, cegah 0 karena mismatch jumlah kolom
     */
    public function combinedCalculate(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt'
        ]);

        $criteria = Criteria::whereNull('submission_id')->orderBy('id')->get();
        $alternatives = Alternative::whereNull('submission_id')->orderBy('id')->get();

        // Enforce exact 6 kriteria & 5 alternatif agar mapping kolom CSV cocok (mencegah 0 di data mentah)
        if ($criteria->count() !== 6 || $alternatives->count() !== 5) {
            return redirect()->back()->with('error', 'Import CSV hanya mendukung persis 6 kriteria dan 5 alternatif (tanpa submission). Saat ini ada ' . $criteria->count() . ' kriteria dan ' . $alternatives->count() . ' alternatif. Sesuaikan dulu.');
        }

        // ===================================================================
        // ROBUST CSV READER: auto-detect delimiter (sangat penting untuk Excel Indonesia yang pakai ;)
        // ===================================================================
        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (count($lines) < 2) {
            return redirect()->back()->with('error', 'File CSV kosong atau tidak memiliki baris data (butuh minimal header + 1 baris data).');
        }

        $dataLine = $lines[1]; // baris data (baris 2, index 1) — baris 0 = header teks kuesioner

        // Deteksi delimiter terbaik berdasarkan jumlah kolom terbanyak
        $delims = [',', ';', "\t"];
        $bestDelim = ',';
        $maxCols = 0;
        foreach ($delims as $d) {
            $test = str_getcsv($dataLine, $d);
            if (count($test) > $maxCols) {
                $maxCols = count($test);
                $bestDelim = $d;
            }
        }

        $row = str_getcsv($dataLine, $bestDelim);

        if (!$row || count($row) < 48) {
            return redirect()->back()->with('error', 'File CSV tidak memiliki cukup kolom (minimal ~48 kolom untuk 6 kriteria + 5 alternatif). Periksa format CSV Anda (gunakan Export CSV dengan delimiter yang benar).');
        }

        DB::beginTransaction();
        try {
            // ===================================================================
            // BAGIAN I: DATA AHP (Kolom Indeks 3 s/d 17) - INDEKS KRITERIA DIPERBAIKI
            // ===================================================================
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

            // ===================================================================
            // BAGIAN II: DATA COCOSO (Mulai dari Kolom Indeks 18 Berurutan)
            // Sekarang 100% cocok dengan jumlah kriteria & alternatif yang dipakai di view & service
            // ===================================================================
            $csvCol = 18;
            
            // Loop luar membaca Kriteria (C1-C6) secara berurutan
            foreach ($criteria as $crit) {
                // Loop dalam membaca Alternatif (A1-A5) untuk kriteria aktif
                foreach ($alternatives as $alt) {
                    $rawVal = isset($row[$csvCol]) ? trim($row[$csvCol]) : '0';
                    $scoreVal = floatval(str_replace(',', '.', $rawVal));

                    Score::updateOrCreate(
                        ['alternative_id' => $alt->id, 'criteria_id' => $crit->id],
                        ['value' => $scoreVal]
                    );
                    
                    $csvCol++; // Maju ke kolom berikutnya di file CSV
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
