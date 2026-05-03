@extends('layouts.app')

@section('title', 'Detail Pengajuan')

@section('content')
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="fw-bold mb-0">Pengajuan: {{ $submission->title }} (#{{ $submission->id }})</h4>
                    <div class="d-flex align-items-center gap-2">
                        @if($submission->status == 'pending')
                            <span class="badge bg-warning px-3 py-2">Menunggu Admin</span>
                        @else
                            <span class="badge bg-success px-3 py-2">Selesai Diproses</span>
                        @endif
                        @if($submission->status == 'processed')
                            <a href="{{ route('user.submission.resend', $submission->id) }}" 
                               class="btn btn-outline-primary btn-sm"
                               onclick="return confirm('Kirim ulang pengajuan ini? Hasil perhitungan akan dihapus dan Anda bisa mengedit nilai sebelum dikirim kembali ke admin.')">
                                <i class="ti ti-refresh me-1"></i> Kirim Ulang
                            </a>
                        @endif
                        <form action="{{ route('user.submission.destroy', $submission->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengajuan ini? Semua data terkait akan hilang.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="ti ti-trash me-1"></i> Hapus
                            </button>
                        </form>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">Tanggal Pengajuan:</p>
                        <p class="fw-bold">{{ $submission->created_at->format('d F Y H:i') }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">Pemohon:</p>
                        <p class="fw-bold">{{ $submission->user->name }}</p>
                    </div>
                    <div class="col-md-12 mt-3">
                        <p class="mb-1 text-muted">Deskripsi Masalah:</p>
                        <div class="p-3 bg-light rounded border">
                            {{ $submission->description ?? 'Tidak ada deskripsi.' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($submission->status == 'processed' && $submission->result_data)
            @if(isset($submission->result_data['manual_text']))
                <div class="card shadow-sm border-success mb-4">
                    <div class="card-header bg-success text-white p-3">
                        <h5 class="mb-0 fw-bold">Kesimpulan</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="p-3 bg-success-subtle rounded border border-success-subtle">
                            {!! nl2br(e($submission->result_data['manual_text'])) !!}
                        </div>
                    </div>
                </div>
            @endif

            @if(isset($submission->result_data['weights']) || isset($submission->result_data['cr']))
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white p-3">
                        <h5 class="mb-0 fw-bold">Hasil Pembobotan Kriteria (AHP)</h5>
                    </div>
                    <div class="card-body p-4">
                        @if(isset($submission->result_data['weights']))
                            <div class="table-responsive mb-3">
                                <table class="table table-bordered table-hover text-center align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Kriteria</th>
                                            <th>Bobot (Weight)</th>
                                            <th>Persentase</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($submission->result_data['weights'] as $w)
                                            <tr>
                                                <td class="text-start fw-bold">{{ $w['name'] }}</td>
                                                <td>{{ number_format($w['weight'], 2) }}</td>
                                                <td>{{ number_format($w['weight'] * 100, 2) }}%</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if(isset($submission->result_data['cr']) || isset($submission->result_data['ci']) || isset($submission->result_data['ri']))
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <h6 class="fw-bold mb-3">Nilai Konsistensi:</h6>
                                </div>
                                @if(isset($submission->result_data['n_criteria']))
                                <div class="col-md-3">
                                    <div class="p-3 bg-light rounded border text-center">
                                        <p class="mb-1 text-muted small">Jumlah Kriteria</p>
                                        <p class="mb-0 fw-bold fs-5">{{ $submission->result_data['n_criteria'] }}</p>
                                    </div>
                                </div>
                                @endif
                                @if(isset($submission->result_data['ci']))
                                <div class="col-md-3">
                                    <div class="p-3 bg-light rounded border text-center">
                                        <p class="mb-1 text-muted small">Consistency Index (CI)</p>
                                        <p class="mb-0 fw-bold fs-5">{{ number_format($submission->result_data['ci'], 6) }}</p>
                                    </div>
                                </div>
                                @endif
                                @if(isset($submission->result_data['ri']))
                                <div class="col-md-3">
                                    <div class="p-3 bg-light rounded border text-center">
                                        <p class="mb-1 text-muted small">Random Index (RI)</p>
                                        <p class="mb-0 fw-bold fs-5">{{ number_format($submission->result_data['ri'], 2) }}</p>
                                    </div>
                                </div>
                                @endif
                                @if(isset($submission->result_data['cr']))
                                <div class="col-md-3">
                                    <div class="p-3 {{ $submission->result_data['cr'] < 0.1 ? 'bg-success-subtle border-success' : 'bg-danger-subtle border-danger' }} rounded border text-center">
                                        <p class="mb-1 text-muted small">Consistency Ratio (CR)</p>
                                        <p class="mb-0 fw-bold fs-5">{{ number_format($submission->result_data['cr'], 4) }}</p>
                                        <small class="{{ $submission->result_data['cr'] < 0.1 ? 'text-success' : 'text-danger' }}">
                                            {{ $submission->result_data['cr'] < 0.1 ? '(Konsisten)' : '(Tidak Konsisten)' }}
                                        </small>
                                    </div>
                                </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if(isset($submission->result_data['ranking']))
                <div class="card shadow-sm border-success mb-4">
                    <div class="card-header bg-success text-white p-3">
                        <h5 class="mb-0 fw-bold">Hasil Perankingan (CoCoSo)</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-hover text-center align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="10%">Peringkat</th>
                                        <th class="text-start">Alternatif</th>
                                        <th width="12%">Si</th>
                                        <th width="12%">Pi</th>
                                        <th width="10%">Ka</th>
                                        <th width="10%">Kb</th>
                                        <th width="10%">Kc</th>
                                        <th width="12%">Skor Akhir (Qi)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($submission->result_data['ranking'] as $index => $res)
                                        <tr>
                                            <td>
                                                <span class="badge {{ $index == 0 ? 'bg-warning text-dark' : 'bg-light text-dark' }} px-3">
                                                    #{{ $res['rank'] ?? ($index + 1) }}
                                                </span>
                                            </td>
                                            <td class="text-start fw-bold text-dark">{{ $res['name'] }}</td>
                                            <td>{{ isset($res['si']) ? number_format($res['si'], 4) : '-' }}</td>
                                            <td>{{ isset($res['pi']) ? number_format($res['pi'], 4) : '-' }}</td>
                                            <td>{{ isset($res['ka']) ? number_format($res['ka'], 4) : '-' }}</td>
                                            <td>{{ isset($res['kb']) ? number_format($res['kb'], 4) : '-' }}</td>
                                            <td>{{ isset($res['kc']) ? number_format($res['kc'], 4) : '-' }}</td>
                                            <td class="fw-bold text-success">{{ number_format($res['qi'], 4) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-light p-3">
                        <p class="mb-0 small text-muted">
                            <strong>Keterangan:</strong> 
                            Si = Weighted Sum, Pi = Weighted Product, Ka = Max Strategy, Kb = Relative Advantage, Kc = Average Advantage, Qi = Combined Score<br>
                            <strong>Catatan:</strong> 
                            @if(isset($submission->result_data['manual']) && $submission->result_data['manual'])
                                Hasil ini telah ditinjau dan divalidasi secara manual oleh Admin.
                            @else
                                Hasil ini dihitung otomatis menggunakan metode AHP untuk pembobotan kriteria dan CoCoSo untuk perankingan.
                            @endif
                        </p>
                    </div>
                </div>
            @endif
        @else
            <div class="alert alert-info py-4 text-center shadow-sm">
                <i class="ti ti-clock fs-2 d-block mb-3"></i>
                <h5>Data sedang menunggu antrian untuk diproses oleh Admin.</h5>
                <p class="mb-0">Silakan cek kembali secara berkala untuk melihat hasil perhitungan.</p>
            </div>
        @endif

        <div class="text-start mb-5">
            <a href="{{ route('user.dashboard') }}" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i> Kembali ke Dashboard</a>
        </div>
    </div>
</div>
@endsection
