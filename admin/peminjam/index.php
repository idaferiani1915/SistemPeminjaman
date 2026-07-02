<?php
// admin/peminjam/index.php
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth_guard.php';

// Memastikan user adalah admin
guard('admin');

$base = get_base_path();

try {
    // Ambil daftar pengguna dengan role 'peminjam'
    $stmt = $pdo->prepare("SELECT id, nama, email, created_at FROM users WHERE role = 'peminjam' ORDER BY created_at DESC");
    $stmt->execute();
    $daftar_peminjam = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Gagal mengambil data pengguna: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Peminjam — Sistem Peminjaman Fasilitas</title>
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
        .table-custom {
            color: var(--text-primary);
            background: var(--bg-secondary);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            overflow: hidden;
        }
        .table-custom th {
            background-color: var(--bg-tertiary);
            color: var(--text-secondary);
            border-bottom: 1px solid var(--glass-border);
            padding: 15px;
        }
        .table-custom td {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            padding: 15px;
            border-bottom: 1px solid var(--glass-border);
            vertical-align: middle;
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
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
            <div>
                <h1 class="text-white">Daftar Peminjam</h1>
                <p class="text-secondary mb-0">Kelola akun pengguna yang dapat melakukan peminjaman.</p>
            </div>
            <div>
                <a href="<?= $base ?>/admin/peminjam/tambah.php" class="btn-glass">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/>
                    </svg>
                    Tambah Peminjam Baru
                </a>
            </div>
        </div>

        <!-- Alert messages -->
        <?php if (isset($_SESSION['sukses_msg'])): ?>
            <div class="alert alert-success border-0 rounded-4 mb-4 position-relative" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                <?= htmlspecialchars($_SESSION['sukses_msg']) ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close" style="float: right; background: none; border: none; font-size: 1.2rem; line-height: 1; color: white;">&times;</button>
            </div>
            <?php unset($_SESSION['sukses_msg']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_msg'])): ?>
            <div class="alert alert-danger border-0 rounded-4 mb-4 position-relative" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                <?= htmlspecialchars($_SESSION['error_msg']) ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close" style="float: right; background: none; border: none; font-size: 1.2rem; line-height: 1; color: white;">&times;</button>
            </div>
            <?php unset($_SESSION['error_msg']); ?>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-borderless table-custom">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="28%">Nama</th>
                        <th width="28%">Email</th>
                        <th width="18%">Tanggal Didaftarkan</th>
                        <th width="12%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($daftar_peminjam) > 0): ?>
                        <?php $no = 1; foreach ($daftar_peminjam as $peminjam): ?>
                            <tr>
                                <td class="text-secondary"><?= $no++ ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($peminjam['nama']) ?></td>
                                <td><?= htmlspecialchars($peminjam['email']) ?></td>
                                <td class="text-secondary"><?= date('d/m/Y H:i', strtotime($peminjam['created_at'])) ?></td>
                                <td class="text-center">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-danger px-3 rounded-pill"
                                        onclick="konfirmasiHapus(<?= $peminjam['id'] ?>, '<?= htmlspecialchars($peminjam['nama'], ENT_QUOTES) ?>')"
                                        title="Hapus peminjam ini">
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-secondary">
                                <div class="mb-3">
                                    <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="opacity: 0.5;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                Belum ada peminjam yang didaftarkan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Hidden form untuk hapus (POST, bukan GET, agar aman) -->
    <form id="formHapus" method="POST" action="<?= $base ?>/admin/peminjam/hapus.php" style="display:none;">
        <input type="hidden" name="id" id="hapus_id">
    </form>

    <!-- Modal Konfirmasi Hapus -->
    <div class="modal fade" id="modalHapus" tabindex="-1" aria-labelledby="modalHapusLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 16px; border: 1px solid var(--glass-border); background: var(--bg-secondary);">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="modalHapusLabel" style="color: var(--text-primary);">
                        Konfirmasi Hapus Peminjam
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="d-flex align-items-start gap-3">
                        <div style="width:42px; height:42px; border-radius:50%; background:rgba(239,68,68,0.1); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <svg width="22" height="22" fill="none" stroke="#ef4444" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div>
                            <p style="color: var(--text-primary); margin-bottom: 6px;">
                                Anda akan menghapus akun peminjam:
                            </p>
                            <p class="fw-bold" id="hapus_nama" style="color: #ef4444; margin-bottom: 6px; font-size: 1.05rem;"></p>
                            <p style="color: var(--text-secondary); font-size: 0.88rem; margin: 0;">
                                Tindakan ini permanen dan tidak dapat dibatalkan. Seluruh data riwayat peminjaman terkait juga akan terpengaruh.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 gap-2">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger rounded-pill px-4" onclick="document.getElementById('formHapus').submit();">
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        function konfirmasiHapus(id, nama) {
            document.getElementById('hapus_id').value   = id;
            document.getElementById('hapus_nama').textContent = nama;
            const modal = new bootstrap.Modal(document.getElementById('modalHapus'));
            modal.show();
        }
    </script>
</body>
</html>

