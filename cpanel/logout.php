<?php
session_start();

// Log the logout action if database is available
try {
    require_once __DIR__ . '/../config/database.php';
    $db = Database::getInstance()->getConnection();

    if (isset($_SESSION['cpanel_user_id'])) {
        $stmt = $db->prepare("INSERT INTO cpanel_audit_log (user_id, action, ip_address, user_agent) VALUES (?, 'logout', ?, ?)");
        $stmt->execute([
            $_SESSION['cpanel_user_id'],
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
} catch (Exception $e) {
    // Continue with logout even if logging fails
}

// Clear all session data
$_SESSION = [];

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php?logged_out=1');
exit;
