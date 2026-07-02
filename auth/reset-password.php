<?php
// auth/reset-password.php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/log_helper.php';
require_once __DIR__ . '/../middleware/auth_guard.php';

$base = get_base_path();
$error_message   = '';
$success_message = '';
$token_valid     = false;
$user_id         = null;

$token = trim($_GET['token'] ?? '');

if (empty($token)) {
    $error_message = 'Tautan reset tidak valid. Silakan minta ulang.';
} else {
    // Validasi token: harus ada, belum dipakai, dan belum kadaluarsa
    $stmt = $pdo->prepare("
        SELECT pr.id AS reset_id, pr.user_id, pr.expires_at, u.email, u.nama
        FROM password_resets pr
        JOIN users u ON u.id = pr.user_id
        WHERE pr.token = ?
          AND pr.used = 0
          AND pr.expires_at > NOW()
    ");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if ($reset) {
        $token_valid = true;
        $user_id     = $reset['user_id'];

        // Proses POST form ubah password
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password         = $_POST['password']         ?? '';
            $password_confirm = $_POST['password_confirm'] ?? '';

            if (empty($password) || empty($password_confirm)) {
                $error_message = 'Semua kolom harus diisi.';
            } elseif (strlen($password) < 6) {
                $error_message = 'Kata sandi minimal 6 karakter.';
            } elseif ($password !== $password_confirm) {
                $error_message = 'Konfirmasi kata sandi tidak cocok.';
            } else {
                $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

                // Update password
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $user_id]);

                // Tandai token sebagai sudah dipakai
                $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?")->execute([$reset['reset_id']]);

                // Catat log
                log_activity($pdo, $user_id, 'RESET_PASSWORD', 'users', $user_id, 'Kata sandi berhasil direset melalui tautan email.');

                $success_message = 'Kata sandi berhasil diubah!';
                $token_valid     = false; // Sembunyikan form setelah sukses
            }
        }
    } else {
        $error_message = 'Tautan ini sudah kadaluarsa atau tidak valid. Silakan minta tautan reset baru.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Kata Sandi — Sistem Peminjaman Fasilitas</title>
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
        .input-wrapper {
            position: relative;
        }
        .toggle-pw {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-secondary);
            padding: 0;
            display: flex;
            align-items: center;
        }
        .toggle-pw:hover { color: var(--primary); }
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
            display: block;
            text-align: center;
            text-decoration: none;
        }
        .btn-full:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-login {
            width: 100%;
            padding: 13px;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #fff;
            margin-top: 16px;
            display: block;
            text-align: center;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        .btn-login:hover { opacity: 0.9; transform: translateY(-1px); }
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
        .success-icon {
            text-align: center;
            margin-bottom: 20px;
        }
        .success-icon svg {
            width: 64px;
            height: 64px;
            color: #10b981;
        }
        .password-hint {
            font-size: 0.78rem;
            color: var(--text-secondary);
            margin-top: 6px;
        }
        .strength-bar {
            height: 4px;
            border-radius: 2px;
            background: #e2e8f0;
            margin-top: 8px;
            overflow: hidden;
        }
        .strength-fill {
            height: 100%;
            border-radius: 2px;
            transition: width 0.3s ease, background 0.3s ease;
            width: 0%;
        }
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
                <div class="login-subtitle">Buat Kata Sandi Baru</div>
            </div>

            <?php if (!empty($success_message)): ?>
                <!-- Tampilan sukses -->
                <div class="success-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h2 style="text-align:center; color: var(--text-primary); font-size:1.3rem; margin-bottom:8px;">Berhasil!</h2>
                <p style="text-align:center; color: var(--text-secondary); font-size:0.9rem; margin-bottom:24px;">
                    Kata sandi Anda telah berhasil diubah. Sekarang Anda dapat masuk menggunakan kata sandi baru Anda.
                </p>
                <a href="<?= $base ?>/auth/login.php" class="btn-login">
                    <svg style="display:inline; vertical-align:-4px; margin-right:6px;" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    Masuk Sekarang
                </a>

            <?php elseif (!empty($error_message)): ?>
                <!-- Token invalid / kadaluarsa -->
                <div class="alert-glass alert-glass-error">
                    <span class="icon-wrap">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                    <span><?= htmlspecialchars($error_message) ?></span>
                </div>
                <a href="<?= $base ?>/auth/lupa-password.php" class="btn-full">Minta Tautan Reset Baru</a>

            <?php elseif ($token_valid): ?>
                <!-- Form ubah kata sandi -->
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

                <form method="POST" action="?token=<?= htmlspecialchars($token) ?>" id="resetForm">
                    <div class="form-group">
                        <label class="form-label" for="password">Kata Sandi Baru</label>
                        <div class="input-wrapper">
                            <input class="input-glass" type="password" id="password" name="password" placeholder="Minimal 6 karakter" required minlength="6" oninput="checkStrength(this.value)">
                            <button type="button" class="toggle-pw" onclick="toggleVisibility('password', this)" tabindex="-1">
                                <svg id="eye-icon-1" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                        <div class="strength-bar">
                            <div class="strength-fill" id="strength-fill"></div>
                        </div>
                        <p class="password-hint" id="strength-label">Minimal 6 karakter.</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password_confirm">Konfirmasi Kata Sandi</label>
                        <div class="input-wrapper">
                            <input class="input-glass" type="password" id="password_confirm" name="password_confirm" placeholder="Ulangi kata sandi baru" required oninput="checkMatch()">
                            <button type="button" class="toggle-pw" onclick="toggleVisibility('password_confirm', this)" tabindex="-1">
                                <svg id="eye-icon-2" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                        <p class="password-hint" id="match-label" style="display:none;"></p>
                    </div>

                    <button class="btn-full" type="submit">Ubah Kata Sandi</button>
                </form>
            <?php endif; ?>

            <div class="footer-note">
                Sistem Peminjaman Fasilitas &copy; 2026
            </div>
        </div>
    </div>

    <script>
        function toggleVisibility(fieldId, btn) {
            const input = document.getElementById(fieldId);
            if (input.type === 'password') {
                input.type = 'text';
                btn.style.color = 'var(--primary)';
            } else {
                input.type = 'password';
                btn.style.color = 'var(--text-secondary)';
            }
        }

        function checkStrength(val) {
            const fill   = document.getElementById('strength-fill');
            const label  = document.getElementById('strength-label');
            let score = 0;
            if (val.length >= 6) score++;
            if (val.length >= 10) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            const pct    = ['0%', '25%', '50%', '75%', '90%', '100%'][score];
            const colors = ['#e2e8f0', '#ef4444', '#f97316', '#eab308', '#22c55e', '#10b981'];
            const texts  = ['', 'Sangat Lemah', 'Lemah', 'Cukup', 'Kuat', 'Sangat Kuat'];
            fill.style.width      = pct;
            fill.style.background = colors[score];
            label.textContent     = score === 0 ? 'Minimal 6 karakter.' : texts[score];
            label.style.color     = colors[score];
        }

        function checkMatch() {
            const pw   = document.getElementById('password').value;
            const cfrm = document.getElementById('password_confirm').value;
            const lbl  = document.getElementById('match-label');
            if (cfrm === '') { lbl.style.display = 'none'; return; }
            lbl.style.display = 'block';
            if (pw === cfrm) {
                lbl.textContent = '✓ Kata sandi cocok';
                lbl.style.color = '#10b981';
            } else {
                lbl.textContent = '✗ Kata sandi tidak cocok';
                lbl.style.color = '#ef4444';
            }
        }
    </script>
</body>
</html>
