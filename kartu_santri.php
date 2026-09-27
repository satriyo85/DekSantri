<?php
require_once 'config.php';

$id_santri = intval($_GET['id'] ?? 0);

// Query dilengkapi dengan penghitungan umur otomatis dari tanggal_lahir
$stmt = $pdo->prepare("
    SELECT *, 
           TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) AS umur 
    FROM santri 
    WHERE id_santri = ?
");
$stmt->execute([$id_santri]);
$santri = $stmt->fetch();

if (!$santri) {
    $santri = $pdo->query("
        SELECT *, 
               TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) AS umur 
        FROM santri 
        LIMIT 1
    ")->fetch();
}

// Fallback hitung umur melalui PHP jika data tanggal_lahir ada
$umurSantri = isset($santri['umur']) ? $santri['umur'] : 0;
if (!empty($santri['tanggal_lahir'])) {
    $tglLahir = new DateTime($santri['tanggal_lahir']);
    $hariIni = new DateTime();
    $umurSantri = $hariIni->diff($tglLahir)->y;
}

$riwayatRM = $pdo->prepare("SELECT * FROM rekam_medis WHERE id_santri = ? ORDER BY tanggal_kunjungan DESC LIMIT 5");
$riwayatRM->execute([$santri['id_santri']]);
$kunjungan = $riwayatRM->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Berobat Santri - <?= htmlspecialchars($santri['nama_lengkap']) ?></title>
    <style>
        body { font-family: 'Times New Roman', serif; padding: 25px; line-height: 1.5; font-size: 13px; color: #000; }
        .kop { text-align: center; border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 15px; }
        .kop h2 { margin: 0; font-size: 18px; }
        .kop h3 { margin: 2px 0; font-size: 14px; color: #047857; }
        .kop p { margin: 0; font-size: 11px; }
        .judul { text-align: center; font-weight: bold; font-size: 15px; text-decoration: underline; margin-bottom: 15px; }
        .box-santri { background: #f8fafc; border: 1px solid #cbd5e1; padding: 12px; margin-bottom: 18px; border-radius: 6px; }
        table.tbl { width: 100%; border-collapse: collapse; }
        table.tbl td { padding: 4px 6px; }
        table.history { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px; }
        table.history th, table.history td { border: 1px solid #333; padding: 6px; }
        table.history th { background: #e2e8f0; }
        .ttd { float: right; width: 250px; text-align: center; margin-top: 25px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 15px;">
        <button onclick="window.print()">🖨️ Cetak Kartu Berobat Santri</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <div class="kop">
        <h2>PONDOK PESANTREN AL AMIEN KEDIRI</h2>
        <h3>POS KESEHATAN PESANTREN (POSKESTREN)</h3>
        <p>Jl. KH. Hasyim Asy'ari, Kota Kediri | Telp: (0354) 771234</p>
    </div>

    <div class="judul">KARTU BEROBAT SANTRI (KBS)</div>

    <div class="box-santri">
        <table class="tbl">
            <tr>
                <td width="22%"><strong>Nama Lengkap</strong></td><td width="2%">:</td><td width="36%"><strong><?= htmlspecialchars($santri['nama_lengkap']) ?></strong></td>
                <td width="18%"><strong>Gol. Darah</strong></td><td width="2%">:</td><td style="color:#b91c1c; font-weight:bold;"><?= htmlspecialchars($santri['golongan_darah']) ?></td>
            </tr>
            <tr>
                <td><strong>NIS / Umur</strong></td><td>:</td><td><?= htmlspecialchars($santri['nis']) ?> / <?= $umurSantri ?> Tahun</td>
                <td><strong>Jenis Kelamin</strong></td><td>:</td><td><?= $santri['jenis_kelamin'] == 'L' ? 'Laki-laki (Putra)' : 'Perempuan (Putri)' ?></td>
            </tr>
            <tr>
                <td><strong>Asrama / Kelas</strong></td><td>:</td><td><?= htmlspecialchars($santri['asrama']) ?> (<?= htmlspecialchars($santri['kelas']) ?>)</td>
                <td><strong>Komplek</strong></td><td>:</td><td><?= htmlspecialchars($santri['komplek']) ?></td>
            </tr>
            <tr>
                <td><strong>Riwayat Alergi</strong></td><td>:</td><td colspan="4"><strong style="color:#b91c1c;"><?= htmlspecialchars($santri['riwayat_alergi'] ?: 'Tidak ada alergi obat') ?></strong></td>
            </tr>
            <tr>
                <td><strong>Wali / No. HP</strong></td><td>:</td><td colspan="4"><?= htmlspecialchars($santri['nama_wali']) ?> (<?= htmlspecialchars($santri['no_hp_wali']) ?>) - <?= htmlspecialchars($santri['alamat_asal']) ?></td>
            </tr>
        </table>
    </div>

    <h6 style="font-weight: bold; margin-bottom: 4px;">CATATAN RIWAYAT PEMERIKSAAN KESEHATAN SANTRI:</h6>
    <table class="history">
        <thead>
            <tr>
                <th width="15%">Tanggal</th>
                <th width="35%">Keluhan & Anamnesa</th>
                <th width="25%">Diagnosa (ICD-10)</th>
                <th width="15%">Tindakan</th>
                <th width="10%">Paraf</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($kunjungan)): ?>
                <tr><td colspan="5" style="text-align: center; color: #888;">Belum ada catatan riwayat berobat.</td></tr>
            <?php else: foreach ($kunjungan as $k): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($k['tanggal_kunjungan'])) ?></td>
                    <td><?= htmlspecialchars($k['keluhan_utama']) ?></td>
                    <td><strong><?= htmlspecialchars($k['diagnosis_utama']) ?></strong> (<?= htmlspecialchars($k['kode_icd10'] ?: '-') ?>)</td>
                    <td><?= htmlspecialchars($k['tindakan_medis']) ?></td>
                    <td style="text-align: center;">Nakes</td>
                </tr>
            <?php endforeach; endif; ?>
            <?php for ($i = count($kunjungan); $i < 4; $i++): ?>
                <tr><td style="height: 24px;">&nbsp;</td><td></td><td></td><td></td><td></td></tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <div class="ttd">
        <p>Kediri, <?= formatTanggalIndo(date('Y-m-d')) ?><br>Pengelola Poskestren Al Amien,</p>
        <br><br><br>
        <p><strong>Ns. Siti Rahmah, S.Kep</strong></p>
    </div>
</body>
</html>