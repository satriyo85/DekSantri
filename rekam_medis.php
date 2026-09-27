<?php
require_once 'config.php';
cekLogin();

$pesanSukses = '';
$pesanError = '';
$action = sanitize($_GET['action'] ?? 'list');
$detailId = intval($_GET['detail'] ?? 0);

// PROSES SIMPAN PEMERIKSAAN MEDIS & SKRINING TB/BB/IMT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_pemeriksaan'])) {
    $id_santri = intval($_POST['id_santri']);
    $keluhan = sanitize($_POST['keluhan_utama']);
    $anamnesa = sanitize($_POST['anamnesa']);
    $td = sanitize($_POST['tekanan_darah']);
    
    // Antropometri & Skrining Gizi
    $bb = floatval($_POST['berat_badan'] ?? 0);
    $tb = floatval($_POST['tinggi_badan'] ?? 0);
    $imt = floatval($_POST['imt'] ?? 0);
    $status_gizi = sanitize($_POST['status_gizi'] ?? 'Normal');

    $suhu = floatval($_POST['suhu_tubuh']);
    $nadi = intval($_POST['nadi'] ?: 80);
    $laju_nafas = intval($_POST['laju_nafas'] ?: 20);
    $diagnosa = sanitize($_POST['diagnosis_utama']);
    $icd = sanitize($_POST['kode_icd10']);
    $tindakan = sanitize($_POST['tindakan_medis']);
    $istirahat = intval($_POST['durasi_istirahat_hari'] ?? 0);
    $edukasi = sanitize($_POST['catatan_edukasi']);

    $nomor_rm = 'RM-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO rekam_medis 
            (nomor_rm, id_santri, keluhan_utama, anamnesa, tekanan_darah, berat_badan, tinggi_badan, imt, status_gizi, suhu_tubuh, nadi, laju_nafas, diagnosis_utama, kode_icd10, tindakan_medis, durasi_istirahat_hari, catatan_edukasi)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $nomor_rm, $id_santri, $keluhan, $anamnesa, $td, $bb, $tb, $imt, $status_gizi, $suhu, $nadi, $laju_nafas, $diagnosa, $icd, $tindakan, $istirahat, $edukasi
        ]);
        $id_rm_baru = $pdo->lastInsertId();

        // Update data TB dan BB santri
        if ($tb > 0 || $bb > 0) {
            $stmtUpdateSantri = $pdo->prepare("UPDATE santri SET berat_badan = ?, tinggi_badan = ? WHERE id_santri = ?");
            $stmtUpdateSantri->execute([$bb, $tb, $id_santri]);
        }

        // Simpan Resep Obat Dinamis
        if (!empty($_POST['obat_id']) && is_array($_POST['obat_id'])) {
            $stmtResep = $pdo->prepare("INSERT INTO resep_detail (id_rm, id_obat, dosis, aturan_pakai, jumlah) VALUES (?, ?, ?, ?, ?)");
            $stmtStok = $pdo->prepare("UPDATE obat SET stok = stok - ? WHERE id_obat = ? AND stok >= ?");

            foreach ($_POST['obat_id'] as $idx => $idObat) {
                $idObat = intval($idObat);
                $qty = intval($_POST['obat_qty'][$idx] ?? 0);
                $dosis = sanitize($_POST['obat_dosis'][$idx] ?? '-');
                $aturan = sanitize($_POST['obat_aturan'][$idx] ?? '-');

                if ($idObat > 0 && $qty > 0) {
                    $cekStok = $pdo->prepare("SELECT stok, nama_obat FROM obat WHERE id_obat = ?");
                    $cekStok->execute([$idObat]);
                    $rowObat = $cekStok->fetch();

                    if ($rowObat && $rowObat['stok'] >= $qty) {
                        $stmtResep->execute([$id_rm_baru, $idObat, $dosis, $aturan, $qty]);
                        $stmtStok->execute([$qty, $idObat, $qty]);
                    } else {
                        throw new Exception("Stok untuk obat " . ($rowObat['nama_obat'] ?? '') . " tidak mencukupi.");
                    }
                }
            }
        }

        // Simpan Surat Izin Sakit jika ada istirahat
        if ($istirahat > 0) {
            $noSurat = sprintf("%03d/POSKESTREN-AM/%s/%s", rand(10, 999), date('m'), date('Y'));
            $tglMulai = date('Y-m-d');
            $tglSelesai = date('Y-m-d', strtotime("+$istirahat days"));
            $keringanan = "KBM Madrasah, Pengajian Kitab Asrama, Piket";

            $stmtSurat = $pdo->prepare("
                INSERT INTO surat_izin_sakit (nomor_surat, id_rm, id_santri, tanggal_mulai, tanggal_selesai, jumlah_hari, keringanan, petugas_pemberi_izin)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Ns. Siti Rahmah, S.Kep')
            ");
            $stmtSurat->execute([$noSurat, $id_rm_baru, $id_santri, $tglMulai, $tglSelesai, $istirahat, $keringanan]);
        }

        $pdo->commit();
        $pesanSukses = "Pemeriksaan dan hasil skrining IMT santri ($nomor_rm) berhasil disimpan!";
        $action = 'list';
    } catch (Exception $e) {
        $pdo->rollBack();
        $pesanError = "Gagal menyimpan rekam medis: " . $e->getMessage();
    }
}

