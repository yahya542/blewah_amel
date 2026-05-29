<?php

use App\Models\Kuesioner;
use App\Models\KuesionerComparison;
use App\Models\KuesionerScore;
use Illuminate\Support\Facades\DB;

// Load Laravel context if needed, but since we run this as a script, we can use DB facade if bootstrapped.
// However, the easiest way is to use a standard seeder approach or a simple PHP script that uses Eloquent.

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$data = [
    ["Juma'i", "40", "1","1","1","4","5","4","1","5","6","1","1","1","1","4","1","5","3","5","3","2","3","3","5","2","2","5","3","5","2","3","5","3","5","3","2","3","2","5","3","2","2","3","1","2","5"],
    ["Hosni", "35", "1","1","1","4","5","4","1","5","6","1","1","1","1","4","1","5","4","5","4","3","4","4","5","3","3","5","4","5","3","4","5","4","5","4","3","4","3","5","4","3","3","4","2","3","5"],
    ["Amam", "41", "2","3","2","4","5","4","3","5","6","2","2","3","2","4","2","5","4","5","4","3","4","4","5","3","3","5","4","5","3","4","5","4","5","4","3","4","3","5","4","3","3","4","2","3","5"],
    ["Amir", "36", "2","3","2","4","5","4","3","5","6","2","2","3","3","4","2","5","5","5","5","4","5","5","5","4","4","5","5","5","4","5","5","5","5","5","4","5","4","5","5","4","4","5","3","4","5"],
    ["Juhari", "37", "2","3","2","4","5","4","3","5","6","2","2","3","3","4","2","5","4","5","4","3","4","4","5","3","3","5","4","5","3","4","5","4","5","4","3","4","3","5","4","3","3","4","2","3","5"],
    ["Kusairi", "39", "2","3","2","4","5","4","3","5","6","2","2","3","3","4","2","5","3","5","3","2","3","3","5","2","2","5","3","5","2","3","5","3","5","3","2","3","2","5","3","2","2","3","1","2","5"],
    ["Halim", "40", "4","9","4","4","5","4","9","5","6","4","4","9","9","4","4","5","5","5","5","4","5","5","5","4","4","5","5","5","4","5","5","5","5","5","4","5","4","5","5","4","4","5","3","4","5"],
    ["Rusdi", "40", "4","9","4","4","5","4","9","5","6","4","4","9","9","4","4","5","4","5","4","3","4","4","5","3","3","5","4","5","3","4","5","4","5","4","3","4","3","5","4","3","3","4","2","3","5"],
    ["Rauf", "38", "2","3","2","4","5","4","3","5","6","2","2","3","3","4","2","5","4","5","4","3","4","4","5","3","3","5","4","5","3","4","5","4","5","4","3","4","3","5","4","3","3","4","2","3","5"],
    ["Junaidi", "42", "2","3","2","4","5","4","3","5","6","2","2","3","3","4","2","5","4","5","4","3","4","4","5","3","3","5","4","5","3","4","5","4","5","4","3","4","3","5","4","3","3","4","2","3","5"]
];

$kriteriaNames = ['Waktu Panen', 'Jumlah Buah', 'Ketahanan Penyakit', 'Kualitas Buah', 'Berat Buah', 'Harga Bibit'];
$varietasNames = ['A1 Golden Aroma', 'A2 Varietas Aruna', 'A3 Sweet Net', 'A4 King Blewah', 'A5 Rangipo'];

$ahpPairs = [
    [0, 1], [0, 2], [0, 3], [0, 4], [0, 5],
    [1, 2], [1, 3], [1, 4], [1, 5],
    [2, 3], [2, 4], [2, 5],
    [3, 4], [3, 5],
    [4, 5]
];

DB::beginTransaction();

try {
    foreach ($data as $row) {
        $nama = $row[0];
        $usia = $row[1];

        $kuesioner = Kuesioner::create([
            'nama_responden' => $nama,
            'usia' => $usia,
            'status' => 'pending',
            'hasil_json' => []
        ]);

        // Seeds Comparison
        for ($i = 0; $i < 15; $i++) {
            $pair = $ahpPairs[$i];
            KuesionerComparison::create([
                'kuesioner_id' => $kuesioner->id,
                'kriteria_from' => $kriteriaNames[$pair[0]],
                'kriteria_to' => $kriteriaNames[$pair[1]],
                'value' => (double)$row[$i + 2]
            ]);
        }

        // Seeds Scores
        // Row index for scores start at 17 (2 name, 15 pairs)
        $scoreIdx = 17;
        foreach ($kriteriaNames as $kIdx => $kName) {
            foreach ($varietasNames as $vIdx => $vName) {
                KuesionerScore::create([
                    'kuesioner_id' => $kuesioner->id,
                    'kriteria' => $kName,
                    'varietas' => $vName,
                    'score' => (double)$row[$scoreIdx]
                ]);
                $scoreIdx++;
            }
        }
    }
    DB::commit();
    echo "Seeding completed successfully!\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
