@extends('layouts.app')

@section('title', 'Perhitungan AHP')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <h5 class="page-title mb-4">Perhitungan AHP (Pembobotan Kriteria)</h5>
        
        <form action="{{ route('admin.ahp.calculate') }}" method="POST">
          @csrf
          <div class="alert alert-info border-0 shadow-none mb-4">
            <h6 class="alert-heading fw-bold">Input Perbandingan Berpasangan (Pairwise Comparison)</h6>
            <p class="mb-0 small">Bandingkan kepentingan kriteria satu dengan kriteria lainnya.</p>
            
            <div class="mt-2 p-2 bg-light rounded">
              <strong>Skala Kepentingan Saaty (1-9):</strong>
              <table class="table table-sm table-bordered mb-0 mt-2">
                <tr class="table-light">
                  <td class="text-center"><strong>1</strong></td>
                  <td>Sama Penting - Kedua kriteria sama pentingnya</td>
                  <td class="text-center"><strong>6</strong></td>
                  <td>Antara 5 dan 7</td>
                </tr>
                <tr class="table-light">
                  <td class="text-center"><strong>2</strong></td>
                  <td>Antara 1 dan 3</td>
                  <td class="text-center"><strong>7</strong></td>
                  <td>Sangat Lebih Penting - Kriteria 1 lebih penting sekali</td>
                </tr>
                <tr class="table-light">
                  <td class="text-center"><strong>3</strong></td>
                  <td>Sedikit Lebih Penting - Kriteria 1 sedikit lebih penting</td>
                  <td class="text-center"><strong>8</strong></td>
                  <td>Antara 7 dan 9</td>
                </tr>
                <tr class="table-light">
                  <td class="text-center"><strong>4</strong></td>
                  <td>Antara 3 dan 5</td>
                  <td class="text-center"><strong>9</strong></td>
                  <td>Mutlak Lebih Penting - Kriteria 1 mutlak lebih penting</td>
                </tr>
                <tr class="table-light">
                  <td class="text-center"><strong>5</strong></td>
                  <td>Lebih Penting - Kriteria 1 lebih penting</td>
                  <td class="text-center"><strong>0.1-0.9</strong></td>
                  <td>Kebalikan - Kriteria 2 lebih penting</td>
                </tr>
              </table>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered text-center align-middle">
              <thead class="bg-light">
                <tr>
                  <th width="25%">Kriteria 1</th>
                  <th width="50%">Nilai Perbandingan</th>
                  <th width="25%">Kriteria 2</th>
                </tr>
              </thead>
              <tbody>
                @if($criteria->count() < 2)
                  <tr>
                    <td colspan="3" class="text-center py-4 text-muted">
                      <i class="ti ti-info-circle d-block mb-2" style="font-size: 2rem;"></i>
                      Belum ada kriteria atau kriteria kurang dari 2.<br>
                      <a href="{{ route('admin.criteria.index') }}" class="btn btn-sm btn-outline-primary mt-2">Tambah Kriteria</a>
                    </td>
                  </tr>
                @else
                @foreach($criteria as $i => $c1)
                  @foreach($criteria as $j => $c2)
                    @if($i < $j)
                    <tr>
                      <td class="fw-bold">{{ $c1->name }}</td>
                      <td>
                        <input 
                          type="number" 
                          step="0.0000000001" 
                          min="0.1" 
                          max="9" 
                          name="comparison[{{ $c1->id }}][{{ $c2->id }}]" 
                          class="form-control form-control-sm d-inline-block w-25 text-center"
                          value="{{ isset($savedComparisons[$c1->id][$c2->id]) ? $savedComparisons[$c1->id][$c2->id] : 1 }}"
                          placeholder="0.1-9"
                        >
                        <small class="text-muted d-block">Semakin tinggi = {{ $c1->name }} semakin penting</small>
                      </td>
                      <td class="fw-bold">{{ $c2->name }}</td>
                    </tr>
                    @endif
                  @endforeach
                @endforeach
                @endif
              </tbody>
            </table>
          </div>
          <div class="text-end mt-3">
            <button type="submit" class="btn btn-primary px-4">Hitung Bobot</button>
          </div>
        </form>

        @if($results)
        <hr class="my-5">
        <h6 class="fw-bold mb-3">Hasil Perhitungan Bobot</h6>
        <div class="table-responsive">
          <table class="table table-bordered text-center">
            <thead class="bg-primary text-white">
              <tr>
                <th>Kriteria</th>
                @foreach($results['criteria'] as $c)
                <th>{{ $c->name }}</th>
                @endforeach
                <th class="bg-dark">Bobot (Eigenvector)</th>
              </tr>
            </thead>
            <tbody>
              @foreach($results['criteria'] as $i => $c)
              <tr>
                <td class="fw-bold bg-light">{{ $c->name }}</td>
                @foreach($results['criteria'] as $j => $c2)
                <td>{{ rtrim(rtrim(number_format($results['matrix'][$i][$j], 10), '0'), '.') }}</td>
                @endforeach
                <td class="fw-bold bg-light-primary">{{ rtrim(rtrim(number_format($results["weightsIndexed"][$i], 2), '0'), '.') }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="row mt-4">
          <div class="col-md-6">
            <div class="alert {{ $results['cr'] < 0.1 ? 'alert-success' : 'alert-danger' }} border-0 shadow-none">
              <strong>Lambda Max (λ max):</strong> {{ rtrim(rtrim(number_format($results['lambdaMax'], 10), '0'), '.') }}
              <br>
              <strong>Consistency Index (CI):</strong> {{ rtrim(rtrim(number_format($results['ci'], 10), '0'), '.') }}
              <br>
              <strong>Random Index (RI):</strong> {{ rtrim(rtrim(number_format($results['ri'], 10), '0'), '.') }}
              <br>
              <strong>Consistency Ratio (CR):</strong> {{ rtrim(rtrim(number_format($results['cr'], 10), '0'), '.') }}
              <br>
              <small>{{ $results['cr'] < 0.1 ? '✓ CR < 0.1 - Matriks Konsisten (Dapat Digunakan)' : '✗ CR >= 0.1 - Matriks Tidak Konsisten (Harus Diperbaiki)' }}</small>
            </div>
          </div>
          @if($results['cr'] >= 0.1)
          <div class="col-md-6">
            <div class="alert alert-warning border-0 shadow-none">
              <strong>⚠️ Peringatan:</strong>
              <br>
              <small>Nilai CR >= 0.1 menunjukkan perbandingan tidak konsisten. Hasil tidak dapat digunakan untuk CoCoSo. Silakan perbaiki perbandingan di atas.</small>
            </div>
          </div>
          @endif
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
    .bg-light-primary { background-color: #d4edda !important; }
</style>
@endpush
