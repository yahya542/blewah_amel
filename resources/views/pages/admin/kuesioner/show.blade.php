<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Kuesioner - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Google Sans', 'Arial', sans-serif;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 900px;
            margin: 24px auto;
            padding: 0 16px;
        }
        .card {
            border: 1px solid #dadce0;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: white;
            border-bottom: 1px solid #dadce0;
            padding: 16px 24px;
        }
        .card-header h5 {
            font-weight: 500;
            color: #202124;
        }
        .card-body {
            padding: 24px;
        }
        .section-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin: 24px 0 16px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #dadce0;
        }
        .response-card {
            border: 1px solid #dadce0;
            border-radius: 4px;
            margin-bottom: 16px;
        }
        .response-header {
            background-color: #f2f3f5;
            padding: 12px 16px;
            font-weight: 500;
            font-size: 14px;
            border-bottom: 1px solid #dadce0;
        }
        .response-body {
            padding: 16px;
        }
        .table th, .table td {
            border: 1px solid #dadce0;
            padding: 8px;
            font-size: 13px;
        }
        .result-box {
            background-color: #e6f4ea;
            border: 1px solid #CEE7D1;
            border-radius: 4px;
            padding: 16px;
            margin-top: 24px;
        }
        .result-box h6 {
            color: #137333;
            font-weight: 500;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Detail Kuesioner: {{ $group->nama_projek }}</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <strong>Jumlah Responden: {{ count($group->semua_jawaban) }}</strong>
                    @if($group->status == 'pending')
                        <form action="{{ route('admin.kuesioner.eksekusi', $group->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="bi bi-sparkles"></i> Eksekusi
                            </button>
                        </form>
                    @endif
                </div>

                @foreach($group->semua_jawaban as $index => $orang)
                    <div class="response-card">
                        <div class="response-header">
                            Responden {{ $index + 1 }}: {{ $orang['nama'] }} (Usia: {{ $orang['usia'] }} tahun)
                        </div>
                        <div class="response-body">
                            <div class="section-title">Matriks Kriteria (AHP)</div>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Waktu Panen</th>
                                        <th>Mobilitas Tanah</th>
                                        <th>Ketersediaan Air</th>
                                        <th>Kemudahan Perawatan</th>
                                        <th>Hasil Pertanian</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $kriteriaList = ['Waktu Panen', 'Mobilitas Tanah', 'Ketersediaan Air', 'Kemudahan Perawatan', 'Hasil Pertanian']; @endphp
                                    @for($i = 0; $i < 5; $i++)
                                        <tr>
                                            <td class="fw-bold">{{ $kriteriaList[$i] }}</td>
                                            @for($j = 0; $j < 5; $j++)
                                                <td>{{ $orang['kriteria'][$i][$j] ?? 1 }}</td>
                                            @endfor
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>

                            <div class="section-title">Penilaian Varietas (CoCoSo)</div>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Varietas</th>
                                        <th>Waktu Panen</th>
                                        <th>Mobilitas Tanah</th>
                                        <th>Ketersediaan Air</th>
                                        <th>Kemudahan Perawatan</th>
                                        <th>Hasil Pertanian</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $varietasList = ['A1 Golden Aroma', 'A2 Red Velvet', 'A3 Long Staple', 'A4 Premium', 'A5 Classic']; @endphp
                                    @for($i = 0; $i < 5; $i++)
                                        <tr>
                                            <td class="fw-bold">{{ $varietasList[$i] }}</td>
                                            @for($j = 0; $j < 5; $j++)
                                                <td>{{ $orang['varietas'][$i][$j] ?? 50 }}</td>
                                            @endfor
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach

                @if($group->hasil_akhir_json)
                    <div class="result-box">
                        <h6><i class="bi bi-trophy"></i> Hasil Perhitungan</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Bobot Kriteria:</strong>
                                <table class="table table-sm">
                                    <tbody>
                                        @foreach($group->hasil_akhir_json['weights'] as $w)
                                            <tr><td>{{ $w['name'] }}</td><td class="text-end">{{ number_format($w['weight'], 2) }}</td></tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <strong>Peringkat Varietas:</strong>
                                <table class="table table-sm">
                                    <thead><tr><th>Rank</th><th>Varietas</th><th>Qi</th></tr></thead>
                                    <tbody>
                                        @foreach($group->hasil_akhir_json['ranking'] as $index => $res)
                                            <tr><td>#{{ $index + 1 }}</td><td>{{ $res['name'] }}</td><td class="text-end fw-bold">{{ number_format($res['qi'], 3) }}</td></tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <div class="mt-3">
            <a href="{{ route('admin.kuesioner.dashboard') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
</body>
</html>