-- phpMyAdmin SQL Dump
-- version 5.0.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 27, 2026 at 05:14 AM
-- Server version: 10.4.11-MariaDB
-- PHP Version: 7.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dek_santri_alamien`
--

-- --------------------------------------------------------

--
-- Table structure for table `informed_consent`
--

CREATE TABLE `informed_consent` (
  `id_consent` int(11) NOT NULL,
  `nomor_consent` varchar(50) NOT NULL,
  `id_santri` int(11) NOT NULL,
  `id_rm` int(11) DEFAULT NULL,
  `tanggal_persetujuan` datetime NOT NULL DEFAULT current_timestamp(),
  `nama_wali_pengasuh` varchar(100) NOT NULL,
  `hubungan` varchar(50) NOT NULL,
  `no_hp_wali` varchar(20) NOT NULL,
  `jenis_tindakan` varchar(100) NOT NULL,
  `diagnosa` varchar(150) NOT NULL,
  `penjelasan_tindakan` text NOT NULL,
  `status_persetujuan` enum('Setuju','Menolak') NOT NULL DEFAULT 'Setuju',
  `nakes_penjelas` varchar(100) NOT NULL,
  `saksi_pesantren` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `informed_consent`
--

INSERT INTO `informed_consent` (`id_consent`, `nomor_consent`, `id_santri`, `id_rm`, `tanggal_persetujuan`, `nama_wali_pengasuh`, `hubungan`, `no_hp_wali`, `jenis_tindakan`, `diagnosa`, `penjelasan_tindakan`, `status_persetujuan`, `nakes_penjelas`, `saksi_pesantren`) VALUES
(1, 'IC-20260925/868', 4, NULL, '2026-09-26 00:50:46', 'Wati', 'Orang Tua Kandung', '085647895328', 'RUJUK KE RS TERDEKAT', 'ASMA AKUT', 'menjalani rawat jalan selamat 3 hari tp blm sembuh', 'Setuju', 'Ns. Siti Rahmah, S.Kep', 'Ustadz Pembina Asrama'),
(2, 'IC-20260926/724', 3, NULL, '2026-09-26 11:14:43', 'Hj. Maryamah', 'Orang Tua Kandung', '081335678901', 'Rujuk ke puskesmas terdekat', 'ISPA', 'segera ditangani oleh dokter s[ecialis', 'Setuju', 'Ns. Siti Rahmah, S.Kep', 'Ustadz Pembina Asrama');

-- --------------------------------------------------------

--
-- Table structure for table `obat`
--

CREATE TABLE `obat` (
  `id_obat` int(11) NOT NULL,
  `kode_obat` varchar(20) NOT NULL,
  `nama_obat` varchar(100) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `satuan` varchar(20) NOT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `stok_minimum` int(11) NOT NULL DEFAULT 10,
  `tgl_kadaluarsa` date NOT NULL,
  `lokasi_rak` varchar(50) DEFAULT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `obat`
--

INSERT INTO `obat` (`id_obat`, `kode_obat`, `nama_obat`, `kategori`, `satuan`, `stok`, `stok_minimum`, `tgl_kadaluarsa`, `lokasi_rak`, `keterangan`) VALUES
(1, 'OBT-001', 'Paracetamol 500 mg', 'Analgesik/Antipiretik', 'Tablet', 176, 50, '2027-12-31', 'Lemari A - Rak 1', 'Pereda demam & nyeri'),
(2, 'OBT-002', 'Antasida Doen Tablet Kunyah', 'Antasida/Lambung', 'Tablet', 118, 40, '2027-08-15', 'Lemari A - Rak 2', 'Meredakan maag dan asam lambung'),
(3, 'OBT-003', 'Permethrin Cream 5% (Scabimite)', 'Obat Kulit/Salep', 'Tube', 38, 25, '2027-05-20', 'Lemari B - Rak Kulit', 'Obat kutu & scabies pesantren'),
(4, 'OBT-004', 'Salep 2-4 Sulfur', 'Obat Kulit/Salep', 'Tube', 35, 20, '2027-10-10', 'Lemari B - Rak Kulit', 'Gatal & jamur kulit santri'),
(5, 'OBT-005', 'Amoxicillin 500 mg', 'Antibiotik', 'Kaplet', 88, 30, '2027-04-18', 'Lemari A - Rak 3', 'Antibiotik infeksi bakteri'),
(6, 'OBT-006', 'Cetirizine 10 mg', 'Antihistamin/Anti Alergi', 'Tablet', 64, 20, '2027-11-25', 'Lemari A - Rak 2', 'Alergi dan gatal-gatal'),
(7, 'OBT-008', 'OBH Sirup Batuk Hitam 100 ml', 'Obat Batuk/Flu', 'Botol', 22, 15, '2027-06-30', 'Lemari C - Sirup', 'Meredakan batuk berdahak');

-- --------------------------------------------------------

--
-- Table structure for table `petugas_poskestren`
--

CREATE TABLE `petugas_poskestren` (
  `id_petugas` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `profesi` enum('Dokter','Perawat','Apoteker','Kader Santri','Admin') NOT NULL DEFAULT 'Perawat',
  `no_hp` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `petugas_poskestren`
--

INSERT INTO `petugas_poskestren` (`id_petugas`, `username`, `password`, `nama_lengkap`, `profesi`, `no_hp`, `created_at`) VALUES
(1, 'admin', '4fb6d0045fa11349b273e3500bdff2b1', 'Ns. Siti Rahmah, S.Kep', 'Perawat', '081234567890', '2026-09-25 17:34:49'),
(2, 'dokter', 'cab2d8232139ee4f469a920732578f71', 'dr. H. Ahmad Fauzi', 'Dokter', '081298765432', '2026-09-25 17:34:49'),
(3, 'kader', '88f6a6ed7283ea5be22643709098edbe', 'Ahmad Zaini (Kader Santri Husada)', 'Kader Santri', '085712345678', '2026-09-25 18:12:26'),
(4, 'mahmud', 'e1aa6aa12922a1275c9c8f8e54bac8d6', 'Muhammad Ali Mahmud', 'Admin', '', '2026-09-26 02:44:10');

-- --------------------------------------------------------

--
-- Table structure for table `rekam_medis`
--

CREATE TABLE `rekam_medis` (
  `id_rm` int(11) NOT NULL,
  `nomor_rm` varchar(30) NOT NULL,
  `id_santri` int(11) NOT NULL,
  `id_petugas` int(11) DEFAULT NULL,
  `tanggal_kunjungan` datetime NOT NULL DEFAULT current_timestamp(),
  `keluhan_utama` text NOT NULL,
  `anamnesa` text NOT NULL,
  `tekanan_darah` varchar(20) DEFAULT NULL,
  `berat_badan` decimal(5,2) DEFAULT 0.00,
  `tinggi_badan` decimal(5,2) DEFAULT 0.00,
  `imt` decimal(4,1) DEFAULT 0.0,
  `status_gizi` varchar(50) DEFAULT NULL,
  `suhu_tubuh` decimal(4,1) NOT NULL,
  `nadi` int(11) DEFAULT NULL,
  `laju_nafas` int(11) DEFAULT NULL,
  `berat_badan_kunjungan` decimal(5,2) DEFAULT NULL,
  `diagnosis_utama` varchar(150) NOT NULL,
  `kode_icd10` varchar(10) DEFAULT NULL,
  `tindakan_medis` enum('Rawat Jalan','Istirahat di Asrama','Rawat Inap Poskestren','Rujuk ke Puskesmas','Rujuk ke Rumah Sakit') NOT NULL,
  `durasi_istirahat_hari` int(11) DEFAULT 0,
  `lokasi_rujukan` varchar(100) DEFAULT NULL,
  `catatan_edukasi` text DEFAULT NULL,
  `status_pemeriksaan` enum('Selesai','Observasi','Dirujuk') DEFAULT 'Selesai',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `rekam_medis`
--

INSERT INTO `rekam_medis` (`id_rm`, `nomor_rm`, `id_santri`, `id_petugas`, `tanggal_kunjungan`, `keluhan_utama`, `anamnesa`, `tekanan_darah`, `berat_badan`, `tinggi_badan`, `imt`, `status_gizi`, `suhu_tubuh`, `nadi`, `laju_nafas`, `berat_badan_kunjungan`, `diagnosis_utama`, `kode_icd10`, `tindakan_medis`, `durasi_istirahat_hari`, `lokasi_rujukan`, `catatan_edukasi`, `status_pemeriksaan`, `created_at`) VALUES
(1, 'RM-20260925-953', 1, NULL, '2026-09-26 00:42:56', 'mual muntah', 'sudah 3 hari', '', '0.00', '0.00', '0.0', NULL, '36.8', 80, 20, NULL, 'akut', '', 'Rawat Jalan', 2, NULL, 'minum obat sesuai resep', 'Selesai', '2026-09-25 17:42:56'),
(2, 'RM-20260925-698', 4, NULL, '2026-09-26 00:57:32', 'mual muntah', 'sudah 3 hari', '', '0.00', '0.00', '0.0', NULL, '36.8', 80, 20, NULL, 'akut', '', 'Rujuk ke Puskesmas', 0, NULL, '', 'Selesai', '2026-09-25 17:57:32'),
(3, 'RM-20260926-976', 3, NULL, '2026-09-26 08:21:40', 'demam, muntah', 'sudah 3 hari panas', '', '0.00', '0.00', '0.0', NULL, '38.0', 80, 20, NULL, 'gastritis akut', '', 'Istirahat di Asrama', 3, NULL, '', 'Selesai', '2026-09-26 01:21:40'),
(4, 'RM-20260926-740', 7, NULL, '2026-09-26 19:45:10', 'demam, muntah, panas', 'sudah 2 hari panas dan muntah', '115/80', '50.00', '156.00', '20.5', 'Normal / Ideal', '39.5', 80, 20, NULL, 'malnutrisi , keracunan makanan', '', 'Istirahat di Asrama', 3, NULL, 'cuci tangan sebelum makan, jangan jajan sembarang', 'Selesai', '2026-09-26 12:45:10');

-- --------------------------------------------------------

--
-- Table structure for table `resep_detail`
--

CREATE TABLE `resep_detail` (
  `id_resep` int(11) NOT NULL,
  `id_rm` int(11) NOT NULL,
  `id_obat` int(11) NOT NULL,
  `dosis` varchar(50) NOT NULL,
  `aturan_pakai` varchar(100) NOT NULL,
  `jumlah` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `resep_detail`
--

INSERT INTO `resep_detail` (`id_resep`, `id_rm`, `id_obat`, `dosis`, `aturan_pakai`, `jumlah`) VALUES
(1, 1, 6, '3x1', 'sesudah makan', 1),
(2, 2, 1, '3x1', 'sesudah makan', 2),
(3, 2, 2, '3x1', 'sesudah makan', 2),
(4, 3, 5, '3x1', 'sesudah makan', 1),
(5, 3, 1, '3x1', 'sesudah makan', 1),
(6, 4, 5, '3x1', 'sesudah makan', 1),
(7, 4, 1, '3x1', 'sesudah makan', 1);

-- --------------------------------------------------------

--
-- Table structure for table `santri`
--

CREATE TABLE `santri` (
  `id_santri` int(11) NOT NULL,
  `nis` varchar(20) NOT NULL,
  `nama_lengkap` varchar(120) NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `tempat_lahir` varchar(60) NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `asrama` varchar(100) NOT NULL,
  `komplek` enum('Putra','Putri') NOT NULL,
  `kelas` varchar(50) NOT NULL,
  `golongan_darah` enum('A','B','AB','O','Belum Tahu') DEFAULT 'Belum Tahu',
  `berat_badan` decimal(5,2) DEFAULT 0.00,
  `tinggi_badan` decimal(5,2) DEFAULT 0.00,
  `riwayat_alergi` text DEFAULT NULL,
  `riwayat_penyakit_khusus` text DEFAULT NULL,
  `nama_wali` varchar(100) NOT NULL,
  `no_hp_wali` varchar(20) NOT NULL,
  `alamat_asal` text NOT NULL,
  `status` enum('Aktif','Alumni','Cuti Sakit') DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `santri`
--

INSERT INTO `santri` (`id_santri`, `nis`, `nama_lengkap`, `jenis_kelamin`, `tempat_lahir`, `tanggal_lahir`, `asrama`, `komplek`, `kelas`, `golongan_darah`, `berat_badan`, `tinggi_badan`, `riwayat_alergi`, `riwayat_penyakit_khusus`, `nama_wali`, `no_hp_wali`, `alamat_asal`, `status`, `created_at`) VALUES
(1, '202401001', 'Ahmad Faiz Al-Faruqi', 'L', 'Kediri', '2008-04-12', 'Asrama Al-Faruq (Kamar 04)', 'Putra', 'XII MA Al Amien IPA 1', 'O', '58.00', '168.00', 'Tidak ada', 'Riwayat Maag / Gastritis', 'H. Sudarsono, S.Pd', '081234567891', 'Kec. Pesantren, Kota Kediri', 'Aktif', '2026-09-25 17:14:25'),
(2, '202401002', 'Muhammad Zaki Rabbani', 'L', 'Nganjuk', '2009-08-20', 'Asrama Al-Ghazali (Kamar 02)', 'Putra', 'XI MA Al Amien IPS 2', 'B', '52.00', '162.00', 'Alergi Dingin', 'Tidak ada', 'Drs. Supriyanto', '085790123456', 'Kertosono, Nganjuk', 'Aktif', '2026-09-25 17:14:25'),
(3, '202401003', 'Fatimah Az-Zahra', 'P', 'Tulungagung', '2008-11-05', 'Asrama Khodijah (Kamar 07)', 'Putri', 'XI MA Al Amien Keagamaan', 'A', '47.00', '156.00', 'Alergi Penisilin (Amoxicillin)', 'Tidak ada', 'Hj. Maryamah', '081335678901', 'Ngunut, Tulungagung', 'Aktif', '2026-09-25 17:14:25'),
(4, '202410103', 'Ali bin mahmud', 'L', 'Kediri', '2020-01-14', 'Al Amien / Kamar 07', 'Putra', 'SDI', 'A', '56.00', '160.00', 'udang', '-', 'Wati', '085647895328', 'Jombang', 'Aktif', '2026-09-25 17:33:53'),
(6, '202401004', 'Siti Aisyah Putri', 'P', 'Blitar', '2010-02-14', 'Asrama Aisyah (Kamar 03)', 'Putri', 'X MA Al Amien IPA 2', 'AB', '45.00', '153.00', 'Tidak ada', 'Asma Kambuhan', 'H. Bambang Irawan', '081223344556', 'Sutojayan, Blitar', 'Aktif', '2026-09-26 03:53:51'),
(7, '202401005', 'Rizky Dwi Mahendra', 'L', 'Kediri', '2002-05-18', 'Asrama Abu Bakar (Kamar 01)', 'Putra', 'XI MA Al Amien IPA 1', 'O', '50.00', '156.00', 'Tidak ada', 'Tidak ada', 'Bpk. Hendro', '082133445566', 'Mojoroto, Kota Kediri', 'Aktif', '2026-09-26 03:53:51');

-- --------------------------------------------------------

--
-- Table structure for table `surat_izin_sakit`
--

CREATE TABLE `surat_izin_sakit` (
  `id_surat` int(11) NOT NULL,
  `nomor_surat` varchar(50) NOT NULL,
  `id_rm` int(11) NOT NULL,
  `id_santri` int(11) NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `jumlah_hari` int(11) NOT NULL,
  `keringanan` text NOT NULL,
  `petugas_pemberi_izin` varchar(100) NOT NULL,
  `catatan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `surat_izin_sakit`
