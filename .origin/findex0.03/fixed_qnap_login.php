<?php
// Fixed QNAP Login for PHP 5.6
session_start();

// Skip auth check for login page
define('SKIP_AUTH_CHECK', true);

// Include the simplified auth class
require_once 'qnap_auth_simple.php';

$auth = new SimpleQNAPAuth();
$error_message = '';
$success_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        // Attempt authentication
        try {
            if ($auth->authenticate($username, $password)) {
                // Login successful - clear any existing session first
                session_regenerate_id(true);
                
                $_SESSION['qnap_logged_in'] = true;
                $_SESSION['qnap_username'] = $username;
                $_SESSION['last_activity'] = time();
                
                // Get user info
                $user_info = $auth->getUserInfo($username);
                if ($user_info) {
                    $_SESSION['qnap_user_info'] = $user_info;
                    $_SESSION['qnap_is_admin'] = $auth->isAdmin($username);
                } else {
                    $_SESSION['qnap_user_info'] = array('username' => $username);
                    $_SESSION['qnap_is_admin'] = ($username === 'admin');
                }
                
                // Force redirect immediately
                header('Location: qnap_index_simple.php');
                echo '<script>window.location.href = "qnap_index_simple.php";</script>';
                echo '<meta http-equiv="refresh" content="0;url=qnap_index_simple.php">';
                exit();
            } else {
                $error_message = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            $error_message = 'Authentication error: ' . $e->getMessage();
        }
    }
}

// Handle messages from URL
if (isset($_GET['message'])) {
    $success_message = htmlspecialchars($_GET['message']);
}

// Get available users for display
try {
    $available_users = $auth->getValidUsers();
} catch (Exception $e) {
    $available_users = array('admin', 'guest');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QNAP File Browser - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
        }
        
        .success-message {
            background: #efe;
            color: #363;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #363;
        }
        
        .users-info {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .users-info h3 {
            color: #333;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .user-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .user-tag {
            background: #f0f0f0;
            color: #666;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .user-tag:hover {
            background: #e0e0e0;
        }
        
        .system-info {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            font-size: 12px;
            color: #666;
        }
        
        .debug-link {
            margin-top: 15px;
            text-align: center;
        }
        
        .debug-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🗂️ File Browser</h1>
            <p>QNAP NAS Login (Fixed)</p>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="success-message">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
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
            
            <button type="submit" class="login-btn">Login</button>
        </form>
        
        <?php if (!empty($available_users)): ?>
            <div class="users-info">
                <h3>Available Users:</h3>
                <div class="user-list">
                    <?php foreach ($available_users as $user): ?>
                        <span class="user-tag" onclick="document.getElementById('username').value='<?php echo htmlspecialchars($user); ?>'"><?php echo htmlspecialchars($user); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="system-info">
            <strong>System Info:</strong><br>
            PHP Version: <?php echo PHP_VERSION; ?><br>
            Server Time: <?php echo date('Y-m-d H:i:s'); ?><br>
            <br>
            <strong>Your QNAP Users:</strong> admin, guest, alibae, bilalbabo
        </div>
        
        <div class="debug-link">
            <a href="debug_qnap_auth.php">🐛 Debug Authentication</a>
        </div>
    </div>
</body>
</html> 