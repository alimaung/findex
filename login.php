<?php
session_start();

// Configuration - Change these credentials!
$VALID_USERS = [
    'admin' => 'your_secure_password_here',
    'user' => 'another_password_here'
];

// Check if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error_message = '';
$success_message = '';
$login_attempts = isset($_SESSION['login_attempts']) ? $_SESSION['login_attempts'] : 0;
$last_attempt = isset($_SESSION['last_attempt']) ? $_SESSION['last_attempt'] : 0;

// Check for messages from logout or other redirects
if (isset($_GET['message'])) {
    $success_message = htmlspecialchars($_GET['message']);
}

// Rate limiting - 5 attempts per 15 minutes
if ($login_attempts >= 5 && (time() - $last_attempt) < 900) {
    $remaining_time = 900 - (time() - $last_attempt);
    $error_message = "Too many failed attempts. Please wait " . ceil($remaining_time / 60) . " minutes.";
}

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error_message)) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (isset($VALID_USERS[$username]) && $VALID_USERS[$username] === $password) {
        // Successful login
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['login_time'] = time();
        unset($_SESSION['login_attempts']);
        unset($_SESSION['last_attempt']);
        
        header('Location: index.php');
        exit;
    } else {
        // Failed login
        $_SESSION['login_attempts'] = $login_attempts + 1;
        $_SESSION['last_attempt'] = time();
        $error_message = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QNAP TS-251+ Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .login-header h1 {
            font-size: 2em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .login-header p {
            opacity: 0.9;
            font-size: 1.1em;
        }

        .login-form {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .error-message {
            background: #e74c3c;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            animation: shake 0.5s ease-in-out;
        }

        .success-message {
            background: #27ae60;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            animation: slideDown 0.5s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .security-info {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-size: 14px;
            color: #6c757d;
        }

        .security-info h4 {
            color: #495057;
            margin-bottom: 8px;
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #7f8c8d;
            font-size: 18px;
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 10px;
            }
            
            .login-form {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🔐 QNAP TS-251+</h1>
            <p>Secure File Browser Access</p>
        </div>
        
        <div class="login-form">
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">👤 Username</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                           autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="password">🔑 Password</label>
                    <div class="password-toggle">
                        <input type="password" id="password" name="password" required 
                               autocomplete="current-password">
                        <button type="button" class="password-toggle-btn" onclick="togglePassword()">👁️</button>
                    </div>
                </div>
                
                <button type="submit" class="login-btn">
                    🚀 Access File Browser
                </button>
            </form>
            
            <div class="security-info">
                <h4>🛡️ Security Features</h4>
                <ul style="margin-left: 20px; margin-top: 8px;">
                    <li>Session-based authentication</li>
                    <li>Rate limiting (5 attempts per 15 minutes)</li>
                    <li>Secure password handling</li>
                    <li>Auto-logout after inactivity</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.password-toggle-btn');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }

        // Auto-focus username field
        document.getElementById('username').focus();
        
        // Enter key handling
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.querySelector('.login-btn').click();
            }
        });
    </script>
</body>
</html> 