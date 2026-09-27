<?php
require_once 'config.php';

$id_rm = intval($_GET['id_rm'] ?? 0);
$stmt = $pdo->prepare("
    SELECT rm.*, s.nama_lengkap, s.nis, s.asrama, s.kelas, s.komplek,
           sis.nomor_surat, sis.tanggal_mulai, sis.tanggal_selesai, sis.jumlah_hari, sis.keringanan, sis.petugas_pemberi_izin
    FROM rekam_medis rm 
    JOIN santri s ON rm.id_santri = s.id_santri 
    LEFT JOIN surat_izin_sakit sis ON rm.id_rm = sis.id_rm
    WHERE rm.id_rm = ?
");
$stmt->execute([$id_rm]);
$data = $stmt->fetch();

if (!$data) {
    // Coba ambil data santri pertama jika belum ada parameter
    $data = $pdo->query("SELECT rm.*, s.nama_lengkap, s.nis, s.asrama, s.kelas, s.komplek, '049/POSKESTREN-AM/09/2026' as nomor_surat, CURDATE() as tanggal_mulai, DATE_ADD(CURDATE(), INTERVAL 2 DAY) as tanggal_selesai, 2 as jumlah_hari, 'KBM Madrasah, Mengaji, Piket' as keringanan, 'Ns. Siti Rahmah, S.Kep' as petugas_pemberi_izin FROM rekam_medis rm JOIN santri s ON rm.id_santri = s.id_santri LIMIT 1")->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Izin Sakit Santri - Poskestren Al Amien Kediri</title>
    <style>
        body { font-family: 'Times New Roman', serif; padding: 25px; line-height: 1.6; font-size: 13.5px; color: #000; }
        .kop-surat { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px; }
        .kop-surat h2 { margin: 0; font-size: 19px; }
        .kop-surat h3 { margin: 3px 0; font-size: 15px; color: #047857; }
        .kop-surat p { margin: 0; font-size: 11px; }
        .judul-surat { text-align: center; font-weight: bold; font-size: 15px; text-decoration: underline; margin-bottom: 4px; }
        .nomor-surat { text-align: center; margin-bottom: 20px; font-size: 12.5px; }
        table.identitas { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table.identitas td { padding: 4px 6px; vertical-align: top; }
        .tanda-tangan { float: right; width: 250px; text-align: center; margin-top: 25px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 15px; padding: 8px; background: #e0f2fe; border: 1px solid #7dd3fc; border-radius: 4px;">
        <button onclick="window.print()" style="padding: 5px 12px; cursor: pointer; font-weight: bold;">🖨️ Cetak Surat</button>
        <button onclick="window.close()" style="padding: 5px 12px; cursor: pointer;">Tutup</button>
    </div>

    <div class="kop-surat">
        <h2>PONDOK PESANTREN AL AMIEN KEDIRI</h2>
        <h3>POS KESEHATAN PESANTREN (POSKESTREN)</h3>
        <p>Jl. KH. Hasyim Asy'ari, Kec. Pesantren, Kota Kediri, Jawa Timur 64133 | Telp: (0354) 771234</p>
    </div>

    <div class="judul-surat">SURAT KETERANGAN ISTIRAHAT SAKIT</div>
    <div class="nomor-surat">Nomor: <?= htmlspecialchars($data['nomor_surat'] ?: '049/POSKESTREN-AM/09/2026') ?></div>

    <p>Yang bertanda tangan di bawah ini, Tenaga Medis Poskestren Al Amien Kediri menerangkan bahwa:</p>

    <table class="identitas">
        <tr><td width="26%">Nama Lengkap Santri</td><td width="2%">:</td><td><strong><?= htmlspecialchars($data['nama_lengkap']) ?></strong></td></tr>
        <tr><td>Nomor Induk Santri (NIS)</td><td>:</td><td><?= htmlspecialchars($data['nis']) ?></td></tr>
        <tr><td>Kelas / Asrama</td><td>:</td><td><?= htmlspecialchars($data['kelas']) ?> / <?= htmlspecialchars($data['asrama']) ?> (Komplek <?= htmlspecialchars($data['komplek']) ?>)</td></tr>
        <tr><td>Hasil Diagnosa Medis</td><td>:</td><td><strong><?= htmlspecialchars($data['diagnosis_utama']) ?></strong> (Kode ICD: <?= htmlspecialchars($data['kode_icd10'] ?: '-') ?>)</td></tr>
        <tr><td>Tanda Vital (TTV)</td><td>:</td><td>Suhu: <?= $data['suhu_tubuh'] ?> °C | Tekanan Darah: <?= $data['tekanan_darah'] ?: '-' ?></td></tr>
    </table>

    <p>Berdasarkan hasil pemeriksaan klinis, santri tersebut dalam kondisi sakit dan membutuhkan waktu istirahat selama <strong><?= $data['jumlah_hari'] ?: 2 ?> hari</strong> terhitung mulai tanggal <strong><?= formatTanggalIndo($data['tanggal_mulai']) ?></strong> s.d. <strong><?= formatTanggalIndo($data['tanggal_selesai']) ?></strong>.</p>

    <p>Diberikan keringanan dispensasi untuk:</p>
    <ul>
        <li>Kegiatan Belajar Mengajar (KBM) Madrasah</li>
        <li>Pengajian Kitab & Taklim Asrama</li>
        <li>Piket Kebersihan Lingkungan Pondok</li>
    </ul>

    <p>Demikian surat keterangan ini diberikan agar dapat dipergunakan sebagaimana mestinya.</p>

    <div class="tanda-tangan">
        <p>Kediri, <?= formatTanggalIndo(date('Y-m-d')) ?><br>Petugas Medis Poskestren,</p>
        <br><br><br>
        <p><strong><?= htmlspecialchars($data['petugas_pemberi_izin'] ?: 'Ns. Siti Rahmah, S.Kep') ?></strong><br>SIP: 19890412 201503 2 001</p>
    </div>
    <div style="clear: both; margin-top: 40px; border-top: 1px dashed #ccc; padding-top: 5px; font-size: 9px; color: #666; display: flex; justify-content: space-between;">
        <span>Dokumen Resmi Poskestren Al Amien Kediri - Dicetak otomatis melalui Sistem DEK SANTRI</span>
        <span>&copy; <?= date('Y') ?> Poskestren Al Amien Kediri</span>
    </div>
</body>
</html>