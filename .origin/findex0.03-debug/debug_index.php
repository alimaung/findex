<?php
// Debug Index for PHP 5.6
session_start();

$debug_info = array();
$debug_info[] = "=== DEBUG INDEX PAGE ===";
$debug_info[] = "Session ID: " . session_id();
$debug_info[] = "Current time: " . date('Y-m-d H:i:s');

// Check session data
$debug_info[] = "\n=== SESSION DATA ===";
if (empty($_SESSION)) {
    $debug_info[] = "ERROR: Session is empty!";
} else {
    $debug_info[] = "Session data: " . print_r($_SESSION, true);
}

// Check if logged in
if (!isset($_SESSION['qnap_logged_in']) || $_SESSION['qnap_logged_in'] !== true) {
    $debug_info[] = "\n=== AUTHENTICATION CHECK ===";
    $debug_info[] = "ERROR: Not logged in!";
    $debug_info[] = "qnap_logged_in value: " . (isset($_SESSION['qnap_logged_in']) ? var_export($_SESSION['qnap_logged_in'], true) : 'NOT SET');
} else {
    $debug_info[] = "\n=== AUTHENTICATION CHECK ===";
    $debug_info[] = "SUCCESS: User is logged in";
    $debug_info[] = "Username: " . (isset($_SESSION['qnap_username']) ? $_SESSION['qnap_username'] : 'NOT SET');
    $debug_info[] = "Is Admin: " . (isset($_SESSION['qnap_is_admin']) ? ($_SESSION['qnap_is_admin'] ? 'YES' : 'NO') : 'NOT SET');
}

// Test including the auth file
$debug_info[] = "\n=== TESTING AUTH FILE INCLUDE ===";
try {
    // Don't auto-redirect for debug
    define('SKIP_AUTH_CHECK', true);
    require_once 'qnap_auth_simple.php';
    $debug_info[] = "SUCCESS: Auth file included without errors";
    
    // Test the functions
    if (function_exists('getQNAPUser')) {
        $current_user = getQNAPUser();
        $debug_info[] = "getQNAPUser() returned: " . $current_user;
    } else {
        $debug_info[] = "ERROR: getQNAPUser() function not found";
    }
    
    if (function_exists('isQNAPAdmin')) {
        $is_admin = isQNAPAdmin();
        $debug_info[] = "isQNAPAdmin() returned: " . ($is_admin ? 'true' : 'false');
    } else {
        $debug_info[] = "ERROR: isQNAPAdmin() function not found";
    }
    
} catch (Exception $e) {
    $debug_info[] = "ERROR including auth file: " . $e->getMessage();
} catch (Error $e) {
    $debug_info[] = "FATAL ERROR including auth file: " . $e->getMessage();
}

// Test file permissions
$debug_info[] = "\n=== FILE PERMISSIONS ===";
$files_to_check = array(
    'qnap_auth_simple.php',
    'qnap_index_simple.php', 
    'qnap_browse_simple.php',
    'qnap_logout_simple.php'
);

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $perms = fileperms($file);
        $debug_info[] = "$file: EXISTS (permissions: " . decoct($perms & 0777) . ")";
    } else {
        $debug_info[] = "$file: MISSING";
    }
}

// Test directory access
$debug_info[] = "\n=== DIRECTORY ACCESS ===";
$web_dir = '/share/CACHEDEV1_DATA/Web';
if (is_dir($web_dir)) {
    $debug_info[] = "Web directory exists: $web_dir";
    if (is_readable($web_dir)) {
        $debug_info[] = "Web directory is readable";
        try {
            $files = scandir($web_dir);
            $debug_info[] = "Directory contains " . count($files) . " items";
        } catch (Exception $e) {
            $debug_info[] = "Error reading directory: " . $e->getMessage();
        }
    } else {
        $debug_info[] = "ERROR: Web directory is not readable";
    }
} else {
    $debug_info[] = "ERROR: Web directory does not exist";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Index - QNAP File Browser</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .debug { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .debug h2 { margin-top: 0; color: #333; }
        .debug pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; line-height: 1.4; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .nav { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .nav a { display: inline-block; margin-right: 15px; padding: 8px 16px; background: #007cba; color: white; text-decoration: none; border-radius: 4px; }
        .nav a:hover { background: #005a87; }
    </style>
</head>
<body>
    <div class="nav">
        <a href="debug_login.php">🔐 Debug Login</a>
        <a href="qnap_login_simple.php">🚪 Regular Login</a>
        <a href="qnap_index_simple.php">📁 Try Index</a>
        <a href="phpinfo.php">ℹ️ PHP Info</a>
    </div>

    <div class="debug">
        <h2>🐛 Debug Index Page</h2>
        <pre><?php echo implode("\n", $debug_info); ?></pre>
    </div>
    
    <?php if (isset($_SESSION['qnap_logged_in']) && $_SESSION['qnap_logged_in'] === true): ?>
        <div class="debug">
            <h2>✅ Authentication Status: LOGGED IN</h2>
            <p class="success">You are successfully logged in! The issue might be in the index page itself.</p>
            <p><a href="qnap_index_simple.php">Try accessing the file browser now</a></p>
        </div>
    <?php else: ?>
        <div class="debug">
            <h2>❌ Authentication Status: NOT LOGGED IN</h2>
            <p class="error">You need to log in first.</p>
            <p><a href="debug_login.php">Go to debug login page</a></p>
        </div>
    <?php endif; ?>
</body>
</html> 