<?php
// admin/pengembalian/index.php
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth_guard.php';

// Memastikan user adalah admin
guard('admin');

$base = get_base_path();

try {
    // Jalankan auto-update status peminjaman ke 'Terlambat' jika sudah melewati tgl_kembali_rencana
    $current_date = date('Y-m-d');
    $pdo->prepare("
        UPDATE peminjaman 
        SET status = 'Terlambat' 
        WHERE status = 'Aktif' AND tgl_kembali_rencana < ?
    ")->execute([$current_date]);

    // Ambil peminjaman aktif & terlambat
    $stmt = $pdo->query("
        SELECT p.*, a.nama_alat, a.kode_qr, u.nama AS nama_user, u.email AS email_user 
        FROM peminjaman p 
        JOIN aset a ON p.aset_id = a.id 
        JOIN users u ON p.user_id = u.id 
        WHERE p.status IN ('Aktif', 'Terlambat') 
        ORDER BY p.tgl_pinjam DESC
    ");
    $peminjaman_aktif = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Gagal mengambil data peminjaman: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-in Pengembalian — Sistem Peminjaman Fasilitas</title>
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
                        <a class="nav-link px-3" href="<?= $base ?>/admin/peminjam/index.php">Kelola Peminjam</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 active" href="<?= $base ?>/admin/pengembalian/index.php">Pengembalian</a>
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
        <div class="mb-4">
            <h1 class="text-white">Kelola Pengembalian Aset</h1>
            <p class="text-secondary">Konfirmasi pengembalian (check-in) untuk aset yang sedang dipinjam mahasiswa.</p>
        </div>

        <!-- Alert messages -->
        <?php if (isset($_SESSION['sukses_msg'])): ?>
            <div class="alert-glass alert-glass-success alert-dismissible fade show" role="alert">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span><?= htmlspecialchars($_SESSION['sukses_msg']) ?></span>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close" style="float: right; background: none; border: none; font-size: 1.2rem; line-height: 1; color: white;">&times;</button>
            </div>
            <?php unset($_SESSION['sukses_msg']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_msg'])): ?>
            <div class="alert-glass alert-glass-error alert-dismissible fade show" role="alert">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span><?= htmlspecialchars($_SESSION['error_msg']) ?></span>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close" style="float: right; background: none; border: none; font-size: 1.2rem; line-height: 1; color: white;">&times;</button>
            </div>
            <?php unset($_SESSION['error_msg']); ?>
        <?php endif; ?>

        <!-- Active Borrowings Card -->
        <div class="glass-card">
            <div class="table-responsive">
                <table class="table table-borderless table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Aset</th>
                            <th>Kode QR</th>
                            <th>Peminjam</th>
                            <th>Tanggal Pinjam</th>
                            <th>Rencana Kembali</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($peminjaman_aktif) > 0): ?>
                            <?php foreach ($peminjaman_aktif as $pinjam): ?>
                                <tr>
                                    <td>
                                        <div class="text-black font-heading fw-semibold"><?= htmlspecialchars($pinjam['nama_alat']) ?></div>
                                    </td>
                                    <td>
                                        <span class="font-monospace text-black"><?= htmlspecialchars($pinjam['kode_qr']) ?></span>
                                    </td>
                                    <td>
                                        <div class="text-black"><?= htmlspecialchars($pinjam['nama_user']) ?></div>
                                        <div class="small text-black"><?= htmlspecialchars($pinjam['email_user']) ?></div>
                                    </td>
                                    <td class="text-black"><?= date('d-m-Y H:i', strtotime($pinjam['tgl_pinjam'])) ?></td>
                                    <td class="text-black"><?= date('d-m-Y', strtotime($pinjam['tgl_kembali_rencana'])) ?></td>
                                    <td>
                                        <?php
                                        $status_class = $pinjam['status'] === 'Terlambat' ? 'badge-glass-danger' : 'badge-glass-warning';
                                        ?>
                                        <span class="badge-glass <?= $status_class ?>"><?= htmlspecialchars($pinjam['status']) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= $base ?>/admin/pengembalian/checkin.php?id=<?= $pinjam['id'] ?>" class="btn btn-success btn-sm px-3 py-2 rounded-3 d-inline-flex align-items-center gap-1" onclick="return confirm('Apakah Anda yakin ingin memproses check-in pengembalian aset ini?')">
                                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            Konfirmasi Pengembalian
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-black py-5">
                                    <div class="fs-5">Tidak ada peminjaman aktif yang perlu dikembalikan.</div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
