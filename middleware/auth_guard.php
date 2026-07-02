<?php
// middleware/auth_guard.php

require_once __DIR__ . '/../helpers/log_helper.php';

if (!function_exists('get_base_path')) {
    function get_base_path(): string {
        $doc_root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
        $proj_root = str_replace('\\', '/', dirname(__DIR__));
        
        $doc_root_lower = strtolower($doc_root);
        $proj_root_lower = strtolower($proj_root);
        
        if (strpos($proj_root_lower, $doc_root_lower) === 0) {
            $path = substr($proj_root, strlen($doc_root));
            return rtrim($path, '/');
        }
        return '';
    }
}

if (!function_exists('guard')) {
    function guard(string $required_role): void {
        // Hubungkan ke database jika ada
        global $pdo;
        if (!isset($pdo)) {
            // Jika pdo belum didefinisikan, coba load
            $db_file = __DIR__ . '/../config/database.php';
            if (file_exists($db_file)) {
                require_once $db_file;
            }
        }

        $base = get_base_path();

        // 1. Cek apakah user sudah login
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            // Catat log akses ditolak tanpa user_id
            if (isset($pdo)) {
                log_activity($pdo, null, 'ACCESS_DENIED', '', null, 'Mencoba mengakses halaman terbatas tanpa login: ' . $_SERVER['REQUEST_URI']);
            }
            header("Location: " . $base . "/auth/login.php");
            exit;
        }

        // 2. Cek apakah role sesuai
        if ($_SESSION['role'] !== $required_role) {
            if (isset($pdo)) {
                log_activity($pdo, $_SESSION['user_id'], 'ACCESS_DENIED', '', null, 'Akses ditolak ke halaman ' . $_SERVER['REQUEST_URI'] . ' (Peran saat ini: ' . $_SESSION['role'] . ')');
            }
            
            // Redirect sesuai role yang sah
            if ($_SESSION['role'] === 'admin') {
                header("Location: " . $base . "/admin/dashboard.php");
            } else {
                header("Location: " . $base . "/mobile/dashboard.php");
            }
            exit;
        }
    }
}
