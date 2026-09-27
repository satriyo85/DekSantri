<?php
require_once 'config.php';

// Tangkap ID baik dari id_consent, id, id_rm, maupun id_santri
$id_consent = intval($_GET['id_consent'] ?? ($_GET['id'] ?? 0));
$id_rm      = intval($_GET['id_rm'] ?? 0);
$id_santri  = intval($_GET['id_santri'] ?? 0);

$data = null;

// 1. Prioritas: Ambil berdasarkan id_consent jika dicetak dari menu Informed Consent
if ($id_consent > 0) {
    $stmt = $pdo->prepare("
        SELECT ic.*, s.*, 
               TIMESTAMPDIFF(YEAR, s.tanggal_lahir, CURDATE()) AS umur_santri,
               ic.nama_wali_pengasuh AS wali_persetujuan,
               ic.hubungan AS hubungan_wali,
               ic.no_hp_wali AS kontak_wali,
               ic.diagnosa AS diagnosa_tindakan,
               ic.jenis_tindakan AS tindakan_persetujuan,
               ic.penjelasan_tindakan,
               ic.nakes_penjelas,
               ic.saksi_pesantren
        FROM informed_consent ic
        JOIN santri s ON ic.id_santri = s.id_santri
        WHERE ic.id_consent = ?
    ");
    $stmt->execute([$id_consent]);
    $data = $stmt->fetch();
}

// 2. Jika tidak ada id_consent, ambil berdasarkan id_rm (dari tombol di Rekam Medis)
if (!$data && $id_rm > 0) {
    // Cek apakah ada record informed_consent yang terhubung ke id_rm ini
    $stmt = $pdo->prepare("
        SELECT ic.*, s.*, 
               TIMESTAMPDIFF(YEAR, s.tanggal_lahir, CURDATE()) AS umur_santri,
               ic.nama_wali_pengasuh AS wali_persetujuan,
               ic.hubungan AS hubungan_wali,
               ic.no_hp_wali AS kontak_wali,
               ic.diagnosa AS diagnosa_tindakan,
               ic.jenis_tindakan AS tindakan_persetujuan,
               ic.penjelasan_tindakan,
               ic.nakes_penjelas,
               ic.saksi_pesantren
        FROM informed_consent ic
        JOIN santri s ON ic.id_santri = s.id_santri
        WHERE ic.id_rm = ?
        ORDER BY ic.id_consent DESC LIMIT 1
    ");
    $stmt->execute([$id_rm]);
    $data = $stmt->fetch();

    // Jika belum pernah diinput di tabel informed_consent, tarik dari rekam_medis santri bersangkutan
    if (!$data) {
        $stmtRM = $pdo->prepare("
            SELECT rm.*, s.*, 
                   TIMESTAMPDIFF(YEAR, s.tanggal_lahir, CURDATE()) AS umur_santri,
                   s.nama_wali AS wali_persetujuan,
                   'Orang Tua Kandung / Pengasuh Asrama' AS hubungan_wali,
                   s.no_hp_wali AS kontak_wali,
                   rm.diagnosis_utama AS diagnosa_tindakan,
                   rm.tindakan_medis AS tindakan_persetujuan,
                   'Telah diberikan edukasi mengenai kondisi dan tindakan medis yang diperlukan.' AS penjelasan_tindakan,
                   'Ns. Siti Rahmah, S.Kep' AS nakes_penjelas,
                   'Ustadz Pembina Asrama' AS saksi_pesantren
            FROM rekam_medis rm
            JOIN santri s ON rm.id_santri = s.id_santri
            WHERE rm.id_rm = ?
        ");
        $stmtRM->execute([$id_rm]);
        $data = $stmtRM->fetch();
    }
}

// 3. Jika hanya membawa id_santri
if (!$data && $id_santri > 0) {
    $stmtS = $pdo->prepare("
        SELECT s.*, 
               TIMESTAMPDIFF(YEAR, s.tanggal_lahir, CURDATE()) AS umur_santri,
               s.nama_wali AS wali_persetujuan,
               'Orang Tua Kandung / Pengasuh Asrama' AS hubungan_wali,
               s.no_hp_wali AS kontak_wali,
               '-' AS diagnosa_tindakan,
               'Tindakan Medis / Rujukan' AS tindakan_persetujuan,
               'Persetujuan tindakan medis bagi santri.' AS penjelasan_tindakan,
               'Ns. Siti Rahmah, S.Kep' AS nakes_penjelas,
               'Ustadz Pembina Asrama' AS saksi_pesantren
        FROM santri s
        WHERE s.id_santri = ?
    ");
    $stmtS->execute([$id_santri]);
    $data = $stmtS->fetch();
}

// Jika data masih tidak ditemukan sama sekali
if (!$data) {
    die("
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
        <div class='container my-5 text-center'>
            <div class='alert alert-warning py-4'>
                <h5>Data Informed Consent Tidak Ditemukan</h5>
                <p>Parameter santri atau rekam medis tidak valid.</p>
                <button onclick='window.close()' class='btn btn-secondary btn-sm'>Tutup Halaman</button>
            </div>
        </div>
    ");
}

// Perhitungan umur yang presisi
$umur = $data['umur_santri'] ?? 0;
if ($umur == 0 && !empty($data['tanggal_lahir'])) {
    $umur = (new DateTime())->diff(new DateTime($data['tanggal_lahir']))->y;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Informed Consent - <?= htmlspecialchars($data['nama_lengkap']) ?></title>
    <style>
        body { font-family: 'Times New Roman', serif; padding: 25px; line-height: 1.5; font-size: 13px; color: #000; }
        .kop { text-align: center; border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 15px; }
        .kop h2 { margin: 0; font-size: 18px; }
        .kop h3 { margin: 2px 0; font-size: 14px; color: #047857; }
        .kop p { margin: 0; font-size: 11px; }
        .judul { text-align: center; font-weight: bold; font-size: 14px; text-decoration: underline; }
        .sub { text-align: center; font-size: 11px; font-weight: bold; color: #b91c1c; margin-bottom: 15px; }
        .box-hukum { background: #fffbeb; border: 1px solid #fde68a; padding: 6px 10px; font-size: 11px; margin-bottom: 12px; }
        table.tbl { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.tbl td { padding: 3px 6px; vertical-align: top; }
        .pernyataan { background: #f8fafc; border: 1px solid #333; padding: 8px; margin: 12px 0; text-align: justify; }
        .ttd-row { width: 100%; margin-top: 25px; }
        .ttd-row td { text-align: center; width: 33.3%; vertical-align: top; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 15px;">
        <button onclick="window.print()">🖨️ Cetak Informed Consent</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <div class="kop">
        <h2>PONDOK PESANTREN AL AMIEN KEDIRI</h2>
        <h3>POS KESEHATAN PESANTREN (POSKESTREN)</h3>
        <p>Jl. KH. Hasyim Asy'ari, Kota Kediri | Telp: (0354) 771234</p>
    </div>

    <div class="judul">SURAT PERSETUJUAN TINDAKAN MEDIS / RUJUKAN (INFORMED CONSENT)</div>
    <div class="sub">KHUSUS PASIEN SANTRI DI BAWAH UMUR (&lt; 18 TAHUN)</div>

    <div class="box-hukum">
        <strong>Pemberitahuan Regulasi:</strong> Sesuai Permenkes RI tentang Persetujuan Tindakan Kedokteran, bagi pasien anak &lt; 18 tahun, persetujuan diberikan oleh Orang Tua, Wali, atau Pengasuh Asrama selaku kuasa wali santri di pesantren.
    </div>

    <p><strong>I. IDENTITAS PEMBERI PERSETUJUAN (WALI / PENGASUH ASRAMA):</strong></p>
    <table class="tbl">
        <tr><td width="28%">Nama Lengkap</td><td width="2%">:</td><td><strong><?= htmlspecialchars($data['wali_persetujuan'] ?? '-') ?></strong></td></tr>
        <tr><td>Hubungan</td><td>:</td><td><?= htmlspecialchars($data['hubungan_wali'] ?? 'Wali Santri / Pengasuh Asrama') ?></td></tr>
        <tr><td>Nomor WhatsApp / HP</td><td>:</td><td><?= htmlspecialchars($data['kontak_wali'] ?? '-') ?></td></tr>
    </table>

    <p><strong>II. IDENTITAS PASIEN SANTRI (&lt; 18 TAHUN):</strong></p>
    <table class="tbl">
        <tr><td width="28%">Nama Lengkap Santri</td><td width="2%">:</td><td><strong><?= htmlspecialchars($data['nama_lengkap']) ?></strong> (NIS: <?= htmlspecialchars($data['nis']) ?>)</td></tr>
        <tr><td>Umur / Tanggal Lahir</td><td>:</td><td><strong><?= $umur ?> Tahun</strong> / <?= formatTanggalIndo($data['tanggal_lahir']) ?></td></tr>
        <tr><td>Kelas / Asrama</td><td>:</td><td><?= htmlspecialchars($data['kelas']) ?> / <?= htmlspecialchars($data['asrama']) ?> (Komplek <?= htmlspecialchars($data['komplek']) ?>)</td></tr>
        <tr><td>Riwayat Alergi</td><td>:</td><td><strong style="color: #b91c1c;"><?= htmlspecialchars($data['riwayat_alergi'] ?: 'Tidak ada alergi') ?></strong></td></tr>
    </table>

    <p><strong>III. RINCIAN TINDAKAN / RUJUKAN:</strong></p>
    <table class="tbl">
        <tr><td width="28%">Diagnosa Klinis</td><td width="2%">:</td><td><strong style="color: #b91c1c;"><?= htmlspecialchars($data['diagnosa_tindakan']) ?></strong></td></tr>
        <tr><td>Tindakan / Rujukan Medis</td><td>:</td><td><strong><?= htmlspecialchars($data['tindakan_persetujuan']) ?></strong></td></tr>
        <tr><td>Penjelasan / Catatan</td><td>:</td><td><?= htmlspecialchars($data['penjelasan_tindakan']) ?></td></tr>
    </table>

    <div class="pernyataan">
        <em>Bismillahirrohmanirrohim.</em> Dengan ini saya menyatakan telah mendapat penjelasan lengkap mengenai kondisi santri dan menyatakan <strong>SETUJU</strong> untuk dilakukan tindakan medis dan/atau rujukan ke fasilitas kesehatan lanjutan demi keselamatan santri.
    </div>

    <table class="ttd-row">
        <tr>
            <td>Nakes Penjelas,<br><br><br><br><strong><?= htmlspecialchars($data['nakes_penjelas'] ?: 'Ns. Siti Rahmah, S.Kep') ?></strong></td>
            <td>Saksi Pesantren,<br><br><br><br><strong><?= htmlspecialchars($data['saksi_pesantren'] ?: 'Ustadz Pembina Asrama') ?></strong></td>
            <td>Wali / Kuasa Wali Santri,<br><br><br><br><strong><?= htmlspecialchars($data['wali_persetujuan'] ?? '-') ?></strong></td>
        </tr>
    </table>

    <div style="clear: both; margin-top: 30px; border-top: 1px dashed #ccc; padding-top: 5px; font-size: 9px; color: #666; display: flex; justify-content: space-between;">
        <span>Dokumen Resmi Poskestren Al Amien Kediri - Dicetak otomatis melalui Sistem DEK SANTRI</span>
        <span>&copy; <?= date('Y') ?> Poskestren Al Amien Kediri</span>
    </div>
</body>
</html>