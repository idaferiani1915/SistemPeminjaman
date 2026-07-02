<?php
// auth/lupa-password.php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth_guard.php';

$base = get_base_path();
$error_message  = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error_message = 'Email wajib diisi.';
    } else {
        // Cari user berdasarkan email
        $stmt = $pdo->prepare("SELECT id, nama FROM users WHERE email = ? AND role = 'peminjam'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Hapus token lama yang belum dipakai
            $pdo->prepare("DELETE FROM password_resets WHERE user_id = ? AND used = 0")->execute([$user['id']]);

            // Buat token baru (64 char hex)
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', time() + 3600); // berlaku 1 jam

            $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)")
                ->execute([$user['id'], $token, $expires_at]);

            // Kirim email
            $reset_url = "http://" . $_SERVER['HTTP_HOST'] . $base . "/auth/reset-password.php?token=" . $token;
            $nama = $user['nama'];

            $subject = "Reset Kata Sandi - Sistem Peminjaman Fasilitas";
            $body    = "Halo, $nama!\n\nAnda menerima email ini karena ada permintaan reset kata sandi untuk akun Anda.\n\nKlik tautan berikut untuk mengubah kata sandi Anda:\n$reset_url\n\nTautan ini berlaku selama 1 jam. Jika Anda tidak merasa meminta reset kata sandi, abaikan email ini.\n\nSalam,\nTim Sistem Peminjaman Fasilitas";

            $headers  = "From: noreply@sistempeminjaman.id\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            $sent = mail($email, $subject, $body, $headers);

            if ($sent) {
                $success_message = "Tautan reset kata sandi telah dikirim ke email <strong>$email</strong>. Periksa inbox atau folder spam Anda.";
            } else {
                // Jika mail() gagal (server lokal), tetap tampilkan sukses tapi juga tampilkan link langsung untuk testing
                $success_message = "Tautan reset kata sandi berhasil dibuat. Karena server lokal tidak dapat mengirim email, gunakan tautan berikut untuk testing: <a href='$reset_url'>$reset_url</a>";
            }
        } else {
            // Jangan beritahu jika email tidak terdaftar (keamanan)
            $success_message = "Jika email tersebut terdaftar, tautan reset kata sandi akan dikirimkan.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Kata Sandi — Sistem Peminjaman Fasilitas</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/global.css?v=3">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 20px;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            border-radius: 20px;
            background: #ffffff;
            border: 1px solid rgba(0,0,0,0.08);
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            padding: 40px 36px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .login-logo {
            font-size: 1.6rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-family: var(--font-heading);
        }
        .login-subtitle {
            color: var(--text-secondary);
            font-size: 0.88rem;
            margin-top: 4px;
        }
        .form-group { margin-bottom: 20px; }
        .form-label {
            display: block;
            color: var(--text-primary);
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }
        .input-glass {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid rgba(0,0,0,0.12);
            border-radius: 10px;
            font-size: 0.95rem;
            color: var(--text-primary);
            background: #f8fafc;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        .input-glass:focus {
            outline: none;
            border-color: var(--accent);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .btn-full {
            width: 100%;
            padding: 13px;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: #fff;
            margin-top: 8px;
        }
        .btn-full:hover { opacity: 0.9; transform: translateY(-1px); }
        .alert-glass {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .alert-glass-error {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #dc2626;
        }
        .alert-glass-success {
            background: rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #059669;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            font-size: 0.88rem;
            color: var(--text-secondary);
            text-decoration: none;
        }
        .back-link:hover { color: var(--primary); }
        .footer-note {
            text-align: center;
            margin-top: 24px;
            font-size: 0.78rem;
            color: var(--text-muted);
        }
        .icon-wrap { flex-shrink: 0; margin-top: 1px; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">Sistem Peminjaman</div>
                <div class="login-subtitle">Reset Kata Sandi</div>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert-glass alert-glass-error">
                    <span class="icon-wrap">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                    <span><?= htmlspecialchars($error_message) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert-glass alert-glass-success">
                    <span class="icon-wrap">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                    <span><?= $success_message ?></span>
                </div>
                <a href="<?= $base ?>/auth/login.php" class="btn-full" style="display:block; text-align:center; text-decoration:none; margin-top:0;">Kembali ke Halaman Login</a>
            <?php else: ?>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 24px; line-height: 1.6;">
                    Masukkan email yang terdaftar pada akun Anda. Kami akan mengirimkan tautan untuk mengubah kata sandi.
                </p>
                <form method="POST" action="" class="login-form">
                    <div class="form-group">
                        <label class="form-label" for="email">Alamat Email</label>
                        <input class="input-glass" type="email" id="email" name="email" placeholder="nama@contoh.com" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    </div>
                    <button class="btn-full" type="submit">Kirim Tautan Reset</button>
                </form>
            <?php endif; ?>

            <a href="<?= $base ?>/auth/login.php" class="back-link">← Kembali ke Login</a>

            <div class="footer-note">
                Sistem Peminjaman Fasilitas &copy; 2026
            </div>
        </div>
    </div>
</body>
</html>
