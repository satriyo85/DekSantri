<?php
require_once 'config.php';

// Jika sudah login, langsung alihkan ke dashboard
if (isset($_SESSION['user_poskestren'])) {
    header("Location: index.php");
    exit;
}

$pesanError = '';
$pesanSukses = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = sanitize($_POST['nama_lengkap'] ?? '');
    $username     = strtolower(trim(sanitize($_POST['username'] ?? '')));
    $password     = $_POST['password'] ?? '';
    $konfirmasi   = $_POST['konfirmasi_password'] ?? '';
    $profesi      = sanitize($_POST['profesi'] ?? 'Kader Santri');
    $no_hp        = sanitize($_POST['no_hp'] ?? '');

    // Validasi Input
    if (empty($nama_lengkap) || empty($username) || empty($password)) {
        $pesanError = 'Nama lengkap, username, dan kata sandi wajib diisi!';
    } elseif ($password !== $konfirmasi) {
        $pesanError = 'Konfirmasi kata sandi tidak cocok!';
    } elseif (strlen($password) < 5) {
        $pesanError = 'Kata sandi minimal 5 karakter demi keamanan!';
    } else {
        // Cek apakah username sudah dipakai
        $cek = $pdo->prepare("SELECT id_petugas FROM petugas_poskestren WHERE username = ?");
        $cek->execute([$username]);
        if ($cek->rowCount() > 0) {
            $pesanError = "Username <strong>$username</strong> sudah digunakan. Silakan gunakan username lain.";
        } else {
            try {
                // Simpan ke database dengan enkripsi MD5 (sesuai format bawaan database)
                $stmt = $pdo->prepare("
                    INSERT INTO petugas_poskestren (username, password, nama_lengkap, profesi, no_hp)
                    VALUES (?, MD5(?), ?, ?, ?)
                ");
                $stmt->execute([$username, $password, $nama_lengkap, $profesi, $no_hp]);

                $pesanSukses = "Pendaftaran berhasil! Akun Anda siap digunakan. Silakan login.";
            } catch (Exception $e) {
                $pesanError = "Gagal mendaftarkan akun: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Petugas - DEK SANTRI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }
        .card-register {
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card card-register border-0 bg-white p-4">
                <div class="text-center mb-3">
                    <span class="fs-1">📝</span>
                    <h4 class="fw-bold text-success mb-1">Buat Akun Petugas</h4>
                    <p class="small text-muted mb-0">Poskestren Pondok Pesantren Al Amien Kediri</p>
                </div>

                <?php if ($pesanError): ?>
                    <div class="alert alert-danger py-2 small mb-3" role="alert">
                        <?= $pesanError ?>
                    </div>
                <?php endif; ?>

                <?php if ($pesanSukses): ?>
                    <div class="alert alert-success py-2 small mb-3 text-center" role="alert">
                        <?= $pesanSukses ?><br>
                        <a href="login.php" class="btn btn-success btn-sm mt-2 fw-bold px-3">Masuk ke Halaman Login</a>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <div class="mb-2">
                            <label class="form-label small fw-bold mb-1">Nama Lengkap & Gelar *</label>
                            <input type="text" name="nama_lengkap" class="form-control form-control-sm" required placeholder="Contoh: dr. H. Ahmad Fauzi / Ns. Siti / Zaini">
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-bold mb-1">Username *</label>
                                <input type="text" name="username" class="form-control form-control-sm font-monospace" required placeholder="huruf kecil, cth: zaini">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold mb-1">No. WhatsApp / HP</label>
                                <input type="text" name="no_hp" class="form-control form-control-sm" placeholder="08xxxxxxxx">
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-bold mb-1">Profesi / Peran (Role) *</label>
                            <select name="profesi" class="form-select form-select-sm" required>
                                <option value="Kader Santri">Kader Santri (Santri Husada)</option>
                                <option value="Perawat">Perawat (Nakes)</option>
                                <option value="Dokter">Dokter (Nakes)</option>
                                <option value="Apoteker">Apoteker / Farmasi</option>
                                <option value="Admin">Admin Poskestren</option>
                            </select>
                            <small class="text-muted" style="font-size: 11px;">*Peran menentukan batasan akses menu di aplikasi.</small>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold mb-1">Kata Sandi *</label>
                                <input type="password" name="password" class="form-control form-control-sm" required placeholder="••••••••">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold mb-1">Ulangi Sandi *</label>
                                <input type="password" name="konfirmasi_password" class="form-control form-control-sm" required placeholder="••••••••">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100 fw-bold py-2 shadow-sm mb-2">
                            Daftarkan Akun ➔
                        </button>
                    </form>
                <?php endif; ?>

                <div class="text-center mt-3 pt-3 border-top">
                    <span class="small text-muted">Sudah memiliki akun? </span>
                    <a href="login.php" class="small fw-bold text-success text-decoration-none">Masuk di sini</a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>