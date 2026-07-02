<?php
// auth/logout.php
session_start();

require_once __DIR__ . '/../middleware/auth_guard.php';

$base = get_base_path();

// Bersihkan session
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirect ke halaman login
header("Location: " . $base . "/auth/login.php");
exit;
