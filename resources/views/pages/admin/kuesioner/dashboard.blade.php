<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kuesioner - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Google Sans', 'Arial', sans-serif;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 1200px;
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
        .card-header h4 {
            font-weight: 500;
            color: #202124;
        }
        .card-body {
            padding: 16px 24px;
        }
        .btn-success {
            background-color: #34a853;
            border-color: #34a853;
        }
        .btn-success:hover {
            background-color: #2d9a47;
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            background-color: #f2f3f5;
            color: #5f6368;
            font-weight: 500;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge {
            font-size: 12px;
            font-weight: 500;
        }
        .form-check-input:checked {
            background-color: #4285f4;
            border-color: #4285f4;
        }
    </style>
</head>
<body>
    <div class="container">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Dashboard Kuesioner</h4>
                <form action="{{ route('admin.kuesioner.eksekusi_terpilih') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Eksekusi rata-rata dari data terpilih?')">
                        <i class="bi bi-sparkles"></i> Eksekusi Terpilih
                    </button>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="40"><input type="checkbox" id="checkAll"></th>
                                <th>ID</th>
                                <th>Nama Responden</th>
                                <th>Usia</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($kuesioners as $k)
                                <tr>
                                    <td><input type="checkbox" name="kuesioner_ids[]" value="{{ $k->id }}" class="form-check-input"></td>
                                    <td>{{ $k->id }}</td>
                                    <td>{{ $k->nama_responden }}</td>
                                    <td>{{ $k->usia }} tahun</td>
                                    <td>{{ $k->created_at->format('d M Y H:i') }}</td>
                                    <td>
                                        @if($k->status == 'pending')
                                            <span class="badge bg-warning">Menunggu</span>
                                        @else
                                            <span class="badge bg-success">Selesai</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada data kuesioner.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('checkAll').addEventListener('change', function() {
            let checkboxes = document.querySelectorAll('.form-check-input');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    </script>
</body>
</html>