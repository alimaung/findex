<?php
session_start();

$error = '';

// Handle login
if ($_POST) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Simple authentication - just check admin/admin for now
    if ($username === 'admin' && $password === 'admin') {
        $_SESSION['qnap_logged_in'] = true;
        $_SESSION['qnap_username'] = 'admin';
        $_SESSION['qnap_is_admin'] = true;
        $_SESSION['last_activity'] = time();
        $_SESSION['qnap_user_info'] = array('username' => 'admin');
        
        // Force redirect with JavaScript as backup
        echo '<script>window.location.href = "qnap_index_simple.php";</script>';
        header('Location: qnap_index_simple.php');
        exit();
    } else {
        $error = 'Invalid login';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Login</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 100px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .error { color: red; margin-bottom: 15px; }
    </style>
</head>
<body>
    <h2>🔐 Simple Login</h2>
    
    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Username:</label>
            <input type="text" name="username" required>
        </div>
        
        <div class="form-group">
            <label>Password:</label>
            <input type="password" name="password" required>
        </div>
        
        <button type="submit">Login</button>
    </form>
    
    <p><strong>Test:</strong> admin / admin</p>
</body>
</html> 