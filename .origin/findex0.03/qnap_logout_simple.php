<?php
// Simplified QNAP Logout for PHP 5.6
session_start();

// Destroy the session
session_destroy();

// Redirect to login page with logout message
header('Location: qnap_login_simple.php?message=' . urlencode('You have been logged out successfully.'));
exit;
?> 