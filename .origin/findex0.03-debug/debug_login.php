<?php
// Debug Login for PHP 5.6
session_start();

// Skip authentication check for login page
define('SKIP_AUTH_CHECK', true);

// Include the simplified auth class
require_once 'qnap_auth_simple.php';

// Remove the auto-check from the auth file for login page
session_destroy();
session_start();

$auth = new SimpleQNAPAuth();
$debug_info = array();

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    $debug_info[] = "Form submitted with username: " . htmlspecialchars($username);
    
    if (empty($username) || empty($password)) {
        $debug_info[] = "ERROR: Empty username or password";
    } else {
        $debug_info[] = "Attempting authentication...";
        
        // Test file access first
        $debug_info[] = "Testing file access:";
        $debug_info[] = "- /etc/passwd readable: " . (is_readable('/etc/passwd') ? 'YES' : 'NO');
        $debug_info[] = "- /etc/shadow readable: " . (is_readable('/etc/shadow') ? 'YES' : 'NO');
        
        // Try authentication
        try {
            $auth_result = $auth->authenticate($username, $password);
            $debug_info[] = "Authentication result: " . ($auth_result ? 'SUCCESS' : 'FAILED');
            
            if ($auth_result) {
                $debug_info[] = "Setting session variables...";
                $_SESSION['qnap_logged_in'] = true;
                $_SESSION['qnap_username'] = $username;
                $_SESSION['last_activity'] = time();
                
                // Get user info
                $user_info = $auth->getUserInfo($username);
                if ($user_info) {
                    $_SESSION['qnap_user_info'] = $user_info;
                    $_SESSION['qnap_is_admin'] = $auth->isAdmin($username);
                    $debug_info[] = "User info retrieved: " . print_r($user_info, true);
                } else {
                    $_SESSION['qnap_user_info'] = array('username' => $username);
                    $_SESSION['qnap_is_admin'] = ($username === 'admin');
                    $debug_info[] = "Using fallback user info";
                }
                
                $debug_info[] = "Session data: " . print_r($_SESSION, true);
                $debug_info[] = "About to redirect to qnap_index_simple.php";
                
                // Check if target file exists
                if (file_exists('qnap_index_simple.php')) {
                    $debug_info[] = "Target file qnap_index_simple.php exists";
                } else {
                    $debug_info[] = "ERROR: Target file qnap_index_simple.php does not exist!";
                }
            }
        } catch (Exception $e) {
            $debug_info[] = "EXCEPTION during authentication: " . $e->getMessage();
        }
    }
}

// Get available users for display
try {
    $available_users = $auth->getValidUsers();
    $debug_info[] = "Available users: " . implode(', ', $available_users);
} catch (Exception $e) {
    $debug_info[] = "Error getting users: " . $e->getMessage();
    $available_users = array('admin', 'user');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Login - QNAP File Browser</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug { background: #f0f0f0; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .debug h3 { margin-top: 0; }
        .debug pre { background: white; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .form-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 400px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #005a87; }
    </style>
</head>
<body>
    <h1>🐛 Debug Login</h1>
    
    <div class="form-container">
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">Login & Debug</button>
        </form>
        
        <p><strong>Test Users:</strong> admin/admin, user/user</p>
    </div>
    
    <?php if (!empty($debug_info)): ?>
        <div class="debug">
            <h3>🔍 Debug Information</h3>
            <pre><?php echo implode("\n", $debug_info); ?></pre>
        </div>
    <?php endif; ?>
    
    <div class="debug">
        <h3>📊 System Information</h3>
        <pre>PHP Version: <?php echo PHP_VERSION; ?>
Server Time: <?php echo date('Y-m-d H:i:s'); ?>
Current Directory: <?php echo getcwd(); ?>
Session ID: <?php echo session_id(); ?>

File Checks:
- qnap_auth_simple.php: <?php echo file_exists('qnap_auth_simple.php') ? 'EXISTS' : 'MISSING'; ?>
- qnap_index_simple.php: <?php echo file_exists('qnap_index_simple.php') ? 'EXISTS' : 'MISSING'; ?>
- qnap_browse_simple.php: <?php echo file_exists('qnap_browse_simple.php') ? 'EXISTS' : 'MISSING'; ?>

Current Session Data:
<?php print_r($_SESSION); ?></pre>
    </div>
</body>
</html> 