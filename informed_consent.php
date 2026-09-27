<?php
require_once 'config.php';
// Kader tidak diizinkan membuka menu rujukan klinis, informed consent, atau rekap laporan
proteksiRole(['Admin', 'Dokter', 'Perawat']);
$pesanSukses = '';
$pesanError = '';
$action = sanitize($_GET['action'] ?? 'list');

// PROSES SIMPAN INFORMED CONSENT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_consent'])) {
    $id_santri = intval($_POST['id_santri']);
    $id_rm = !empty($_POST['id_rm']) ? intval($_POST['id_rm']) : null;
    $nama_wali = sanitize($_POST['nama_wali_pengasuh']);
    $hubungan = sanitize($_POST['hubungan']);
    $no_hp = sanitize($_POST['no_hp_wali']);
    $tindakan = sanitize($_POST['jenis_tindakan']);
    $diagnosa = sanitize($_POST['diagnosa']);
    $penjelasan = sanitize($_POST['penjelasan_tindakan']);
    $status = sanitize($_POST['status_persetujuan']);
    $nakes = sanitize($_POST['nakes_penjelas']);
    $saksi = sanitize($_POST['saksi_pesantren']);

    $noConsent = sprintf("IC-%s/%03d", date('Ymd'), rand(10, 999));

    try {
        $stmt = $pdo->prepare("
            INSERT INTO informed_consent 
            (nomor_consent, id_santri, id_rm, nama_wali_pengasuh, hubungan, no_hp_wali, jenis_tindakan, diagnosa, penjelasan_tindakan, status_persetujuan, nakes_penjelas, saksi_pesantren)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$noConsent, $id_santri, $id_rm, $nama_wali, $hubungan, $no_hp, $tindakan, $diagnosa, $penjelasan, $status, $nakes, $saksi]);

        $pesanSukses = "Lembar persetujuan informed consent santri ($noConsent) berhasil disimpan!";
        $action = 'list';
    } catch (Exception $e) {
        $pesanError = "Gagal menyimpan informed consent: " . $e->getMessage();
    }
}

// HAPUS DATA
if (isset($_GET['hapus'])) {
    $idHapus = intval($_GET['hapus']);
    try {
        $pdo->prepare("DELETE FROM informed_consent WHERE id_consent = ?")->execute([$idHapus]);
        $pesanSukses = "Data informed consent berhasil dihapus!";
    } catch (Exception $e) {
        $pesanError = "Gagal menghapus: " . $e->getMessage();
    }
}

// AMBIL SANTRI KHUSUS < 18 TAHUN (ATAU SEMUA SANTRI AKTIF)
$santriUnder18 = $pdo->query("
    SELECT id_santri, nis, nama_lengkap, asrama, nama_wali, no_hp_wali, 
           TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) AS umur
    FROM santri 
    WHERE status='Aktif' 
    ORDER BY nama_lengkap ASC
")->fetchAll();

