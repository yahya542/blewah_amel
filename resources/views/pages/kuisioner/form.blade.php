<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuesioner Penelitian Pemilihan Bibit Unggul Blewah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Roboto', 'Arial', sans-serif;
            background-color: #f0ebf8;
            color: #202124;
            line-height: 1.5;
        }

        .gf-wrap {
            max-width: 680px;
            margin: 28px auto 60px;
            padding: 0 12px;
        }

        /* ── Header Card ── */
        .gf-header {
            background: #fff;
            border-top: 10px solid #7248b9;
            border-radius: 8px 8px 0 0;
            padding: 24px 28px 20px;
            margin-bottom: 12px;
            box-shadow: 0 1px 2px rgba(0,0,0,.10), 0 2px 8px rgba(0,0,0,.06);
        }
        .gf-header h1 {
            font-family: 'Google Sans', sans-serif;
            font-size: 1.45rem;
            font-weight: 700;
            color: #202124;
            margin-bottom: 10px;
        }
        .gf-header p {
            font-size: 0.88rem;
            color: #5f6368;
            margin-bottom: 4px;
        }
        .gf-required-note {
            font-size: 0.82rem;
            color: #d93025;
            margin-top: 10px;
        }

        /* ── Section Card ── */
        .gf-section {
            background: #fff;
            border-radius: 8px;
            padding: 22px 28px 16px;
            margin-bottom: 12px;
            box-shadow: 0 1px 2px rgba(0,0,0,.10), 0 2px 8px rgba(0,0,0,.06);
            border-left: 4px solid #7248b9;
        }
        .gf-section-title {
            font-family: 'Google Sans', sans-serif;
            font-size: 1.0rem;
            font-weight: 700;
            color: #202124;
            margin-bottom: 4px;
        }
        .gf-section-desc {
            font-size: 0.84rem;
            color: #5f6368;
            margin-bottom: 14px;
        }
        .gf-scale-box {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 12px 16px;
            font-size: 0.82rem;
            color: #444;
            margin-bottom: 18px;
        }
        .gf-scale-box p { margin-bottom: 3px; }

        /* ── Question Card ── */
        .gf-question {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 18px 22px 16px;
            margin-bottom: 10px;
            box-shadow: 0 1px 2px rgba(0,0,0,.06);
            transition: box-shadow .15s, border-color .15s;
        }
        .gf-question:hover {
            border-color: #c4a6f0;
            box-shadow: 0 2px 8px rgba(114,72,185,.10);
        }
        .gf-question-label {
            font-size: 0.95rem;
            font-weight: 500;
            color: #202124;
            margin-bottom: 14px;
        }
        .gf-question-label .qn { color: #7248b9; font-weight: 700; margin-right: 4px; }
        .gf-question-label .req { color: #d93025; margin-left: 3px; }

        /* ── Scale Buttons (AHP 1–9) ── */
        .scale-row {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        .scale-item { flex: 1; min-width: 36px; }
        .scale-item input { display: none; }
        .scale-item .sc {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 42px;
            border-radius: 6px;
            border: 2px solid #dadce0;
            background: #fff;
            font-size: 0.9rem;
            font-weight: 600;
            color: #5f6368;
            cursor: pointer;
            transition: all .13s;
        }
        .scale-item:hover .sc {
            border-color: #7248b9;
            background: #f0ebf8;
            color: #7248b9;
        }
        .scale-item input:checked + .sc {
            background: #7248b9;
            border-color: #7248b9;
            color: #fff;
            box-shadow: 0 2px 6px rgba(114,72,185,.35);
        }
        .scale-hint {
            display: flex;
            justify-content: space-between;
            font-size: 0.68rem;
            color: #9aa0a6;
            margin-top: 6px;
            padding: 0 2px;
        }

        /* ── Likert Buttons (1–5) ── */
        .likert-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .likert-item { flex: 1; min-width: 60px; }
        .likert-item input { display: none; }
        .likert-item .lb {
            display: block;
            padding: 9px 4px 7px;
            border-radius: 8px;
            border: 2px solid #dadce0;
            background: #fff;
            text-align: center;
            font-size: 0.88rem;
            font-weight: 700;
            color: #5f6368;
            cursor: pointer;
            transition: all .13s;
        }
        .likert-item .ll {
            display: block;
            text-align: center;
            font-size: 0.65rem;
            color: #9aa0a6;
            margin-top: 4px;
            line-height: 1.2;
        }
        .likert-item:hover .lb {
            border-color: #7248b9;
            background: #f0ebf8;
            color: #7248b9;
        }
        .likert-item input:checked + .lb {
            background: #7248b9;
            border-color: #7248b9;
            color: #fff;
        }
        .likert-item input:checked ~ .ll {
            color: #7248b9;
            font-weight: 600;
        }

        /* ── Criteria Group Header ── */
        .crit-header {
            font-family: 'Google Sans', sans-serif;
            font-size: 0.9rem;
            font-weight: 700;
            color: #7248b9;
            border-bottom: 2px solid #ede4f9;
            padding-bottom: 8px;
            margin-bottom: 12px;
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .crit-header .cnum {
            background: #7248b9;
            color: #fff;
            width: 24px; height: 24px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            flex-shrink: 0;
        }

        /* ── Submit Bar ── */
        .gf-submit {
            background: #fff;
            border-radius: 8px;
            padding: 18px 24px;
            margin-top: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 2px rgba(0,0,0,.10), 0 2px 8px rgba(0,0,0,.06);
        }
        .gf-submit .btn-submit {
            background: #7248b9;
            color: #fff;
            border: none;
            padding: 11px 32px;
            border-radius: 4px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: background .15s;
        }
        .gf-submit .btn-submit:hover { background: #5c3a9b; }
        .gf-submit .btn-clear {
            background: none;
            border: none;
            color: #7248b9;
            font-size: 0.88rem;
            cursor: pointer;
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .gf-section, .gf-header { padding: 16px 14px; }
            .gf-question { padding: 14px 12px; }
            .scale-item .sc { height: 38px; font-size: 0.82rem; }
        }
    </style>
</head>
<body>
<div class="gf-wrap">

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Header --}}
    <div class="gf-header">
        <h1>Kuesioner Penelitian Pemilihan Bibit Unggul Blewah Untuk Mendukung Produktivitas Pertanian</h1>
        <p class="gf-required-note"><span style="color:#d93025;">*</span> Menunjukkan pertanyaan yang wajib diisi</p>
    </div>

    <form action="{{ route('kuisioner.submit', $kuesioner->id) }}" method="POST" id="kuesioner-form">
        @csrf

        {{-- Identitas Responden --}}
        <div class="gf-section">
            <div class="gf-section-title">Identitas Responden</div>
            <div class="gf-section-desc">Isi data diri Anda sebelum mengisi kuesioner.</div>

            <div class="gf-question">
                <div class="gf-question-label">Nama Responden <span class="req">*</span></div>
                <input type="text" name="nama_responden" class="form-control" placeholder="Masukkan nama lengkap Anda" required style="font-size:0.92rem;">
            </div>
            <div class="gf-question">
                <div class="gf-question-label">Usia <span class="req">*</span></div>
                <input type="number" name="usia" class="form-control" placeholder="Contoh: 35" min="1" max="120" required style="font-size:0.92rem; max-width:200px;">
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- BAGIAN A: Perbandingan Kriteria (AHP)                 --}}
        {{-- ===================================================== --}}
        <div class="gf-section">
            <div class="gf-section-title">A. PETUNJUK PENILAIAN KRITERIA</div>
            <div class="gf-section-desc">Beri nilai 1–9 sesuai tingkat kepentingan antar kriteria.</div>
            <div class="gf-scale-box">
                <p><strong>Skala Saaty:</strong></p>
                <p>1 = Sama penting &nbsp;|&nbsp; 3 = Sedikit lebih penting &nbsp;|&nbsp; 5 = Lebih penting</p>
                <p>7 = Sangat penting &nbsp;|&nbsp; 9 = Mutlak lebih penting</p>
                <p>2, 4, 6, 8 = Nilai antara</p>
            </div>

            @php
                $kriteriaList = [
                    'Waktu Panen',
                    'Jumlah Buah',
                    'Ketahanan Penyakit',
                    'Kualitas Buah',
                    'Berat Buah',
                    'Harga Bibit',
                ];
                $qNum = 1;
            @endphp

            @for ($i = 0; $i < count($kriteriaList); $i++)
                @for ($j = $i + 1; $j < count($kriteriaList); $j++)
                <div class="gf-question">
                    <div class="gf-question-label">
                        <span class="qn">{{ $qNum }}.</span>
                        {{ $kriteriaList[$i] }} vs {{ $kriteriaList[$j] }}
                        <span class="req">*</span>
                    </div>
                    <div class="scale-row">
                        @for ($v = 1; $v <= 9; $v++)
                        <label class="scale-item">
                            <input type="radio"
                                   name="kriteria[{{ $i }}][{{ $j }}]"
                                   value="{{ $v }}"
                                   {{ $v == 1 ? 'checked' : '' }}
                                   required>
                            <span class="sc">{{ $v }}</span>
                        </label>
                        @endfor
                    </div>
                    <div class="scale-hint">
                        <span>Sama penting</span>
                        <span>Mutlak lebih penting</span>
                    </div>
                </div>
                @php $qNum++; @endphp
                @endfor
            @endfor
        </div>

        {{-- ===================================================== --}}
        {{-- BAGIAN B: Penilaian Bibit Blewah (Likert 1–5)        --}}
        {{-- ===================================================== --}}
        <div class="gf-section">
            <div class="gf-section-title">B. PENGISIAN PENILAIAN BIBIT BLEWAH</div>
            <div class="gf-section-desc">Menggunakan Skala Likert – Beri Nilai 1 – 5</div>
            <div class="gf-scale-box">
                <p><strong>Keterangan:</strong></p>
                <p>5 = Sangat Baik &nbsp;|&nbsp; 4 = Baik &nbsp;|&nbsp; 3 = Cukup Baik &nbsp;|&nbsp; 2 = Tidak Baik &nbsp;|&nbsp; 1 = Sangat Tidak Baik</p>
            </div>

            @php
                $varietasList = [
                    'A1 Golden Aroma',
                    'A2 Varietas Aruna',
                    'A3 Sweet Net',
                    'A4 King Blewah',
                    'A5 Rangipo',
                ];
                $likertLabels = [1 => 'Sangat Tidak Baik', 2 => 'Tidak Baik', 3 => 'Cukup Baik', 4 => 'Baik', 5 => 'Sangat Baik'];
                $cqNum = 1;
            @endphp

            @for ($cIdx = 0; $cIdx < count($kriteriaList); $cIdx++)
            <div class="crit-header">
                <span class="cnum">{{ $cqNum }}</span>
                {{ strtoupper($kriteriaList[$cIdx]) }}
            </div>
            @foreach ($varietasList as $vIdx => $varietas)
            <div class="gf-question">
                <div class="gf-question-label">
                    {{ $varietas }} <span class="req">*</span>
                </div>
                <div class="likert-row">
                    @foreach ($likertLabels as $val => $label)
                    <label class="likert-item">
                        <input type="radio"
                               name="varietas[{{ $vIdx }}][{{ $cIdx }}]"
                               value="{{ $val }}"
                               {{ $val == 3 ? 'checked' : '' }}
                               required>
                        <span class="lb">{{ $val }}</span>
                        <span class="ll">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endforeach
            @php $cqNum++; @endphp
            @endfor
        </div>

        {{-- Submit --}}
        <div class="gf-submit">
            <button type="button" class="btn-clear" onclick="document.getElementById('kuesioner-form').reset()">
                Hapus formulir
            </button>
            <button type="submit" class="btn-submit">
                Kirim
            </button>
        </div>
    </form>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>