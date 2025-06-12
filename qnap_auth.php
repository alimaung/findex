<?php
// QNAP NAS Authentication System
session_start();

class QNAPAuth {
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
        
        // Try multiple authentication methods
        return $this->authenticateWithPAM($username, $password) || 
               $this->authenticateWithShadow($username, $password) ||
               $this->authenticateWithQNAPAPI($username, $password);
    }
    
    /**
     * Authenticate using PAM (Pluggable Authentication Modules)
     */
    private function authenticateWithPAM($username, $password) {
        // Check if PAM extension is available
        if (!function_exists('pam_auth')) {
            return false;
        }
        
        try {
            return pam_auth($username, $password);
        } catch (Exception $e) {
            error_log("PAM authentication failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Authenticate using shadow file (if accessible)
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
     * Authenticate using QNAP's web API
     */
    private function authenticateWithQNAPAPI($username, $password) {
        // QNAP's authentication endpoint
        $auth_url = 'http://127.0.0.1:8080/cgi-bin/authLogin.cgi';
        
        $post_data = http_build_query([
            'user' => $username,
            'pwd' => base64_encode($password),
            'serviceKey' => '1'
        ]);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $post_data,
                'timeout' => 10
            ]
        ]);
        
        try {
            $response = @file_get_contents($auth_url, false, $context);
            if ($response !== false) {
                // Parse XML response
                $xml = @simplexml_load_string($response);
                if ($xml && isset($xml->authPassed)) {
                    return (string)$xml->authPassed === '1';
                }
            }
        } catch (Exception $e) {
            error_log("QNAP API authentication failed: " . $e->getMessage());
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
            return [
                'username' => $username,
                'uid' => $matches[2],
                'gid' => $matches[3],
                'gecos' => $matches[4],
                'home' => $matches[5],
                'shell' => $matches[6]
            ];
        }
        
        return null;
    }
    
    /**
     * Check if user has admin privileges
     */
    public function isAdmin($username) {
        // Check if user is in admin group
        $groups_output = shell_exec("groups " . escapeshellarg($username) . " 2>/dev/null");
        if ($groups_output) {
            return strpos($groups_output, 'admin') !== false || 
                   strpos($groups_output, 'administrators') !== false ||
                   strpos($groups_output, 'wheel') !== false;
        }
        
        // Fallback: check UID (0 = root)
        $user_info = $this->getUserInfo($username);
        return $user_info && $user_info['uid'] === '0';
    }
    
    /**
     * Get list of valid system users (excluding system accounts)
     */
    public function getValidUsers() {
        if (!is_readable($this->passwd_file)) {
            return [];
        }
        
        $users = [];
        $passwd_content = file_get_contents($this->passwd_file);
        $lines = explode("\n", $passwd_content);
        
        foreach ($lines as $line) {
            if (empty($line)) continue;
            
            $parts = explode(':', $line);
            if (count($parts) >= 7) {
                $username = $parts[0];
                $uid = intval($parts[2]);
                $shell = $parts[6];
                
                // Include users with UID >= 1000 (regular users) or known QNAP users
                if (($uid >= 1000 && $uid < 65534) || 
                    in_array($username, ['admin', 'guest']) ||
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
    $redirect_url = 'qnap_login.php';
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
    return $_SESSION['qnap_username'] ?? 'Unknown';
}

function isQNAPAdmin() {
    return $_SESSION['qnap_is_admin'] ?? false;
}

function getQNAPUserInfo() {
    return $_SESSION['qnap_user_info'] ?? [];
}

// Auto-check authentication when this file is included
if (!checkQNAPAuth()) {
    exit;
}
?> 