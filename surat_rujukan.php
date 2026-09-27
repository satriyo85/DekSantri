<?php
require_once 'config.php';
// Kader tidak diizinkan membuka menu rujukan klinis, informed consent, atau rekap laporan
proteksiRole(['Admin', 'Dokter', 'Perawat']);
$pesanSukses = '';
$pesanError = '';
$action = sanitize($_GET['action'] ?? 'list');

// Faskes Rujukan Terdekat di Sekitar Pesantren (Kediri)
$daftarFaskes = [
    'Puskesmas Ngletih Kota Kediri',
    'Puskesmas Pesantren 1 Kota Kediri',
    'Puskesmas Pesantren 2 Kota Kediri',
    'RSUD Gambiran Kota Kediri',
    'RSUD Kilisuci Kota Kediri',
    'RS Bhayangkara Kediri',
    'RS Baptis Kediri'
];

// PROSES SIMPAN RUJUKAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_rujukan'])) {
    $id_santri = intval($_POST['id_santri']);
    $id_rm = !empty($_POST['id_rm']) ? intval($_POST['id_rm']) : null;
    $faskes = sanitize($_POST['faskes_tujuan']);
    $poli = sanitize($_POST['poli_tujuan']);
    $diagnosa = sanitize($_POST['diagnosa_sementara']);
    $icd = sanitize($_POST['kode_icd10']);
    $anamnesa = sanitize($_POST['anamnesa_ringkas']);
    $ttv = sanitize($_POST['ttv_fisik']);
    $terapi = sanitize($_POST['terapi_awal']);
    $alasan = sanitize($_POST['alasan_rujukan']);
    $dokter = sanitize($_POST['dokter_pengirim']);
    $tgl = date('Y-m-d');

    // Generate Nomor Rujukan Otomatis
    $noSurat = sprintf("%03d/RUJ-POSKESTREN/%s/%s", rand(10, 999), date('m'), date('Y'));

    try {
        $stmt = $pdo->prepare("
            INSERT INTO surat_rujukan 
            (nomor_surat, id_rm, id_santri, tanggal_surat, faskes_tujuan, poli_tujuan, diagnosa_sementara, kode_icd10, anamnesa_ringkas, ttv_fisik, terapi_awal, alasan_rujukan, dokter_pengirim)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$noSurat, $id_rm, $id_santri, $tgl, $faskes, $poli, $diagnosa, $icd, $anamnesa, $ttv, $terapi, $alasan, $dokter]);
        
        $pesanSukses = "Surat rujukan ke $faskes berhasil diterbitkan!";
        $action = 'list';
    } catch (Exception $e) {
        $pesanError = "Gagal menerbitkan rujukan: " . $e->getMessage();
    }
}

// HAPUS RUJUKAN
if (isset($_GET['hapus'])) {
    $idHapus = intval($_GET['hapus']);
    try {
        $pdo->prepare("DELETE FROM surat_rujukan WHERE id_rujukan = ?")->execute([$idHapus]);
        $pesanSukses = "Surat rujukan berhasil dihapus!";
    } catch (Exception $e) {
        $pesanError = "Gagal menghapus: " . $e->getMessage();
    }
}

// AMBIL DATA SANTRI & REKAM MEDIS TERBARU
$santriList = $pdo->query("SELECT id_santri, nis, nama_lengkap, asrama, kelas FROM santri WHERE status='Aktif' ORDER BY nama_lengkap ASC")->fetchAll();
$rmList = $pdo->query("SELECT rm.id_rm, rm.nomor_rm, rm.diagnosis_utama, s.nama_lengkap FROM rekam_medis rm JOIN santri s ON rm.id_santri = s.id_santri ORDER BY rm.tanggal_kunjungan DESC LIMIT 20")->fetchAll();

