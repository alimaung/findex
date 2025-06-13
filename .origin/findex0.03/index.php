<?php
// Start session
session_start();

// Check if user is already logged in
if (isset($_SESSION['qnap_logged_in']) && $_SESSION['qnap_logged_in'] === true) {
    // User is logged in, redirect to file browser
    header('Location: qnap_index_simple.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QNAP File Browser - Home</title>
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
            color: #333;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 800px;
            text-align: center;
        }
        
        .logo {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 16px;
        }
        
        p {
            color: #666;
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 40px 0;
        }
        
        .feature {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: left;
        }
        
        .feature h3 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .feature p {
            font-size: 14px;
            margin-bottom: 0;
        }
        
        .login-btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            transition: transform 0.2s ease;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
        }
        
        .footer {
            margin-top: 40px;
            color: #666;
            font-size: 14px;
        }
        
        @media (max-width: 600px) {
            .container {
                padding: 20px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            p {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🗂️</div>
        <h1>Welcome to QNAP File Browser</h1>
        <p>Your secure and easy-to-use file management solution for QNAP NAS.</p>
        
        <div class="features">
            <div class="feature">
                <h3>🔒 Secure Access</h3>
                <p>Protected by QNAP's robust authentication system.</p>
            </div>
            <div class="feature">
                <h3>📁 Easy Navigation</h3>
                <p>Intuitive interface for browsing and managing files.</p>
            </div>
            <div class="feature">
                <h3>⚡ Fast Performance</h3>
                <p>Optimized for quick file operations and transfers.</p>
            </div>
        </div>
        
        <a href="fixed_qnap_login.php" class="login-btn">Login to File Browser</a>
        
        <div class="footer">
            <p>QNAP File Browser v1.0 | Running on PHP 5.6+</p>
        </div>
    </div>
    
    <script>
        // Auto-redirect after 5 seconds
        setTimeout(function() {
            window.location.href = 'fixed_qnap_login.php';
        }, 5000);
    </script>
</body>
</html> 