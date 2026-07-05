<?php
// admin/inventaris/index.php
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth_guard.php';

// Memastikan user adalah admin
guard('admin');

$base = get_base_path();

try {
    // Ambil daftar aset terurut dari yang terbaru dimasukkan
    $stmt = $pdo->query("SELECT * FROM aset ORDER BY id DESC");
    $daftar_aset = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Gagal mengambil data aset: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Inventaris Fasilitas — Sistem Peminjaman Fasilitas</title>
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
        .asset-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--glass-border);
            background-color: rgba(255,255,255,0.05);
        }
        .qr-thumb {
            width: 48px;
            height: 48px;
            border-radius: 4px;
            border: 1px solid var(--glass-border);
            cursor: pointer;
            transition: opacity 0.2s ease, box-shadow 0.2s ease;
        }
        .qr-thumb:hover {
            opacity: 0.85;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        /* Fix: pastikan teks btn-glass tetap terlihat saat hover */
        .btn-glass:hover {
            color: #fff !important;
            -webkit-text-fill-color: #fff !important;
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
                        <a class="nav-link px-3 active" href="<?= $base ?>/admin/inventaris/index.php">Inventaris Fasilitas</a>
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
    <div class="container my-5">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
            <div>
                <h1 class="text-white">Inventaris Fasilitas & Ruangan</h1>
                <p class="text-secondary mb-0">Kelola dan pantau seluruh fasilitas, alat, dan ruangan yang terdaftar.</p>
            </div>
            <div>
                <a href="<?= $base ?>/admin/inventaris/tambah.php" class="btn-glass">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/>
                    </svg>
                    Tambah Aset Baru
                </a>
            </div>
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

        <!-- Table Card -->
        <div class="glass-card">
            <div class="table-responsive">
                <table class="table table-borderless table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Kode QR</th>
                            <th>Nama Aset</th>
                            <th>Kategori</th>
                            <th>Kondisi</th>
                            <th>Status</th>
                            <th>QR Code</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($daftar_aset) > 0): ?>
                            <?php foreach ($daftar_aset as $aset): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($aset['foto']) && file_exists(__DIR__ . '/../../uploads/alat/' . $aset['foto'])): ?>
                                            <img src="<?= $base ?>/uploads/alat/<?= htmlspecialchars($aset['foto']) ?>" alt="<?= htmlspecialchars($aset['nama_alat']) ?>" class="asset-img">
                                        <?php else: ?>
                                            <div class="asset-img d-flex align-items-center justify-content-center text-muted small">No Pic</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="font-monospace text-black"><?= htmlspecialchars($aset['kode_qr']) ?></td>
                                    <td class="text-black font-heading fw-semibold"><?= htmlspecialchars($aset['nama_alat']) ?></td>
                                    <td class="text-black"><?= htmlspecialchars($aset['kategori'] ?? '-') ?></td>
                                    <td>
                                        <?php
                                        $cond_class = 'badge-glass-success';
                                        if ($aset['kondisi'] === 'Rusak') {
                                            $cond_class = 'badge-glass-danger';
                                        } elseif ($aset['kondisi'] === 'Maintenance') {
                                            $cond_class = 'badge-glass-warning';
                                        }
                                        ?>
                                        <span class="badge-glass <?= $cond_class ?>"><?= htmlspecialchars($aset['kondisi']) ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = $aset['status'] === 'Tersedia' ? 'badge-glass-success' : 'badge-glass-danger';
                                        ?>
                                        <span class="badge-glass <?= $status_class ?>"><?= htmlspecialchars($aset['status']) ?></span>
                                    </td>
                                    <td>
                                        <img
                                            src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($aset['kode_qr']) ?>"
                                            alt="QR Code <?= htmlspecialchars($aset['kode_qr']) ?>"
                                            class="qr-thumb"
                                            title="Klik untuk perbesar"
                                            onclick="bukaQR('<?= urlencode($aset['kode_qr']) ?>', '<?= htmlspecialchars($aset['nama_alat'], ENT_QUOTES) ?>')">
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="<?= $base ?>/admin/inventaris/edit.php?id=<?= $aset['id'] ?>" class="btn btn-warning btn-sm d-flex align-items-center gap-1 rounded-3 py-2 px-3 text-dark">
                                                <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                                </svg>
                                                Edit
                                            </a>
                                            <a href="<?= $base ?>/admin/inventaris/hapus.php?id=<?= $aset['id'] ?>" class="btn btn-danger btn-sm d-flex align-items-center gap-1 rounded-3 py-2 px-3" onclick="return confirm('Apakah Anda yakin ingin menghapus aset [<?= htmlspecialchars($aset['nama_alat']) ?>] ini? Tindakan ini tidak dapat dibatalkan.')">
                                                <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                                Hapus
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-black py-5">
                                    <div class="fs-5 mb-2">Belum ada aset yang terdaftar</div>
                                    <a href="<?= $base ?>/admin/inventaris/tambah.php" class="btn btn-outline-primary btn-sm rounded-pill mt-2">Tambah Sekarang</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Lightbox QR Code -->
    <div class="modal fade" id="modalQR" tabindex="-1" aria-labelledby="modalQRLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 16px; border: 1px solid var(--glass-border); background: var(--bg-secondary);">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="modalQRLabel" style="color: var(--text-primary);">QR Code Aset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <img id="qrLargeImg" src="" alt="QR Code" style="width: 280px; height: 280px; border-radius: 8px; border: 1px solid var(--glass-border);">
                    <p class="mt-3 fw-bold font-monospace" id="qrKodeLabel" style="color: var(--text-primary); font-size: 1.1rem;"></p>
                    <p id="qrNamaLabel" style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 4px;"></p>
                    <a id="qrDownloadLink" href="" download="qr-code.png" class="btn btn-outline-primary btn-sm rounded-pill mt-2 px-4">
                        Download QR
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts
        setTimeout(function() {
            document.querySelectorAll('.alert-glass').forEach(function(el) {
                el.style.transition = 'opacity 0.5s';
                el.style.opacity   = '0';
                setTimeout(function() { el.remove(); }, 500);
            });
        }, 5000);

        function bukaQR(kodeEncoded, namaAlat) {
            const kode = decodeURIComponent(kodeEncoded);
            const url  = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' + kodeEncoded;
            document.getElementById('qrLargeImg').src        = url;
            document.getElementById('qrKodeLabel').textContent = kode;
            document.getElementById('qrNamaLabel').textContent = namaAlat;
            document.getElementById('qrDownloadLink').href   = url;
            document.getElementById('qrDownloadLink').download = 'QR-' + kode + '.png';
            new bootstrap.Modal(document.getElementById('modalQR')).show();
        }
    </script>
</body>
</html>
