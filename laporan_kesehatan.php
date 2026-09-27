<?php
require_once 'config.php';
cekLogin();
proteksiRole(['Admin', 'Dokter', 'Perawat']);

// Filter Periode
$tipe = sanitize($_GET['tipe'] ?? 'bulanan');
$tahun = intval($_GET['tahun'] ?? date('Y'));
$bulan = intval($_GET['bulan'] ?? date('n'));
$triwulan = intval($_GET['triwulan'] ?? 3);
$semester = intval($_GET['semester'] ?? 2);
$tglMulaiKustom = sanitize($_GET['tgl_mulai'] ?? date('Y-m-01'));
$tglAkhirKustom = sanitize($_GET['tgl_akhir'] ?? date('Y-m-d'));

$tglAwal = '';
$tglAkhir = '';
$labelPeriode = '';

if ($tipe === 'bulanan') {
    $tglAwal = sprintf("%04d-%02d-01", $tahun, $bulan);
    $lastDay = date('t', strtotime($tglAwal));
    $tglAkhir = sprintf("%04d-%02d-%02d", $tahun, $bulan, $lastDay);
    $labelPeriode = "Bulan " . ($namaBulan[$bulan] ?? '') . " $tahun";
} elseif ($tipe === 'triwulan') {
    if ($triwulan == 1) {
        $tglAwal = "$tahun-01-01"; $tglAkhir = "$tahun-03-31"; $labelPeriode = "Triwulan I (Jan-Mar) $tahun";
    } elseif ($triwulan == 2) {
        $tglAwal = "$tahun-04-01"; $tglAkhir = "$tahun-06-30"; $labelPeriode = "Triwulan II (Apr-Jun) $tahun";
    } elseif ($triwulan == 3) {
        $tglAwal = "$tahun-07-01"; $tglAkhir = "$tahun-09-30"; $labelPeriode = "Triwulan III (Jul-Sep) $tahun";
    } else {
        $tglAwal = "$tahun-10-01"; $tglAkhir = "$tahun-12-31"; $labelPeriode = "Triwulan IV (Okt-Des) $tahun";
    }
} elseif ($tipe === 'semester') {
    if ($semester == 1) {
        $tglAwal = "$tahun-01-01"; $tglAkhir = "$tahun-06-30"; $labelPeriode = "Semester 1 (Jan-Jun) $tahun";
    } else {
        $tglAwal = "$tahun-07-01"; $tglAkhir = "$tahun-12-31"; $labelPeriode = "Semester 2 (Jul-Des) $tahun";
    }
} elseif ($tipe === 'kustom') {
    $tglAwal = $tglMulaiKustom;
    $tglAkhir = $tglAkhirKustom;
    $labelPeriode = "Rentang $tglMulaiKustom sd $tglAkhirKustom";
} else {
    $tglAwal = '2020-01-01';
    $tglAkhir = '2030-12-31';
    $labelPeriode = "Semua Periode";
}

