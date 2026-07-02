<?php
// helpers/log_helper.php

if (!function_exists('log_activity')) {
    function log_activity(
        PDO    $pdo,
        ?int   $user_id,
        string $action,
        string $tabel_target = '',
        ?int   $record_id    = null,
        string $keterangan   = ''
    ): void {
        $ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        try {
            $stmt = $pdo->prepare("
                INSERT INTO log_activity (user_id, ip_address, action, tabel_target, record_id, keterangan)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $ip, $action, $tabel_target, $record_id, $keterangan]);
        } catch (PDOException $e) {
            // Abaikan error logging agar tidak merusak alur aplikasi utama, 
            // namun dalam production sebaiknya dicatat ke error log server.
            error_log("Gagal mencatat log_activity: " . $e->getMessage());
        }
    }
}
