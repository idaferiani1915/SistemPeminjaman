<?php
// mobile/dashboard.php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth_guard.php';

// Memastikan user adalah peminjam
guard('peminjam');

$base = get_base_path();
$user_id = $_SESSION['user_id'];

try {
    // Auto-update status ke 'Terlambat' untuk peminjaman user ini jika tgl_kembali_rencana sudah lewat
    $current_date = date('Y-m-d');
    $pdo->prepare("
        UPDATE peminjaman 
        SET status = 'Terlambat' 
        WHERE user_id = ? AND status = 'Aktif' AND tgl_kembali_rencana < ?
    ")->execute([$user_id, $current_date]);

    // Ambil peminjaman aktif & terlambat dari user ini
    $stmt = $pdo->prepare("
        SELECT p.*, a.nama_alat, a.kode_qr, a.foto 
        FROM peminjaman p 
        JOIN aset a ON p.aset_id = a.id 
        WHERE p.user_id = ? AND p.status IN ('Aktif', 'Terlambat') 
        ORDER BY p.tgl_pinjam DESC
    ");
    $stmt->execute([$user_id]);
    $peminjaman_aktif = $stmt->fetchAll();

    // Hitung ringkasan
    $total_aktif = count($peminjaman_aktif);
    $total_terlambat = 0;
    foreach ($peminjaman_aktif as $p) {
        if ($p['status'] === 'Terlambat') {
            $total_terlambat++;
        }
    }
} catch (PDOException $e) {
    die("Terjadi kesalahan koneksi sistem.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <!-- §6.1 Viewport tag -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Dashboard Peminjam — Sistem Peminjaman</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/global.css?v=3">
    <style>
        .hero-section {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(6, 186, 212, 0.2) 100%);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid var(--glass-border);
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: -20px;
            right: -20px;
            width: 80px;
            height: 80px;
            background: var(--accent);
            filter: blur(40px);
            opacity: 0.3;
        }
        .welcome-title {
            font-size: 1.5rem;
            margin-bottom: 4px;
            background: linear-gradient(135deg, #0f172a 0%, #475569 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .welcome-desc {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }
        .badge-count {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 12px;
            text-align: center;
            gap: 4px;
        }
        .badge-count-number {
            font-size: 1.5rem;
            font-weight: 700;
            font-family: var(--font-heading);
            display: block;
        }
        .badge-count-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        .asset-card {
            display: flex;
            align-items: center;
            gap: 16px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--glass-border);
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 12px;
        }
        .asset-card-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
        }
        .asset-card-info {
            flex-grow: 1;
        }
        .asset-card-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 2px;
        }
        .asset-card-meta {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        .cta-scan-container {
            margin: 24px 0;
            text-align: center;
        }
        .btn-scan-large {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            padding: 16px;
            font-size: 1.1rem;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            border: none;
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3);
            transition: all 0.3s ease;
        }
        .btn-scan-large:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(99, 102, 241, 0.5);
        }
    </style>
</head>
<body>
    <div class="mobile-wrapper">
        
        <!-- Mobile Header -->
        <header class="mobile-header">
            <div class="mobile-logo">Sistem Peminjaman</div>
        </header>

        <!-- Main Content Area -->
        <div style="padding: 20px;">
            
            <!-- Hero Card -->
            <div class="hero-section">
                <h1 class="welcome-title">Halo, <?= htmlspecialchars($_SESSION['nama']) ?></h1>
                <p class="welcome-desc">Selamat datang di aplikasi mobile peminjaman fasilitas dan aset.</p>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 16px;">
                    <div class="badge-count">
                        <span class="badge-count-number text-white"><?= $total_aktif ?></span>
                        <span class="badge-count-label">Sedang Dipinjam</span>
                    </div>
                    <div class="badge-count">
                        <span class="badge-count-number text-danger"><?= $total_terlambat ?></span>
                        <span class="badge-count-label">Terlambat Kembali</span>
                    </div>
                </div>
            </div>

            <!-- Big Scan Button -->
            <div class="cta-scan-container">
                <a href="<?= $base ?>/mobile/scan.php" class="btn-glass btn-scan-large">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>
                    Mulai Scan QR
                </a>
            </div>

            <!-- Active Borrowing List -->
            <div>
                <h3 style="font-size: 1.1rem; margin-bottom: 12px; color: var(--text-primary);">Aset Yang Anda Pinjam</h3>
                
                <?php if (count($peminjaman_aktif) > 0): ?>
                    <?php foreach ($peminjaman_aktif as $pinjam): ?>
                        <div class="asset-card">
                            <?php if (!empty($pinjam['foto']) && file_exists(__DIR__ . '/../uploads/alat/' . $pinjam['foto'])): ?>
                                <img src="<?= $base ?>/uploads/alat/<?= htmlspecialchars($pinjam['foto']) ?>" alt="Foto" class="asset-card-img">
                            <?php else: ?>
                                <div class="asset-card-img d-flex align-items-center justify-content-center text-muted" style="font-size: 0.65rem;">No Pic</div>
                            <?php endif; ?>
                            
                            <div class="asset-card-info">
                                <div class="asset-card-title"><?= htmlspecialchars($pinjam['nama_alat']) ?></div>
                                <div class="asset-card-meta">QR: <span class="font-monospace text-info"><?= htmlspecialchars($pinjam['kode_qr']) ?></span></div>
                                <div class="asset-card-meta">Kembali Rencana: <?= date('d M Y', strtotime($pinjam['tgl_kembali_rencana'])) ?></div>
                            </div>
                            
                            <div>
                                <?php
                                $badge_type = $pinjam['status'] === 'Terlambat' ? 'badge-glass-danger' : 'badge-glass-warning';
                                ?>
                                <span class="badge-glass <?= $badge_type ?>" style="font-size: 0.65rem; padding: 4px 6px;"><?= htmlspecialchars($pinjam['status']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="glass-card" style="text-align: center; padding: 32px 16px; color: var(--text-secondary);">
                        <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin-bottom: 12px; opacity: 0.5;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        <p style="font-size: 0.85rem;">Tidak ada peminjaman aktif saat ini.</p>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>

        <!-- Sticky Bottom Navigation -->
        <nav class="mobile-nav">
            <a href="<?= $base ?>/mobile/dashboard.php" class="mobile-nav-item active">
                <svg fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                </svg>
                <span>Dashboard</span>
            </a>
            <a href="<?= $base ?>/mobile/scan.php" class="mobile-nav-item">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                </svg>
                <span>Scan QR</span>
            </a>
            <a href="<?= $base ?>/mobile/riwayat.php" class="mobile-nav-item">
                <svg fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                </svg>
                <span>Riwayat</span>
            </a>
            <a href="<?= $base ?>/auth/logout.php" class="mobile-nav-item" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span>Keluar</span>
            </a>
        </nav>

    </div>
</body>
</html>
