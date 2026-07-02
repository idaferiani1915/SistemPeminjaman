<?php
// admin/log/index.php
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth_guard.php';

// Memastikan user adalah admin
guard('admin');

$base = get_base_path();

// Ambil filter aksi jika ada
$action_filter = isset($_GET['action']) ? trim($_GET['action']) : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    // Bangun query secara dinamis
    $query = "
        SELECT l.*, u.nama AS nama_user, u.email AS email_user 
        FROM log_activity l 
        LEFT JOIN users u ON l.user_id = u.id 
        WHERE 1=1
    ";
    $params = [];

    if (!empty($action_filter)) {
        $query .= " AND l.action = ?";
        $params[] = $action_filter;
    }

    if (!empty($search_query)) {
        $query .= " AND (u.nama LIKE ? OR l.keterangan LIKE ? OR l.ip_address LIKE ?)";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
    }

    $query .= " ORDER BY l.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();

    // Dapatkan semua daftar aksi unik untuk opsi filter dropdown
    $actions_stmt = $pdo->query("SELECT DISTINCT action FROM log_activity ORDER BY action ASC");
    $all_actions = $actions_stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    die("Gagal mengambil data log: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jejak Audit Log Aktivitas — Sistem Peminjaman Fasilitas</title>
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
                        <a class="nav-link px-3" href="<?= $base ?>/admin/pengembalian/index.php">Pengembalian</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 active" href="<?= $base ?>/admin/log/index.php">Log Aktivitas</a>
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
            <h1 class="text-white">Jejak Audit & Log Aktivitas</h1>
            <p class="text-secondary">Rekaman log aktivitas sistem peminjaman yang aman (Read-Only).</p>
        </div>

        <!-- Filter Form -->
        <div class="glass-card mb-4">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="search">Cari Kata Kunci</label>
                    <input type="text" class="input-glass" id="search" name="search" placeholder="Nama user, keterangan, IP..." value="<?= htmlspecialchars($search_query) ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="action">Filter Tipe Event</label>
                    <select class="input-glass" id="action" name="action">
                        <option value="">Semua Event</option>
                        <?php foreach ($all_actions as $act): ?>
                            <option value="<?= htmlspecialchars($act) ?>" <?= $action_filter === $act ? 'selected' : '' ?>><?= htmlspecialchars($act) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn-glass flex-grow-1">Filter</button>
                    <?php if (!empty($action_filter) || !empty($search_query)): ?>
                        <a href="<?= $base ?>/admin/log/index.php" class="btn-glass btn-glass-secondary text-center">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Logs Table -->
        <div class="glass-card">
            <div class="table-responsive">
                <table class="table table-borderless table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Aktor</th>
                            <th>Aksi</th>
                            <th>Target</th>
                            <th>ID Record</th>
                            <th>Detail / Keterangan</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($logs) > 0): ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="text-black"><?= date('d-m-Y H:i:s', strtotime($log['created_at'])) ?></td>
                                    <td>
                                        <?php if ($log['user_id']): ?>
                                            <div class="text-black font-heading fw-medium"><?= htmlspecialchars($log['nama_user']) ?></div>
                                            <div class="small text-black"><?= htmlspecialchars($log['email_user']) ?></div>
                                        <?php else: ?>
                                            <span class="text-black italic">Sistem / Anonim</span>
                                        <?php endif; ?>
                                    </td>
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
                                    <td class="font-monospace text-black"><?= htmlspecialchars($log['tabel_target'] ?: '-') ?></td>
                                    <td class="text-black"><?= htmlspecialchars($log['record_id'] ?: '-') ?></td>
                                    <td class="text-black"><?= htmlspecialchars($log['keterangan']) ?></td>
                                    <td class="font-monospace text-black"><?= htmlspecialchars($log['ip_address']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-black py-5">Tidak ada data log aktivitas yang cocok dengan pencarian.</td>
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
