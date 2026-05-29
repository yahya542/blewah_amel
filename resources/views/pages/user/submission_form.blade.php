@extends('layouts.app')

@section('title', 'Kuesioner Penelitian Pemilihan Bibit Unggul Blewah')

@push('styles')
<style>
    .gform-header {
        background: linear-gradient(135deg, #1a73e8 0%, #0d47a1 100%);
        border-radius: 12px 12px 0 0;
        padding: 28px 28px 24px;
        color: white;
        margin-bottom: 0;
    }
    .gform-header h2 {
        font-size: 1.35rem;
        font-weight: 700;
        margin-bottom: 8px;
    }
    .gform-header p {
        font-size: 0.85rem;
        opacity: 0.9;
        margin-bottom: 0;
    }

    .gform-card {
        border-radius: 0 0 12px 12px;
        border: 1px solid #e0e0e0;
        border-top: 4px solid #1a73e8;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        padding: 28px 28px 20px;
        background: #fff;
        margin-bottom: 20px;
    }
    .gform-section-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #202124;
        margin-bottom: 6px;
    }
    .gform-section-desc {
        font-size: 0.85rem;
        color: #5f6368;
        margin-bottom: 20px;
    }
    .gform-scale-legend {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 14px 18px;
        margin-bottom: 22px;
        font-size: 0.82rem;
        border: 1px solid #e0e0e0;
    }
    .gform-scale-legend p {
        margin-bottom: 4px;
        color: #444;
    }
    .gform-scale-legend strong {
        color: #1a73e8;
    }

    /* Question Item */
    .gform-question {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        padding: 20px 24px 18px;
        margin-bottom: 14px;
        transition: box-shadow 0.15s ease, border-color 0.15s ease;
        position: relative;
    }
    .gform-question:hover {
        box-shadow: 0 2px 10px rgba(26, 115, 232, 0.1);
        border-color: #bdd0f5;
    }
    .gform-question-label {
        font-size: 0.95rem;
        font-weight: 500;
        color: #202124;
        margin-bottom: 14px;
        display: flex;
        align-items: flex-start;
        gap: 6px;
    }
    .gform-question-label .q-num {
        font-weight: 700;
        color: #1a73e8;
        min-width: 22px;
    }
    .gform-question-label .q-required {
        color: #d32f2f;
        font-size: 0.85rem;
        margin-left: 2px;
    }

    /* Radio Scale (AHP) */
    .scale-options {
        display: flex;
        align-items: center;
        gap: 0;
        flex-wrap: wrap;
    }
    .scale-option {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
        min-width: 40px;
        cursor: pointer;
    }
    .scale-option input[type="radio"] {
        display: none;
    }
    .scale-option .scale-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 2px solid #dadce0;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        font-weight: 600;
        color: #5f6368;
        transition: all 0.15s ease;
        cursor: pointer;
    }
    .scale-option:hover .scale-circle {
        border-color: #1a73e8;
        background: #e8f0fe;
        color: #1a73e8;
    }
    .scale-option input[type="radio"]:checked + .scale-circle {
        background: #1a73e8;
        border-color: #1a73e8;
        color: #fff;
        box-shadow: 0 2px 6px rgba(26, 115, 232, 0.35);
    }
    .scale-labels {
        display: flex;
        justify-content: space-between;
        margin-top: 8px;
        font-size: 0.72rem;
        color: #9aa0a6;
        padding: 0 8px;
    }

    /* Likert Scale (CoCoSo) */
    .likert-options {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }
    .likert-option {
        display: flex;
        flex-direction: column;
        align-items: center;
        cursor: pointer;
        min-width: 64px;
        flex: 1;
    }
    .likert-option input[type="radio"] {
        display: none;
    }
    .likert-option .likert-btn {
        width: 100%;
        padding: 9px 6px;
        border-radius: 8px;
        border: 2px solid #dadce0;
        background: #fff;
        font-size: 0.82rem;
        font-weight: 600;
        color: #5f6368;
        text-align: center;
        transition: all 0.15s ease;
        cursor: pointer;
    }
    .likert-option .likert-label {
        font-size: 0.68rem;
        color: #9aa0a6;
        text-align: center;
        margin-top: 4px;
        line-height: 1.2;
    }
    .likert-option:hover .likert-btn {
        border-color: #1a73e8;
        background: #e8f0fe;
        color: #1a73e8;
    }
    .likert-option input[type="radio"]:checked + .likert-btn {
        background: #1a73e8;
        border-color: #1a73e8;
        color: #fff;
    }
    .likert-option input[type="radio"]:checked ~ .likert-label {
        color: #1a73e8;
        font-weight: 600;
    }

    /* Criteria group header */
    .criteria-group-header {
        font-size: 0.9rem;
        font-weight: 700;
        color: #1a73e8;
        letter-spacing: 0.01em;
        padding: 10px 0 8px;
        border-bottom: 2px solid #e8f0fe;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .criteria-group-header .badge-num {
        background: #1a73e8;
        color: white;
        font-size: 0.75rem;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .page-separator {
        border: none;
        border-top: 3px solid #1a73e8;
        margin: 32px 0;
        opacity: 0.15;
    }

    .submit-bar {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        padding: 20px 24px;
        margin-bottom: 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }

    @media (max-width: 576px) {
        .scale-options { gap: 4px; }
        .scale-option .scale-circle { width: 34px; height: 34px; font-size: 0.78rem; }
        .likert-options { gap: 6px; }
        .gform-card { padding: 18px 14px; }
        .gform-question { padding: 16px 14px; }
    }
</style>
@endpush

@section('content')
<div class="row justify-content-center mt-3 mb-5">
    <div class="col-12 col-lg-9">

        {{-- Header --}}
        <div class="gform-header mb-0">
            <h2>Kuesioner Penelitian Pemilihan Bibit Unggul Blewah<br>Untuk Mendukung Produktivitas Pertanian</h2>
            <p class="mt-2 mb-0" style="opacity:0.75; font-size:0.8rem;">
                Pengajuan: <strong>{{ $submission->title }}</strong>
            </p>
        </div>
        <div class="gform-card" style="border-radius: 0 0 12px 12px; border-top: none; margin-bottom: 24px;">
            <p class="text-muted small mb-1"><span class="text-danger">*</span> Menunjukkan pertanyaan yang wajib diisi</p>
        </div>

        @if ($errors->any())
        <div class="alert alert-danger mb-3">
            <strong>Ada kesalahan:</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('user.submission.submit_values', $submission->id) }}" method="POST">
            @csrf

            {{-- ================================================================= --}}
            {{-- BAGIAN A: PERBANDINGAN KRITERIA (AHP) --}}
            {{-- ================================================================= --}}
            <div class="gform-card">
                <div class="gform-section-title">A. PETUNJUK PENILAIAN KRITERIA</div>
                <div class="gform-section-desc">Beri nilai 1–9 sesuai tingkat kepentingan.</div>
                <div class="gform-scale-legend">
                    <p><strong>Skala Saaty:</strong></p>
                    <p>1 = Sama penting &nbsp;|&nbsp; 3 = Sedikit lebih penting &nbsp;|&nbsp; 5 = Lebih penting</p>
                    <p>7 = Sangat penting &nbsp;|&nbsp; 9 = Mutlak lebih penting</p>
                    <p>2, 4, 6, 8 = Nilai antara</p>
                </div>

                @php $qNum = 1; @endphp
                @foreach($criteria as $i => $c1)
                    @foreach($criteria as $j => $c2)
                        @if($i < $j)
                        <div class="gform-question">
                            <div class="gform-question-label">
                                <span class="q-num">{{ $qNum }}.</span>
                                <span>{{ $c1->name }} vs {{ $c2->name }} <span class="q-required">*</span></span>
                            </div>
                            <div class="scale-options">
                                @for($v = 1; $v <= 9; $v++)
                                <label class="scale-option">
                                    <input type="radio"
                                        name="comparison[{{ $c1->id }}][{{ $c2->id }}]"
                                        value="{{ $v }}"
                                        {{ $v == 1 ? 'checked' : '' }}
                                        required>
                                    <span class="scale-circle">{{ $v }}</span>
                                </label>
                                @endfor
                            </div>
                            <div class="scale-labels">
                                <span>Sama penting</span>
                                <span>Mutlak lebih penting</span>
                            </div>
                        </div>
                        @php $qNum++; @endphp
                        @endif
                    @endforeach
                @endforeach
            </div>

            {{-- ================================================================= --}}
            {{-- BAGIAN B: PENILAIAN ALTERNATIF (CoCoSo / Likert) --}}
            {{-- ================================================================= --}}
            <div class="gform-card mt-3">
                <div class="gform-section-title">B. PENGISIAN PENILAIAN BIBIT BLEWAH</div>
                <div class="gform-section-desc">Menggunakan Skala Likert – Beri Nilai 1 – 5</div>
                <div class="gform-scale-legend">
                    <p><strong>Keterangan:</strong></p>
                    <p>5 = Sangat Baik &nbsp;|&nbsp; 4 = Baik &nbsp;|&nbsp; 3 = Cukup Baik &nbsp;|&nbsp; 2 = Tidak Baik &nbsp;|&nbsp; 1 = Sangat Tidak Baik</p>
                </div>

                @php $cqNum = 1; @endphp
                @foreach($criteria as $crit)
                <div class="criteria-group-header">
                    <span class="badge-num">{{ $cqNum }}</span>
                    {{ strtoupper($crit->name) }}
                </div>
                @foreach($alternatives as $alt)
                <div class="gform-question">
                    <div class="gform-question-label">
                        <span>{{ $alt->name }} <span class="q-required">*</span></span>
                    </div>
                    <div class="likert-options">
                        @foreach([1 => 'Sangat Tidak Baik', 2 => 'Tidak Baik', 3 => 'Cukup Baik', 4 => 'Baik', 5 => 'Sangat Baik'] as $val => $label)
                        <label class="likert-option">
                            <input type="radio"
                                name="score[{{ $alt->id }}][{{ $crit->id }}]"
                                value="{{ $val }}"
                                {{ $val == 3 ? 'checked' : '' }}
                                required>
                            <span class="likert-btn">{{ $val }}</span>
                            <span class="likert-label">{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
                @php $cqNum++; @endphp
                @endforeach
            </div>

            {{-- Submit Bar --}}
            <div class="submit-bar">
                <a href="{{ route('user.dashboard') }}" class="btn btn-light px-4">
                    <i class="ti ti-arrow-left me-1"></i> Batal
                </a>
                <button type="submit" class="btn btn-primary px-5 py-2 fw-bold">
                    <i class="ti ti-send me-1"></i> Kirim Pengajuan
                </button>
            </div>
        </form>

    </div>
</div>
@endsection
