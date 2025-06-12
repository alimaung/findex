<?php
// Authentication check script
session_start();

// Session timeout (30 minutes of inactivity)
$session_timeout = 1800;

function checkAuth() {
    global $session_timeout;
    
    // Check if user is logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        redirectToLogin();
        return false;
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > $session_timeout) {
            // Session expired
            session_destroy();
            redirectToLogin('Session expired. Please login again.');
            return false;
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    return true;
}

function redirectToLogin($message = '') {
    $redirect_url = 'login.php';
    if (!empty($message)) {
        $redirect_url .= '?message=' . urlencode($message);
    }
    header('Location: ' . $redirect_url);
    exit;
}

function logout() {
    session_destroy();
    redirectToLogin('You have been logged out.');
}

function getLoggedInUser() {
    return $_SESSION['username'] ?? 'Unknown';
}

function getLoginTime() {
    return $_SESSION['login_time'] ?? time();
}

// Auto-check authentication when this file is included
if (!checkAuth()) {
    exit;
}
?> 