<?php
require_once 'config.php';

$id_rm = intval($_GET['id_rm'] ?? 0);

// Query dilengkapi dengan penghitungan umur otomatis dari tanggal_lahir
$stmt = $pdo->prepare("
    SELECT rm.*, s.*, 
           TIMESTAMPDIFF(YEAR, s.tanggal_lahir, CURDATE()) AS umur 
    FROM rekam_medis rm 
    JOIN santri s ON rm.id_santri = s.id_santri 
    WHERE rm.id_rm = ?
");
$stmt->execute([$id_rm]);
$data = $stmt->fetch();

if (!$data) {
    $data = $pdo->query("
        SELECT rm.*, s.*, 
               TIMESTAMPDIFF(YEAR, s.tanggal_lahir, CURDATE()) AS umur 
        FROM rekam_medis rm 
        JOIN santri s ON rm.id_santri = s.id_santri 
        LIMIT 1
    ")->fetch();
}

// Fallback hitung umur via PHP jika data tanggal_lahir ada
$umurSantri = isset($data['umur']) ? $data['umur'] : 0;
if (!empty($data['tanggal_lahir'])) {
    $tglLahir = new DateTime($data['tanggal_lahir']);
    $hariIni = new DateTime();
    $umurSantri = $hariIni->diff($tglLahir)->y;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Rujukan Medis Pasien Santri</title>
    <style>
        body { font-family: 'Times New Roman', serif; padding: 25px; line-height: 1.5; font-size: 13px; }
        .kop { text-align: center; border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 16px; }
        .kop h2 { margin: 0; font-size: 19px; }
        .kop h3 { margin: 3px 0; font-size: 15px; color: #047857; }
        .kop p { margin: 0; font-size: 11px; }
        .judul { text-align: center; font-weight: bold; font-size: 15px; text-decoration: underline; }
        .nomor { text-align: center; margin-bottom: 15px; font-size: 12px; }
        .faskes-box { background: #f8fafc; border: 1px solid #cbd5e1; padding: 8px 12px; margin-bottom: 14px; border-radius: 4px; }
        table.tbl { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.tbl td { padding: 3px 6px; vertical-align: top; }
        .box { border: 1px solid #e2e8f0; padding: 6px 10px; margin-bottom: 6px; }
        .ttd { float: right; width: 250px; text-align: center; margin-top: 25px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 15px;">
        <button onclick="window.print()">🖨️ Cetak Rujukan</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <div class="kop">
        <h2>PONDOK PESANTREN AL AMIEN KEDIRI</h2>
        <h3>POS KESEHATAN PESANTREN (POSKESTREN)</h3>
        <p>Jl. KH. Hasyim Asy'ari, Kec. Pesantren, Kota Kediri | Telp: (0354) 771234</p>
    </div>

    <div class="judul">SURAT RUJUKAN MEDIS PASIEN SANTRI</div>
    <div class="nomor">Nomor: 018/RUJ-POSKESTREN/IX/2026</div>

    <div class="faskes-box">
        <div>Kepada Yth. Sejawat:</div>
        <div><strong>Poli Rawat Jalan / Instalasi Gawat Darurat (IGD)</strong></div>
        <div style="font-size: 14px; font-weight: bold; color: #047857;">Puskesmas Ngletih / RSUD Gambiran Kota Kediri</div>
    </div>

    <p>Dengan hormat, bersama ini kami rujuk pasien santri:</p>
    <table class="tbl">
        <tr><td width="28%">Nama Lengkap</td><td width="2%">:</td><td><strong><?= htmlspecialchars($data['nama_lengkap'] ?? '-') ?></strong> (<?= ($data['jenis_kelamin'] ?? 'L') == 'L' ? 'Laki-laki' : 'Perempuan' ?>)</td></tr>
        <tr><td>NIS / Umur</td><td>:</td><td><?= htmlspecialchars($data['nis'] ?? '-') ?> / <?= $umurSantri ?> Tahun</td></tr>
        <tr><td>Asrama / Kelas</td><td>:</td><td><?= htmlspecialchars($data['asrama'] ?? '-') ?> / <?= htmlspecialchars($data['kelas'] ?? '-') ?></td></tr>
        <tr><td>Riwayat Alergi</td><td>:</td><td><strong style="color: #b91c1c;"><?= htmlspecialchars(($data['riwayat_alergi'] ?? '') ?: 'Tidak ada') ?></strong></td></tr>
        <tr><td>Kontak Wali Santri</td><td>:</td><td><?= htmlspecialchars($data['nama_wali'] ?? '-') ?> (<?= htmlspecialchars($data['no_hp_wali'] ?? '-') ?>)</td></tr>
    </table>

    <div class="box"><strong>1. Anamnesa & Keluhan:</strong><br><?= htmlspecialchars($data['keluhan_utama'] ?? '-') ?>. <?= htmlspecialchars($data['anamnesa'] ?? '-') ?></div>
    <div class="box"><strong>2. TTV & Pemeriksaan Fisik:</strong><br>Suhu: <?= $data['suhu_tubuh'] ?? '-' ?> °C | TD: <?= $data['tekanan_darah'] ?: '-' ?> | Nadi: <?= $data['nadi'] ?: 80 ?> x/m</div>
    <div class="box"><strong>3. Diagnosa Medis Sementara:</strong><br><strong><?= htmlspecialchars($data['diagnosis_utama'] ?? '-') ?></strong> (Kode ICD: <?= htmlspecialchars($data['kode_icd10'] ?: '-') ?>)</div>
    <div class="box"><strong>4. Alasan Dirujuk:</strong><br>Memerlukan pemeriksaan penunjang laboratorium dan penanganan dokter spesialis lanjutan.</div>

    <div class="ttd">
        <p>Kediri, <?= formatTanggalIndo(date('Y-m-d')) ?><br>Dokter Penanggung Jawab,</p>
        <br><br><br>
        <p><strong>dr. H. Ahmad Fauzi</strong><br>SIP: 503/446/SIP-D/2023</p>
    </div>
    <div style="clear: both; margin-top: 40px; border-top: 1px dashed #ccc; padding-top: 5px; font-size: 9px; color: #666; display: flex; justify-content: space-between;">
        <span>Dokumen Resmi Poskestren Al Amien Kediri - Dicetak otomatis melalui Sistem DEK SANTRI</span>
        <span>&copy; <?= date('Y') ?> Poskestren Al Amien Kediri</span>
    </div>
</body>
</html>