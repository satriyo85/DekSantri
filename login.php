<?php
require_once 'config.php';

// Jika sudah login, langsung alihkan ke dashboard
if (isset($_SESSION['user_poskestren'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM petugas_poskestren WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && ($user['password'] === md5($password) || password_verify($password, $user['password']))) {
            $_SESSION['user_poskestren'] = [
                'id_petugas'   => $user['id_petugas'],
                'username'     => $user['username'],
                'nama_lengkap' => $user['nama_lengkap'],
                'profesi'      => $user['profesi'],
                'no_hp'        => $user['no_hp']
            ];
            header("Location: index.php");
            exit;
        } else {
            $error = 'Username atau Kata Sandi salah!';
        }
    } else {
        $error = 'Harap isi semua kolom login.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Petugas - DEK SANTRI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-login {
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            overflow: hidden;
        }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card card-login border-0 bg-white p-4">
                <div class="text-center mb-4">
                    <span class="fs-1">🏥</span>
                    <h4 class="fw-bold text-success mb-1">DEK SANTRI</h4>
                    <p class="small text-muted mb-0">Poskestren Pondok Pesantren Al Amien Kediri</p>
		    <p class="small text-muted mb-0">Digitalisasi Pengelolaan Data dan Riwayat Kesehatan Santri Berbasis Teknologi Informasi</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small mb-3 text-center" role="alert">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="admin / dokter / kader" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Kata Sandi</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100 fw-bold py-2 shadow-sm mb-2">
                        Masuk Sistem ➔
                    </button>
                    <!-- Tombol Buat Akun Baru -->
                    <a href="register.php" class="btn btn-outline-success w-100 fw-semibold py-2">
                        ➕ Buat Akun Petugas Baru
                    </a>
                </form>
		<?php renderFooter(); ?>
                
            </div>
        </div>
    </div>
</div>
</body>
</html>