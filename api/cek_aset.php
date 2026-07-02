<?php
// api/cek_aset.php
session_start();

// S-03: Cek apakah user sudah login. Jika tidak login, tidak boleh mengakses API ini
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Autentikasi diperlukan. Silakan login kembali.',
        'data' => null
    ]);
    exit;
}

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$kode_qr = isset($_GET['kode_qr']) ? trim($_GET['kode_qr']) : '';

if (empty($kode_qr)) {
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Kode QR kosong atau tidak disertakan.',
        'data' => null
    ]);
    exit;
}

try {
    // S-01: Prepared statement
    $stmt = $pdo->prepare("SELECT * FROM aset WHERE kode_qr = ?");
    $stmt->execute([$kode_qr]);
    $aset = $stmt->fetch();

    if ($aset) {
        // Sanitasi output untuk menghindari XSS sebelum dikirim (opsional namun bagus untuk ketahanan)
        $clean_aset = [
            'id' => (int)$aset['id'],
            'kode_qr' => htmlspecialchars($aset['kode_qr']),
            'nama_alat' => htmlspecialchars($aset['nama_alat']),
            'kategori' => htmlspecialchars($aset['kategori'] ?? ''),
            'kondisi' => htmlspecialchars($aset['kondisi']),
            'status' => htmlspecialchars($aset['status']),
            'foto' => htmlspecialchars($aset['foto'] ?? '')
        ];

        echo json_encode([
            'sukses' => true,
            'pesan' => 'Aset ditemukan.',
            'data' => $clean_aset
        ]);
    } else {
        echo json_encode([
            'sukses' => false,
            'pesan' => 'Aset dengan kode QR "' . htmlspecialchars($kode_qr) . '" tidak terdaftar di sistem.',
            'data' => null
        ]);
    }
} catch (PDOException $e) {
    // S-06: Jangan ekspos error DB mentah ke API client
    http_response_code(500);
    echo json_encode([
        'sukses' => false,
        'pesan' => 'Terjadi kesalahan internal pada server.',
        'data' => null
    ]);
}
exit;
