<?php
/**
 * Authentication Check
 * Include this file at the top of protected pages
 */
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['tenant_id'])) {
    // User not logged in, redirect to login page
    header('Location: login.php');
    exit;
}

// Optional: Check if session is still valid (session timeout)
$sessionTimeout = 3600 * 8; // 8 hours
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $sessionTimeout)) {
    // Session expired
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Make session data available as variables
$currentUserId = $_SESSION['user_id'];
$currentUsername = $_SESSION['username'];
$currentTenantId = $_SESSION['tenant_id'];
$currentTenantName = $_SESSION['tenant_name'];
$currentTenantCode = $_SESSION['tenant_code'];
$currentUserRole = $_SESSION['role'];
$currentUserFullName = $_SESSION['full_name'] ?? $currentUsername;