// DAFTAR SURAT PERSETUJUAN
$stmtList = $pdo->query("
    SELECT ic.*, s.nama_lengkap, s.nis, s.asrama, TIMESTAMPDIFF(YEAR, s.tanggal_lahir, CURDATE()) as umur_santri 
    FROM informed_consent ic 
    JOIN santri s ON ic.id_santri = s.id_santri 
    ORDER BY ic.id_consent DESC
");
$daftarConsent = $stmtList->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informed Consent Santri < 18 Th - DEK SANTRI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php renderNavbar('consent'); ?>

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
        <!-- FORM INFORMED CONSENT SANTRI UNDER 18 -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-warning text-dark py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">📝 Form Informed Consent Tindakan / Rujukan Santri (&lt; 18 Tahun)</h5>
                <a href="informed_consent.php" class="btn btn-outline-dark btn-sm">Kembali</a>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-info py-2 small mb-3">
                    <strong>Ketentuan Regulasi Kesehatan:</strong> Santri di bawah umur 18 tahun membutuhkan persetujuan tindakan medis invasif atau persetujuan rujukan faskes dari Orang Tua / Pengasuh Asrama selaku kuasa wali sah di pondok pesantren.
                </div>
                <form method="POST">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">I. Identitas Santri & Wali Kuasa</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Pilih Santri (&lt; 18 Tahun) *</label>
                            <select name="id_santri" id="sel_santri" class="form-select form-select-sm" required onchange="isiWali(this)">
                                <option value="">-- Pilih Santri --</option>
                                <?php foreach ($santriUnder18 as $s): ?>
                                    <option value="<?= $s['id_santri'] ?>" 
                                            data-wali="<?= htmlspecialchars($s['nama_wali']) ?>" 
                                            data-hp="<?= htmlspecialchars($s['no_hp_wali']) ?>">
                                        <?= htmlspecialchars($s['nama_lengkap']) ?> (<?= $s['umur'] ?> Th) - <?= htmlspecialchars($s['asrama']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nama Wali / Pengasuh Asrama *</label>
                            <input type="text" name="nama_wali_pengasuh" id="nama_wali" class="form-control form-control-sm" required placeholder="Nama orang tua atau ustadz pengasuh">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Hubungan dengan Santri *</label>
                            <select name="hubungan" class="form-select form-select-sm" required>
                                <option value="Orang Tua Kandung">Orang Tua Kandung</option>
                                <option value="Wali Santri">Wali Santri Sah</option>
                                <option value="Pengasuh Asrama (Kuasa Wali)">Pengasuh Asrama (Kuasa Wali Pesantren)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nomor WhatsApp / HP Wali *</label>
                            <input type="text" name="no_hp_wali" id="no_hp_wali" class="form-control form-control-sm" required placeholder="08xxxx">
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">II. Penjelasan Tindakan & Keputusan</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Diagnosa Klinis *</label>
                            <input type="text" name="diagnosa" class="form-control form-control-sm" required placeholder="Contoh: Vulnus Laceratum (Luka Robek) / Asma Akut">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tindakan Medis / Rujukan yang Direncanakan *</label>
                            <input type="text" name="jenis_tindakan" class="form-control form-control-sm" required placeholder="Contoh: Hecting (Jahit Luka) / Pemasangan Infus / Rujukan RS">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Uraian Penjelasan Risiko & Manfaat *</label>
                            <textarea name="penjelasan_tindakan" class="form-control form-control-sm" rows="2" required placeholder="Telah dijelaskan indikasi perlunya tindakan, manfaat penanganan segera, serta risiko jika tidak ditangani."></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Pernyataan Sikap *</label>
                            <select name="status_persetujuan" class="form-select form-select-sm" required>
                                <option value="Setuju">SETUJU (Memberi Izin)</option>
                                <option value="Menolak">MENOLAK (Tidak Mengizinkan)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Nakes Penjelas *</label>
                            <input type="text" name="nakes_penjelas" class="form-control form-control-sm" required value="Ns. Siti Rahmah, S.Kep">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Saksi Pesantren *</label>
                            <input type="text" name="saksi_pesantren" class="form-control form-control-sm" required value="Ustadz Pembina Asrama">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="informed_consent.php" class="btn btn-secondary btn-sm px-4">Batal</a>
                        <button type="submit" name="simpan_consent" class="btn btn-warning btn-sm px-4 fw-bold">💾 Simpan & Cetak Lembar Persetujuan</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            function isiWali(sel) {
                const opt = sel.options[sel.selectedIndex];
                document.getElementById('nama_wali').value = opt.getAttribute('data-wali') || '';
                document.getElementById('no_hp_wali').value = opt.getAttribute('data-hp') || '';
            }
        </script>

    <?php else: ?>
        <!-- LIST INFORMED CONSENT -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 fw-bold text-dark mb-1">Arsip Informed Consent Santri (&lt; 18 Tahun)</h1>
                <p class="text-muted small mb-0">Total: <?= count($daftarConsent) ?> lembar persetujuan tindakan/rujukan</p>
            </div>
            <a href="informed_consent.php?action=tambah" class="btn btn-warning btn-sm px-3 shadow-sm fw-bold">
                ➕ Buat Informed Consent
            </a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No. Consent</th>
                                <th>Tanggal</th>
                                <th>Nama Santri (Umur)</th>
                                <th>Wali / Kuasa Wali</th>
                                <th>Jenis Tindakan</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($daftarConsent)): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada informed consent tercatat.</td></tr>
                            <?php else: foreach ($daftarConsent as $c): ?>
                                <tr>
                                    <td class="font-monospace small fw-bold"><?= htmlspecialchars($c['nomor_consent']) ?></td>
                                    <td class="small"><?= date('d/m/Y H:i', strtotime($c['tanggal_persetujuan'])) ?></td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($c['nama_lengkap']) ?></div>
                                        <div class="small text-muted"><?= $c['umur_santri'] ?> Thn · <?= htmlspecialchars($c['asrama']) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($c['nama_wali_pengasuh']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($c['hubungan']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($c['jenis_tindakan']) ?></td>
                                    <td>
                                        <span class="badge <?= $c['status_persetujuan'] === 'Setuju' ? 'bg-success' : 'bg-danger' ?>">
                                            <?= htmlspecialchars($c['status_persetujuan']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="cetak_informed_consent.php?id_consent=<?= $c['id_consent'] ?>" target="_blank" class="btn btn-sm btn-outline-warning">🖨️ Cetak</a>
                                        <a href="informed_consent.php?hapus=<?= $c['id_consent'] ?>" onclick="return confirm('Hapus data informed consent ini?')" class="btn btn-sm btn-outline-secondary">🗑️</a>
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