--

INSERT INTO `surat_izin_sakit` (`id_surat`, `nomor_surat`, `id_rm`, `id_santri`, `tanggal_mulai`, `tanggal_selesai`, `jumlah_hari`, `keringanan`, `petugas_pemberi_izin`, `catatan`) VALUES
(2, '515/POSKESTREN-AM/09/2026', 1, 1, '2026-09-25', '2026-09-27', 2, 'KBM Madrasah, Pengajian Kitab Asrama, Piket', 'Ns. Siti Rahmah, S.Kep', NULL),
(3, '365/POSKESTREN-AM/09/2026', 1, 2, '2026-09-26', '2026-09-28', 2, 'KBM Madrasah, Pengajian Asrama, Piket Kebersihan', 'Ns. Siti Rahmah, S.Kep', ''),
(4, '511/POSKESTREN-AM/09/2026', 3, 3, '2026-09-26', '2026-09-29', 3, 'KBM Madrasah, Pengajian Kitab Asrama, Piket', 'Ns. Siti Rahmah, S.Kep', NULL),
(5, '672/POSKESTREN-AM/09/2026', 2, 4, '2026-09-27', '2026-09-30', 3, 'KBM Madrasah, Pengajian Asrama, Piket Kebersihan', 'Ns. Siti Rahmah, S.Kep', ''),
(6, '261/POSKESTREN-AM/09/2026', 4, 7, '2026-09-26', '2026-09-29', 3, 'KBM Madrasah, Pengajian Kitab Asrama, Piket', 'Ns. Siti Rahmah, S.Kep', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `surat_rujukan`
--

CREATE TABLE `surat_rujukan` (
  `id_rujukan` int(11) NOT NULL,
  `nomor_surat` varchar(50) NOT NULL,
  `id_rm` int(11) DEFAULT NULL,
  `id_santri` int(11) NOT NULL,
  `tanggal_surat` date NOT NULL,
  `faskes_tujuan` varchar(100) NOT NULL,
  `poli_tujuan` varchar(100) NOT NULL,
  `diagnosa_sementara` varchar(150) NOT NULL,
  `kode_icd10` varchar(20) DEFAULT NULL,
  `anamnesa_ringkas` text NOT NULL,
  `ttv_fisik` text NOT NULL,
  `terapi_awal` text NOT NULL,
  `alasan_rujukan` text NOT NULL,
  `dokter_pengirim` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `surat_rujukan`
--

INSERT INTO `surat_rujukan` (`id_rujukan`, `nomor_surat`, `id_rm`, `id_santri`, `tanggal_surat`, `faskes_tujuan`, `poli_tujuan`, `diagnosa_sementara`, `kode_icd10`, `anamnesa_ringkas`, `ttv_fisik`, `terapi_awal`, `alasan_rujukan`, `dokter_pengirim`) VALUES
(1, '975/RUJ-POSKESTREN/09/2026', 1, 1, '2026-09-25', 'Puskesmas Ngletih Kota Kediri', 'Instalasi Gawat Darurat (IGD) / Rawat Inap', 'mUAL AKUT', '', 'NYERI BAWAH PERUT', 'TD:110/70', 'Paracetamol infus / oral, kompres hangat, rehidrasi', 'Memerlukan pemeriksaan penunjang lab darah lengkap dan evaluasi dokter spesialis bedah/anak.', 'dr. H. Ahmad Fauzi'),
(2, '170/RUJ-POSKESTREN/09/2026', 3, 3, '2026-09-26', 'RSUD Gambiran Kota Kediri', 'Instalasi Gawat Darurat (IGD) / Rawat Inap', 'Mual Akut', '', 'mendadak muntah muntan', 'TD : 120;80, SUHU : 39', 'Paracetamol infus / oral, kompres hangat, rehidrasi', 'Memerlukan pemeriksaan penunjang lab darah lengkap dan evaluasi dokter spesialis bedah/anak.', 'dr. H. Ahmad Fauzi');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `informed_consent`
--
ALTER TABLE `informed_consent`
  ADD PRIMARY KEY (`id_consent`),
  ADD UNIQUE KEY `nomor_consent` (`nomor_consent`),
  ADD KEY `id_santri` (`id_santri`);

--
-- Indexes for table `obat`
--
ALTER TABLE `obat`
  ADD PRIMARY KEY (`id_obat`),
  ADD UNIQUE KEY `kode_obat` (`kode_obat`);

--
-- Indexes for table `petugas_poskestren`
--
ALTER TABLE `petugas_poskestren`
  ADD PRIMARY KEY (`id_petugas`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `rekam_medis`
--
ALTER TABLE `rekam_medis`
  ADD PRIMARY KEY (`id_rm`),
  ADD UNIQUE KEY `nomor_rm` (`nomor_rm`),
  ADD KEY `id_santri` (`id_santri`);

--
-- Indexes for table `resep_detail`
--
ALTER TABLE `resep_detail`
  ADD PRIMARY KEY (`id_resep`),
  ADD KEY `id_rm` (`id_rm`),
  ADD KEY `id_obat` (`id_obat`);

--
-- Indexes for table `santri`
--
ALTER TABLE `santri`
  ADD PRIMARY KEY (`id_santri`),
  ADD UNIQUE KEY `nis` (`nis`);

--
-- Indexes for table `surat_izin_sakit`
--
ALTER TABLE `surat_izin_sakit`
  ADD PRIMARY KEY (`id_surat`),
  ADD UNIQUE KEY `nomor_surat` (`nomor_surat`),
  ADD KEY `id_rm` (`id_rm`),
  ADD KEY `id_santri` (`id_santri`);

--
-- Indexes for table `surat_rujukan`
--
ALTER TABLE `surat_rujukan`
  ADD PRIMARY KEY (`id_rujukan`),
  ADD UNIQUE KEY `nomor_surat` (`nomor_surat`),
  ADD KEY `id_santri` (`id_santri`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `informed_consent`
--
ALTER TABLE `informed_consent`
  MODIFY `id_consent` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `obat`
--
ALTER TABLE `obat`
  MODIFY `id_obat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `petugas_poskestren`
--
ALTER TABLE `petugas_poskestren`
  MODIFY `id_petugas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `rekam_medis`
--
ALTER TABLE `rekam_medis`
  MODIFY `id_rm` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `resep_detail`
--
ALTER TABLE `resep_detail`
  MODIFY `id_resep` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `santri`
--
ALTER TABLE `santri`
  MODIFY `id_santri` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `surat_izin_sakit`
--
ALTER TABLE `surat_izin_sakit`
  MODIFY `id_surat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `surat_rujukan`
--
ALTER TABLE `surat_rujukan`
  MODIFY `id_rujukan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `informed_consent`
--
ALTER TABLE `informed_consent`
  ADD CONSTRAINT `informed_consent_ibfk_1` FOREIGN KEY (`id_santri`) REFERENCES `santri` (`id_santri`) ON DELETE CASCADE;

--
-- Constraints for table `rekam_medis`
--
ALTER TABLE `rekam_medis`
  ADD CONSTRAINT `rekam_medis_ibfk_1` FOREIGN KEY (`id_santri`) REFERENCES `santri` (`id_santri`) ON DELETE CASCADE;

--
-- Constraints for table `resep_detail`
--
ALTER TABLE `resep_detail`
  ADD CONSTRAINT `resep_detail_ibfk_1` FOREIGN KEY (`id_rm`) REFERENCES `rekam_medis` (`id_rm`) ON DELETE CASCADE,
  ADD CONSTRAINT `resep_detail_ibfk_2` FOREIGN KEY (`id_obat`) REFERENCES `obat` (`id_obat`);

--
-- Constraints for table `surat_izin_sakit`
--
ALTER TABLE `surat_izin_sakit`
  ADD CONSTRAINT `surat_izin_sakit_ibfk_1` FOREIGN KEY (`id_rm`) REFERENCES `rekam_medis` (`id_rm`) ON DELETE CASCADE,
  ADD CONSTRAINT `surat_izin_sakit_ibfk_2` FOREIGN KEY (`id_santri`) REFERENCES `santri` (`id_santri`) ON DELETE CASCADE;

--
-- Constraints for table `surat_rujukan`
--
ALTER TABLE `surat_rujukan`
  ADD CONSTRAINT `surat_rujukan_ibfk_1` FOREIGN KEY (`id_santri`) REFERENCES `santri` (`id_santri`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
