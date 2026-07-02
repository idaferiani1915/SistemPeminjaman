<?php
// admin/inventaris/hapus.php
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth_guard.php';
require_once __DIR__ . '/../../helpers/log_helper.php';

// Memastikan user adalah admin
guard('admin');

$base = get_base_path();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['error_msg'] = 'ID Aset tidak valid.';
    header("Location: " . $base . "/admin/inventaris/index.php");
    exit;
}

try {
    // S-01: Cari data aset terlebih dahulu untuk mendapatkan nama dan foto
    $stmt = $pdo->prepare("SELECT nama_alat, kode_qr, foto FROM aset WHERE id = ?");
    $stmt->execute([$id]);
    $aset = $stmt->fetch();

    if (!$aset) {
        $_SESSION['error_msg'] = 'Aset tidak ditemukan.';
        header("Location: " . $base . "/admin/inventaris/index.php");
        exit;
    }

    // Coba hapus aset dari database
    $stmt_delete = $pdo->prepare("DELETE FROM aset WHERE id = ?");
    $stmt_delete->execute([$id]);

    // Jika berhasil didelete database, hapus file fotonya jika ada
    if (!empty($aset['foto'])) {
        $file_path = __DIR__ . '/../../uploads/alat/' . $aset['foto'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    // S-05 & §5.6: Catat ke log_activity
    log_activity(
        $pdo, 
        $_SESSION['user_id'], 
        'HAPUS_ASET', 
        'aset', 
        $id, 
        "Menghapus aset: " . $aset['nama_alat'] . " (" . $aset['kode_qr'] . ")"
    );

    $_SESSION['sukses_msg'] = 'Aset ' . $aset['nama_alat'] . ' berhasil dihapus.';
} catch (PDOException $e) {
    // S-06: Tangani constraint integrity (RESTRICT) jika barang sudah pernah dipinjam
    if ($e->getCode() == '23000') {
        $_SESSION['error_msg'] = 'Aset "' . $aset['nama_alat'] . '" tidak dapat dihapus karena memiliki riwayat transaksi peminjaman. Sebagai alternatif, Anda dapat mengubah kondisinya menjadi "Rusak" atau "Maintenance".';
    } else {
        $_SESSION['error_msg'] = 'Gagal menghapus aset karena kesalahan sistem.';
    }
}

header("Location: " . $base . "/admin/inventaris/index.php");
exit;
