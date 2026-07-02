<?php
// config/database.php — satu-satunya sumber koneksi

$dsn = "mysql:host=localhost;dbname=db_peminjaman_aset;charset=utf8mb4";
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // S-06: Jangan tampilkan error PHP mentah ke user
    die("Error: Koneksi database bermasalah. Silakan pastikan server database MySQL Anda (XAMPP/Laragon) sudah dijalankan.");
}
