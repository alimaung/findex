<?php
// Debug QNAP Authentication - Step by Step
session_start();

// Skip auth check for debugging
define('SKIP_AUTH_CHECK', true);

$debug_steps = array();
$debug_steps[] = "=== QNAP AUTHENTICATION DEBUG ===";
$debug_steps[] = "Time: " . date('Y-m-d H:i:s');
$debug_steps[] = "PHP Version: " . PHP_VERSION;

// Step 1: Test basic file access
$debug_steps[] = "\n=== STEP 1: FILE ACCESS TEST ===";
$shadow_file = '/etc/shadow';
$passwd_file = '/etc/passwd';

$debug_steps[] = "Shadow file ($shadow_file):";
$debug_steps[] = "- Exists: " . (file_exists($shadow_file) ? 'YES' : 'NO');
$debug_steps[] = "- Readable: " . (is_readable($shadow_file) ? 'YES' : 'NO');

$debug_steps[] = "Passwd file ($passwd_file):";
$debug_steps[] = "- Exists: " . (file_exists($passwd_file) ? 'YES' : 'NO');
$debug_steps[] = "- Readable: " . (is_readable($passwd_file) ? 'YES' : 'NO');

// Step 2: Read and parse passwd file
$debug_steps[] = "\n=== STEP 2: PASSWD FILE ANALYSIS ===";
if (is_readable($passwd_file)) {
    try {
        $passwd_content = file_get_contents($passwd_file);
        $passwd_lines = explode("\n", $passwd_content);
        $debug_steps[] = "Passwd file contains " . count($passwd_lines) . " lines";
        
        $users_found = array();
        foreach ($passwd_lines as $line) {
            if (empty($line)) continue;
            $parts = explode(':', $line);
            if (count($parts) >= 7) {
                $username = $parts[0];
                $uid = intval($parts[2]);
                $shell = $parts[6];
                
                // Show interesting users
                if (($uid >= 1000 && $uid < 65534) || 
                    in_array($username, array('admin', 'guest')) ||
                    strpos($shell, 'nologin') === false) {
                    $users_found[] = "$username (UID: $uid, Shell: $shell)";
                }
            }
        }
        $debug_steps[] = "Interesting users found: " . count($users_found);
        foreach ($users_found as $user) {
            $debug_steps[] = "- $user";
        }
    } catch (Exception $e) {
        $debug_steps[] = "ERROR reading passwd file: " . $e->getMessage();
    }
} else {
    $debug_steps[] = "Cannot read passwd file";
}

// Step 3: Read and parse shadow file
$debug_steps[] = "\n=== STEP 3: SHADOW FILE ANALYSIS ===";
if (is_readable($shadow_file)) {
    try {
        $shadow_content = file_get_contents($shadow_file);
        $shadow_lines = explode("\n", $shadow_content);
        $debug_steps[] = "Shadow file contains " . count($shadow_lines) . " lines";
        
        $shadow_users = array();
        foreach ($shadow_lines as $line) {
            if (empty($line)) continue;
            $parts = explode(':', $line);
            if (count($parts) >= 2) {
                $username = $parts[0];
                $hash = $parts[1];
                
                // Only show users we care about
                if (in_array($username, array('admin', 'guest', 'alibae', 'bilalbabo'))) {
                    $hash_type = 'unknown';
                    if ($hash === '*' || $hash === '!') {
                        $hash_type = 'disabled';
                    } elseif (substr($hash, 0, 3) === '$1$') {
                        $hash_type = 'MD5';
                    } elseif (substr($hash, 0, 3) === '$6$') {
                        $hash_type = 'SHA-512';
                    } elseif (substr($hash, 0, 3) === '$5$') {
                        $hash_type = 'SHA-256';
                    }
                    
                    $shadow_users[] = "$username: $hash_type (" . substr($hash, 0, 20) . "...)";
                }
            }
        }
        
        foreach ($shadow_users as $user) {
            $debug_steps[] = "- $user";
        }
    } catch (Exception $e) {
        $debug_steps[] = "ERROR reading shadow file: " . $e->getMessage();
    }
} else {
    $debug_steps[] = "Cannot read shadow file";
}

