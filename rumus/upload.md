Untuk membuat fitur upload file Excel (.xlsx) atau CSV agar tidak perlu input manual satu per satu, Anda bisa menggunakan bantuan package pihak ketiga yang sangat populer di ekosistem Laravel, yaitu Laravel Excel (maatwebsite/excel).

Berikut adalah gambaran langkah-langkah yang bisa Anda pakai untuk menerapkannya:

1. Install Package Laravel Excel
Ini adalah library yang paling mudah digunakan untuk membaca (import) dan menulis (export) file Excel/CSV di Laravel. Anda akan menjalankannya melalui terminal di dalam folder proyek Anda:

bash
composer require maatwebsite/excel
2. Sediakan Format/Template Excel yang Baku
Sistem butuh format standar agar bisa membacanya dengan tepat. Anda bisa membuat file template Excel (misalnya template_ahp_cocoso.xlsx) yang bisa didownload oleh user. Contoh formatnya bisa berupa matriks:

Kolom A: Nama Alternatif
Kolom B, C, D, dst: Kriteria 1, Kriteria 2, Kriteria 3, dst.
3. Buat Class Import
Laravel Excel menggunakan class khusus untuk mengatur cara membaca file. Anda akan membuat class Import (misalnya AhpCocosoImport):

bash
php artisan make:import AhpCocosoImport
Di dalam class ini app/Imports/AhpCocosoImport.php, Anda akan menulis logika untuk memetakan setiap baris Excel menjadi data yang akan dimasukkan ke database (seperti tabel alternatives dan evaluation_matrices atau sejenisnya).

4. Buat Form Upload di Blade View
Tambahkan form upload di view Anda (misalnya di 
resources/views/pages/ranking/index.blade.php
 atau di halaman project). Form ini harus bertipe multipart/form-data.

html
<form action="{{ route('import.excel') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="file" name="file" accept=".xlsx, .csv" required>
    <button type="submit">Upload & Hitung</button>
</form>
5. Buat Route dan Controller Method
Tambahkan route di 
routes/web.php
 untuk menangani submit form tersebut:

php
Route::post('/import-ahp-cocoso', [RankingController::class, 'importExcel'])->name('import.excel');
Lalu, buat fungsinya di controller untuk mengeksekusi import dan kemudian memanggil service perhitungan AHP / CoCoSo yang sudah ada sebelumnya.

php
public function importExcel(Request $request)
{
    // 1. Validasi file
    $request->validate(['file' => 'required|mimes:xlsx,csv']);
    // 2. Lakukan import data dari file ke Database melalui class Import
    Excel::import(new AhpCocosoImport, $request->file('file'));
    // 3. Setelah data masuk ke database, panggil fungsi perhitungan AHP & CoCoSo
    // ... panggil service AHP & Cocoso Anda di sini ...
    // 4. Return kembali ke halaman ranking beserta pesan sukses
    return redirect()->back()->with('success', 'Data berhasil diimport dan dihitung!');
}
6. Integrasi dengan Sistem Multi-Role (Opsional/Sesuai Sistem Anda)
Jika proses ini dilakukan oleh "User" dalam sebuah project (berdasarkan riwayat proyek Anda), pastikan file yang diupload tersebut dikaitkan (di-attach) ke project_id yang sedang aktif sehingga datanya tidak bercampur dengan project milik orang lain.

Apakah Anda ingin saya bantu membuatkan file template, form, dan logika import ini secara langsung ke dalam kode sistem Anda sekarang? Jika iya, silakan konfirmasi dan beri tahu saya dari mana kita akan mulai (misalnya di halaman mana form upload ini mau diletakkan).

Good
Bad
Bad response
