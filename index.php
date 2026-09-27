<?php
require_once 'config.php';
cekLogin(); // Wajib login untuk semua user (Admin, Nakes, Kader)
// Ambil Statistik
$totalSantri = (int)$pdo->query("SELECT COUNT(*) FROM santri WHERE status='Aktif'")->fetchColumn();
$sakitHariIni = (int)$pdo->query("SELECT COUNT(*) FROM rekam_medis WHERE DATE(tanggal_kunjungan) = CURDATE()")->fetchColumn();
$rawatInap = (int)$pdo->query("SELECT COUNT(*) FROM rekam_medis WHERE tindakan_medis IN ('Rawat Inap Poskestren', 'Istirahat di Asrama') AND DATE(tanggal_kunjungan) >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)")->fetchColumn();
$obatMenipis = (int)$pdo->query("SELECT COUNT(*) FROM obat WHERE stok <= stok_minimum")->fetchColumn();

// 6 Kunjungan Terakhir
$stmtKunjungan = $pdo->query("
    SELECT rm.*, s.nama_lengkap, s.nis, s.asrama, s.komplek 
    FROM rekam_medis rm 
    JOIN santri s ON rm.id_santri = s.id_santri 
    ORDER BY rm.tanggal_kunjungan DESC 
    LIMIT 6
");
$kunjunganTerbaru = $stmtKunjungan->fetchAll();

// Top 5 Morbiditas Penyakit
$topPenyakit = $pdo->query("
    SELECT diagnosis_utama, COUNT(*) as jumlah 
    FROM rekam_medis 
    GROUP BY diagnosis_utama 
    ORDER BY jumlah DESC 
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - DEK SANTRI Poskestren Al Amien</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php renderNavbar('dashboard'); ?>

<main class="container my-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Dashboard Pelayanan Poskestren</h1>
            <p class="text-muted small mb-0">Pos Kesehatan Pondok Pesantren Al Amien Kediri</p>
        </div>
        <div class="mt-2 mt-sm-0 d-flex gap-2">
            <a href="rekam_medis.php?action=tambah" class="btn btn-success btn-sm px-3 shadow-sm">
                ➕ Pemeriksaan Santri
            </a>
            <a href="santri.php?action=tambah" class="btn btn-outline-success btn-sm px-3">
                👤 Tambah Santri
            </a>
        </div>
    </div>

    <!-- 4 Kartu Statistik Ringkasan -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-0 border-start border-4 border-success">
                <div class="card-body">
                    <div class="text-muted small">Total Santri Aktif</div>
                    <div class="fs-3 fw-bold text-dark"><?= number_format($totalSantri) ?></div>
                    <div class="small text-success mt-1">Terdaftar di Sistem</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-0 border-start border-4 border-primary">
                <div class="card-body">
                    <div class="text-muted small">Kunjungan Hari Ini</div>
                    <div class="fs-3 fw-bold text-primary"><?= number_format($sakitHariIni) ?></div>
                    <div class="small text-muted mt-1">Pasien berobat</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-0 border-start border-4 border-warning">
                <div class="card-body">
                    <div class="text-muted small">Istirahat / Rawat Inap</div>
                    <div class="fs-3 fw-bold text-warning"><?= number_format($rawatInap) ?></div>
                    <div class="small text-muted mt-1">Dalam masa pemulihan</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-0 border-start border-4 border-danger">
                <div class="card-body">
                    <div class="text-muted small">Stok Obat Menipis</div>
                    <div class="fs-3 fw-bold text-danger"><?= number_format($obatMenipis) ?></div>
                    <div class="small text-danger mt-1">Perlu restock segera</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Tabel Pasien Terkini -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-bold mb-0 text-dark">
                        📋 Kunjungan & Riwayat Medis Terbaru
                    </h5>
                    <a href="rekam_medis.php" class="btn btn-outline-secondary btn-sm">Lihat Semua</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No. RM</th>
                                    <th>Nama Santri</th>
                                    <th>Diagnosa</th>
                                    <th>Suhu</th>
                                    <th>Tindakan</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($kunjunganTerbaru)): ?>
                                    <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada kunjungan tercatat.</td></tr>
                                <?php else: foreach ($kunjunganTerbaru as $row): ?>
                                    <tr>
                                        <td class="fw-semibold small font-monospace"><?= htmlspecialchars($row['nomor_rm']) ?></td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
                                            <div class="small text-muted">NIS: <?= htmlspecialchars($row['nis']) ?> · <?= htmlspecialchars($row['asrama']) ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($row['diagnosis_utama']) ?></td>
                                        <td>
                                            <span class="badge <?= $row['suhu_tubuh'] >= 38.0 ? 'bg-danger' : 'bg-info' ?>">
                                                <?= number_format($row['suhu_tubuh'], 1) ?> °C
                                            </span>
                                        </td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($row['tindakan_medis']) ?></span></td>
                                        <td class="text-center">
                                            <a href="rekam_medis.php?detail=<?= $row['id_rm'] ?>" class="btn btn-sm btn-outline-success">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5 Morbiditas Terbanyak -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title fw-bold mb-0 text-dark">🩺 Morbiditas Terbanyak</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($topPenyakit)): ?>
                        <p class="text-muted small">Belum ada data diagnosa.</p>
                    <?php else: foreach ($topPenyakit as $p): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="fw-semibold"><?= htmlspecialchars($p['diagnosis_utama']) ?></span>
                                <span class="text-muted"><?= $p['jumlah'] ?> kasus</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: <?= min(100, $p['jumlah'] * 15) ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Tautan Cepat -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0 text-dark">⚡ Menu Layanan Medis</h6>
                </div>
                <div class="list-group list-group-flush small">
                    <a href="surat_izin.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span>📝 Buat & Cetak Surat Izin Sakit</span>
                        <span>›</span>
                    </a>
                    <a href="laporan_kesehatan.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span>📊 Rekapitulasi Laporan & Export Excel</span>
                        <span>›</span>
                    </a>
                    <a href="obat.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span>💊 Manajemen Stok Obat Pesantren</span>
                        <span>›</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>
<?php renderFooter(); ?>
<?php renderMobileNav('dashboard'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>