<?php
require_once 'config.php';

$pesanSukses = '';
$pesanError = '';

// PROSES TAMBAH SURAT IZIN MANUAL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_surat'])) {
    $id_santri = intval($_POST['id_santri']);
    $tglMulai = sanitize($_POST['tanggal_mulai']);
    $tglSelesai = sanitize($_POST['tanggal_selesai']);
    $hari = intval($_POST['jumlah_hari']);
    $keringanan = sanitize($_POST['keringanan']);
    $petugas = sanitize($_POST['petugas_pemberi_izin']);
    $catatan = sanitize($_POST['catatan'] ?? '');

    $nomor_surat = sprintf("%03d/POSKESTREN-AM/%s/%s", rand(10, 999), date('m'), date('Y'));

    try {
        // Cari id_rm terakhir santri ini
        $lastRM = $pdo->prepare("SELECT id_rm FROM rekam_medis WHERE id_santri = ? ORDER BY tanggal_kunjungan DESC LIMIT 1");
        $lastRM->execute([$id_santri]);
        $id_rm = $lastRM->fetchColumn() ?: 1;

        $stmt = $pdo->prepare("
            INSERT INTO surat_izin_sakit 
            (nomor_surat, id_rm, id_santri, tanggal_mulai, tanggal_selesai, jumlah_hari, keringanan, petugas_pemberi_izin, catatan)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$nomor_surat, $id_rm, $id_santri, $tglMulai, $tglSelesai, $hari, $keringanan, $petugas, $catatan]);
        $pesanSukses = "Surat Izin Sakit berhasil diterbitkan ($nomor_surat)";
    } catch (Exception $e) {
        $pesanError = "Gagal menerbitkan surat: " . $e->getMessage();
    }
}

// DAFTAR SURAT IZIN SAKIT
$stmt = $pdo->query("
    SELECT sis.*, s.nama_lengkap, s.nis, s.asrama, s.kelas, s.no_hp_wali, rm.diagnosis_utama
    FROM surat_izin_sakit sis
    JOIN santri s ON sis.id_santri = s.id_santri
    LEFT JOIN rekam_medis rm ON sis.id_rm = rm.id_rm
    ORDER BY sis.id_surat DESC
");
$daftarSurat = $stmt->fetchAll();
$santriList = $pdo->query("SELECT id_santri, nis, nama_lengkap, asrama FROM santri ORDER BY nama_lengkap ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Izin Sakit - DEK SANTRI Poskestren Al Amien</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php renderNavbar('surat_izin'); ?>

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

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Surat Keterangan Istirahat Sakit</h1>
            <p class="text-muted small mb-0">Dispensasi resmi KBM madrasah & kegiatan asrama santri</p>
        </div>
        <button type="button" class="btn btn-success btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahSurat">
            ➕ Terbitkan Surat Izin
        </button>
    </div>

    <!-- Tabel Surat Izin -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nomor Surat</th>
                            <th>Nama Santri</th>
                            <th>Diagnosa Medis</th>
                            <th>Masa Berlaku</th>
                            <th>Lama</th>
                            <th>Keringanan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($daftarSurat)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada surat izin yang diterbitkan.</td></tr>
                        <?php else: foreach ($daftarSurat as $s): 
                            $isExpired = date('Y-m-d') > $s['tanggal_selesai'];
                        ?>
                            <tr>
                                <td class="font-monospace small fw-bold text-success">
                                    <?= htmlspecialchars($s['nomor_surat']) ?>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($s['nama_lengkap']) ?></div>
                                    <div class="small text-muted">NIS: <?= htmlspecialchars($s['nis']) ?> · <?= htmlspecialchars($s['asrama']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($s['diagnosis_utama'] ?: 'Pemeriksaan Poskestren') ?></td>
                                <td class="small">
                                    <div><?= date('d/m/Y', strtotime($s['tanggal_mulai'])) ?> s.d. <?= date('d/m/Y', strtotime($s['tanggal_selesai'])) ?></div>
                                    <span class="badge <?= $isExpired ? 'bg-secondary' : 'bg-success' ?>">
                                        <?= $isExpired ? 'Selesai' : 'Masa Istirahat Aktif' ?>
                                    </span>
                                </td>
                                <td class="fw-bold"><?= $s['jumlah_hari'] ?> Hari</td>
                                <td class="small text-muted"><?= htmlspecialchars($s['keringanan']) ?></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="cetak_surat.php?id_rm=<?= $s['id_rm'] ?>" target="_blank" class="btn btn-outline-success">
                                            🖨️ Cetak Resmi
                                        </a>
                                        <?php if ($s['no_hp_wali']): ?>
                                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $s['no_hp_wali']) ?>" target="_blank" class="btn btn-outline-primary" title="Hubungi Wali Santri">
                                                💬 WA Wali
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Modal Tambah Surat Izin -->
<div class="modal fade" id="modalTambahSurat" tabindex="-1">
    <div class="modal-dialog modal-md">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold">Terbitkan Surat Izin Sakit</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label small fw-bold">Pilih Santri *</label>
                    <select name="id_santri" class="form-select form-select-sm" required>
                        <option value="">-- Pilih Santri --</option>
                        <?php foreach ($santriList as $san): ?>
                            <option value="<?= $san['id_santri'] ?>"><?= htmlspecialchars($san['nama_lengkap']) ?> (<?= $san['asrama'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Mulai Tanggal *</label>
                    <input type="date" name="tanggal_mulai" required class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Sampai Tanggal *</label>
                    <input type="date" name="tanggal_selesai" required class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('+2 days')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Jumlah Hari *</label>
                    <input type="number" name="jumlah_hari" required class="form-control form-control-sm" value="2" min="1">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Petugas Pemberi Izin</label>
                    <input type="text" name="petugas_pemberi_izin" required class="form-control form-control-sm" value="Ns. Siti Rahmah, S.Kep">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Bentuk Keringanan *</label>
                    <input type="text" name="keringanan" required class="form-control form-control-sm" value="KBM Madrasah, Pengajian Asrama, Piket Kebersihan">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Catatan Klinis</label>
                    <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Istirahat di kamar asrama santri">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="tambah_surat" class="btn btn-success btn-sm px-3">Terbitkan Surat</button>
            </div>
        </form>
    </div>
</div>
<?php renderFooter(); ?>
<?php renderMobileNav('surat_izin'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>