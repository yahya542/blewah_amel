@extends('layouts.app')

@section('title', 'Hasil Perangkingan Kuesioner')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm mb-4">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1">Hasil Perangkingan Gabungan</h4>
                    <p class="text-muted mb-0">Berdasarkan {{ $data['total_responden'] ?? 0 }} responden terpilih</p>
                </div>
                <div>
                    <a href="{{ route('admin.kuesioner.dashboard') }}" class="btn btn-outline-primary btn-sm">
                        <i class="ti ti-arrow-left me-1"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>

        @if(!$data)
            <div class="alert alert-warning border-0 shadow-none p-4">
                <i class="ti ti-alert-triangle fs-7 mb-2 d-block"></i>
                <h5 class="fw-bold">Data Belum Tersedia</h5>
                <p class="mb-0">Silakan pilih responden di dashboard dan klik "Eksekusi Terpilih" untuk melihat hasil di sini.</p>
            </div>
        @else
            <div class="row">
                {{-- Bobot Kriteria --}}
                <div class="col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white py-3">
                            <h5 class="fw-bold mb-0">Bobot Kriteria (AHP)</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Kriteria</th>
                                            <th class="text-end">Bobot</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($data['weights'] as $w)
                                        <tr>
                                            <td class="fw-medium">{{ $w['name'] }}</td>
                                            <td class="text-end fw-bold text-primary">{{ number_format($w['weight'], 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="alert alert-info py-2 px-3 mt-3 mb-0 small">
                                <strong>Consistency Ratio (CR):</strong> {{ number_format($data['cr'], 3) }}
                                <br>
                                <span class="badge {{ $data['cr'] < 0.1 ? 'bg-success' : 'bg-danger' }} mt-1">
                                    {{ $data['cr'] < 0.1 ? 'Matriks Konsisten' : 'Tidak Konsisten' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Hasil Ranking --}}
                <div class="col-lg-8">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white py-3">
                            <h5 class="fw-bold mb-0">Peringkat Varietas Blewah (CoCoSo)</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover text-center align-middle">
                                    <thead class="bg-primary text-white">
                                        <tr>
                                            <th width="10%">Rank</th>
                                            <th class="text-start">Nama Varietas</th>
                                            <th>Nilai $S_i$</th>
                                            <th>Nilai $P_i$</th>
                                            <th class="bg-dark">Skor Akhir ($Q_i$)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($data['ranking'] as $index => $res)
                                        <tr>
                                            <td>
                                                <span class="badge {{ $index == 0 ? 'bg-warning' : ($index < 3 ? 'bg-success' : 'bg-secondary') }} rounded-pill px-3">
                                                    {{ $index + 1 }}
                                                </span>
                                            </td>
                                            <td class="fw-bold text-start text-dark">{{ $res['name'] }}</td>
                                            <td>{{ number_format($res['si'], 4) }}</td>
                                            <td>{{ number_format($res['pi'], 4) }}</td>
                                            <td class="fw-bold text-primary bg-light-primary">{{ number_format($res['qi'], 3) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-4 p-3 bg-light rounded">
                                <h6 class="fw-bold mb-2"><i class="ti ti-info-circle me-1"></i> Kesimpulan Kalkulasi:</h6>
                                <p class="mb-0 small text-muted">
                                    Berdasarkan penilaian dari <strong>{{ $data['total_responden'] }} responden</strong>, varietas 
                                    <strong class="text-primary">{{ $data['ranking'][0]['name'] }}</strong> menduduki peringkat pertama 
                                    dengan skor evaluasi tertinggi sebesar <strong>{{ number_format($data['ranking'][0]['qi'], 3) }}</strong>.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
    .bg-primary { background: linear-gradient(135deg, #1a73e8 0%, #0d47a1 100%) !important; }
    .bg-light-primary { background-color: #e8f0fe !important; }
    .text-primary { color: #1a73e8 !important; }
</style>
@endpush
