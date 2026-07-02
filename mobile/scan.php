<?php
// mobile/scan.php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth_guard.php';
require_once __DIR__ . '/../helpers/log_helper.php';

// Memastikan user adalah peminjam
guard('peminjam');

$base = get_base_path();
$user_id = $_SESSION['user_id'];
$error_message = '';
$sukses_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aset_id = filter_input(INPUT_POST, 'aset_id', FILTER_VALIDATE_INT);
    $tgl_kembali_rencana = $_POST['tgl_kembali_rencana'] ?? '';

    if (!$aset_id || empty($tgl_kembali_rencana)) {
        $error_message = 'Data peminjaman tidak lengkap.';
    } else {
        try {
            // S-01: Prepared statement untuk cek status terupdate dari aset
            $stmt_aset = $pdo->prepare("SELECT * FROM aset WHERE id = ?");
            $stmt_aset->execute([$aset_id]);
            $aset = $stmt_aset->fetch();

            if (!$aset) {
                $error_message = 'Aset tidak ditemukan.';
            } elseif ($aset['status'] !== 'Tersedia') {
                $error_message = 'Maaf, aset sedang dipinjam oleh orang lain.';
            } elseif ($aset['kondisi'] !== 'Baik') {
                $error_message = 'Maaf, aset tidak dalam kondisi Baik (sedang Rusak/Maintenance).';
            } elseif (strtotime($tgl_kembali_rencana) < strtotime(date('Y-m-d'))) {
                $error_message = 'Tanggal rencana pengembalian tidak boleh di masa lalu.';
            } else {
                // §5.5 & §8.5: Operasi multi-tabel dibungkus transaksi PDO
                $pdo->beginTransaction();

                // 1. Insert ke peminjaman
                $stmt_insert = $pdo->prepare("
                    INSERT INTO peminjaman (user_id, aset_id, tgl_pinjam, tgl_kembali_rencana, status)
                    VALUES (?, ?, NOW(), ?, 'Aktif')
                ");
                $stmt_insert->execute([$user_id, $aset_id, $tgl_kembali_rencana]);
                $new_pinjam_id = $pdo->lastInsertId();

                // 2. Update status aset jadi Dipinjam
                $stmt_update_aset = $pdo->prepare("
                    UPDATE aset 
                    SET status = 'Dipinjam' 
                    WHERE id = ?
                ");
                $stmt_update_aset->execute([$aset_id]);

                // 3. Catat log_activity
                log_activity(
                    $pdo, 
                    $user_id, 
                    'PINJAM_ASET', 
                    'peminjaman', 
                    $new_pinjam_id, 
                    "Meminjam aset: " . $aset['nama_alat'] . " (" . $aset['kode_qr'] . ") s.d " . $tgl_kembali_rencana
                );

                $pdo->commit();

                $_SESSION['sukses_msg'] = 'Peminjaman aset "' . $aset['nama_alat'] . '" berhasil diajukan!';
                header("Location: " . $base . "/mobile/dashboard.php");
                exit;
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_message = 'Terjadi kesalahan sistem saat menyimpan transaksi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Scan QR Item — Sistem Peminjaman</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/global.css?v=3">
    <!-- html5-qrcode library via CDN -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <style>
        .scanner-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 16px;
            margin-bottom: 20px;
            text-align: center;
            overflow: hidden;
            position: relative;
        }

        #reader {
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            border: none !important;
            background: #000;
        }

        /* Override style bawaan library biar senada */
        #reader__dashboard_section_swaplink {
            display: none !important;
        }

        .scanning-line {
            position: absolute;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--accent);
            box-shadow: 0 0 10px var(--accent);
            animation: scan-animation 2s linear infinite;
            pointer-events: none;
            z-index: 10;
            display: none;
        }

        @keyframes scan-animation {
            0% { top: 10%; }
            50% { top: 90%; }
            100% { top: 10%; }
        }

        .asset-details-container {
            display: none;
            animation: fadeInUp 0.3s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .info-val {
            color: var(--text-primary);
            font-weight: 500;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="mobile-wrapper">
        
        <!-- Mobile Header -->
        <header class="mobile-header">
            <div class="mobile-logo">Scan QR Aset</div>
            <a href="<?= $base ?>/mobile/dashboard.php" style="color: var(--text-secondary); font-size: 0.9rem; display: flex; align-items: center; gap: 4px;">
                &larr; Batal
            </a>
        </header>

        <div style="padding: 20px;">
            
            <?php if (!empty($error_message)): ?>
                <div class="alert-glass alert-glass-error">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span><?= htmlspecialchars($error_message) ?></span>
                </div>
            <?php endif; ?>

            <!-- Scanner Box -->
            <div id="scanner-wrapper" class="scanner-card">
                <div class="scanning-line" id="scan-line"></div>
                <div id="reader"></div>
                
                <div style="margin-top: 16px;">
                    <div id="scan-instructions" style="font-size: 0.85rem; color: var(--text-secondary);">
                        Arahkan kamera ke QR Code fasilitas atau aset.
                    </div>
                    <button class="btn-glass btn-glass-secondary" id="btn-manual-toggle" style="margin-top: 12px; font-size: 0.8rem; padding: 8px 16px; display: none;">
                        Scan Ulang
                    </button>
                </div>
            </div>

            <!-- Asset Details & Peminjaman Form (Akan muncul jika scan berhasil) -->
            <div class="glass-card asset-details-container" id="details-box">
                <h3 style="font-size: 1.1rem; margin-bottom: 16px; color: var(--text-primary);" id="detail-title">Detail Aset Ditemukan</h3>
                
                <div id="detail-error-box" class="alert-glass alert-glass-error" style="display: none;"></div>

                <div class="mb-4">
                    <div class="info-row">
                        <span class="info-label">Nama Item</span>
                        <span class="info-val" id="val-nama">-</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Kode QR</span>
                        <span class="info-val font-monospace text-info" id="val-kode">-</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Kategori</span>
                        <span class="info-val" id="val-kategori">-</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Kondisi</span>
                        <span class="info-val" id="val-kondisi">-</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status</span>
                        <span class="info-val" id="val-status">-</span>
                    </div>
                </div>

                <!-- Borrow Form -->
                <form id="borrow-form" method="POST" action="" style="display: none;">
                    <input type="hidden" name="aset_id" id="form-aset-id">
                    
                    <div class="form-group">
                        <label class="form-label" for="tgl_kembali_rencana">Tanggal Rencana Pengembalian</label>
                        <input class="input-glass" type="date" id="tgl_kembali_rencana" name="tgl_kembali_rencana" required min="<?= date('Y-m-d') ?>">
                    </div>

                    <button type="submit" class="btn-glass" style="width: 100%;">Ajukan Peminjaman</button>
                </form>

                <button class="btn-glass btn-glass-secondary" id="btn-cancel-scan" style="width: 100%; margin-top: 10px;">Scan Aset Lain</button>
            </div>

        </div>

        <!-- Sticky Bottom Navigation -->
        <nav class="mobile-nav">
            <a href="<?= $base ?>/mobile/dashboard.php" class="mobile-nav-item">
                <svg fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                </svg>
                <span>Dashboard</span>
            </a>
            <a href="<?= $base ?>/mobile/scan.php" class="mobile-nav-item active">
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

    <script type="text/javascript">
        let html5QrCode = null;

        function startScanning() {
            document.getElementById('scanner-wrapper').style.display = 'block';
            document.getElementById('details-box').style.display = 'none';
            document.getElementById('scan-line').style.display = 'block';

            html5QrCode = new Html5Qrcode("reader");
            const qrCodeSuccessCallback = (decodedText, decodedResult) => {
                // §6.2: Scanner wajib distop sebelum fetch ke server
                html5QrCode.stop().then(() => {
                    document.getElementById('scan-line').style.display = 'none';
                    cekAset(decodedText);
                }).catch((err) => {
                    console.error("Gagal menghentikan scanner: ", err);
                    // Lanjut fetch meskipun error stop untuk toleransi
                    cekAset(decodedText);
                });
            };

            const config = { 
                fps: 15, 
                qrbox: function(width, height) {
                    const minEdge = Math.min(width, height);
                    const qrboxSize = Math.floor(minEdge * 0.7);
                    return {
                        width: qrboxSize,
                        height: qrboxSize
                    };
                }
            };

            // Memulai kamera belakang
            html5QrCode.start(
                { facingMode: "environment" }, 
                config, 
                qrCodeSuccessCallback
            ).catch((err) => {
                document.getElementById('scan-instructions').innerHTML = 
                    `<span class="text-danger">Gagal membuka kamera: ${err}. Pastikan izin kamera diberikan.</span>`;
                document.getElementById('scan-line').style.display = 'none';
            });
        }

        function cekAset(kodeQr) {
            // Fetch ke API
            const apiUrl = `<?= $base ?>/api/cek_aset.php?kode_qr=${encodeURIComponent(kodeQr)}`;
            
            fetch(apiUrl)
                .then(response => response.json())
                .then(res => {
                    document.getElementById('scanner-wrapper').style.display = 'none';
                    const detailsBox = document.getElementById('details-box');
                    detailsBox.style.display = 'block';

                    const detailErrorBox = document.getElementById('detail-error-box');
                    const borrowForm = document.getElementById('borrow-form');

                    if (res.sukses) {
                        detailErrorBox.style.display = 'none';
                        
                        // Set info teks
                        document.getElementById('val-nama').innerText = res.data.nama_alat;
                        document.getElementById('val-kode').innerText = res.data.kode_qr;
                        document.getElementById('val-kategori').innerText = res.data.kategori || '-';
                        document.getElementById('val-kondisi').innerText = res.data.kondisi;
                        document.getElementById('val-status').innerText = res.data.status;

                        // §9.5/§9.7 Rule check: status = Tersedia DAN kondisi = Baik
                        if (res.data.status === 'Tersedia' && res.data.kondisi === 'Baik') {
                            borrowForm.style.display = 'block';
                            document.getElementById('form-aset-id').value = res.data.id;
                        } else {
                            borrowForm.style.display = 'none';
                            detailErrorBox.style.display = 'flex';
                            detailErrorBox.innerText = `Aset tidak tersedia untuk dipinjam saat ini karena berstatus "${res.data.status}" dengan kondisi "${res.data.kondisi}".`;
                        }
                    } else {
                        // Error dari API (aset tidak ditemukan)
                        borrowForm.style.display = 'none';
                        detailErrorBox.style.display = 'flex';
                        detailErrorBox.innerText = res.pesan;
                        
                        document.getElementById('val-nama').innerText = '-';
                        document.getElementById('val-kode').innerText = kodeQr;
                        document.getElementById('val-kategori').innerText = '-';
                        document.getElementById('val-kondisi').innerText = '-';
                        document.getElementById('val-status').innerText = '-';
                    }
                })
                .catch(err => {
                    console.error("Fetch error: ", err);
                    document.getElementById('scanner-wrapper').style.display = 'none';
                    document.getElementById('details-box').style.display = 'block';
                    document.getElementById('detail-error-box').style.display = 'flex';
                    document.getElementById('detail-error-box').innerText = "Gagal menghubungi server.";
                });
        }

        // Event listeners
        document.getElementById('btn-cancel-scan').addEventListener('click', function() {
            startScanning();
        });

        // Jalankan scanner saat dokumen siap
        window.addEventListener('DOMContentLoaded', (event) => {
            startScanning();
        });
    </script>
</body>
</html>