// EKSPOR EXCEL (DIPERBAIKI: MENGGUNAKAN CSV DENGAN BOM UTF-8 RESMI AGAR TIDAK ERROR/MISMATCH)
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    // Bersihkan buffer output sebelum download
    if (ob_get_level()) ob_end_clean();

    $namaFile = "Laporan_Poskestren_" . preg_replace('/[^a-zA-Z0-9]/', '_', $labelPeriode) . ".csv";

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $namaFile . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Buka stream output
    $output = fopen('php://output', 'w');

    // Tambahkan Byte Order Mark (BOM) UTF-8 agar Excel membuka karakter dengan benar
    fputs($output, "\xEF\xBB\xBF");

    // Ambil data kunjungan (umur dihitung langsung via TIMESTAMPDIFF)
    $stmtKunjungan = $pdo->prepare("
        SELECT rm.*, s.nis, s.nama_lengkap, s.asrama, s.kelas, s.jenis_kelamin,
               TIMESTAMPDIFF(YEAR, s.tanggal_lahir, CURDATE()) AS umur
        FROM rekam_medis rm 
        JOIN santri s ON rm.id_santri = s.id_santri 
        WHERE DATE(rm.tanggal_kunjungan) BETWEEN ? AND ? 
        ORDER BY rm.tanggal_kunjungan ASC
    ");
    $stmtKunjungan->execute([$tglAwal, $tglAkhir]);
    $kunjungan = $stmtKunjungan->fetchAll();

    // Baris Judul
    fputcsv($output, ['LAPORAN PELAYANAN KESEHATAN SANTRI - POSKESTREN AL AMIEN KEDIRI']);
    fputcsv($output, ['Periode', $labelPeriode, "Rentang: $tglAwal s.d. $tglAkhir"]);
    fputcsv($output, []); // Baris kosong

    // Header Kolom
    fputcsv($output, [
        'No', 
        'No. RM', 
        'Tanggal Kunjungan', 
        'NIS', 
        'Nama Lengkap Santri', 
        'Jenis Kelamin', 
        'Umur (Thn)', 
        'Asrama / Kamar', 
        'Diagnosa Medis', 
        'Kode ICD-10', 
        'Suhu (°C)', 
        'Tekanan Darah', 
        'Tindakan Medis'
    ]);

    // Data Baris
    $no = 1;
    foreach ($kunjungan as $k) {
        fputcsv($output, [
            $no++,
            $k['nomor_rm'],
            $k['tanggal_kunjungan'],
            "'" . $k['nis'], // Tanda petik agar angka NIS tidak diubah Excel menjadi notasi ilmiah
            $k['nama_lengkap'],
            $k['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan',
            $k['umur'],
            $k['asrama'],
            $k['diagnosis_utama'],
            $k['kode_icd10'] ?: '-',
            $k['suhu_tubuh'],
            $k['tekanan_darah'] ?: '-',
            $k['tindakan_medis']
        ]);
    }

    fclose($output);
    exit;
}

// 10 BESAR MORBIDITAS
$stmtMorbiditas = $pdo->prepare("
    SELECT diagnosis_utama, kode_icd10, COUNT(*) as jumlah 
    FROM rekam_medis 
    WHERE DATE(tanggal_kunjungan) BETWEEN ? AND ? 
    GROUP BY diagnosis_utama, kode_icd10 
    ORDER BY jumlah DESC 
    LIMIT 10
");
$stmtMorbiditas->execute([$tglAwal, $tglAkhir]);
$topMorbiditas = $stmtMorbiditas->fetchAll();

// SEBARAN KASUS PER ASRAMA
$stmtAsrama = $pdo->prepare("
    SELECT s.asrama, COUNT(*) as jumlah 
    FROM rekam_medis rm 
    JOIN santri s ON rm.id_santri = s.id_santri 
    WHERE DATE(rm.tanggal_kunjungan) BETWEEN ? AND ? 
    GROUP BY s.asrama 
    ORDER BY jumlah DESC
");
$stmtAsrama->execute([$tglAwal, $tglAkhir]);
$sebaranAsrama = $stmtAsrama->fetchAll();

// TOTAL REKAM MEDIS PERIODE INI
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM rekam_medis WHERE DATE(tanggal_kunjungan) BETWEEN ? AND ?");
$stmtTotal->execute([$tglAwal, $tglAkhir]);
$totalPeriode = (int)$stmtTotal->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kesehatan - DEK SANTRI Poskestren Al Amien</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>@media print { .no-print { display: none !important; } }</style>
</head>
<body class="bg-light">

<div class="no-print"><?php renderNavbar('laporan'); ?></div>

<main class="container my-4">
    <!-- Header Filter & Unduh -->
    <div class="card shadow-sm border-0 mb-4 no-print">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h1 class="h4 fw-bold text-dark mb-1">Laporan Pelayanan Kesehatan & Morbiditas</h1>
                    <p class="text-muted small mb-0">Poskestren Pondok Pesantren Al Amien Kediri</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'excel'])) ?>" class="btn btn-success btn-sm px-3 shadow-sm">
                        📥 Unduh Data Excel / CSV
                    </a>
                    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm px-3">
                        🖨️ Cetak Lembar Resmi
                    </button>
                </div>
            </div>

            <!-- Filter Segmentasi Periode -->
            <form method="GET" class="row g-2 align-items-center pt-3 border-top">
                <div class="col-md-3">
                    <label class="form-label small fw-bold mb-1">Tipe Periode</label>
                    <select name="tipe" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="bulanan" <?= $tipe == 'bulanan' ? 'selected' : '' ?>>📅 Bulanan</option>
                        <option value="triwulan" <?= $tipe == 'triwulan' ? 'selected' : '' ?>>📊 Triwulan (3 Bulan)</option>
                        <option value="semester" <?= $tipe == 'semester' ? 'selected' : '' ?>>🗓️ 6 Bulanan (Semester)</option>
                        <option value="kustom" <?= $tipe == 'kustom' ? 'selected' : '' ?>>⚙️ Rentang Kustom</option>
                        <option value="semua" <?= $tipe == 'semua' ? 'selected' : '' ?>>🌐 Semua Data</option>
                    </select>
                </div>

                <?php if ($tipe == 'bulanan'): ?>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold mb-1">Bulan</label>
                        <select name="bulan" class="form-select form-select-sm" onchange="this.form.submit()">
                            <?php foreach ($namaBulan as $bIdx => $bNama): ?>
                                <option value="<?= $bIdx ?>" <?= $bulan == $bIdx ? 'selected' : '' ?>><?= $bNama ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php elseif ($tipe == 'triwulan'): ?>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold mb-1">Pilihan Triwulan</label>
                        <select name="triwulan" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="1" <?= $triwulan == 1 ? 'selected' : '' ?>>Triwulan I (Jan-Mar)</option>
                            <option value="2" <?= $triwulan == 2 ? 'selected' : '' ?>>Triwulan II (Apr-Jun)</option>
                            <option value="3" <?= $triwulan == 3 ? 'selected' : '' ?>>Triwulan III (Jul-Sep)</option>
                            <option value="4" <?= $triwulan == 4 ? 'selected' : '' ?>>Triwulan IV (Okt-Des)</option>
                        </select>
                    </div>
                <?php elseif ($tipe == 'semester'): ?>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold mb-1">Pilihan Semester</label>
                        <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="1" <?= $semester == 1 ? 'selected' : '' ?>>Semester 1 / Ganjil (Jan-Jun)</option>
                            <option value="2" <?= $semester == 2 ? 'selected' : '' ?>>Semester 2 / Genap (Jul-Des)</option>
                        </select>
                    </div>
                <?php elseif ($tipe == 'kustom'): ?>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold mb-1">Mulai</label>
                        <input type="date" name="tgl_mulai" value="<?= $tglMulaiKustom ?>" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold mb-1">Sampai</label>
                        <input type="date" name="tgl_akhir" value="<?= $tglAkhirKustom ?>" class="form-control form-control-sm">
                    </div>
                <?php endif; ?>

                <div class="col-md-2">
                    <label class="form-label small fw-bold mb-1">Tahun</label>
                    <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="2025" <?= $tahun == 2025 ? 'selected' : '' ?>>2025</option>
                        <option value="2026" <?= $tahun == 2026 ? 'selected' : '' ?>>2026</option>
                        <option value="2027" <?= $tahun == 2027 ? 'selected' : '' ?>>2027</option>
                    </select>
                </div>

                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-success btn-sm w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- TAMPILAN RESMI CETAK -->
    <div class="card shadow-sm border-0 p-4 mb-4 bg-white">
        <div class="text-center pb-3 mb-4" style="border-bottom: 3px double #000;">
            <h4 class="fw-bold mb-0 text-uppercase">PONDOK PESANTREN AL AMIEN KEDIRI</h4>
            <h5 class="fw-bold text-success mb-1">POS KESEHATAN PESANTREN (POSKESTREN)</h5>
            <p class="small text-muted mb-0">Jl. KH. Hasyim Asy'ari, Kec. Pesantren, Kota Kediri, Jawa Timur 64133 | Telp: (0354) 771234</p>
        </div>

        <div class="text-center mb-4">
            <h5 class="fw-bold text-dark text-uppercase mb-1">LAPORAN PELAYANAN KESEHATAN SANTRI</h5>
            <p class="text-muted small">Periode: <strong><?= $labelPeriode ?></strong> (<?= $tglAwal ?> s.d. <?= $tglAkhir ?>) | Total Kunjungan: <strong><?= $totalPeriode ?> Pasien</strong></p>
        </div>

        <!-- 10 Besar Morbiditas -->
        <h6 class="fw-bold text-success mb-2">I. 10 BESAR KASUS PENYAKIT (TOP 10 MORBIDITAS PESANTREN)</h6>
        <div class="table-responsive mb-4">
            <table class="table table-bordered table-sm small align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="5%" class="text-center">No</th>
                        <th>Nama Diagnosa Penyakit</th>
                        <th width="15%">Kode ICD-10</th>
                        <th width="15%" class="text-center">Jumlah Kasus</th>
                        <th width="15%" class="text-center">Persentase</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topMorbiditas)): ?>
                        <tr><td colspan="5" class="text-center py-3 text-muted">Tidak ada kasus tercatat pada periode ini.</td></tr>
                    <?php else: foreach ($topMorbiditas as $idx => $m): 
                        $pct = $totalPeriode > 0 ? round(($m['jumlah'] / $totalPeriode) * 100, 1) : 0;
                    ?>
                        <tr>
                            <td class="text-center"><?= $idx + 1 ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($m['diagnosis_utama']) ?></td>
                            <td class="font-monospace text-muted"><?= htmlspecialchars($m['kode_icd10'] ?: '-') ?></td>
                            <td class="text-center fw-bold"><?= $m['jumlah'] ?></td>
                            <td class="text-center"><?= $pct ?>%</td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Sebaran Kasus per Asrama -->
        <h6 class="fw-bold text-success mb-2">II. SEBARAN KASUS KESEHATAN PER ASRAMA SANTRI</h6>
        <div class="table-responsive mb-4">
            <table class="table table-bordered table-sm small align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="5%" class="text-center">No</th>
                        <th>Nama Asrama / Komplek</th>
                        <th width="25%" class="text-center">Jumlah Kunjungan Berobat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sebaranAsrama)): ?>
                        <tr><td colspan="3" class="text-center py-3 text-muted">Belum ada data kunjungan.</td></tr>
                    <?php else: foreach ($sebaranAsrama as $idx => $a): ?>
                        <tr>
                            <td class="text-center"><?= $idx + 1 ?></td>
                            <td><?= htmlspecialchars($a['asrama']) ?></td>
                            <td class="text-center fw-bold"><?= $a['jumlah'] ?> Santri</td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Tanda Tangan Pengesahan -->
        <div class="row text-center mt-5 pt-3">
            <div class="col-6">
                <p class="small mb-5">Mengetahui,<br>Pengasuh Pondok Pesantren Al Amien,</p>
                <p class="fw-bold mb-0 text-decoration-underline">KH. Anwar Iskandar</p>
                <p class="small text-muted">Pengasuh Pesantren</p>
            </div>
            <div class="col-6">
                <p class="small mb-5">Kediri, <?= formatTanggalIndo(date('Y-m-d')) ?><br>Kepala Poskestren Al Amien,</p>
                <p class="fw-bold mb-0 text-decoration-underline">dr. H. Ahmad Fauzi</p>
                <p class="small text-muted">SIP: 503/446/SIP-D/2023</p>
            </div>
        </div>
    </div>
</main>

<div class="no-print">
    <?php renderFooter(); ?>
    <?php renderMobileNav('laporan'); ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>