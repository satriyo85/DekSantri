<?php
/**
 * DEK SANTRI - Poskestren Al Amien Kediri
 * Konfigurasi Database & Parameter Sistem
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'dek_santri_alamien');

define('APP_NAME', 'DEK SANTRI');
define('INSTANSI_NAME', 'Poskestren Al Amien Kediri');
define('INSTANSI_ALAMAT', 'Jl. KH. Hasyim Asy\'ari, Kec. Pesantren, Kota Kediri, Jawa Timur 64133');
define('INSTANSI_TELP', '(0354) 771234 / 0812-3456-7890');

$namaBulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

function formatTanggalIndo($tanggal) {
    global $namaBulan;
    if (!$tanggal || $tanggal == '0000-00-00' || $tanggal == '0000-00-00 00:00:00') return '-';
    $time = strtotime($tanggal);
    if (!$time) return '-';
    $bulanIdx = (int)date('n', $time);
    return date('d', $time) . ' ' . ($namaBulan[$bulanIdx] ?? '') . ' ' . date('Y', $time);
}

function sanitize($data) {
    if ($data === null) return '';
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

// HELPER PROTEKSI LOGIN & ROLE
function cekLogin() {
    if (!isset($_SESSION['user_poskestren'])) {
        header("Location: login.php");
        exit;
    }
}

function getPetugas() {
    return $_SESSION['user_poskestren'] ?? null;
}

function getRole() {
    return $_SESSION['user_poskestren']['profesi'] ?? '';
}

function isAdmin() {
    return getRole() === 'Admin';
}

function isNakes() {
    return in_array(getRole(), ['Dokter', 'Perawat', 'Admin']);
}

function isKader() {
    return getRole() === 'Kader Santri';
}

function proteksiRole(array $rolesDiizinkan) {
    cekLogin();
    if (!in_array(getRole(), $rolesDiizinkan)) {
        die("
            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
            <div class='container my-5 text-center'>
                <div class='alert alert-danger py-4 shadow-sm'>
                    <h4 class='fw-bold'>⛔ Akses Dibatasi!</h4>
                    <p class='mb-3'>Peran Anda (<strong>" . htmlspecialchars(getRole()) . "</strong>) tidak memiliki hak akses untuk membuka halaman ini.</p>
                    <a href='index.php' class='btn btn-outline-danger btn-sm px-4'>Kembali ke Dashboard</a>
                </div>
            </div>
        ");
    }
}

// Inisialisasi Koneksi PDO
$pdo = null;
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . htmlspecialchars($e->getMessage()));
}

// Helper Render Navbar Dinamis Berdasarkan Role
function renderNavbar($activeMenu = '') {
    $petugas = getPetugas();
    $role = getRole();
    $nama = $petugas['nama_lengkap'] ?? 'Petugas';

    echo '
    <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
                <span class="fs-4">🏥</span>
                <span>DEK SANTRI</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMain">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link ' . ($activeMenu == 'dashboard' ? 'active fw-bold' : '') . '" href="index.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link ' . ($activeMenu == 'santri' ? 'active fw-bold' : '') . '" href="santri.php">Data Santri</a></li>
                    <li class="nav-item"><a class="nav-link ' . ($activeMenu == 'rekam_medis' ? 'active fw-bold' : '') . '" href="rekam_medis.php">Rekam Medis</a></li>
                    <li class="nav-item"><a class="nav-link ' . ($activeMenu == 'obat' ? 'active fw-bold' : '') . '" href="obat.php">Farmasi & Obat</a></li>
                    <li class="nav-item"><a class="nav-link ' . ($activeMenu == 'surat_izin' ? 'active fw-bold' : '') . '" href="surat_izin.php">Surat Izin</a></li>';

                    // Menu khusus Nakes & Admin
                    if ($role === 'Admin' || $role === 'Dokter' || $role === 'Perawat') {
                        echo '
                        <li class="nav-item"><a class="nav-link ' . ($activeMenu == 'rujukan' ? 'active fw-bold' : '') . '" href="surat_rujukan.php">Rujukan</a></li>
                        <li class="nav-item"><a class="nav-link ' . ($activeMenu == 'consent' ? 'active fw-bold' : '') . '" href="informed_consent.php">Informed Consent</a></li>';
                    }

                    // Menu Laporan (Admin & Nakes)
                    if ($role === 'Admin' || $role === 'Dokter' || $role === 'Perawat') {
                        echo '
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle ' . ($activeMenu == 'laporan' ? 'active fw-bold' : '') . '" href="#" role="button" data-bs-toggle="dropdown">
                                Laporan
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="laporan_kesehatan.php?tipe=bulanan">📅 Laporan Bulanan</a></li>
                                <li><a class="dropdown-item" href="laporan_kesehatan.php?tipe=triwulan">📊 Laporan Triwulan</a></li>
                                <li><a class="dropdown-item" href="laporan_kesehatan.php?tipe=semester">🗓️ Laporan 6 Bulan</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="laporan_kesehatan.php?tipe=kustom">⚙️ Rekap Kustom & Excel</a></li>
                            </ul>
                        </li>';
                    }

    echo '      </ul>
                <div class="d-flex align-items-center gap-2">
                    <div class="text-white text-end d-none d-md-block">
                        <div class="fw-bold small">' . htmlspecialchars($nama) . '</div>
                        <span class="badge bg-light text-success fw-bold" style="font-size: 10px;">' . htmlspecialchars($role) . '</span>
                    </div>
                    <a href="logout.php" onclick="return confirm(\'Keluar dari sistem?\')" class="btn btn-outline-light btn-sm ms-2">
                        Keluar 🚪
                    </a>
                </div>
            </div>
        </div>
    </nav>';
}

function renderMobileNav($activeMenu = '') {
    echo '
    <nav class="fixed-bottom bg-white border-top py-2 d-md-none shadow">
        <div class="container d-flex justify-content-around text-center">
            <a href="index.php" class="' . ($activeMenu == 'dashboard' ? 'text-success fw-bold' : 'text-secondary') . ' text-decoration-none small">
                <div class="fs-5">🏠</div>Home
            </a>
            <a href="santri.php" class="' . ($activeMenu == 'santri' ? 'text-success fw-bold' : 'text-secondary') . ' text-decoration-none small">
                <div class="fs-5">👥</div>Santri
            </a>
            <a href="rekam_medis.php" class="' . ($activeMenu == 'rekam_medis' ? 'text-success fw-bold' : 'text-secondary') . ' text-decoration-none small">
                <div class="fs-5">🩺</div>RM
            </a>
            <a href="obat.php" class="' . ($activeMenu == 'obat' ? 'text-success fw-bold' : 'text-secondary') . ' text-decoration-none small">
                <div class="fs-5">💊</div>Obat
            </a>
            <a href="logout.php" class="text-danger text-decoration-none small">
                <div class="fs-5">🚪</div>Keluar
            </a>
        </div>
    </nav>
    <style>@media(max-width:768px){body{padding-bottom:70px;}}</style>';
}
// Helper Render Footer & Copyright Resmi
function renderFooter() {
    $tahun = date('Y');
    echo '
    <footer class="bg-white border-top py-3 mt-5 text-center text-muted small">
        <div class="container">
            <div>
                &copy; ' . $tahun . ' <strong>' . APP_NAME . '</strong> - ' . INSTANSI_NAME . '. All Rights Reserved.
            </div>
            <div style="font-size: 11px;" class="text-secondary mt-1">
                Sistem Rekam Medis & Manajemen Pelayanan Kesehatan Santri Pondok Pesantren Al Amien Kediri
            </div>
        </div>
    </footer>';
}
?>