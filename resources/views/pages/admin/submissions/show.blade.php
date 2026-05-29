@extends('layouts.app')

@section('title', 'Detail Pengajuan User')

@section('content')
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="fw-bold mb-0">Pengajuan: {{ $submission->title }} (#{{ $submission->id }})</h4>
                    <div>
                        @if($submission->status == 'pending')
                            <span class="badge bg-warning px-3 py-2">Menunggu Diproses</span>
                        @else
                            <span class="badge bg-success px-3 py-2">Selesai</span>
                        @endif
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">Pemohon:</p>
                        <p class="fw-bold mb-0">{{ $submission->user->name }} ({{ $submission->user->email }})</p>
                        <p class="text-muted small">Diajukan pada: {{ $submission->created_at->format('d F Y H:i') }}</p>
                    </div>
                    <div class="col-md-12 mt-3">
                        <p class="mb-1 text-muted">Deskripsi Masalah:</p>
                        <div class="p-3 bg-light rounded border">
                            {{ $submission->description ?? 'Tidak ada deskripsi.' }}
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        @if($submission->status == 'pending')
                            <form action="{{ route('admin.submissions.auto_process', $submission->id) }}" method="POST" class="d-inline" id="autoProcessForm_{{ $submission->id }}">
                                @csrf
                                <button type="submit" class="btn btn-success px-4 py-2" onclick="handleAutoProcess({{ $submission->id }})">
                                    <i class="ti ti-sparkles me-1"></i> Auto-Process (AHP + CoCoSo)
                                </button>
                            </form>
                            <a href="{{ route('admin.submissions.input_result', $submission->id) }}" class="btn btn-primary px-4 py-2">
                                <i class="ti ti-edit me-1"></i> Input Hasil Manual
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if($submission->comparisons->count() > 0 || $submission->scores->count() > 0)
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white p-3">
                        <h6 class="fw-bold mb-0">Data Perbandingan Kriteria (AHP)</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Kriteria 1</th>
                                        <th class="text-center">Nilai</th>
                                        <th>Kriteria 2</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($submission->comparisons as $comp)
                                        <tr>
                                            <td>{{ $comp->criteria1->name }} <span class="text-muted small">({{ ucfirst($comp->criteria1->type) }})</span></td>
                                            <td class="text-center fw-bold">{{ number_format($comp->value, 3) }}</td>

                                            <td>{{ $comp->criteria2->name }} <span class="text-muted small">({{ ucfirst($comp->criteria2->type) }})</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white p-3">
                        <h6 class="fw-bold mb-0">Data Penilaian Alternatif (CoCoSo)</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Alternatif</th>
                                        <th>Kriteria</th>
                                        <th class="text-center">Nilai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($submission->scores as $score)
                                        <tr>
                                            <td>{{ $score->alternative->name }}</td>
                                            <td>{{ $score->criteria->name }} <span class="text-muted small">({{ ucfirst($score->criteria->type) }})</span></td>
                                            <td class="text-center fw-bold">{{ number_format($score->value, 2) }}</td>

                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($submission->status == 'processed' && $submission->result_data)
            <div class="card shadow-sm border-success mb-4">
                <div class="card-header bg-success text-white p-3">
                    <h5 class="mb-0 fw-bold">Hasil Perankingan Akhir</h5>
                </div>
                <div class="card-body p-4">
                    @if(isset($submission->result_data['manual_text']))
                        <div class="mb-4">
                            <h6 class="fw-bold text-success"><i class="ti ti-notes me-1"></i> Kesimpulan Admin:</h6>
                            <div class="p-3 bg-success-subtle rounded border border-success-subtle">
                                {!! nl2br(e($submission->result_data['manual_text'])) !!}
                            </div>
                        </div>
                        <hr>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-hover text-center mb-0">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th class="text-start">Alternatif</th>
                                    <th>Qi (Score)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($submission->result_data['ranking'] as $index => $res)
                                    <tr>
                                        <td>#{{ $index + 1 }}</td>
                                        <td class="text-start fw-bold">{{ $res['name'] }}</td>
                                        <td class="fw-bold text-success">{{ number_format($res['qi'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <div class="text-start mb-5">
            <a href="{{ route('admin.submissions.index') }}" class="btn btn-light">Kembali</a>
        </div>
    </div>
</div>

<script>
function handleAutoProcess(id) {
    const form = document.getElementById('autoProcessForm_' + id);
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...';
    
    form.onsubmit = function(e) {
        e.preventDefault();
    };
    
    fetch('/admin/submissions/' + id + '/auto-process', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Sukses: ' + data.message);
            location.reload();
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-sparkles me-1"></i> Auto-Process (AHP + CoCoSo)';
            alert('Error: ' + (data.message || 'Terjadi kesalahan'));
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-sparkles me-1"></i> Auto-Process (AHP + CoCoSo)';
        alert('Error: ' + error.message);
    });
}
</script>
@endsection
