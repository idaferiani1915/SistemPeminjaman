<?php
// admin/inventaris/tambah.php
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth_guard.php';
require_once __DIR__ . '/../../helpers/log_helper.php';

// Memastikan user adalah admin
guard('admin');

$base = get_base_path();
$error_message = '';
$sukses_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_qr = trim($_POST['kode_qr'] ?? '');
    $nama_alat = trim($_POST['nama_alat'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $kondisi = $_POST['kondisi'] ?? 'Baik';
    $status = 'Tersedia'; // Default baru ditambah adalah Tersedia

    // Validasi input
    if (empty($kode_qr) || empty($nama_alat)) {
        $error_message = 'Kode QR dan Nama Alat wajib diisi.';
    } else {
        try {
            // Cek apakah kode QR sudah terdaftar
            $stmt_check = $pdo->prepare("SELECT id FROM aset WHERE kode_qr = ?");
            $stmt_check->execute([$kode_qr]);
            if ($stmt_check->fetch()) {
                $error_message = 'Kode QR ini sudah digunakan oleh aset lain.';
            } else {
                $filename = null;

                // Proses Upload Foto jika ada file
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $file = $_FILES['foto'];
                    $allowed_extensions = ['jpg', 'jpeg', 'png'];
                    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                    switch ($file['error']) {
                        case UPLOAD_ERR_OK:
                            break;
                        case UPLOAD_ERR_INI_SIZE:
                        case UPLOAD_ERR_FORM_SIZE:
                            $error_message = 'Ukuran file terlalu besar. Maksimal adalah 2MB.';
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $error_message = 'File hanya terupload sebagian. Silakan coba lagi.';
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            $error_message = 'Folder sementara upload tidak tersedia di server.';
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            $error_message = 'Server gagal menulis file sementara.';
                            break;
                        case UPLOAD_ERR_EXTENSION:
                            $error_message = 'Upload dibatalkan oleh ekstensi PHP.';
                            break;
                        default:
                            $error_message = 'Terjadi kesalahan saat mengunggah file. Silakan coba lagi.';
                            break;
                    }

                    if (empty($error_message)) {
                        if (!is_uploaded_file($file['tmp_name'])) {
                            $error_message = 'File upload tidak valid.';
                        } else {
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
                                $filename = uniqid() . '_' . time() . '.' . $file_ext;
                                $upload_dir = __DIR__ . '/../../uploads/alat/';

                                // Buat folder jika belum ada
                                if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
                                    $error_message = 'Gagal membuat folder upload di server.';
                                    $filename = null;
                                } elseif (!is_writable($upload_dir)) {
                                    $error_message = 'Folder upload tidak memiliki izin tulis.';
                                    $filename = null;
                                } elseif (!move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
                                    $error_message = 'Gagal menyimpan foto di server.';
                                    $filename = null;
                                }
                            }
                        }
                    }
                }

                // Jika tidak ada error validasi foto
                if (empty($error_message)) {
                    // S-01: Insert data aset baru
                    $stmt_insert = $pdo->prepare("
                        INSERT INTO aset (kode_qr, nama_alat, kategori, kondisi, status, foto)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt_insert->execute([$kode_qr, $nama_alat, $kategori, $kondisi, $status, $filename]);
                    
                    $new_id = $pdo->lastInsertId();

                    // S-05 & §5.6: Catat ke log_activity
                    log_activity(
                        $pdo, 
                        $_SESSION['user_id'], 
                        'TAMBAH_ASET', 
                        'aset', 
                        $new_id, 
                        "Menambah aset baru: $nama_alat ($kode_qr), Kondisi: $kondisi"
                    );

                    $_SESSION['sukses_msg'] = 'Aset berhasil ditambahkan!';
                    header("Location: " . $base . "/admin/inventaris/index.php");
                    exit;
                }
            }
        } catch (PDOException $e) {
            $error_message = 'Gagal menyimpan data ke database. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Fasilitas Baru — Sistem Peminjaman Fasilitas</title>
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
            <h1 class="text-white">Tambah Fasilitas Baru</h1>
            <p class="text-secondary">Daftarkan fasilitas, ruangan, atau alat baru ke dalam database inventaris.</p>
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
                    <input class="input-glass" type="text" id="kode_qr" name="kode_qr" placeholder="Contoh: LAB-005" required value="<?= isset($_POST['kode_qr']) ? htmlspecialchars($_POST['kode_qr']) : '' ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="nama_alat">Nama Item</label>
                    <input class="input-glass" type="text" id="nama_alat" name="nama_alat" placeholder="Nama fasilitas, ruangan, atau alat" required value="<?= isset($_POST['nama_alat']) ? htmlspecialchars($_POST['nama_alat']) : '' ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="kategori">Kategori</label>
                    <input class="input-glass" type="text" id="kategori" name="kategori" placeholder="Contoh: Optik, Elektronik, Alat Ukur" value="<?= isset($_POST['kategori']) ? htmlspecialchars($_POST['kategori']) : '' ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="kondisi">Kondisi Fisik</label>
                    <select class="input-glass" id="kondisi" name="kondisi">
                        <option value="Baik" <?= isset($_POST['kondisi']) && $_POST['kondisi'] === 'Baik' ? 'selected' : '' ?>>Baik</option>
                        <option value="Rusak" <?= isset($_POST['kondisi']) && $_POST['kondisi'] === 'Rusak' ? 'selected' : '' ?>>Rusak</option>
                        <option value="Maintenance" <?= isset($_POST['kondisi']) && $_POST['kondisi'] === 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="foto">Foto Item (Maksimal 2MB, JPG/PNG)</label>
                    <input class="input-glass" type="file" id="foto" name="foto" accept="image/png, image/jpeg, image/jpg">
                </div>

                <div class="d-flex justify-content-end gap-3 mt-4">
                    <a href="<?= $base ?>/admin/inventaris/index.php" class="btn-glass btn-glass-secondary text-center" style="text-decoration: none;">Batal</a>
                    <button class="btn-glass" type="submit">Simpan Aset</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