// Step 4: Test authentication class loading
$debug_steps[] = "\n=== STEP 4: AUTH CLASS TEST ===";
try {
    require_once 'qnap_auth_simple.php';
    $debug_steps[] = "✅ Auth class loaded successfully";
    
    $auth = new SimpleQNAPAuth();
    $debug_steps[] = "✅ Auth class instantiated";
    
    // Test getValidUsers
    try {
        $valid_users = $auth->getValidUsers();
        $debug_steps[] = "✅ getValidUsers() returned: " . implode(', ', $valid_users);
    } catch (Exception $e) {
        $debug_steps[] = "❌ getValidUsers() failed: " . $e->getMessage();
    }
    
} catch (Exception $e) {
    $debug_steps[] = "❌ Failed to load auth class: " . $e->getMessage();
}

// Step 5: Test specific user authentication
$debug_steps[] = "\n=== STEP 5: AUTHENTICATION TEST ===";
if (isset($auth)) {
    $test_users = array(
        array('admin', 'admin'),
        array('admin', 'password'),
        array('admin', ''),
        array('guest', 'guest'),
        array('nonexistent', 'test')
    );
    
    foreach ($test_users as $test_user) {
        $username = $test_user[0];
        $password = $test_user[1];
        
        $debug_steps[] = "\nTesting: $username / " . ($password ? str_repeat('*', strlen($password)) : '(empty)');
        
        try {
            // Test getUserInfo first
            $user_info = $auth->getUserInfo($username);
            if ($user_info) {
                $debug_steps[] = "- User exists in passwd: YES (UID: {$user_info['uid']})";
            } else {
                $debug_steps[] = "- User exists in passwd: NO";
            }
            
            // Test authentication
            $auth_result = $auth->authenticate($username, $password);
            $debug_steps[] = "- Authentication result: " . ($auth_result ? '✅ SUCCESS' : '❌ FAILED');
            
        } catch (Exception $e) {
            $debug_steps[] = "- Authentication error: " . $e->getMessage();
        }
    }
}

// Step 6: Test crypt function
$debug_steps[] = "\n=== STEP 6: CRYPT FUNCTION TEST ===";
$test_password = 'admin';
$test_hashes = array(
    '$1$salt$hash',  // MD5
    '$6$salt$hash',  // SHA-512
    'plaintext'      // Plain
);

foreach ($test_hashes as $hash) {
    try {
        $result = crypt($test_password, $hash);
        $debug_steps[] = "crypt('$test_password', '$hash') = " . substr($result, 0, 30) . "...";
    } catch (Exception $e) {
        $debug_steps[] = "crypt() error with '$hash': " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>QNAP Auth Debug</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        .debug { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; line-height: 1.4; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .nav { margin-bottom: 20px; }
        .nav a { display: inline-block; margin-right: 10px; padding: 8px 16px; background: #007cba; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="nav">
        <a href="simple_login.php">🔐 Working Login</a>
        <a href="qnap_login_simple.php">🐛 Broken Login</a>
        <a href="debug_login.php">🔍 Debug Login</a>
    </div>

    <div class="debug">
        <h2>🐛 QNAP Authentication Debug</h2>
        <pre><?php echo implode("\n", $debug_steps); ?></pre>
    </div>
    
    <div class="debug">
        <h3>🧪 Manual Test Form</h3>
        <p>Try authenticating with a specific user:</p>
        <form method="POST" action="">
            <p>
                Username: <input type="text" name="test_username" value="admin" style="padding: 5px;">
                Password: <input type="password" name="test_password" value="" style="padding: 5px;">
                <button type="submit" name="manual_test" style="padding: 5px 10px;">Test Auth</button>
            </p>
        </form>
        
        <?php
        if (isset($_POST['manual_test']) && isset($auth)) {
            $test_username = $_POST['test_username'];
            $test_password = $_POST['test_password'];
            
            echo "<h4>Manual Test Results:</h4>";
            echo "<pre>";
            echo "Username: " . htmlspecialchars($test_username) . "\n";
            echo "Password: " . str_repeat('*', strlen($test_password)) . "\n\n";
            
            try {
                $result = $auth->authenticate($test_username, $test_password);
                echo "Result: " . ($result ? "✅ SUCCESS" : "❌ FAILED") . "\n";
                
                if ($result) {
                    $user_info = $auth->getUserInfo($test_username);
                    echo "User Info: " . print_r($user_info, true);
                }
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage() . "\n";
            }
            echo "</pre>";
        }
        ?>
    </div>
</body>
</html> 