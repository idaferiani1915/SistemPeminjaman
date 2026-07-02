<?php
// admin/dashboard.php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth_guard.php';

// Memastikan user adalah admin
guard('admin');

$base = get_base_path();

// Hitung data untuk dashboard
try {
    // 1. Total Aset
    $total_aset = $pdo->query("SELECT COUNT(*) FROM aset")->fetchColumn();

    // 2. Aset Dipinjam
    $aset_dipinjam = $pdo->query("SELECT COUNT(*) FROM aset WHERE status = 'Dipinjam'")->fetchColumn();

    // 3. Peminjaman Aktif (Aktif/Terlambat)
    $peminjaman_aktif = $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status IN ('Aktif', 'Terlambat')")->fetchColumn();

    // 4. Total Log Kegiatan
    $total_log = $pdo->query("SELECT COUNT(*) FROM log_activity")->fetchColumn();

    // 5. Log Terbaru (5 Log terakhir)
    $stmt_logs = $pdo->query("
        SELECT l.*, u.nama AS nama_user 
        FROM log_activity l 
        LEFT JOIN users u ON l.user_id = u.id 
        ORDER BY l.created_at DESC 
        LIMIT 5
    ");
    $recent_logs = $stmt_logs->fetchAll();
} catch (PDOException $e) {
    // S-06: Tangani error dengan ramah
    die("Error mengambil data dashboard: Koneksi terputus atau tabel tidak siap.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin — Sistem Peminjaman Fasilitas</title>
    <!-- Bootstrap 5 CSS CDN -->
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
        .dashboard-header {
            margin-top: 40px;
            margin-bottom: 30px;
        }
        .stat-card {
            border: 1px solid var(--glass-border);
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--glass-shadow);
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
                        <a class="nav-link px-3 active" href="<?= $base ?>/admin/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="<?= $base ?>/admin/inventaris/index.php">Inventaris Fasilitas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="<?= $base ?>/admin/peminjam/index.php">Kelola Peminjam</a>
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
    <div class="container mb-5">
        <div class="dashboard-header d-flex justify-content-between align-items-center">
            <div>
                <h1 class="text-white">Selamat Datang, <?= htmlspecialchars($_SESSION['nama']) ?></h1>
                <p class="text-secondary mb-0">Overview status inventarisasi fasilitas, ruangan, dan log transaksi saat ini.</p>
            </div>
            <div class="text-secondary text-end d-none d-md-block">
                <div>Tanggal: <?= date('d M Y') ?></div>
                <div class="small">Admin Session Aktif</div>
            </div>
        </div>

        <!-- Stats Cards Grid -->
        <div class="row g-4 mb-5">
            <div class="col-6 col-lg-3">
                <div class="glass-card stat-card h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="text-secondary small text-uppercase fw-bold mb-2">Total Aset</div>
                        <div class="fs-1 fw-bold text-white font-heading"><?= $total_aset ?></div>
                    </div>
                    <div class="mt-3">
                        <a href="<?= $base ?>/admin/inventaris/index.php" class="small text-decoration-none text-info">Kelola Aset &rarr;</a>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="glass-card stat-card h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="text-secondary small text-uppercase fw-bold mb-2">Aset Dipinjam</div>
                        <div class="fs-1 fw-bold text-warning font-heading"><?= $aset_dipinjam ?></div>
                    </div>
                    <div class="mt-3">
                        <span class="small text-secondary"><?= $total_aset > 0 ? round(($aset_dipinjam / $total_aset) * 100, 1) : 0 ?>% dari total aset</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="glass-card stat-card h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="text-secondary small text-uppercase fw-bold mb-2">Peminjaman Aktif</div>
                        <div class="fs-1 fw-bold text-success font-heading"><?= $peminjaman_aktif ?></div>
                    </div>
                    <div class="mt-3">
                        <a href="<?= $base ?>/admin/pengembalian/index.php" class="small text-decoration-none text-success">Check-in Pengembalian &rarr;</a>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="glass-card stat-card h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="text-secondary small text-uppercase fw-bold mb-2">Log Audit</div>
                        <div class="fs-1 fw-bold text-info font-heading"><?= $total_log ?></div>
                    </div>
                    <div class="mt-3">
                        <a href="<?= $base ?>/admin/log/index.php" class="small text-decoration-none text-info">Lihat Semua Log &rarr;</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Logs Section -->
        <div class="row">
            <div class="col-12">
                <div class="glass-card">
                    <h3 class="text-white mb-4">Aktivitas Terbaru</h3>
                    <div class="table-responsive">
                        <table class="table table-borderless table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Aktor</th>
                                    <th>Aksi</th>
                                    <th>Detail / Keterangan</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($recent_logs) > 0): ?>
                                    <?php foreach ($recent_logs as $log): ?>
                                        <tr>
                                            <td class="text-black"><?= date('d-m-Y H:i:s', strtotime($log['created_at'])) ?></td>
                                            <td class="text-black font-heading fw-medium"><?= htmlspecialchars($log['nama_user'] ?? 'Sistem / Anonim') ?></td>
                                            <td>
                                                <?php
                                                $badge_class = 'bg-secondary';
                                                if (strpos($log['action'], 'SUCCESS') !== false || $log['action'] === 'TAMBAH_ASET' || $log['action'] === 'CHECK_IN') {
                                                    $badge_class = 'bg-success';
                                                } elseif (strpos($log['action'], 'FAILED') !== false || $log['action'] === 'ACCESS_DENIED' || $log['action'] === 'HAPUS_ASET') {
                                                    $badge_class = 'bg-danger';
                                                } elseif ($log['action'] === 'EDIT_ASET') {
                                                    $badge_class = 'bg-warning text-dark';
                                                }
                                                ?>
                                                <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($log['action']) ?></span>
                                            </td>
                                            <td class="text-black"><?= htmlspecialchars($log['keterangan']) ?></td>
                                            <td class="text-black font-monospace"><?= htmlspecialchars($log['ip_address']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-black py-4">Belum ada rekaman aktivitas log terbaru.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