// PROSES HAPUS REKAM MEDIS
if (isset($_GET['hapus'])) {
    if (!isAdmin()) {
        $pesanError = "Hanya Admin yang berhak menghapus data rekam medis!";
    } else {
        $idHapus = intval($_GET['hapus']);
        try {
            $pdo->prepare("DELETE FROM rekam_medis WHERE id_rm = ?")->execute([$idHapus]);
            $pesanSukses = "Rekam medis berhasil dihapus!";
        } catch (Exception $e) {
            $pesanError = "Gagal menghapus: " . $e->getMessage();
        }
    }
}

// DATA SANTRI & OBAT
$santriList = $pdo->query("
    SELECT id_santri, nis, nama_lengkap, asrama, kelas, riwayat_alergi, berat_badan, tinggi_badan,
           TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) AS umur 
    FROM santri 
    WHERE status='Aktif' 
    ORDER BY nama_lengkap ASC
")->fetchAll();

$obatList = $pdo->query("SELECT id_obat, nama_obat, satuan, stok FROM obat WHERE stok > 0 ORDER BY nama_obat ASC")->fetchAll();

// DETAIL REKAM MEDIS
$dataDetail = null;
if ($detailId > 0) {
    $stmtDet = $pdo->prepare("
        SELECT rm.*, s.nama_lengkap, s.nis, s.asrama, s.kelas, s.golongan_darah, s.nama_wali, s.no_hp_wali, s.riwayat_alergi,
               TIMESTAMPDIFF(YEAR, s.tanggal_lahir, CURDATE()) AS umur
        FROM rekam_medis rm 
        JOIN santri s ON rm.id_santri = s.id_santri 
        WHERE rm.id_rm = ?
    ");
    $stmtDet->execute([$detailId]);
    $dataDetail = $stmtDet->fetch();

    $resepDet = $pdo->prepare("SELECT r.*, o.nama_obat, o.satuan FROM resep_detail r JOIN obat o ON r.id_obat = o.id_obat WHERE r.id_rm = ?");
    $resepDet->execute([$detailId]);
    $obatDiberikan = $resepDet->fetchAll();
}

// LIST REKAM MEDIS
$search = sanitize($_GET['q'] ?? '');
$sqlList = "
    SELECT rm.*, s.nama_lengkap, s.nis, s.asrama, s.kelas,
           TIMESTAMPDIFF(YEAR, s.tanggal_lahir, CURDATE()) AS umur
    FROM rekam_medis rm 
    JOIN santri s ON rm.id_santri = s.id_santri
";
$params = [];
if ($search !== '') {
    $sqlList .= " WHERE s.nama_lengkap LIKE ? OR rm.nomor_rm LIKE ? OR rm.diagnosis_utama LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}
$sqlList .= " ORDER BY rm.tanggal_kunjungan DESC";
$stmtList = $pdo->prepare($sqlList);
$stmtList->execute($params);
$daftarRM = $stmtList->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekam Medis & Skrining Santri - DEK SANTRI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php renderNavbar('rekam_medis'); ?>

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

    <?php if ($action === 'tambah'): ?>
        <!-- FORM PEMERIKSAAN MEDIS BARU + SKRINING IMT -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-success text-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">🩺 Form Pemeriksaan & Skrining Santri</h5>
                <a href="rekam_medis.php" class="btn btn-outline-light btn-sm">Kembali ke Daftar</a>
            </div>
            <div class="card-body p-4">
                <div id="alert_under18" class="alert alert-warning d-none align-items-center gap-2 py-2" role="alert">
                    <span class="fs-4">⚠️</span>
                    <div>
                        <strong>Perhatian Regulasi:</strong> Santri ini berumur <strong id="lbl_umur">0</strong> tahun (&lt; 18 Tahun). Jika memerlukan rujukan atau tindakan medis invasif, wajib melampirkan Informed Consent wali kuasa santri[cite: 1].
                    </div>
                </div>

                <form method="POST">
                    <h6 class="fw-bold text-success border-bottom pb-2 mb-3">I. Identitas Santri Pasien</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Pilih Santri *</label>
                            <select name="id_santri" id="id_santri" class="form-select form-select-sm" required onchange="cekKondisiSantri(this)">
                                <option value="">-- Pilih Santri Terdaftar --</option>
                                <?php foreach ($santriList as $s): ?>
                                    <option value="<?= $s['id_santri'] ?>" 
                                            data-umur="<?= $s['umur'] ?>" 
                                            data-tb="<?= $s['tinggi_badan'] ?>"
                                            data-bb="<?= $s['berat_badan'] ?>"
                                            data-alergi="<?= htmlspecialchars($s['riwayat_alergi'] ?: 'Tidak ada') ?>">
                                        <?= htmlspecialchars($s['nama_lengkap']) ?> (NIS: <?= $s['nis'] ?>) - <?= $s['umur'] ?> Th - <?= htmlspecialchars($s['asrama']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Peringatan Alergi Obat</label>
                            <input type="text" id="peringatan_alergi" class="form-control form-control-sm text-danger fw-bold bg-white" readonly value="-">
                        </div>
                    </div>

                    <!-- SKRINING FISIK ANTROPOMETRI (TB, BB, IMT OTOMATIS) -->
                    <h6 class="fw-bold text-success border-bottom pb-2 mb-3">II. Skrining Fisik & Status Gizi (Antropometri)</h6>
                    <div class="row g-3 mb-4 p-3 bg-white border rounded">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Tinggi Badan (TB) *</label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.1" name="tinggi_badan" id="tinggi_badan" class="form-control" required placeholder="Contoh: 165" oninput="hitungIMT()">
                                <span class="input-group-text">cm</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Berat Badan (BB) *</label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.1" name="berat_badan" id="berat_badan" class="form-control" required placeholder="Contoh: 55" oninput="hitungIMT()">
                                <span class="input-group-text">kg</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Nilai IMT / BMI (Otomatis)</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="imt" id="imt" class="form-control fw-bold bg-light" readonly placeholder="0.0">
                                <span class="input-group-text">kg/m²</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Kategori Status Gizi</label>
                            <input type="text" name="status_gizi" id="status_gizi" class="form-control form-control-sm fw-bold bg-light" readonly value="Belum dihitung">
                        </div>
                        <div class="col-12 mt-2">
                            <small class="text-muted" style="font-size: 11px;">
                                *Standar Kemenkes RI: <strong>Kurus / Kurang</strong> (&lt; 18.5) | <strong>Normal / Ideal</strong> (18.5 - 25.0) | <strong>Kelebihan BB</strong> (25.1 - 27.0) | <strong>Obesitas</strong> (&gt; 27.0)
                            </small>
                        </div>
                    </div>

                    <h6 class="fw-bold text-success border-bottom pb-2 mb-3">III. Anamnesa & Tanda Tanda Vital (TTV)</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Keluhan Utama *</label>
                            <input type="text" name="keluhan_utama" required class="form-control form-control-sm" placeholder="Contoh: Pusing, demam, lemas">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Anamnesa Lengkap & Kronologi</label>
                            <input type="text" name="anamnesa" required class="form-control form-control-sm" placeholder="Contoh: Sudah 2 hari, nafsu makan berkurang">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Suhu Tubuh (°C) *</label>
                            <input type="number" step="0.1" name="suhu_tubuh" required class="form-control form-control-sm" value="36.8">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Tekanan Darah (mmHg)</label>
                            <input type="text" name="tekanan_darah" class="form-control form-control-sm" placeholder="120/80">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Denyut Nadi (x/m)</label>
                            <input type="number" name="nadi" class="form-control form-control-sm" value="80">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Laju Nafas (x/m)</label>
                            <input type="number" name="laju_nafas" class="form-control form-control-sm" value="20">
                        </div>
                    </div>

                    <h6 class="fw-bold text-success border-bottom pb-2 mb-3">IV. Diagnosa & Penanganan Poskestren</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Diagnosa Medis Klinis *</label>
                            <input type="text" name="diagnosis_utama" required class="form-control form-control-sm" placeholder="Contoh: ISPA, Gastritis Akut, Malnutrisi Ringan">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Kode ICD-10 (Opsional)</label>
                            <input type="text" name="kode_icd10" class="form-control form-control-sm" placeholder="J02.9, K29.7, E66">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tindakan Medis / Disposisi Pasien *</label>
                            <select name="tindakan_medis" class="form-select form-select-sm" required>
                                <option value="Rawat Jalan">Rawat Jalan (Kembali ke Kegiatan)</option>
                                <option value="Istirahat di Asrama">Istirahat di Asrama</option>
                                <option value="Rawat Inap Poskestren">Rawat Inap / Observasi Poskestren</option>
                                <option value="Rujuk ke Puskesmas">Rujuk ke Puskesmas</option>
                                <option value="Rujuk ke Rumah Sakit">Rujuk ke Rumah Sakit</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Durasi Istirahat (Hari)</label>
                            <input type="number" name="durasi_istirahat_hari" class="form-control form-control-sm" value="0" min="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Edukasi Kesehatan & Rekomendasi Gizi</label>
                            <textarea name="catatan_edukasi" rows="2" class="form-control form-control-sm" placeholder="Anjuran gizi seimbang, pola makan, konsumsi air putih, istirahat"></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                        <h6 class="fw-bold text-success mb-0">V. Peresepan Obat Poskestren</h6>
                        <button type="button" class="btn btn-outline-success btn-sm fw-bold" onclick="tambahBarisObat()">
                            ➕ Tambah Baris Obat
                        </button>
                    </div>

                    <div id="wadah_resep_obat">
                        <div class="row g-2 align-items-center mb-2 item-resep">
                            <div class="col-md-4">
                                <select name="obat_id[]" class="form-select form-select-sm select-obat" onchange="updateSatuanObat(this)">
                                    <option value="">-- Pilih Obat --</option>
                                    <?php foreach ($obatList as $ob): ?>
                                        <option value="<?= $ob['id_obat'] ?>" data-satuan="<?= htmlspecialchars($ob['satuan']) ?>" data-stok="<?= $ob['stok'] ?>">
                                            <?= htmlspecialchars($ob['nama_obat']) ?> (Stok: <?= $ob['stok'] ?> <?= $ob['satuan'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <div class="input-group input-group-sm">
                                    <input type="number" name="obat_qty[]" class="form-control" placeholder="Qty" min="1">
                                    <span class="input-group-text label-satuan small">Pcs</span>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="obat_dosis[]" class="form-control form-control-sm" placeholder="Dosis (cth: 3x1)">
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="obat_aturan[]" class="form-control form-control-sm" placeholder="Aturan (cth: Sesudah makan)">
                            </div>
                            <div class="col-md-1 text-center">
                                <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="hapusBarisObat(this)">🗑️</button>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="rekam_medis.php" class="btn btn-secondary btn-sm px-4">Batal</a>
                        <button type="submit" name="simpan_pemeriksaan" class="btn btn-success btn-sm px-4">💾 Simpan Hasil Pemeriksaan & Skrining</button>
                    </div>
                </form>
            </div>
        </div>

        <template id="template_opsi_obat">
            <option value="">-- Pilih Obat --</option>
            <?php foreach ($obatList as $ob): ?>
                <option value="<?= $ob['id_obat'] ?>" data-satuan="<?= htmlspecialchars($ob['satuan']) ?>" data-stok="<?= $ob['stok'] ?>">
                    <?= htmlspecialchars($ob['nama_obat']) ?> (Stok: <?= $ob['stok'] ?> <?= $ob['satuan'] ?>)
                </option>
            <?php endforeach; ?>
        </template>

        <script>
            function hitungIMT() {
                const tb = parseFloat(document.getElementById('tinggi_badan').value) || 0;
                const bb = parseFloat(document.getElementById('berat_badan').value) || 0;
                const inputImt = document.getElementById('imt');
                const inputStatus = document.getElementById('status_gizi');

                if (tb > 0 && bb > 0) {
                    const meter = tb / 100;
                    const imt = (bb / (meter * meter)).toFixed(1);
                    inputImt.value = imt;

                    if (imt < 18.5) {
                        inputStatus.value = "Kurus / Berat Badan Kurang";
                        inputStatus.className = "form-control form-control-sm fw-bold bg-warning text-dark";
                    } else if (imt >= 18.5 && imt <= 25.0) {
                        inputStatus.value = "Normal / Ideal";
                        inputStatus.className = "form-control form-control-sm fw-bold bg-success text-white";
                    } else if (imt > 25.0 && imt <= 27.0) {
                        inputStatus.value = "Kelebihan Berat Badan (Overweight)";
                        inputStatus.className = "form-control form-control-sm fw-bold bg-warning text-dark";
                    } else {
                        inputStatus.value = "Obesitas";
                        inputStatus.className = "form-control form-control-sm fw-bold bg-danger text-white";
                    }
                } else {
                    inputImt.value = "0.0";
                    inputStatus.value = "Belum dihitung";
                    inputStatus.className = "form-control form-control-sm fw-bold bg-light";
                }
            }

            function cekKondisiSantri(sel) {
                const opt = sel.options[sel.selectedIndex];
                const alergi = opt.getAttribute('data-alergi') || '-';
                const umur = parseInt(opt.getAttribute('data-umur') || 0);
                const tbDefault = parseFloat(opt.getAttribute('data-tb') || 0);
                const bbDefault = parseFloat(opt.getAttribute('data-bb') || 0);

                document.getElementById('peringatan_alergi').value = alergi;
                
                if (tbDefault > 0) document.getElementById('tinggi_badan').value = tbDefault;
                if (bbDefault > 0) document.getElementById('berat_badan').value = bbDefault;
                hitungIMT();

                const boxUnder18 = document.getElementById('alert_under18');
                if (umur > 0 && umur < 18) {
                    document.getElementById('lbl_umur').innerText = umur;
                    boxUnder18.classList.remove('d-none');
                    boxUnder18.classList.add('d-flex');
                } else {
                    boxUnder18.classList.add('d-none');
                    boxUnder18.classList.remove('d-flex');
                }
            }

            function updateSatuanObat(sel) {
                const opt = sel.options[sel.selectedIndex];
                const satuan = opt.getAttribute('data-satuan') || 'Pcs';
                const baris = sel.closest('.item-resep');
                const label = baris.querySelector('.label-satuan');
                if (label) label.textContent = satuan;
            }

            function tambahBarisObat() {
                const wadah = document.getElementById('wadah_resep_obat');
                const templateOpsi = document.getElementById('template_opsi_obat').innerHTML;
                const div = document.createElement('div');
                div.className = 'row g-2 align-items-center mb-2 item-resep';
                div.innerHTML = `
                    <div class="col-md-4">
                        <select name="obat_id[]" class="form-select form-select-sm select-obat" onchange="updateSatuanObat(this)">
                            ${templateOpsi}
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="input-group input-group-sm">
                            <input type="number" name="obat_qty[]" class="form-control" placeholder="Qty" min="1">
                            <span class="input-group-text label-satuan small">Pcs</span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="obat_dosis[]" class="form-control form-control-sm" placeholder="Dosis (cth: 2x1)">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="obat_aturan[]" class="form-control form-control-sm" placeholder="Aturan (cth: Sebelum makan)">
                    </div>
                    <div class="col-md-1 text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="hapusBarisObat(this)">🗑️</button>
                    </div>
                `;
                wadah.appendChild(div);
            }

            function hapusBarisObat(btn) {
                const wadah = document.getElementById('wadah_resep_obat');
                const barisSemua = wadah.querySelectorAll('.item-resep');
                if (barisSemua.length > 1) {
                    btn.closest('.item-resep').remove();
                } else {
                    const baris = btn.closest('.item-resep');
                    baris.querySelector('select').value = '';
                    baris.querySelector('input[type="number"]').value = '';
                    baris.querySelectorAll('input[type="text"]').forEach(function(i) { i.value = ''; });
                    baris.querySelector('.label-satuan').textContent = 'Pcs';
                }
            }
        </script>

    <?php elseif ($dataDetail): ?>
        <!-- DETAIL REKAM MEDIS -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-0 fw-bold text-dark">📋 Detail Rekam Medis: <?= htmlspecialchars($dataDetail['nomor_rm']) ?></h5>
                    <span class="small text-muted">Tanggal: <?= date('d/m/Y H:i', strtotime($dataDetail['tanggal_kunjungan'])) ?> WIB</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <?php if ($dataDetail['umur'] < 18): ?>
                        <a href="cetak_informed_consent.php?id_rm=<?= $dataDetail['id_rm'] ?>" target="_blank" class="btn btn-warning btn-sm fw-bold shadow-sm">
                            📝 Informed Consent (&lt;18 Th)
                        </a>
                    <?php endif; ?>
                    <a href="cetak_surat.php?id_rm=<?= $dataDetail['id_rm'] ?>" target="_blank" class="btn btn-outline-primary btn-sm">🖨️ Surat Izin</a>
                    <a href="cetak_rujukan.php?id_rm=<?= $dataDetail['id_rm'] ?>" target="_blank" class="btn btn-outline-danger btn-sm">🏥 Surat Rujukan</a>
                    <a href="rekam_medis.php" class="btn btn-outline-secondary btn-sm">Kembali</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6 border-end">
                        <h6 class="fw-bold text-success mb-3">Identitas Pasien & Skrining Gizi</h6>
                        <table class="table table-sm">
                            <tr><td width="38%" class="text-muted">Nama Santri</td><td><strong><?= htmlspecialchars($dataDetail['nama_lengkap']) ?></strong> (NIS: <?= htmlspecialchars($dataDetail['nis']) ?>)</td></tr>
                            <tr><td class="text-muted">Umur Pasien</td><td><span class="badge bg-secondary"><?= $dataDetail['umur'] ?> Tahun</span></td></tr>
                            <tr><td class="text-muted">Kelas / Asrama</td><td><?= htmlspecialchars($dataDetail['kelas']) ?> / <?= htmlspecialchars($dataDetail['asrama']) ?></td></tr>
                            <tr>
                                <td class="text-muted">Antropometri Fisik</td>
                                <td>
                                    TB: <strong><?= $dataDetail['tinggi_badan'] ?> cm</strong> | BB: <strong><?=$dataDetail['berat_badan'] ?> kg</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Hasil IMT (BMI)</td>
                                <td>
                                    <strong><?= $dataDetail['imt'] ?> kg/m²</strong> 
                                    <span class="badge bg-info text-dark ms-2"><?= htmlspecialchars($dataDetail['status_gizi'] ?: 'Normal') ?></span>
                                </td>
                            </tr>
                            <tr><td class="text-muted">Riwayat Alergi</td><td><strong class="text-danger"><?= htmlspecialchars($dataDetail['riwayat_alergi'] ?: 'Tidak ada') ?></strong></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold text-success mb-3">Pemeriksaan Klinis & TTV</h6>
                        <table class="table table-sm">
                            <tr><td width="35%" class="text-muted">Keluhan</td><td><?= htmlspecialchars($dataDetail['keluhan_utama']) ?></td></tr>
                            <tr><td class="text-muted">Anamnesa</td><td><?= htmlspecialchars($dataDetail['anamnesa']) ?></td></tr>
                            <tr><td class="text-muted">Tanda Vital (TTV)</td><td>Suhu: <?= $dataDetail['suhu_tubuh'] ?> °C | TD: <?= $dataDetail['tekanan_darah'] ?: '-' ?> | Nadi: <?=$dataDetail['nadi'] ?: '-' ?> x/m</td></tr>
                            <tr><td class="text-muted">Diagnosa Medis</td><td><strong class="text-primary"><?= htmlspecialchars($dataDetail['diagnosis_utama']) ?></strong> (<?= htmlspecialchars($dataDetail['kode_icd10'] ?: '-') ?>)</td></tr>
                            <tr><td class="text-muted">Tindakan / Solusi</td><td><span class="badge bg-success"><?= htmlspecialchars($dataDetail['tindakan_medis']) ?></span></td></tr>
                            <tr><td class="text-muted">Edukasi & Gizi</td><td><?= htmlspecialchars($dataDetail['catatan_edukasi'] ?: '-') ?></td></tr>
                        </table>
                    </div>
                </div>

                <hr>
                <h6 class="fw-bold text-success mb-3">Daftar Terapi Obat Farmasi</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm small">
                        <thead class="table-light">
                            <tr><th>Nama Obat</th><th>Jumlah Diberikan</th><th>Dosis</th><th>Aturan Pakai</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($obatDiberikan)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-2">Tidak ada peresepan obat kimia/farmasi.</td></tr>
                            <?php else: foreach ($obatDiberikan as$o): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($o['nama_obat']) ?></td>
                                    <td><?= $o['jumlah'] ?> <?= htmlspecialchars($o['satuan']) ?></td>
                                    <td><?= htmlspecialchars($o['dosis']) ?></td>
                                    <td><?= htmlspecialchars($o['aturan_pakai']) ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- DAFTAR SEMUA REKAM MEDIS -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 fw-bold text-dark mb-1">Buku Catatan Rekam Medis Santri</h1>
                <p class="text-muted small mb-0">Total: <?= count($daftarRM) ?> riwayat pemeriksaan klinis & skrining gizi</p>
            </div>
            <div class="d-flex gap-2">
                <form method="GET" class="d-flex">
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari santri / diagnosa..." class="form-control form-control-sm me-2">
                    <button class="btn btn-outline-secondary btn-sm" type="submit">Cari</button>
                </form>
                <a href="rekam_medis.php?action=tambah" class="btn btn-success btn-sm px-3 shadow-sm">
                    ➕ Pemeriksaan Baru
                </a>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No. RM</th>
                                <th>Waktu</th>
                                <th>Santri (Umur)</th>
                                <th>Antropometri (TB/BB)</th>
                                <th>IMT & Status Gizi</th>
                                <th>Diagnosa</th>
                                <th>Disposisi</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($daftarRM)): ?>
                                <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada catatan rekam medis ditemukan.</td></tr>
                            <?php else: foreach ($daftarRM as$rm): ?>
                                <tr>
                                    <td class="font-monospace small fw-bold"><?= htmlspecialchars($rm['nomor_rm']) ?></td>
                                    <td class="small text-muted"><?= date('d/m/Y', strtotime($rm['tanggal_kunjungan'])) ?></td>
                                    <td>
                                        <div class="fw-bold d-flex align-items-center gap-1">
                                            <?= htmlspecialchars($rm['nama_lengkap']) ?>
                                            <?php if ($rm['umur'] < 18): ?>
                                                <span class="badge bg-warning text-dark small">⚠️ <?= $rm['umur'] ?> Th</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="small text-muted">NIS: <?= htmlspecialchars($rm['nis']) ?> · <?= htmlspecialchars($rm['asrama']) ?></div>
                                    </td>
                                    <td class="small">
                                        <?= $rm['tinggi_badan'] > 0 ?$rm['tinggi_badan'] . ' cm' : '-' ?> / 
                                        <?= $rm['berat_badan'] > 0 ?$rm['berat_badan'] . ' kg' : '-' ?>
                                    </td>
                                    <td>
                                        <?php if ($rm['imt'] > 0): ?>
                                            <span class="fw-bold"><?= $rm['imt'] ?></span>
                                            <span class="badge <?= ($rm['imt'] < 18.5 || $rm['imt'] > 25) ? 'bg-warning text-dark' : 'bg-success' ?> d-block mt-1" style="font-size: 10px;">
                                                <?= htmlspecialchars($rm['status_gizi'] ?: 'Normal') ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-primary"><?= htmlspecialchars($rm['diagnosis_utama']) ?></div>
                                        <div class="small text-muted">ICD-10: <?= htmlspecialchars($rm['kode_icd10'] ?: '-') ?></div>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($rm['tindakan_medis']) ?></span></td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1">
                                            <a href="rekam_medis.php?detail=<?= $rm['id_rm'] ?>" class="btn btn-sm btn-outline-success">Detail</a>
                                            <?php if ($rm['umur'] < 18): ?>
                                                <a href="cetak_informed_consent.php?id_rm=<?= $rm['id_rm'] ?>" target="_blank" class="btn btn-sm btn-warning text-dark fw-bold">Consent</a>
                                            <?php endif; ?>
                                            <?php if (isAdmin()): ?>
                                                <a href="rekam_medis.php?hapus=<?= $rm['id_rm'] ?>" onclick="return confirm('Hapus data ini?')" class="btn btn-sm btn-outline-danger">🗑️</a>
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
        renderMobileNav('rekam_medis'); 
    } 
    ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>