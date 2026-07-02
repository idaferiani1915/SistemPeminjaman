<?php
// admin/peminjam/hapus.php — Handler hapus peminjam (POST only)
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/log_helper.php';
require_once __DIR__ . '/../../middleware/auth_guard.php';

$base = get_base_path();

// Hanya izinkan admin dan metode POST
guard('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $base/admin/peminjam/index.php");
    exit;
}

$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['error_msg'] = 'ID peminjam tidak valid.';
    header("Location: $base/admin/peminjam/index.php");
    exit;
}

try {
    // Pastikan yang dihapus adalah peminjam (bukan admin)
    $stmt = $pdo->prepare("SELECT id, nama, email, role FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['error_msg'] = 'Peminjam tidak ditemukan.';
        header("Location: $base/admin/peminjam/index.php");
        exit;
    }

    if ($user['role'] !== 'peminjam') {
        $_SESSION['error_msg'] = 'Tidak dapat menghapus akun admin.';
        header("Location: $base/admin/peminjam/index.php");
        exit;
    }

    // Blokir jika masih ada peminjaman AKTIF (belum dikembalikan)
    $stmt_aktif = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE user_id = ? AND status = 'Aktif'");
    $stmt_aktif->execute([$id]);
    $jumlah_aktif = $stmt_aktif->fetchColumn();

    if ($jumlah_aktif > 0) {
        $_SESSION['error_msg'] = "Tidak dapat menghapus {$user['nama']} karena masih memiliki {$jumlah_aktif} peminjaman yang belum dikembalikan.";
        header("Location: $base/admin/peminjam/index.php");
        exit;
    }

    // Hapus semua riwayat peminjaman (yang sudah selesai) agar FK tidak memblokir
    $pdo->prepare("DELETE FROM peminjaman WHERE user_id = ?")->execute([$id]);

    // Hapus token reset password terkait (jika ada)
    $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$id]);

    // Hapus user
    $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'peminjam'")->execute([$id]);

    // Catat log
    log_activity(
        $pdo,
        $_SESSION['user_id'],
        'DELETE_USER',
        'users',
        $id,
        "Admin menghapus akun peminjam: {$user['email']}"
    );

    $_SESSION['sukses_msg'] = "Akun peminjam {$user['nama']} berhasil dihapus.";

} catch (PDOException $e) {
    $_SESSION['error_msg'] = 'Gagal menghapus peminjam: ' . $e->getMessage();
}

header("Location: $base/admin/peminjam/index.php");
exit;
