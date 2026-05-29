<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>spk - Bibit Blewah</title>
    <style>
        /* CSS yang sudah ada tetap di sini */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #fcfdfb; color: #2d3748; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        nav { background: rgba(255, 255, 255, 0.95); padding: 15px 0; position: fixed; width: 100%; top: 0; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .nav-content { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 24px; font-weight: 800; color: #16a34a; text-decoration: none; }
        .logo span { color: #f97316; }
        .nav-links a { text-decoration: none; color: #4a5568; margin-left: 25px; font-weight: 600; transition: 0.3s; }
        .hero { padding: 150px 0 80px 0; display: flex; align-items: center; gap: 50px; background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%); }
        .hero-text { flex: 1; }
        .badge { background: #ffedd5; color: #ea580c; padding: 6px 15px; border-radius: 50px; font-size: 14px; font-weight: 700; display: inline-block; margin-bottom: 20px; }
        h1 { font-size: 48px; line-height: 1.2; color: #1a202c; margin-bottom: 20px; }
        h1 span { color: #16a34a; }
        p.subtitle { font-size: 18px; color: #718096; margin-bottom: 35px; max-width: 500px; }
        .btn-primary { background: #16a34a; color: white; border: none; box-shadow: 0 4px 14px rgba(22, 163, 74, 0.3); padding: 15px 30px; border-radius: 12px; font-weight: 700; text-decoration: none; transition: 0.3s; display: inline-block; }
        .main-img { width: 100%; max-width: 500px; border-radius: 30px; box-shadow: 20px 20px 60px rgba(0,0,0,0.1); border: 8px solid white; }

        /* CSS BARU UNTUK TABEL HASIL */
        .ranking-section { padding: 60px 0; background: #fff; }
        .ranking-card { background: white; border-radius: 24px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .ranking-header { background: #16a34a; color: white; padding: 20px 30px; }
        .ranking-header h2 { margin: 0; font-size: 20px; }
        .table-responsive { padding: 20px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 15px; color: #718096; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid #f7fafc; }
        td { padding: 20px 15px; border-bottom: 1px solid #f7fafc; }
        .rank-number { background: #f0fdf4; color: #16a34a; font-weight: 800; padding: 8px 15px; border-radius: 10px; }
        .top-row { background: #f0fdf4; }
        .score-box { font-weight: 700; color: #16a34a; background: #ecfdf5; padding: 5px 12px; border-radius: 8px; }

        @media (max-width: 768px) { h1 { font-size: 32px; } .hero { flex-direction: column; text-align: center; } }
    </style>
</head>
<body>

    <nav>
        
        <div class="container nav-content">
            <a href="/" class="logo">SPK<span>-bibit</span></a>
            <div class="nav-links">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn-primary" style="padding: 8px 20px; color: white;">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-primary" style="padding: 8px 20px; color: white;">Admin</a>
                    @endauth
                @endif
            </div>
            <div class="nav-links">
               <a href="{{ route('kuisioner') }}" class="btn-primary" style="padding: 8px 20px; color: white; margin-left: 15px;"> Isi Kuisioner</a>
            </div>

            
        </div>
    </nav>

    <section class="hero">
        <div class="container" style="display: flex; align-items: center; gap: 40px; flex-wrap: wrap;">
            <div class="hero-text">
                <span class="badge">🌿 PANEN MELIMPAH</span>
                <h1>Tanam <span>Blewah</span> Unggul Dengan Sistem Pintar.</h1>
                <p class="subtitle">Kelola pembibitan blewah Anda mulai dari pemilihan kriteria hingga hasil ranking terbaik menggunakan metode AHP & COCOSO.</p>
            </div>
            <div class="hero-image">
                <img src="{{ asset('assets/images/b.png') }}" alt="Bibit Blewah" class="main-img">
            </div>
        </div>
    </section>

    <!-- BAGIAN HASIL RANKING ADMIN -->
    <!-- BAGIAN HASIL RANKING TERBARU -->
<section class="ranking-section">
    <div class="container">
        <div style="margin-bottom: 30px; text-align: center;">
            <h2 style="font-size: 32px; color: #1a202c;">Hasil Seleksi <span>Varietas Terbaik</span></h2>
            <p style="color: #718096;">Hanya menampilkan hasil ranking yang disimpan manual oleh admin.</p>
        </div>

        @if(isset($results) && count($results) > 0)
            <div class="ranking-card">
                <div class="ranking-header">
                    <h2>🏆 Tabel Rekomendasi Bibit Unggul</h2>
                </div>
                <div class="table-responsive">
                    @if(!empty($manualText))
                        <div style="padding: 20px; background: #f0fdf4; border-radius: 12px; margin-bottom: 20px; border: 1px solid #d1fae5; color: #065f46;">
                            <strong>Catatan Admin:</strong>
                            <p style="margin: 10px 0 0;">{{ $manualText }}</p>
                        </div>
                    @endif
                    <table>
                        <thead>
                            <tr>
                                <th width="8%">Rank</th>
                                <th>Nama Alternatif</th>
                                
                                <th width="16%" style="text-align: center;">Skor Akhir (Qi)</th>
                                <th width="20%" style="text-align: center;">Status Rekomendasi</th>
                            </tr>
                        </thead>
                        <tbody>
    @foreach($results as $index => $res)
        <tr class="{{ $index == 0 ? 'top-row' : '' }}">
            <td>
                <span class="rank-number" style="{{ $index == 0 ? 'background: #f97316; color: white;' : '' }}">
                    @if($index == 0)
                        🏆
                    @else
                        #{{ $index + 1 }}
                    @endif
                </span>
            </td>
            <td style="font-weight: 600;">
                {{ $res['name'] ?? ($res['alternative']->name ?? 'Varietas Tanpa Nama') }}
            </td>
         
            <td style="text-align: center;">
                <span class="score-box">
                    {{ number_format($res['qi'] ?? 0, 4) }}
                </span>
            </td>
            <td style="text-align: center;">
                @if($index == 0)
                    <span style="background: #16a34a; color: white; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; white-space: nowrap;">Sangat Direkomendasikan</span>
                @elseif($index < 3)
                    <span style="background: #3b82f6; color: white; padding: 4px 12px; border-radius: 20px; font-size: 11px; white-space: nowrap;">Direkomendasikan</span>
                @else
                    <span style="background: #94a3b8; color: white; padding: 4px 12px; border-radius: 20px; font-size: 11px; white-space: nowrap;">Alternatif Pendukung</span>
                @endif
            </td>
        </tr>
    @endforeach
</tbody>

                    </table>
                </div>
            </div>
        @else
            <div style="text-align: center; padding: 50px; background: #f8fafc; border-radius: 20px; border: 2px dashed #e2e8f0;">
                <p style="color: #a0aec0;">Belum ada data hasil perhitungan CoCoSo yang tersedia.</p>
            </div>
        @endif
    </div>
</section>


    <footer style="padding: 40px 0; text-align: center; color: #a0aec0; font-size: 14px;">
        &copy; {{ date('Y') }} SPK-Bibit Blewah. All rights reserved.
    </footer>

</body>
</html>
