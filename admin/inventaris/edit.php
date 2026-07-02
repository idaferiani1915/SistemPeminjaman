<?php
// admin/inventaris/edit.php
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth_guard.php';
require_once __DIR__ . '/../../helpers/log_helper.php';

// Memastikan user adalah admin
guard('admin');

$base = get_base_path();
$error_message = '';
$sukses_message = '';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['error_msg'] = 'ID Aset tidak valid.';
    header("Location: " . $base . "/admin/inventaris/index.php");
    exit;
}

try {
    // S-01: Prepared statement
    $stmt = $pdo->prepare("SELECT * FROM aset WHERE id = ?");
    $stmt->execute([$id]);
    $aset = $stmt->fetch();

    if (!$aset) {
        $_SESSION['error_msg'] = 'Aset tidak ditemukan.';
        header("Location: " . $base . "/admin/inventaris/index.php");
        exit;
    }
} catch (PDOException $e) {
    die("Gagal memuat data aset: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_qr = trim($_POST['kode_qr'] ?? '');
    $nama_alat = trim($_POST['nama_alat'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $kondisi = $_POST['kondisi'] ?? 'Baik';
    $status = $_POST['status'] ?? 'Tersedia';

    if (empty($kode_qr) || empty($nama_alat)) {
        $error_message = 'Kode QR dan Nama Alat wajib diisi.';
    } else {
        try {
            // Cek apakah kode QR sudah digunakan oleh aset lain
            $stmt_check = $pdo->prepare("SELECT id FROM aset WHERE kode_qr = ? AND id != ?");
            $stmt_check->execute([$kode_qr, $id]);
            if ($stmt_check->fetch()) {
                $error_message = 'Kode QR ini sudah digunakan oleh aset lain.';
            } else {
                $filename = $aset['foto']; // Gunakan foto lama secara default

                // Jika ada upload foto baru
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $file = $_FILES['foto'];
                    $allowed_extensions = ['jpg', 'jpeg', 'png'];
                    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                    // S-04 & §5.4: Validasi MIME type menggunakan finfo
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);

                    $allowed_mimes = ['image/jpeg', 'image/png', 'image/pjpeg', 'image/x-png'];

                    if (!in_array($mime, $allowed_mimes) || !in_array($file_ext, $allowed_extensions)) {
                        $error_message = 'Tipe file tidak diizinkan. Hanya file JPG/PNG yang diperbolehkan.';
                    } elseif ($file['size'] > 2097152) { // 2MB
                        $error_message = 'Ukuran file terlalu besar. Maksimal adalah 2MB.';
                    } else {
                        // Rename file secara unik
                        $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
                        $upload_dir = __DIR__ . '/../../uploads/alat/';

                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }

                        if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_filename)) {
                            // Hapus foto lama dari server jika ada
                            if (!empty($aset['foto']) && file_exists($upload_dir . $aset['foto'])) {
                                unlink($upload_dir . $aset['foto']);
                            }
                            $filename = $new_filename;
                        } else {
                            $error_message = 'Gagal menyimpan foto baru di server.';
                        }
                    }
                }

                if (empty($error_message)) {
                    // Update data aset
                    $stmt_update = $pdo->prepare("
                        UPDATE aset 
                        SET kode_qr = ?, nama_alat = ?, kategori = ?, kondisi = ?, status = ?, foto = ?
                        WHERE id = ?
                    ");
                    $stmt_update->execute([$kode_qr, $nama_alat, $kategori, $kondisi, $status, $filename, $id]);

                    // S-05 & §5.6: Catat ke log_activity
                    log_activity(
                        $pdo, 
                        $_SESSION['user_id'], 
                        'EDIT_ASET', 
                        'aset', 
                        $id, 
                        "Mengubah aset ID $id: $nama_alat ($kode_qr), Kondisi: $kondisi, Status: $status"
                    );

                    $_SESSION['sukses_msg'] = 'Data aset berhasil diperbarui!';
                    header("Location: " . $base . "/admin/inventaris/index.php");
                    exit;
                }
            }
        } catch (PDOException $e) {
            $error_message = 'Gagal memperbarui data di database. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Fasilitas — Sistem Peminjaman Fasilitas</title>
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
        .form-card {
            max-width: 600px;
            margin: 0 auto;
        }
        .preview-img {
            max-width: 150px;
            border-radius: 8px;
            border: 1px solid var(--glass-border);
            margin-top: 10px;
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
        <div class="mb-4">
            <a href="<?= $base ?>/admin/inventaris/index.php" class="text-secondary d-inline-flex align-items-center gap-2 mb-2">
                &larr; Kembali ke Daftar Aset
            </a>
            <h1 class="text-white">Edit Fasilitas / Item</h1>
            <p class="text-secondary">Perbarui informasi fasilitas atau alat dalam database inventaris.</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert-glass alert-glass-error form-card mb-4">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <span><?= htmlspecialchars($error_message) ?></span>
            </div>
        <?php endif; ?>

        <div class="glass-card form-card">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label" for="kode_qr">Kode QR / Serial Number</label>
                    <input class="input-glass" type="text" id="kode_qr" name="kode_qr" required value="<?= htmlspecialchars($aset['kode_qr']) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="nama_alat">Nama Item</label>
                    <input class="input-glass" type="text" id="nama_alat" name="nama_alat" required value="<?= htmlspecialchars($aset['nama_alat']) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="kategori">Kategori</label>
                    <input class="input-glass" type="text" id="kategori" name="kategori" value="<?= htmlspecialchars($aset['kategori'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="kondisi">Kondisi Fisik</label>
                    <select class="input-glass" id="kondisi" name="kondisi">
                        <option value="Baik" <?= $aset['kondisi'] === 'Baik' ? 'selected' : '' ?>>Baik</option>
                        <option value="Rusak" <?= $aset['kondisi'] === 'Rusak' ? 'selected' : '' ?>>Rusak</option>
                        <option value="Maintenance" <?= $aset['kondisi'] === 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Status Ketersediaan</label>
                    <select class="input-glass" id="status" name="status">
                        <option value="Tersedia" <?= $aset['status'] === 'Tersedia' ? 'selected' : '' ?>>Tersedia</option>
                        <option value="Dipinjam" <?= $aset['status'] === 'Dipinjam' ? 'selected' : '' ?>>Dipinjam</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="foto">Foto Item (Abaikan jika tidak ingin mengubah)</label>
                    <input class="input-glass" type="file" id="foto" name="foto" accept="image/png, image/jpeg, image/jpg">
                    <?php if (!empty($aset['foto']) && file_exists(__DIR__ . '/../../uploads/alat/' . $aset['foto'])): ?>
                        <div class="mt-3">
                            <span class="d-block text-secondary small mb-2">Foto Saat Ini:</span>
                            <img src="<?= $base ?>/uploads/alat/<?= htmlspecialchars($aset['foto']) ?>" alt="Foto Aset" class="preview-img">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-end gap-3 mt-4">
                    <a href="<?= $base ?>/admin/inventaris/index.php" class="btn-glass btn-glass-secondary text-center" style="text-decoration: none;">Batal</a>
                    <button class="btn-glass" type="submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
