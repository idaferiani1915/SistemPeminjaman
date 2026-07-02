<?php
// mobile/riwayat.php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth_guard.php';

// Memastikan user adalah peminjam
guard('peminjam');

$base = get_base_path();
$user_id = $_SESSION['user_id'];

try {
    // Ambil seluruh riwayat transaksi peminjaman user ini
    $stmt = $pdo->prepare("
        SELECT p.*, a.nama_alat, a.kode_qr 
        FROM peminjaman p 
        JOIN aset a ON p.aset_id = a.id 
        WHERE p.user_id = ? 
        ORDER BY p.tgl_pinjam DESC
    ");
    $stmt->execute([$user_id]);
    $riwayat = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Terjadi kesalahan koneksi sistem.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Riwayat Peminjaman — Sistem Peminjaman</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/global.css?v=3">
    <style>
        .riwayat-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 14px;
            position: relative;
            transition: transform 0.2s ease;
        }

        .riwayat-card.status-aktif {
            border-left: 4px solid var(--warning);
        }

        .riwayat-card.status-terlambat {
            border-left: 4px solid var(--danger);
        }

        .riwayat-card.status-dikembalikan {
            border-left: 4px solid var(--success);
        }

        .riwayat-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
            padding-right: 70px; /* Space for absolute badge */
        }

        .riwayat-status-badge {
            position: absolute;
            top: 16px;
            right: 16px;
        }

        .riwayat-meta {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .riwayat-meta svg {
            width: 14px;
            height: 14px;
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <div class="mobile-wrapper">
        
        <!-- Mobile Header -->
        <header class="mobile-header">
            <div class="mobile-logo">Riwayat Peminjaman</div>
        </header>

        <div style="padding: 20px;">
            
            <?php if (count($riwayat) > 0): ?>
                <?php foreach ($riwayat as $row): ?>
                    <?php 
                    $card_class = 'status-aktif';
                    $badge_class = 'badge-glass-warning';
                    if ($row['status'] === 'Terlambat') {
                        $card_class = 'status-terlambat';
                        $badge_class = 'badge-glass-danger';
                    } elseif ($row['status'] === 'Dikembalikan') {
                        $card_class = 'status-dikembalikan';
                        $badge_class = 'badge-glass-success';
                    }
                    ?>
                    
                    <div class="riwayat-card <?= $card_class ?>">
                        <div class="riwayat-title"><?= htmlspecialchars($row['nama_alat']) ?></div>
                        
                        <div class="riwayat-status-badge">
                            <span class="badge-glass <?= $badge_class ?>" style="font-size: 0.65rem; padding: 4px 6px;"><?= htmlspecialchars($row['status']) ?></span>
                        </div>

                        <div class="riwayat-meta">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                            </svg>
                            <span>Kode QR: <span class="font-monospace text-info"><?= htmlspecialchars($row['kode_qr']) ?></span></span>
                        </div>

                        <div class="riwayat-meta">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1-1v3H1a1 1 0 000 2h18a1 1 0 100-2h-4V1a1 1 0 00-2 0v3H5V1a1 1 0 00-1-1zM1 8h18v10a2 2 0 01-2 2H3a2 2 0 01-2-2V8zm14 3a1 1 0 11-2 0v2a1 1 0 112 0v-2z" clip-rule="evenodd"/>
                            </svg>
                            <span>Dipinjam: <?= date('d M Y, H:i', strtotime($row['tgl_pinjam'])) ?></span>
                        </div>

                        <div class="riwayat-meta">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1-1v3H1a1 1 0 000 2h18a1 1 0 100-2h-4V1a1 1 0 00-2 0v3H5V1a1 1 0 00-1-1zM1 8h18v10a2 2 0 01-2 2H3a2 2 0 01-2-2V8zm14 3a1 1 0 11-2 0v2a1 1 0 112 0v-2z" clip-rule="evenodd"/>
                            </svg>
                            <span>Batas Kembali: <?= date('d M Y', strtotime($row['tgl_kembali_rencana'])) ?></span>
                        </div>

                        <?php if ($row['tgl_kembali_aktual']): ?>
                            <div class="riwayat-meta" style="color: #34d399;">
                                <svg fill="currentColor" viewBox="0 0 20 20" style="color: #34d399;">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Dikembalikan: <?= date('d M Y, H:i', strtotime($row['tgl_kembali_aktual'])) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="glass-card" style="text-align: center; padding: 48px 16px; color: var(--text-secondary);">
                    <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin-bottom: 16px; opacity: 0.5;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                    <p style="font-size: 0.9rem;">Anda belum pernah meminjam fasilitas atau aset.</p>
                </div>
            <?php endif; ?>

        </div>

        <!-- Sticky Bottom Navigation -->
        <nav class="mobile-nav">
            <a href="<?= $base ?>/mobile/dashboard.php" class="mobile-nav-item">
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
            <a href="<?= $base ?>/mobile/riwayat.php" class="mobile-nav-item active">
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
