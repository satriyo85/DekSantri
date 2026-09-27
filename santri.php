<?php
require_once 'config.php';
if (function_exists('cekLogin')) {
    cekLogin();
}

$pesanSukses = '';
$pesanError  = '';
$action      = sanitize($_GET['action'] ?? 'list');
$editId      = intval($_GET['id'] ?? 0);

// Cek hak akses role secara aman
$roleUser = $_SESSION['user_poskestren']['profesi'] ?? 'Admin';
$isAdmin  = ($roleUser === 'Admin');
$isKader  = ($roleUser === 'Kader Santri');

// 1. PROSES TAMBAH SANTRI BARU
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_santri'])) {
    $nis           = sanitize($_POST['nis']);
    $nama_lengkap  = sanitize($_POST['nama_lengkap']);
    $jenis_kelamin = sanitize($_POST['jenis_kelamin']);
    $tempat_lahir  = sanitize($_POST['tempat_lahir']);
    $tanggal_lahir = sanitize($_POST['tanggal_lahir']);
    $asrama        = sanitize($_POST['asrama']);
    $komplek       = sanitize($_POST['komplek']);
    $kelas         = sanitize($_POST['kelas']);
    $golongan_darah= sanitize($_POST['golongan_darah']);
    $berat_badan   = floatval($_POST['berat_badan'] ?? 0);
    $tinggi_badan  = floatval($_POST['tinggi_badan'] ?? 0);
    $riwayat_alergi= sanitize($_POST['riwayat_alergi'] ?? '');
    $riwayat_khusus= sanitize($_POST['riwayat_penyakit_khusus'] ?? '');
    $nama_wali     = sanitize($_POST['nama_wali']);
    $no_hp_wali    = sanitize($_POST['no_hp_wali']);
    $alamat_asal   = sanitize($_POST['alamat_asal']);
    $status        = sanitize($_POST['status'] ?? 'Aktif');

    try {
        $stmt = $pdo->prepare("
            INSERT INTO santri 
            (nis, nama_lengkap, jenis_kelamin, tempat_lahir, tanggal_lahir, asrama, komplek, kelas, golongan_darah, berat_badan, tinggi_badan, riwayat_alergi, riwayat_penyakit_khusus, nama_wali, no_hp_wali, alamat_asal, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $nis, $nama_lengkap, $jenis_kelamin, $tempat_lahir, $tanggal_lahir, $asrama, $komplek, $kelas, $golongan_darah, $berat_badan, $tinggi_badan, $riwayat_alergi, $riwayat_khusus, $nama_wali, $no_hp_wali, $alamat_asal, $status
        ]);
        $pesanSukses = "Data santri baru ($nama_lengkap) berhasil disimpan!";
        $action = 'list';
    } catch (Exception $e) {
        $pesanError = "Gagal menambah santri: " . $e->getMessage();
    }
}

// 2. PROSES UPDATE DATA SANTRI (FITUR EDIT)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_santri'])) {
    $id_santri     = intval($_POST['id_santri']);
    $nis           = sanitize($_POST['nis']);
    $nama_lengkap  = sanitize($_POST['nama_lengkap']);
    $jenis_kelamin = sanitize($_POST['jenis_kelamin']);
    $tempat_lahir  = sanitize($_POST['tempat_lahir']);
    $tanggal_lahir = sanitize($_POST['tanggal_lahir']);
    $asrama        = sanitize($_POST['asrama']);
    $komplek       = sanitize($_POST['komplek']);
    $kelas         = sanitize($_POST['kelas']);
    $golongan_darah= sanitize($_POST['golongan_darah']);
    $berat_badan   = floatval($_POST['berat_badan'] ?? 0);
    $tinggi_badan  = floatval($_POST['tinggi_badan'] ?? 0);
    $riwayat_alergi= sanitize($_POST['riwayat_alergi'] ?? '');
    $riwayat_khusus= sanitize($_POST['riwayat_penyakit_khusus'] ?? '');
    $nama_wali     = sanitize($_POST['nama_wali']);
    $no_hp_wali    = sanitize($_POST['no_hp_wali']);
    $alamat_asal   = sanitize($_POST['alamat_asal']);
    $status        = sanitize($_POST['status'] ?? 'Aktif');

    try {
        $stmt = $pdo->prepare("
            UPDATE santri SET 
                nis = ?, nama_lengkap = ?, jenis_kelamin = ?, tempat_lahir = ?, tanggal_lahir = ?, 
                asrama = ?, komplek = ?, kelas = ?, golongan_darah = ?, berat_badan = ?, 
                tinggi_badan = ?, riwayat_alergi = ?, riwayat_penyakit_khusus = ?, nama_wali = ?, 
                no_hp_wali = ?, alamat_asal = ?, status = ?
            WHERE id_santri = ?
        ");
        $stmt->execute([
            $nis, $nama_lengkap, $jenis_kelamin, $tempat_lahir, $tanggal_lahir, 
            $asrama, $komplek, $kelas, $golongan_darah, $berat_badan, 
            $tinggi_badan, $riwayat_alergi, $riwayat_khusus, $nama_wali, 
            $no_hp_wali, $alamat_asal, $status, $id_santri
        ]);
        $pesanSukses = "Data santri ($nama_lengkap) berhasil diperbarui!";
        $action = 'list';
    } catch (Exception $e) {
        $pesanError = "Gagal memperbarui data santri: " . $e->getMessage();
    }
}

