<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuesioner - SPK Bibit Blewah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Google Sans', 'Arial', sans-serif;
            background-color: #f8f9fa;
        }
        .landing-container {
            max-width: 600px;
            margin: 60px auto;
            padding: 32px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 4px 6px rgba(0,0,0,0.1);
        }
        .landing-title {
            font-size: 28px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
        }
        .landing-description {
            font-size: 16px;
            color: #5f6368;
            margin-bottom: 32px;
        }
        .btn-start {
            background-color: #4285f4;
            color: white;
            border: none;
            padding: 12px 32px;
            font-size: 16px;
            font-weight: 500;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-start:hover {
            background-color: #3367d6;
        }
    </style>
</head>
<body>
    <div class="landing-container text-center">
        <h1 class="landing-title">Kuesioner DSS Bibit Blewah</h1>
        <p class="landing-description">Isi kuesioner ini untuk mendapatkan rekomendasi varietas blewah terbaik berdasarkan kriteria pertanian menggunakan metode AHP dan CoCoSo.</p>
        <a href="{{ route('kuisioner.create') }}" class="btn-start">Mulai Kuesioner</a>
    </div>
</body>
</html>