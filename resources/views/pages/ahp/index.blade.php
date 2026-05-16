@extends('layouts.app')

@section('title', 'Dashboard Analisis Terpadu AHP & CoCoSo')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <h5 class="page-title mb-4">Integrasi Perhitungan AHP & Perankingan CoCoSo</h5>
        
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <strong>✓ Sukses:</strong> {{ session('success') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <strong>⚠️ Error:</strong> {{ session('error') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <form action="{{ route('admin.ahp.combined-calculate') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="alert alert-info border-0 shadow-none mb-4">
            <h6 class="alert-heading fw-bold">Satu Kali Unggah untuk Seluruh Proses Kalkulasi</h6>
            <p class="mb-0 small">Unggah berkas kuesioner `.csv` Anda. Sistem akan memetakan matriks perbandingan kriteria (AHP) dan performa 5 varietas blewah (CoCoSo) secara simultan di latar belakang.</p>
          </div>

          <div class="card bg-light border-0 mb-4">
            <div class="card-body p-5 text-center">
              <div class="mb-3">
                <i class="ti ti-file-analytics text-primary" style="font-size: 4rem;"></i>
              </div>
              <h5>Pilih Berkas Kuesioner Komplet (.csv)</h5>
              <p class="text-muted small">Sistem akan otomatis mengekstrak kolom 3-17 sebagai data kriteria dan kolom 18-47 sebagai nilai alternatif.</p>
              <div class="d-inline-block w-50 my-2">
                <input type="file" name="csv_file" class="form-control" accept=".csv" required>
              </div>
            </div>
          </div>

          <div class="text-end mb-4">
            <button type="submit" class="btn btn-primary px-4 shadow-sm">Proses Data & Tampilkan Hasil</button>
          </div>
        </form>

        @if(isset($ahpResults) && $ahpResults)
        <hr class="my-5">
        <div class="d-flex align-items-center mb-3">
          <i class="ti ti-chart-bar text-success me-2" style="font-size: 1.5rem;"></i>
          <h5 class="fw-bold text-success mb-0">1. Hasil Perhitungan Bobot Kriteria (AHP)</h5>
        </div>
        <div class="table-responsive mb-3">
          <table class="table table-bordered text-center align-middle">
            <thead class="table-dark">
              <tr>
                <th>Kriteria</th>
                @foreach($ahpResults['criteria'] as $c)
                <th>{{ $c->name }}</th>
                @endforeach
                <th class="bg-success">Bobot (Eigenvector)</th>
              </tr>
            </thead>
            <tbody>
              @foreach($ahpResults['criteria'] as $i => $c)
              <tr>
                <td class="fw-bold bg-light">{{ $c->name }}</td>
                @foreach($ahpResults['criteria'] as $j => $c2)
                <td>{{ rtrim(rtrim(number_format($ahpResults['matrix'][$i][$j], 4), '0'), '.') }}</td>
                @endforeach
                <td class="fw-bold bg-light-success text-success">{{ rtrim(rtrim(number_format($ahpResults["weightsIndexed"][$i], 4), '0'), '.') }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="alert {{ $ahpResults['cr'] < 0.1 ? 'alert-success' : 'alert-danger' }} border-0 shadow-none mb-5 py-2 small">
          <strong>Consistency Ratio (CR):</strong> {{ number_format($ahpResults['cr'], 4) }} 
          ({{ $ahpResults['cr'] < 0.1 ? '✓ Matriks Konsisten' : '✗ Matriks Tidak Konsisten' }})
        </div>
        @endif

        @if(isset($cocosoResults) && $cocosoResults)
        <hr class="my-5">
        <div class="d-flex align-items-center mb-3">
          <i class="ti ti-award text-primary me-2" style="font-size: 1.5rem;"></i>
          <h5 class="fw-bold text-primary mb-0">2. Hasil Akhir Evaluasi Perankingan Varietas Blewah (CoCoSo)</h5>
        </div>
        <div class="table-responsive">
          <table class="table table-bordered table-hover text-center align-middle">
            <thead class="bg-primary text-white">
              <tr>
                <th width="8%">Peringkat</th>
                <th class="text-start">Nama Varietas Alternatif</th>
                <th>Nilai Sum ($S_i$)</th>
                <th>Nilai Power ($P_i$)</th>
                <th class="bg-dark text-white">Skor Evaluasi Akhir ($Q_i$)</th>
              </tr>
            </thead>
            <tbody>
              @foreach($cocosoResults as $index => $res)
              <tr>
                <td>
                  <span class="badge {{ $index < 3 ? 'bg-success' : 'bg-secondary' }} px-2 py-1">
                    Rank {{ $index + 1 }}
                  </span>
                </td>
                <td class="fw-bold text-start">{{ $res['alternative']->name }}</td>
                <td>{{ number_format($res['si'], 4) }}</td>
                <td>{{ number_format($res['pi'], 4) }}</td>
                <td class="table-primary fw-bold text-primary">{{ number_format($res['qi'], 4) }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @endif

      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
    .bg-primary { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important; }
    .table-primary { background-color: #e8f5e9 !important; }
    .bg-light-success { background-color: #e8f5e9 !important; }
</style>
@endpush