@extends('layouts.app')
@section('title', 'Manajemen Kuesioner')
@section('content')
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Daftar Kuesioner</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>ID</th>
                            <th>Nama Projek</th>
                            <th>Jumlah Responden</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($groups as $group)
                            <tr>
                                <td>{{ $group->id }}</td>
                                <td>{{ $group->nama_projek }}</td>
                                <td>{{ count($group->semua_jawaban) }}</td>
                                <td>
                                    @if($group->status == 'pending')
                                        <span class="badge bg-warning">Menunggu</span>
                                    @else
                                        <span class="badge bg-success">Selesai</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.kuesioner.show', $group->id) }}" class="btn btn-sm btn-light">Detail</a>
                                    @if($group->status == 'pending')
                                        <form action="{{ route('admin.kuesioner.eksekusi', $group->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Eksekusi AHP & CoCoSo untuk data ini?')">
                                                <i class="ti ti-sparkles"></i> Eksekusi
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada data kuesioner.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection