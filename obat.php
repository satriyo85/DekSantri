<?php
require_once 'config.php';
cekLogin(); // Wajib login

$pesanSukses = '';
$pesanError = '';

// 1. PROSES TAMBAH OBAT BARU
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_obat'])) {
    $kode = sanitize($_POST['kode_obat']);
    $nama = sanitize($_POST['nama_obat']);
    $kategori = sanitize($_POST['kategori']);
    $satuan = sanitize($_POST['satuan']);
    $stok = intval($_POST['stok']);
    $stok_min = intval($_POST['stok_minimum'] ?? 10);
    $exp = sanitize($_POST['tgl_kadaluarsa']);
    $rak = sanitize($_POST['lokasi_rak'] ?? '');
    $ket = sanitize($_POST['keterangan'] ?? '');

    try {
        $stmt = $pdo->prepare("
            INSERT INTO obat (kode_obat, nama_obat, kategori, satuan, stok, stok_minimum, tgl_kadaluarsa, lokasi_rak, keterangan)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$kode, $nama, $kategori, $satuan, $stok, $stok_min, $exp, $rak, $ket]);
        $pesanSukses = "Obat baru berhasil ditambahkan!";
    } catch (Exception $e) {
        $pesanError = "Gagal menambah obat: " . $e->getMessage();
    }
}

// 2. PROSES UPDATE STOK / RESTOCK (DIPERBAIKI)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restock_obat'])) {
    $id_obat = intval($_POST['id_obat'] ?? 0);
    $tambah = intval($_POST['jumlah_tambah'] ?? 0);

    if ($id_obat > 0 && $tambah > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE obat SET stok = stok + ? WHERE id_obat = ?");
            $stmt->execute([$tambah, $id_obat]);

            if ($stmt->rowCount() > 0) {
                $pesanSukses = "Stok obat berhasil ditambahkan sebanyak $tambah!";
            } else {
                $pesanError = "Data obat tidak ditemukan atau stok gagal diperbarui.";
            }
        } catch (Exception $e) {
            $pesanError = "Terjadi kesalahan saat restock obat: " . $e->getMessage();
        }
    } else {
        $pesanError = "Jumlah penambahan stok harus lebih besar dari 0!";
    }
}

// 3. PROSES HAPUS OBAT (HANYA ADMIN)
if (isset($_GET['hapus'])) {
    if (!isAdmin()) {
        $pesanError = "Hanya Admin yang berhak menghapus data inventaris obat!";
    } else {
        $idHapus = intval($_GET['hapus']);
        try {
            $pdo->prepare("DELETE FROM obat WHERE id_obat = ?")->execute([$idHapus]);
            $pesanSukses = "Obat berhasil dihapus!";
        } catch (Exception $e) {
            $pesanError = "Gagal menghapus: " . $e->getMessage();
        }
    }
}

// DAFTAR OBAT
$daftarObat = $pdo->query("SELECT * FROM obat ORDER BY nama_obat ASC")->fetchAll();
$totalItem = count($daftarObat);
$obatKritis = 0;
foreach ($daftarObat as $o) {
    if ($o['stok'] <= $o['stok_minimum']) $obatKritis++;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmasi & Obat - DEK SANTRI Poskestren Al Amien</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php renderNavbar('obat'); ?>

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
            <h1 class="h3 fw-bold text-dark mb-1">Inventaris Obat & Farmasi Poskestren</h1>
            <p class="text-muted small mb-0">Total: <?= $totalItem ?> Item | <span class="text-danger fw-bold"><?= $obatKritis ?> Perlu Restock Segera</span></p>
        </div>
        <?php if (!isKader()): ?>
            <button type="button" class="btn btn-success btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahObat">
                ➕ Tambah Obat Baru
            </button>
        <?php endif; ?>
    </div>

    <!-- Tabel Daftar Obat -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kode</th>
                            <th>Nama Obat</th>
                            <th>Kategori</th>
                            <th>Satuan</th>
                            <th>Sisa Stok</th>
                            <th>Status Stok</th>
                            <th>Kadaluarsa</th>
                            <th>Rak</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($daftarObat)): ?>
                            <tr><td colspan="9" class="text-center py-4 text-muted">Belum ada inventaris obat.</td></tr>
                        <?php else: foreach ($daftarObat as $o): ?>
                            <tr>
                                <td class="font-monospace small fw-bold"><?= htmlspecialchars($o['kode_obat']) ?></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($o['nama_obat']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($o['keterangan'] ?: '-') ?></div>
                                </td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($o['kategori']) ?></span></td>
                                <td><?= htmlspecialchars($o['satuan']) ?></td>
                                <td class="fw-bold fs-6 <?= $o['stok'] <= $o['stok_minimum'] ? 'text-danger' : 'text-dark' ?>">
                                    <?= $o['stok'] ?>
                                </td>
                                <td>
                                    <?php if ($o['stok'] <= $o['stok_minimum']): ?>
                                        <span class="badge bg-danger">Restock Diperlukan</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Aman</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small"><?= date('d/m/Y', strtotime($o['tgl_kadaluarsa'])) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($o['lokasi_rak'] ?: '-') ?></td>
                                <td class="text-center">
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-success" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalRestockObat"
                                            data-id="<?= $o['id_obat'] ?>"
                                            data-nama="<?= htmlspecialchars($o['nama_obat']) ?>"
                                            data-stok="<?= $o['stok'] ?>"
                                            data-satuan="<?= htmlspecialchars($o['satuan']) ?>"
                                            title="Tambah Stok">
                                        ➕ Restock
                                    </button>
                                    <?php if (isAdmin()): ?>
                                        <a href="obat.php?hapus=<?= $o['id_obat'] ?>" onclick="return confirm('Hapus obat ini dari database?')" class="btn btn-sm btn-outline-danger" title="Hapus">
                                            🗑️
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- MODAL RESTOCK OBAT DINAMIS (DIPERBAIKI DILUAR TABEL) -->
<div class="modal fade" id="modalRestockObat" tabindex="-1" aria-labelledby="modalRestockLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form method="POST" action="obat.php" class="modal-content shadow border-0">
            <input type="hidden" name="id_obat" id="restock_id_obat" value="">
            <div class="modal-header bg-success text-white py-2">
                <h6 class="modal-title fw-bold" id="modalRestockLabel">Restock Obat</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label text-muted small mb-0">Nama Obat:</label>
                    <div class="fw-bold text-dark fs-6" id="restock_nama_obat">-</div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small mb-0">Stok Saat Ini:</label>
                    <div class="fw-semibold text-primary" id="restock_stok_saat_ini">0</div>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-bold text-dark">Jumlah Tambahan Stok *</label>
                    <input type="number" name="jumlah_tambah" id="restock_jumlah_tambah" class="form-control" required min="1" value="20" autofocus>
                    <small class="text-muted" style="font-size: 11px;">Stok akan langsung ditambahkan ke jumlah sedia.</small>
                </div>
            </div>
            <div class="modal-footer py-2 bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="restock_obat" class="btn btn-success btn-sm px-3 fw-bold">💾 Tambah Stok</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah Obat Baru -->
<div class="modal fade" id="modalTambahObat" tabindex="-1">
    <div class="modal-dialog modal-md">
        <form method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold">Tambah Obat Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Kode Obat *</label>
                    <input type="text" name="kode_obat" required class="form-control form-control-sm font-monospace" placeholder="OBT-009">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Kategori *</label>
                    <select name="kategori" class="form-select form-select-sm" required>
                        <option value="Analgesik/Antipiretik">Analgesik / Antipiretik</option>
                        <option value="Antibiotik">Antibiotik</option>
                        <option value="Antasida/Lambung">Antasida / Lambung</option>
                        <option value="Obat Kulit/Salep">Obat Kulit / Salep (Scabies)</option>
                        <option value="Antihistamin/Anti Alergi">Antihistamin / Alergi</option>
                        <option value="Obat Batuk/Flu">Obat Batuk & Flu</option>
                        <option value="Rehidrasi / Diare">Rehidrasi & Diare</option>
                        <option value="Vitamin/Suplemen">Vitamin & Suplemen</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Nama Obat & Dosis *</label>
                    <input type="text" name="nama_obat" required class="form-control form-control-sm" placeholder="Contoh: Paracetamol 500 mg">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Satuan *</label>
                    <input type="text" name="satuan" required class="form-control form-control-sm" placeholder="Tablet/Tube/Botol">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Stok Awal *</label>
                    <input type="number" name="stok" required class="form-control form-control-sm" value="50">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Batas Minimum</label>
                    <input type="number" name="stok_minimum" class="form-control form-control-sm" value="15">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Tanggal Kadaluarsa</label>
                    <input type="date" name="tgl_kadaluarsa" required class="form-control form-control-sm">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Lokasi Rak Simpan</label>
                    <input type="text" name="lokasi_rak" class="form-control form-control-sm" placeholder="Lemari A - Rak 2">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Keterangan Khusus</label>
                    <input type="text" name="keterangan" class="form-control form-control-sm" placeholder="Fungsi utama atau peringatan pemakaian">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="tambah_obat" class="btn btn-success btn-sm px-3">Simpan Obat</button>
            </div>
        </form>
    </div>
</div>
<?php renderFooter(); ?>
<?php renderMobileNav('obat'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Script penangkap event saat tombol Restock diklik
const modalRestock = document.getElementById('modalRestockObat');
if (modalRestock) {
    modalRestock.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const nama = button.getAttribute('data-nama');
        const stok = button.getAttribute('data-stok');
        const satuan = button.getAttribute('data-satuan');

        document.getElementById('restock_id_obat').value = id;
        document.getElementById('restock_nama_obat').textContent = nama;
        document.getElementById('restock_stok_saat_ini').textContent = stok + ' ' + satuan;
        document.getElementById('restock_jumlah_tambah').value = 20;
    });
}
</script>
</body>
</html>