<?php
// admin/pengembalian/checkin.php
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth_guard.php';
require_once __DIR__ . '/../../helpers/log_helper.php';

// Memastikan user adalah admin
guard('admin');

$base = get_base_path();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['error_msg'] = 'ID Transaksi tidak valid.';
    header("Location: " . $base . "/admin/pengembalian/index.php");
    exit;
}

try {
    // S-01: Cari data transaksi peminjaman
    $stmt = $pdo->prepare("
        SELECT p.*, a.nama_alat, a.kode_qr 
        FROM peminjaman p
        JOIN aset a ON p.aset_id = a.id
        WHERE p.id = ?
    ");
    $stmt->execute([$id]);
    $transaction = $stmt->fetch();

    if (!$transaction) {
        $_SESSION['error_msg'] = 'Transaksi peminjaman tidak ditemukan.';
        header("Location: " . $base . "/admin/pengembalian/index.php");
        exit;
    }

    if ($transaction['status'] === 'Dikembalikan') {
        $_SESSION['error_msg'] = 'Aset ini sudah dikembalikan sebelumnya.';
        header("Location: " . $base . "/admin/pengembalian/index.php");
        exit;
    }

    // §5.5: Operasi menyentuh lebih dari satu tabel wajib dibungkus transaksi PDO
    $pdo->beginTransaction();

    // 1. Update status peminjaman
    $stmt_update_peminjaman = $pdo->prepare("
        UPDATE peminjaman 
        SET status = 'Dikembalikan', tgl_kembali_aktual = NOW() 
        WHERE id = ?
    ");
    $stmt_update_peminjaman->execute([$id]);

    // 2. Update status aset menjadi Tersedia
    $stmt_update_aset = $pdo->prepare("
        UPDATE aset 
        SET status = 'Tersedia' 
        WHERE id = ?
    ");
    $stmt_update_aset->execute([$transaction['aset_id']]);

    // 3. Catat ke log_activity
    log_activity(
        $pdo, 
        $_SESSION['user_id'], 
        'CHECK_IN', 
        'peminjaman', 
        $id, 
        "Check-in pengembalian aset: " . $transaction['nama_alat'] . " (" . $transaction['kode_qr'] . ") dari user ID: " . $transaction['user_id']
    );

    // Commit semua query jika berhasil
    $pdo->commit();

    $_SESSION['sukses_msg'] = 'Aset "' . $transaction['nama_alat'] . '" berhasil dikembalikan!';
} catch (Exception $e) {
    // Rollback jika terjadi kegagalan
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // S-06: Tangani error dengan ramah
    $_SESSION['error_msg'] = 'Gagal memproses pengembalian aset karena kesalahan sistem.';
}

header("Location: " . $base . "/admin/pengembalian/index.php");
exit;
