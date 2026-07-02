<?php
// admin/peminjam/tambah.php
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth_guard.php';

// Memastikan user adalah admin
guard('admin');

$base = get_base_path();
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = 'peminjam'; // Secara default menambahkan peminjam

    if (empty($nama) || empty($email) || empty($password)) {
        $error_message = "Semua field harus diisi.";
    } else {
        // Cek apakah email sudah terdaftar
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt_check->execute([$email]);
        if ($stmt_check->fetchColumn() > 0) {
            $error_message = "Email tersebut sudah terdaftar. Silakan gunakan email lain.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            // Insert ke database
            try {
                $stmt_insert = $pdo->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
                if ($stmt_insert->execute([$nama, $email, $hashed_password, $role])) {
                    // Log aktivitas
                    $user_id_inserted = $pdo->lastInsertId();
                    log_activity($pdo, $_SESSION['user_id'], 'ADD_USER', 'users', $user_id_inserted, "Admin mendaftarkan peminjam baru: $email");

                    $_SESSION['sukses_msg'] = "Peminjam berhasil didaftarkan.";
                    header("Location: $base/admin/peminjam/index.php");
                    exit();
                } else {
                    $error_message = "Gagal mendaftarkan peminjam.";
                }
            } catch (PDOException $e) {
                $error_message = "Terjadi kesalahan sistem: " . $e->getMessage();
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
    <title>Tambah Peminjam — Sistem Peminjaman Fasilitas</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/global.css?v=3">
    <style>
        body {
            background-color: var(--bg-primary);
        }
        .admin-navbar {
            background-color: var(--bg-secondary);
            border-bottom: 1px solid var(--glass-border);
        }
        .nav-link {
            color: var(--text-secondary);
            font-family: var(--font-heading);
            font-weight: 500;
        }
        .nav-link:hover, .nav-link.active {
            color: var(--text-primary);
        }
        .form-card {
            background: var(--bg-secondary);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 30px;
            max-width: 600px;
            margin: 0 auto;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            color: var(--text-primary);
            font-weight: 500;
            margin-bottom: 8px;
        }
        .input-glass {
            width: 100%;
            padding: 12px 16px;
            background: rgba(0, 0, 0, 0.03);
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .input-glass:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(0, 0, 0, 0.05);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
    </style>
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg admin-navbar sticky-top">
        <div class="container py-2">
            <a class="navbar-brand d-flex align-items-center" href="<?= $base ?>/admin/dashboard.php">
                <span class="fs-4 fw-bold text-white font-heading" style="background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Admin Fasilitas</span>
            </a>
            <button class="navbar-toggler btn-glass-secondary border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto gap-2 mt-2 mt-lg-0">
                    <li class="nav-item">
                        <a class="nav-link px-3" href="<?= $base ?>/admin/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="<?= $base ?>/admin/inventaris/index.php">Inventaris Fasilitas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 active" href="<?= $base ?>/admin/peminjam/index.php">Kelola Peminjam</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="<?= $base ?>/admin/pengembalian/index.php">Pengembalian</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="<?= $base ?>/admin/log/index.php">Log Aktivitas</a>
                    </li>
                    <li class="nav-item ms-lg-3">
                        <a class="btn btn-danger btn-sm px-3 rounded-pill" href="<?= $base ?>/auth/logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?')">Keluar</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="mb-4 text-center">
            <a href="<?= $base ?>/admin/peminjam/index.php" class="text-secondary d-inline-flex align-items-center gap-2 mb-2 text-decoration-none">
                &larr; Kembali ke Daftar Peminjam
            </a>
            <h1 class="text-white">Tambah Peminjam Baru</h1>
            <p class="text-secondary">Daftarkan akun pengguna baru yang dapat meminjam fasilitas dan aset.</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger border-0 rounded-4 mb-4 position-relative mx-auto" style="max-width: 600px; background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                <?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close" style="float: right; background: none; border: none; font-size: 1.2rem; line-height: 1; color: white;">&times;</button>
            </div>
        <?php endif; ?>

        <div class="glass-card form-card">
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="nama">Nama Lengkap</label>
                    <input class="input-glass" type="text" id="nama" name="nama" placeholder="Masukkan nama lengkap" required value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : '' ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Alamat Email</label>
                    <input class="input-glass" type="email" id="email" name="email" placeholder="Contoh: mahasiswa@kampus.ac.id" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Kata Sandi</label>
                    <input class="input-glass" type="password" id="password" name="password" placeholder="Minimal 6 karakter" required minlength="6">
                    <small class="text-secondary mt-1 d-block">Kata sandi akan dienkripsi secara aman (bcrypt).</small>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn-glass w-100 py-3" style="font-size: 1.1rem;">Daftarkan Peminjam</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>
