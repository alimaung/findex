<?php
echo "<h1>PHP Test</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Current Time: " . date('Y-m-d H:i:s') . "</p>";

// Test basic functions
echo "<h2>Function Tests:</h2>";
echo "<p>file_get_contents: " . (function_exists('file_get_contents') ? '✅ Available' : '❌ Missing') . "</p>";
echo "<p>scandir: " . (function_exists('scandir') ? '✅ Available' : '❌ Missing') . "</p>";
echo "<p>session_start: " . (function_exists('session_start') ? '✅ Available' : '❌ Missing') . "</p>";

// Test file access
echo "<h2>File Access Tests:</h2>";
echo "<p>/etc/passwd readable: " . (is_readable('/etc/passwd') ? '✅ Yes' : '❌ No') . "</p>";
echo "<p>Current directory: " . getcwd() . "</p>";
echo "<p>Current directory readable: " . (is_readable('./') ? '✅ Yes' : '❌ No') . "</p>";

phpinfo();
?> 