<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuesioner DSS - SPK Bibit Blewah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Google Sans', 'Arial', sans-serif;
            background-color: #f8f9fa;
            color: #202124;
        }
        .google-form-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 32px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 4px 6px rgba(0,0,0,0.1);
        }
        .form-title {
            font-size: 28px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
        }
        .form-description {
            font-size: 16px;
            color: #5f6368;
            margin-bottom: 24px;
        }
        .btn-primary {
            background-color: #4285f4;
            border-color: #4285f4;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 500;
            border-radius: 4px;
        }
        .btn-primary:hover {
            background-color: #3367d6;
            border-color: #3367d6;
        }
        .btn-secondary {
            background-color: #5f6368;
            border-color: #5f6368;
            padding: 12px 24px;
            font-size: 16px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="google-form-container text-center">
        <h1 class="form-title">Kuesioner DSS</h1>
        <p class="form-description">Sistem Pendukung Keputusan untuk Rekomendasi Varietas Blewah</p>
        
        <form action="{{ route('kuisioner.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="form-label">Nama Projek Kuesioner</label>
                <input type="text" name="nama_projek" class="form-control" placeholder="Contoh: Kuesioner Blewah Kecamatan A" required>
            </div>
            <button type="submit" class="btn btn-primary">Mulai Kuesioner</button>
        </form>
    </div>
</body>
</html>