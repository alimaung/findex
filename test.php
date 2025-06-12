<?php
// Simple PHP test for QNAP TS-251+ File Browser
echo "<h1>🧪 QNAP PHP Test</h1>";
echo "<h2>PHP Configuration Test</h2>";

// Test basic PHP functionality
echo "<p>✅ PHP is working!</p>";
echo "<p>📅 Current time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>🌏 Timezone: " . date_default_timezone_get() . "</p>";

// Test directory reading capability
echo "<h2>Directory Reading Test</h2>";
$testDir = './';
if (is_readable($testDir)) {
    echo "<p>✅ Directory reading: WORKING</p>";
    $files = scandir($testDir);
    echo "<p>📁 Files found: " . (count($files) - 2) . " items</p>";
    
    echo "<h3>Sample Files:</h3><ul>";
    $count = 0;
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && $count < 5) {
            $icon = is_dir($file) ? '📁' : '📄';
            echo "<li>$icon $file</li>";
            $count++;
        }
    }
    echo "</ul>";
} else {
    echo "<p>❌ Directory reading: FAILED</p>";
}

// Test JSON encoding
echo "<h2>JSON Test</h2>";
$testData = ['test' => 'success', 'files' => ['file1.txt', 'file2.txt']];
$json = json_encode($testData);
if ($json) {
    echo "<p>✅ JSON encoding: WORKING</p>";
    echo "<p>📝 Sample JSON: <code>$json</code></p>";
} else {
    echo "<p>❌ JSON encoding: FAILED</p>";
}

// Test file functions
echo "<h2>File Functions Test</h2>";
echo "<p>📊 filesize() function: " . (function_exists('filesize') ? '✅ Available' : '❌ Missing') . "</p>";
echo "<p>📅 filemtime() function: " . (function_exists('filemtime') ? '✅ Available' : '❌ Missing') . "</p>";
echo "<p>🔍 mime_content_type() function: " . (function_exists('mime_content_type') ? '✅ Available' : '❌ Missing') . "</p>";
echo "<p>📁 realpath() function: " . (function_exists('realpath') ? '✅ Available' : '❌ Missing') . "</p>";

// System info
echo "<h2>System Information</h2>";
echo "<p>🐘 PHP Version: " . phpversion() . "</p>";
echo "<p>💾 Memory Limit: " . ini_get('memory_limit') . "</p>";
echo "<p>⏱️ Max Execution Time: " . ini_get('max_execution_time') . " seconds</p>";
echo "<p>📂 Current Directory: " . getcwd() . "</p>";

echo "<hr>";
echo "<p><strong>🎯 Next Step:</strong> If all tests show ✅, your file browser should work perfectly!</p>";
echo "<p><a href='index.html'>🚀 Launch File Browser</a></p>";
?> 