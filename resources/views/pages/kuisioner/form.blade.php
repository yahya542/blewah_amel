<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuesioner DSS - SPK Bibit Blewah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Google Sans', 'Arial', sans-serif;
            background-color: #f8f9fa;
            color: #202124;
            line-height: 1.5;
        }
        .google-form-container {
            max-width: 720px;
            margin: 24px auto;
            padding: 24px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 4px 6px rgba(0,0,0,0.1);
        }
        .form-header {
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #dadce0;
        }
        .form-title {
            font-size: 24px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 8px;
        }
        .form-description {
            font-size: 14px;
            color: #5f6368;
        }
        .question-block {
            margin-bottom: 24px;
        }
        .question-label {
            display: block;
            font-size: 16px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 8px;
        }
        .question-label .required {
            color: #d93025;
            margin-left: 4px;
        }
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #dadce0;
            border-radius: 4px;
            font-size: 16px;
            color: #202124;
            background: white;
        }
        .form-control:focus {
            outline: none;
            border-color: #4285f4;
            box-shadow: 0 0 0 2px rgba(66, 133, 244, 0.2);
        }
        .matrix-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .matrix-table th, .matrix-table td {
            border: 1px solid #dadce0;
            padding: 8px;
            text-align: center;
            font-size: 13px;
        }
        .matrix-table th {
            background-color: #f2f3f5;
            font-weight: 500;
            color: #5f6368;
        }
        .matrix-table td {
            vertical-align: middle;
        }
        .matrix-table input {
            width: 100%;
            padding: 6px;
            border: none;
            text-align: center;
            font-size: 14px;
        }
        .matrix-table input:focus {
            outline: none;
            background-color: #e8f0fe;
        }
        .matrix-table input[readonly] {
            background-color: #f2f3f5;
            cursor: default;
        }
        .submit-btn {
            background-color: #4285f4;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 500;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            margin-top: 16px;
        }
        .submit-btn:hover {
            background-color: #3367d6;
        }
        .page-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin: 24px 0 16px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #dadce0;
        }
    </style>
</head>
<body>
    <div class="google-form-container">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        <div class="form-header">
            <h1 class="form-title">Kuesioner Rekomendasi Varietas Blewah</h1>
            <p class="form-description">Isi form berikut untuk mendapatkan rekomendasi varietas blewah terbaik berdasarkan kriteria yang telah ditentukan.</p>
        </div>

        <form action="{{ route('kuisioner.submit', $kuesioner->id) }}" method="POST">
            @csrf
            
            <div class="question-block">
                <label class="question-label">Nama Responden <span class="required">*</span></label>
                <input type="text" name="nama_responden" class="form-control" placeholder="Masukkan nama lengkap" required>
            </div>

            <div class="question-block">
                <label class="question-label">Usia <span class="required">*</span></label>
                <input type="number" name="usia" class="form-control" placeholder="Masukkan usia" required min="1" max="120">
            </div>

            <h3 class="page-title">Matriks Perbandingan Kriteria (1-9)</h3>
            <p style="font-size: 14px; color: #5f6368; margin-bottom: 16px;">Pilih nilai perbandingan antar kriteria. Isi angka 1 pada diagonal utama.</p>
            
            <table class="matrix-table">
                <thead>
                    <tr>
                        <th>Kriteria</th>
                        @php $kriteriaList = ['Waktu Panen', 'Mobilitas Tanah', 'Ketersediaan Air', 'Kemudahan Perawatan', 'Hasil Pertanian']; @endphp
                        @foreach($kriteriaList as $k)
                            <th>{{ $k }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @for($i = 0; $i < 5; $i++)
                        <tr>
                            <td class="fw-bold">{{ $kriteriaList[$i] }}</td>
                            @for($j = 0; $j < 5; $j++)
                                <td>
                                    <input type="number" name="kriteria[{{ $i }}][{{ $j }}]" 
                                        class="form-control"
                                        value="{{ $i == $j ? 1 : '' }}" 
                                        min="1" max="9" step="0.1" 
                                        {{ $i == $j ? 'readonly' : '' }}
                                        required>
                                </td>
                            @endfor
                        </tr>
                    @endfor
                </tbody>
            </table>

            <h3 class="page-title" style="margin-top: 32px;">Penilaian Varietas (1-100)</h3>
            <p style="font-size: 14px; color: #5f6368; margin-bottom: 16px;">Berikan skor untuk setiap varietas berdasarkan setiap kriteria (1-100).</p>
            
            @php $varietasList = ['A1 Golden Aroma', 'A2 Red Velvet', 'A3 Long Staple', 'A4 Premium', 'A5 Classic']; @endphp
            
            @foreach($varietasList as $vIdx => $varietas)
                <div class="question-block">
                    <label class="question-label">{{ $varietas }} <span class="required">*</span></label>
                    <div class="row">
                        @for($cIdx = 0; $cIdx < 5; $cIdx++)
                            <div class="col-6 col-md-4 col-lg-2-4 mb-2">
                                <label style="font-size: 12px; color: #5f6368;">{{ $kriteriaList[$cIdx] }}</label>
                                <input type="number" name="varietas[{{ $vIdx }}][{{ $cIdx }}]" 
                                    class="form-control"
                                    min="1" max="100" value="50" required>
                            </div>
                        @endfor
                    </div>
                </div>
            @endforeach

            <button type="submit" class="submit-btn">Simpan Jawaban</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>