// DAFTAR SURAT RUJUKAN
$stmtList = $pdo->query("
    SELECT r.*, s.nama_lengkap, s.nis, s.asrama, s.kelas 
    FROM surat_rujukan r 
    JOIN santri s ON r.id_santri = s.id_santri 
    ORDER BY r.id_rujukan DESC
");
$daftarRujukan = $stmtList->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Rujukan Faskes - DEK SANTRI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php renderNavbar('rujukan'); ?>

<main class="container my-4">
    <?php if ($pesanSukses): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($pesanSukses) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($pesanError): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($pesanError) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($action === 'tambah'): ?>
        <!-- FORM PEMBUATAN SURAT RUJUKAN -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-danger text-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">🏥 Penerbitan Surat Rujukan Pasien Santri</h5>
                <a href="surat_rujukan.php" class="btn btn-outline-light btn-sm">Kembali</a>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Pilih Pasien Santri *</label>
                            <select name="id_santri" class="form-select form-select-sm" required>
                                <option value="">-- Pilih Santri --</option>
                                <?php foreach ($santriList as $s): ?>
                                    <option value="<?= $s['id_santri'] ?>"><?= htmlspecialchars($s['nama_lengkap']) ?> (NIS: <?= $s['nis'] ?>) - <?= htmlspecialchars($s['asrama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tautkan Rekam Medis (Opsional)</label>
                            <select name="id_rm" class="form-select form-select-sm">
                                <option value="">-- Tanpa Kunjungan RM Sebelumnya --</option>
                                <?php foreach ($rmList as $r): ?>
                                    <option value="<?= $r['id_rm'] ?>"><?= htmlspecialchars($r['nomor_rm']) ?> - <?= htmlspecialchars($r['nama_lengkap']) ?> (<?= htmlspecialchars($r['diagnosis_utama']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Fasilitas Kesehatan Tujuan Rujukan *</label>
                            <select name="faskes_tujuan" class="form-select form-select-sm" required>
                                <?php foreach ($daftarFaskes as $f): ?>
                                    <option value="<?= $f ?>"><?= $f ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Poli / Unit yang Dituju *</label>
                            <input type="text" name="poli_tujuan" class="form-control form-control-sm" required value="Instalasi Gawat Darurat (IGD) / Rawat Inap">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Diagnosa Medis Sementara *</label>
                            <input type="text" name="diagnosa_sementara" class="form-control form-control-sm" required placeholder="Contoh: Suspect Appendicitis Akut / Febris H-4">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Kode ICD-10 (Bila ada)</label>
                            <input type="text" name="kode_icd10" class="form-control form-control-sm" placeholder="K35.8 / R50.9">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Anamnesa Singkat & Keluhan *</label>
                            <textarea name="anamnesa_ringkas" class="form-control form-control-sm" rows="2" required placeholder="Nyeri perut kanan bawah mendadak sejak tadi malam, demam naik turun..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Hasil TTV & Pemeriksaan Fisik Terakhir *</label>
                            <input type="text" name="ttv_fisik" class="form-control form-control-sm" required placeholder="TD: 110/70, Suhu: 38.7 C, Nadi: 96x/m, Nyeri tekan McBurney (+)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Terapi / Tindakan Awal di Poskestren</label>
                            <input type="text" name="terapi_awal" class="form-control form-control-sm" value="Paracetamol infus / oral, kompres hangat, rehidrasi">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Alasan Dirujuk *</label>
                            <input type="text" name="alasan_rujukan" class="form-control form-control-sm" required value="Memerlukan pemeriksaan penunjang lab darah lengkap dan evaluasi dokter spesialis bedah/anak.">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Dokter / Nakes Perujuk *</label>
                            <input type="text" name="dokter_pengirim" class="form-control form-control-sm" required value="dr. H. Ahmad Fauzi">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="surat_rujukan.php" class="btn btn-secondary btn-sm px-4">Batal</a>
                        <button type="submit" name="simpan_rujukan" class="btn btn-danger btn-sm px-4">🏥 Terbitkan Surat Rujukan</button>
                    </div>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- DAFTAR SURAT RUJUKAN -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 fw-bold text-dark mb-1">Daftar Rujukan Medis ke Faskes Terdekat</h1>
                <p class="text-muted small mb-0">Total: <?= count($daftarRujukan) ?> rujukan dikeluarkan</p>
            </div>
            <a href="surat_rujukan.php?action=tambah" class="btn btn-danger btn-sm px-3 shadow-sm">
                ➕ Buat Rujukan Baru
            </a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No. Surat</th>
                                <th>Tanggal</th>
                                <th>Nama Santri</th>
                                <th>Faskes Rujukan</th>
                                <th>Diagnosa Sementara</th>
                                <th>Dokter Perujuk</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($daftarRujukan)): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada surat rujukan yang dibuat.</td></tr>
                            <?php else: foreach ($daftarRujukan as $row): ?>
                                <tr>
                                    <td class="font-monospace small fw-bold"><?= htmlspecialchars($row['nomor_surat']) ?></td>
                                    <td class="small"><?= date('d/m/Y', strtotime($row['tanggal_surat'])) ?></td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($row['asrama']) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-danger"><?= htmlspecialchars($row['faskes_tujuan']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($row['poli_tujuan']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($row['diagnosa_sementara']) ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars($row['dokter_pengirim']) ?></td>
                                    <td class="text-center">
                                        <a href="cetak_rujukan.php?id_rm=<?= $row['id_rm'] ?: 1 ?>" target="_blank" class="btn btn-sm btn-outline-danger">🖨️ Cetak</a>
                                        <a href="surat_rujukan.php?hapus=<?= $row['id_rujukan'] ?>" onclick="return confirm('Hapus surat rujukan ini?')" class="btn btn-sm btn-outline-secondary">🗑️</a>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>
<?php renderFooter(); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>