// 3. PROSES HAPUS SANTRI
if (isset($_GET['hapus'])) {
    if (!$isAdmin) {
        $pesanError = "Hanya Admin yang berhak menghapus data santri!";
    } else {
        $idHapus = intval($_GET['hapus']);
        try {
            $pdo->prepare("DELETE FROM santri WHERE id_santri = ?")->execute([$idHapus]);
            $pesanSukses = "Data santri berhasil dihapus!";
        } catch (Exception $e) {
            $pesanError = "Gagal menghapus santri: " . $e->getMessage();
        }
    }
}

// AMBIL DATA UNTUK FORM EDIT
$dataEdit = null;
if ($action === 'edit' && $editId > 0) {
    $stmtEdit = $pdo->prepare("SELECT * FROM santri WHERE id_santri = ?");
    $stmtEdit->execute([$editId]);
    $dataEdit = $stmtEdit->fetch();
    if (!$dataEdit) {
        $pesanError = "Data santri tidak ditemukan!";
        $action = 'list';
    }
}

// QUERY DATA SANTRI LENGKAP DENGAN HITUNGAN UMUR
$search = sanitize($_GET['q'] ?? '');
$sqlList = "
    SELECT *, TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) AS umur 
    FROM santri
";
$params = [];
if ($search !== '') {
    $sqlList .= " WHERE nama_lengkap LIKE ? OR nis LIKE ? OR asrama LIKE ? OR kelas LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%", "%$search%"];
}
$sqlList .= " ORDER BY nama_lengkap ASC";
$stmtList = $pdo->prepare($sqlList);
$stmtList->execute($params);
$daftarSantri = $stmtList->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Induk Santri - DEK SANTRI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php renderNavbar('santri'); ?>

