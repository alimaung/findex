<?php
// Simplified QNAP Authentication for PHP 5.6
session_start();

class SimpleQNAPAuth {
    private $shadow_file = '/etc/shadow';
    private $passwd_file = '/etc/passwd';
    
    /**
     * Authenticate user against QNAP system accounts
     */
    public function authenticate($username, $password) {
        // Security: Only allow alphanumeric usernames
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            return false;
        }
        
        // Try shadow file authentication first
        if ($this->authenticateWithShadow($username, $password)) {
            return true;
        }
        
        // Fallback: try simple user check (for testing)
        return $this->authenticateSimple($username, $password);
    }
    
    /**
     * Authenticate using shadow file
     */
    private function authenticateWithShadow($username, $password) {
        if (!is_readable($this->shadow_file) || !is_readable($this->passwd_file)) {
            return false;
        }
        
        try {
            // Get user info from passwd file
            $passwd_content = file_get_contents($this->passwd_file);
            if (!preg_match("/^{$username}:/m", $passwd_content)) {
                return false; // User doesn't exist
            }
            
            // Get password hash from shadow file
            $shadow_content = file_get_contents($this->shadow_file);
            if (preg_match("/^{$username}:([^:]+):/m", $shadow_content, $matches)) {
                $stored_hash = $matches[1];
                
                // Skip disabled accounts
                if ($stored_hash === '*' || $stored_hash === '!' || empty($stored_hash)) {
                    return false;
                }
                
                // Verify password using crypt
                return crypt($password, $stored_hash) === $stored_hash;
            }
        } catch (Exception $e) {
            error_log("Shadow authentication failed: " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Simple authentication fallback
     */
    private function authenticateSimple($username, $password) {
        // For testing - you can customize this
        $simple_users = array(
            'admin' => 'admin',
            'user' => 'user'
        );
        
        if (isset($simple_users[$username]) && $simple_users[$username] === $password) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get user information from QNAP system
     */
    public function getUserInfo($username) {
        if (!is_readable($this->passwd_file)) {
            return null;
        }
        
        $passwd_content = file_get_contents($this->passwd_file);
        if (preg_match("/^{$username}:([^:]*):(\d+):(\d+):([^:]*):([^:]*):([^:]*)/m", $passwd_content, $matches)) {
            return array(
                'username' => $username,
                'uid' => $matches[2],
                'gid' => $matches[3],
                'gecos' => $matches[4],
                'home' => $matches[5],
                'shell' => $matches[6]
            );
        }
        
        return null;
    }
    
    /**
     * Check if user has admin privileges
     */
    public function isAdmin($username) {
        // Simple admin check
        if ($username === 'admin') {
            return true;
        }
        
        // Check UID (0 = root)
        $user_info = $this->getUserInfo($username);
        return $user_info && $user_info['uid'] === '0';
    }
    
    /**
     * Get list of valid system users
     */
    public function getValidUsers() {
        if (!is_readable($this->passwd_file)) {
            return array('admin', 'user'); // Fallback users
        }
        
        $users = array();
        $passwd_content = file_get_contents($this->passwd_file);
        $lines = explode("\n", $passwd_content);
        
        foreach ($lines as $line) {
            if (empty($line)) continue;
            
            $parts = explode(':', $line);
            if (count($parts) >= 7) {
                $username = $parts[0];
                $uid = intval($parts[2]);
                $shell = $parts[6];
                
                // Include users with UID >= 1000 or known QNAP users
                if (($uid >= 1000 && $uid < 65534) || 
                    in_array($username, array('admin', 'guest')) ||
                    strpos($shell, 'nologin') === false) {
                    $users[] = $username;
                }
            }
        }
        
        return array_unique($users);
    }
}

// Session timeout (30 minutes)
$session_timeout = 1800;

function checkQNAPAuth() {
    global $session_timeout;
    
    // Check if user is logged in
    if (!isset($_SESSION['qnap_logged_in']) || $_SESSION['qnap_logged_in'] !== true) {
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
    $redirect_url = 'qnap_login_simple.php';
    if (!empty($message)) {
        $redirect_url .= '?message=' . urlencode($message);
    }
    header('Location: ' . $redirect_url);
    exit;
}

function qnapLogout() {
    session_destroy();
    redirectToLogin('You have been logged out.');
}

function getQNAPUser() {
    if (isset($_SESSION['qnap_username'])) {
        return $_SESSION['qnap_username'];
    }
    return 'Unknown';
}

function isQNAPAdmin() {
    if (isset($_SESSION['qnap_is_admin'])) {
        return $_SESSION['qnap_is_admin'];
    }
    return false;
}

function getQNAPUserInfo() {
    if (isset($_SESSION['qnap_user_info'])) {
        return $_SESSION['qnap_user_info'];
    }
    return array();
}

// Auto-check authentication when this file is included
// (Skip auto-check if we're on the login page)
if (!defined('SKIP_AUTH_CHECK') && !checkQNAPAuth()) {
    exit;
}
?> 