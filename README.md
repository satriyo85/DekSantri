# PANDUAN LENGKAP MENJALANKAN APLIKASI DEK SANTRI (PHP & MySQL)
**Pos Kesehatan Pondok Pesantren Al Amien Kediri**

Aplikasi **DEK SANTRI** dibangun menggunakan **PHP Native (PDO)**, **MySQL**, dan antarmuka responsif **Bootstrap 5** (ringan dan langsung jalan tanpa perlu instal Node.js / Composer di server lokal).

---

## ⚠️ Mengapa Sebelumnya "Tidak Muncul" di Komputer Lokal?
Jika sebelumnya Anda membuka aplikasi di browser dan muncul **halaman putih kosong (blank screen)**, **Not Found 404**, atau **koneksi error**, berikut penyebab utamanya:
1. **File yang belum lengkap**: File navigasi seperti `santri.php`, `obat.php`, dan `surat_izin.php` sebelumnya belum tersedia di folder.
2. **Database belum dibuat di MySQL**: Database `dek_santri_alamien` belum dibuat atau belum di-import melalui phpMyAdmin.
3. **Display Errors PHP belum aktif**: Di beberapa instalasi XAMPP, konfigurasi `php.ini` mematikan `display_errors` secara bawaan sehingga pesan error tidak tampil melainkan layar putih. (Sekarang di `config.php` sudah kami aktifkan otomatis `ini_set('display_errors', 1)` beserta panduan setup interaktif!).

---

## 🚀 Langkah Instalasi & Menjalankan (100% Berhasil)

### 1. Buka XAMPP Control Panel
- Jalankan aplikasi **XAMPP Control Panel**.
- Klik tombol **Start** pada modul **Apache**.
- Klik tombol **Start** pada modul **MySQL**.
- Pastikan kedua tombol berubah menjadi hijau / berstatus *Running*.

### 2. Pindahkan Seluruh File ke Folder `htdocs`
- Buat folder baru bernama: `dek_santri` di dalam direktori web server Anda:
  - **XAMPP Windows**: `C:\xampp\htdocs\dek_santri\`
  - **Laragon**: `C:\laragon\www\dek_santri\`
- Masukkan semua file berikut ke dalam folder `dek_santri` tersebut:
  1. `config.php` *(Konfigurasi database & navbar)*
  2. `index.php` *(Dashboard utama)*
  3. `santri.php` *(Data induk santri & KBS)*
  4. `rekam_medis.php` *(Pemeriksaan klinis, TTV, ICD-10 & resep)*
  5. `obat.php` *(Inventaris obat & peringatan stok menipis)*
  6. `surat_izin.php` *(Surat izin sakit & istirahat)*
  7. `laporan_kesehatan.php` *(Laporan bulanan/triwulan/semester & Excel)*
  8. `cetak_surat.php` *(Cetak Surat Izin Sakit Kop Al Amien)*
  9. `cetak_rujukan.php` *(Cetak Surat Rujukan Faskes/RS)*
  10. `cetak_informed_consent.php` *(Cetak Informed Consent santri <18 th)*
  11. `kartu_santri.php` *(Cetak Kartu Berobat Santri)*
  12. `dek_santri_alamien.sql` *(Database & data awal)*

### 3. Buat Database & Import File SQL
1. Buka browser (Chrome / Edge / Firefox).
2. Kunjungi alamat: **`http://localhost/phpmyadmin`**
3. Di panel sebelah kiri, klik **Baru (New)**.
4. Masukkan nama database: `dek_santri_alamien` lalu klik **Buat (Create)**.
5. Klik database `dek_santri_alamien` yang baru dibuat tersebut.
6. Klik tab **Import** pada menu atas.
7. Klik **Pilih Berkas (Choose File)**, lalu arahkan ke file `dek_santri_alamien.sql`.
8. Gulir ke bawah dan klik tombol **Kirim / Go**.
9. Muncul notifikasi hijau: *"Impor telah selesai dengan sukses"*.

### 4. Buka Aplikasi di Browser
- Buka browser dan ketik alamat:
  👉 **`http://localhost/dek_santri/`**
- Aplikasi dashboard DEK SANTRI akan langsung tampil lengkap dengan data santri, obat, grafik morbiditas, dan menu cetak surat!

---

## 📱 Akses dari HP / Smartphone di Pesantren
1. Hubungkan komputer server XAMPP dan HP ke jaringan Wi-Fi pesantren yang sama.
2. Cek alamat IP lokal komputer Anda di Command Prompt (`ipconfig`), contoh: `192.168.1.15`.
3. Buka browser di HP dan ketik: **`http://192.168.1.15/dek_santri/`**
4. Tampilan akan otomatis menyesuaikan layar ponsel dengan menu navigasi bawah (*mobile bottom navigation*) yang nyaman digunakan oleh nakes dan pengurus asrama.