<main class="container my-4">
    <?php if ($pesanSukses !== ''): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($pesanSukses) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($pesanError !== ''): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($pesanError) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($action === 'edit' && $dataEdit): ?>
        <!-- FORM EDIT DATA SANTRI -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-warning text-dark py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">✏️ Edit Data Induk Santri: <?= htmlspecialchars($dataEdit['nama_lengkap']) ?></h5>
                <a href="santri.php" class="btn btn-outline-dark btn-sm">Kembali ke Daftar</a>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <input type="hidden" name="id_santri" value="<?= $dataEdit['id_santri'] ?>">

                    <h6 class="fw-bold text-success border-bottom pb-2 mb-3">I. Identitas Pribadi Santri</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Nomor Induk Santri (NIS) *</label>
                            <input type="text" name="nis" class="form-control form-control-sm font-monospace" required value="<?= htmlspecialchars($dataEdit['nis']) ?>">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">Nama Lengkap Santri *</label>
                            <input type="text" name="nama_lengkap" class="form-control form-control-sm" required value="<?= htmlspecialchars($dataEdit['nama_lengkap']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Jenis Kelamin *</label>
                            <select name="jenis_kelamin" class="form-select form-select-sm" required>
                                <option value="L" <?= $dataEdit['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki (Putra)</option>
                                <option value="P" <?= $dataEdit['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan (Putri)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Tempat Lahir *</label>
                            <input type="text" name="tempat_lahir" class="form-control form-control-sm" required value="<?= htmlspecialchars($dataEdit['tempat_lahir']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Tanggal Lahir *</label>
                            <input type="date" name="tanggal_lahir" class="form-control form-control-sm" required value="<?= htmlspecialchars($dataEdit['tanggal_lahir']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Golongan Darah</label>
                            <select name="golongan_darah" class="form-select form-select-sm">
                                <?php foreach (['Belum Tahu', 'A', 'B', 'AB', 'O'] as $gol): ?>
                                    <option value="<?= $gol ?>" <?= $dataEdit['golongan_darah'] === $gol ? 'selected' : '' ?>><?= $gol ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <h6 class="fw-bold text-success border-bottom pb-2 mb-3">II. Asrama & Pendidikan Pesantren</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Komplek *</label>
                            <select name="komplek" class="form-select form-select-sm" required>
                                <option value="Putra" <?= $dataEdit['komplek'] === 'Putra' ? 'selected' : '' ?>>Komplek Putra</option>
                                <option value="Putri" <?= $dataEdit['komplek'] === 'Putri' ? 'selected' : '' ?>>Komplek Putri</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Nama Asrama / Kamar *</label>
                            <input type="text" name="asrama" class="form-control form-control-sm" required value="<?= htmlspecialchars($dataEdit['asrama']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Kelas / Jenjang Madrasah *</label>
                            <input type="text" name="kelas" class="form-control form-control-sm" required value="<?= htmlspecialchars($dataEdit['kelas']) ?>">
                        </div>
                    </div>

                    <h6 class="fw-bold text-success border-bottom pb-2 mb-3">III. Riwayat Medis & Fisik</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Tinggi Badan (cm)</label>
                            <input type="number" step="0.1" name="tinggi_badan" class="form-control form-control-sm" value="<?= $dataEdit['tinggi_badan'] ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Berat Badan (kg)</label>
                            <input type="number" step="0.1" name="berat_badan" class="form-control form-control-sm" value="<?= $dataEdit['berat_badan'] ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Riwayat Alergi Obat / Makanan</label>
                            <input type="text" name="riwayat_alergi" class="form-control form-control-sm" value="<?= htmlspecialchars($dataEdit['riwayat_alergi']) ?>" placeholder="Alergi Penisilin, Seafood, dsb">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Riwayat Penyakit Khusus / Bawaan</label>
                            <input type="text" name="riwayat_penyakit_khusus" class="form-control form-control-sm" value="<?= htmlspecialchars($dataEdit['riwayat_penyakit_khusus']) ?>" placeholder="Asma, Maag kronis, Jantung, dsb">
                        </div>
                    </div>

                    <h6 class="fw-bold text-success border-bottom pb-2 mb-3">IV. Wali Santri & Domisili Asal</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Nama Wali Santri *</label>
                            <input type="text" name="nama_wali" class="form-control form-control-sm" required value="<?= htmlspecialchars($dataEdit['nama_wali']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">No. HP / WhatsApp Wali *</label>
                            <input type="text" name="no_hp_wali" class="form-control form-control-sm" required value="<?= htmlspecialchars($dataEdit['no_hp_wali']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Status Santri *</label>
                            <select name="status" class="form-select form-select-sm" required>
                                <option value="Aktif" <?= $dataEdit['status'] === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                                <option value="Cuti Sakit" <?= $dataEdit['status'] === 'Cuti Sakit' ? 'selected' : '' ?>>Cuti Sakit</option>
                                <option value="Alumni" <?= $dataEdit['status'] === 'Alumni' ? 'selected' : '' ?>>Alumni</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Alamat Asal Santri *</label>
                            <textarea name="alamat_asal" rows="2" class="form-control form-control-sm" required><?= htmlspecialchars($dataEdit['alamat_asal']) ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="santri.php" class="btn btn-secondary btn-sm px-4">Batal</a>
                        <button type="submit" name="update_santri" class="btn btn-warning btn-sm px-4 fw-bold">💾 Perbarui Data Santri</button>
                    </div>
                </form>
            </div>
        </div>

    <?php elseif ($action === 'tambah'): ?>
        <!-- FORM TAMBAH SANTRI BARU -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-success text-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">👤 Tambah Santri Baru</h5>
                <a href="santri.php" class="btn btn-outline-light btn-sm">Kembali ke Daftar</a>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <h6 class="fw-bold text-success border-bottom pb-2 mb-3">I. Identitas Pribadi Santri</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Nomor Induk Santri (NIS) *</label>
                            <input type="text" name="nis" class="form-control form-control-sm font-monospace" required placeholder="Contoh: 202601001">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">Nama Lengkap Santri *</label>
                            <input type="text" name="nama_lengkap" class="form-control form-control-sm" required placeholder="Nama lengkap santri">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Jenis Kelamin *</label>
                            <select name="jenis_kelamin" class="form-select form-select-sm" required>
                                <option value="L">Laki-laki (Putra)</option>
                                <option value="P">Perempuan (Putri)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Tempat Lahir *</label>
                            <input type="text" name="tempat_lahir" class="form-control form-control-sm" required placeholder="Kota kelahiran">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Tanggal Lahir *</label>
                            <input type="date" name="tanggal_lahir" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Golongan Darah</label>
                            <select name="golongan_darah" class="form-select form-select-sm">
                                <option value="Belum Tahu">Belum Tahu</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="AB">AB</option>
                                <option value="O">O</option>
                            </select>
                        </div>
                    </div>

                    <h6 class="fw-bold text-success border-bottom pb-2 mb-3">II. Asrama & Pendidikan Pesantren</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Komplek *</label>
                            <select name="komplek" class="form-select form-select-sm" required>
                                <option value="Putra">Komplek Putra</option>
                                <option value="Putri">Komplek Putri</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Nama Asrama / Kamar *</label>
                            <input type="text" name="asrama" class="form-control form-control-sm" required placeholder="Contoh: Asrama Al-Ghazali (Kamar 02)">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Kelas / Jenjang Madrasah *</label>
                            <input type="text" name="kelas" class="form-control form-control-sm" required placeholder="Contoh: X MA IPA 1">
                        </div>
                    </div>

                    <h6 class="fw-bold text-success border-bottom pb-2 mb-3">III. Riwayat Medis & Fisik Awal</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Tinggi Badan (cm)</label>
                            <input type="number" step="0.1" name="tinggi_badan" class="form-control form-control-sm" placeholder="160">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Berat Badan (kg)</label>
                            <input type="number" step="0.1" name="berat_badan" class="form-control form-control-sm" placeholder="50">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Riwayat Alergi Obat / Makanan</label>
                            <input type="text" name="riwayat_alergi" class="form-control form-control-sm" placeholder="Alergi Penisilin, Dingin, dsb">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Riwayat Penyakit Khusus / Bawaan</label>
                            <input type="text" name="riwayat_penyakit_khusus" class="form-control form-control-sm" placeholder="Asma, Maag, Jantung, dsb">
                        </div>
                    </div>

                    <h6 class="fw-bold text-success border-bottom pb-2 mb-3">IV. Wali Santri & Domisili Asal</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Nama Wali Santri *</label>
                            <input type="text" name="nama_wali" class="form-control form-control-sm" required placeholder="Nama orang tua/wali">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">No. HP / WhatsApp Wali *</label>
                            <input type="text" name="no_hp_wali" class="form-control form-control-sm" required placeholder="08xxxxxxxx">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Status Santri *</label>
                            <select name="status" class="form-select form-select-sm" required>
                                <option value="Aktif">Aktif</option>
                                <option value="Cuti Sakit">Cuti Sakit</option>
                                <option value="Alumni">Alumni</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Alamat Asal Santri *</label>
                            <textarea name="alamat_asal" rows="2" class="form-control form-control-sm" required placeholder="Alamat lengkap rumah wali"></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="santri.php" class="btn btn-secondary btn-sm px-4">Batal</a>
                        <button type="submit" name="tambah_santri" class="btn btn-success btn-sm px-4 fw-bold">💾 Simpan Data Santri</button>
                    </div>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- DAFTAR SEMUA DATA SANTRI -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 fw-bold text-dark mb-1">Data Induk Santri Pesantren</h1>
                <p class="text-muted small mb-0">Total: <?= count($daftarSantri) ?> santri terdaftar di sistem</p>
            </div>
            <div class="d-flex gap-2">
                <form method="GET" class="d-flex">
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari NIS / nama / asrama..." class="form-control form-control-sm me-2">
                    <button class="btn btn-outline-secondary btn-sm" type="submit">Cari</button>
                </form>
                <?php if (!$isKader): ?>
                    <a href="santri.php?action=tambah" class="btn btn-success btn-sm px-3 shadow-sm">
                        ➕ Tambah Santri
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>NIS</th>
                                <th>Nama Santri (Umur)</th>
                                <th>JK</th>
                                <th>Asrama / Kelas</th>
                                <th>Riwayat Alergi</th>
                                <th>Wali / Kontak</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($daftarSantri)): ?>
                                <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada data santri ditemukan.</td></tr>
                            <?php else: foreach ($daftarSantri as $s): ?>
                                <tr>
                                    <td class="font-monospace small fw-bold"><?= htmlspecialchars($s['nis']) ?></td>
                                    <td>
                                        <div class="fw-bold d-flex align-items-center gap-1">
                                            <?= htmlspecialchars($s['nama_lengkap']) ?>
                                            <?php if ($s['umur'] < 18): ?>
                                                <span class="badge bg-warning text-dark small" style="font-size: 10px;">&lt;18 Th</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="small text-muted"><?= $s['umur'] ?> Tahun · <?= htmlspecialchars($s['tempat_lahir']) ?></div>
                                    </td>
                                    <td><span class="badge <?= $s['jenis_kelamin'] === 'L' ? 'bg-primary' : 'bg-danger' ?>"><?= $s['jenis_kelamin'] ?></span></td>
                                    <td>
                                        <div class="fw-semibold small"><?= htmlspecialchars($s['asrama']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($s['kelas']) ?> (<?= htmlspecialchars($s['komplek']) ?>)</div>
                                    </td>
                                    <td>
                                        <?php if (!empty($s['riwayat_alergi']) && $s['riwayat_alergi'] !== 'Tidak ada'): ?>
                                            <span class="badge bg-danger"><?= htmlspecialchars($s['riwayat_alergi']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small">
                                        <div><strong><?= htmlspecialchars($s['nama_wali']) ?></strong></div>
                                        <div class="text-muted"><?= htmlspecialchars($s['no_hp_wali']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $s['status'] === 'Aktif' ? 'bg-success' : ($s['status'] === 'Cuti Sakit' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                            <?= htmlspecialchars($s['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1">
                                            <!-- Tombol Edit Data Santri -->
                                            <a href="santri.php?action=edit&id=<?= $s['id_santri'] ?>" class="btn btn-sm btn-outline-warning text-dark" title="Edit Data Santri">
                                                ✏️ Edit
                                            </a>
                                            <!-- Tombol Cetak Kartu Berobat Santri (KBS) -->
                                            <a href="kartu_santri.php?id=<?= $s['id_santri'] ?>" target="_blank" class="btn btn-sm btn-outline-success" title="Cetak Kartu Berobat Santri">
                                                💳 KBS
                                            </a>
                                            <!-- Tombol Hapus (Khusus Admin) -->
                                            <?php if ($isAdmin): ?>
                                                <a href="santri.php?hapus=<?= $s['id_santri'] ?>" onclick="return confirm('Hapus data santri <?= htmlspecialchars(addslashes($s['nama_lengkap'])) ?>? Data rekam medis terkait juga akan terhapus.')" class="btn btn-sm btn-outline-danger" title="Hapus Santri">
                                                    🗑️
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
    <?php endif; ?>
</main>

<div class="no-print">
    <?php 
    if (function_exists('renderFooter')) { 
        renderFooter(); 
    } 
    if (function_exists('renderMobileNav')) { 
        renderMobileNav('santri'); 
    } 
    ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>