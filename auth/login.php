<?php
// auth/login.php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/log_helper.php';
require_once __DIR__ . '/../middleware/auth_guard.php';

$base = get_base_path();

// Redirect jika sudah login
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: " . $base . "/admin/dashboard.php");
    } else {
        header("Location: " . $base . "/mobile/dashboard.php");
    }
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error_message = 'Email dan password wajib diisi.';
    } else {
        try {
            // S-01: Prepared statement
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Login sukses
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];

                // Catat log login sukses
                log_activity($pdo, $user['id'], 'LOGIN_SUCCESS', 'users', $user['id'], 'User login berhasil');

                // Redirect sesuai role
                if ($user['role'] === 'admin') {
                    header("Location: " . $base . "/admin/dashboard.php");
                } else {
                    header("Location: " . $base . "/mobile/dashboard.php");
                }
                exit;
            } else {
                // Login gagal
                $error_message = 'Email atau password salah.';
                log_activity($pdo, null, 'LOGIN_FAILED', 'users', null, 'Percobaan login gagal untuk email: ' . htmlspecialchars($email));
            }
        } catch (PDOException $e) {
            // S-06: Tampilkan pesan ramah, jangan print exception mentah
            $error_message = 'Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Sistem Peminjaman Fasilitas</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/global.css?v=3">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: radial-gradient(circle at 10% 20%, #f8fafc 0%, #e2e8f0 90%);
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, var(--primary-glow) 0%, transparent 60%);
            pointer-events: none;
            z-index: 0;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }

        .login-logo {
            font-size: 1.75rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }

        .login-subtitle {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .login-form {
            position: relative;
            z-index: 1;
        }

        .input-glass {
            background: rgba(15, 23, 42, 0.03) !important;
            color: #0f172a !important;
            border-color: rgba(15, 23, 42, 0.12) !important;
        }

        .input-glass:focus {
            background: rgba(15, 23, 42, 0.05) !important;
            border-color: var(--primary) !important;
        }

        .btn-full {
            width: 100%;
            margin-top: 10px;
        }

        .footer-note {
            text-align: center;
            margin-top: 24px;
            font-size: 0.8rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="glass-card login-card">
            <div class="login-header">
                <div class="login-logo">Sistem Peminjaman</div>
                <div class="login-subtitle">Peminjaman & Inventarisasi Aset</div>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert-glass alert-glass-error">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span><?= htmlspecialchars($error_message) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="login-form" autocomplete="off">
                <div class="form-group">
                    <label class="form-label" for="email">Alamat Email</label>
                    <input class="input-glass" type="email" id="email" name="email" placeholder="nama@contoh.com" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Kata Sandi</label>
                    <input class="input-glass" type="password" id="password" name="password" placeholder="Masukkan kata sandi" required>
                </div>

                <button class="btn-glass btn-full" type="submit">Masuk</button>
            </form>

            <div class="footer-note">
                Sistem Peminjaman Fasilitas &copy; 2026
            </div>
        </div>
    </div>
</body>
</html>
