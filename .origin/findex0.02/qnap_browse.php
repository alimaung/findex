<?php
// Require QNAP authentication
require_once 'qnap_auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Security: Define allowed base paths
$allowedPaths = [
    '/share/Web',
    '/Web',
    './Web',
    '../Web'
];

// Get the requested path from query parameter
$requestedPath = isset($_GET['path']) ? $_GET['path'] : '/Web';

// Sanitize the path
$requestedPath = str_replace(['../', './'], '', $requestedPath);
$requestedPath = rtrim($requestedPath, '/');

// If path is just '/Web' or empty, use current directory
if ($requestedPath === '/Web' || $requestedPath === '') {
    $actualPath = './';
} else {
    // Remove /Web prefix if present and use relative path
    $actualPath = './' . ltrim(str_replace('/Web', '', $requestedPath), '/');
}

// Security check: ensure we're not going outside allowed directories
$realPath = realpath($actualPath);
$currentDir = realpath('./');

if (!$realPath || strpos($realPath, $currentDir) !== 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Check if directory exists and is readable
if (!is_dir($actualPath) || !is_readable($actualPath)) {
    http_response_code(404);
    echo json_encode(['error' => 'Directory not found or not readable']);
    exit;
}

try {
    $files = [];
    $items = scandir($actualPath);
    
    if ($items === false) {
        throw new Exception('Unable to read directory');
    }
    
    // Get current user info for permission checking
    $currentUser = getQNAPUser();
    $isAdmin = isQNAPAdmin();
    
    foreach ($items as $item) {
        // Skip hidden files and current/parent directory references
        if ($item === '.' || $item === '..' || $item[0] === '.') {
            continue;
        }
        
        $itemPath = $actualPath . '/' . $item;
        
        if (!is_readable($itemPath)) {
            continue;
        }
        
        $isFolder = is_dir($itemPath);
        $fileInfo = [
            'name' => $item,
            'isFolder' => $isFolder,
            'size' => $isFolder ? 0 : filesize($itemPath),
            'modified' => date('Y-m-d H:i:s', filemtime($itemPath)),
            'permissions' => substr(sprintf('%o', fileperms($itemPath)), -4),
            'owner' => function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($itemPath))['name'] ?? 'unknown' : 'unknown'
        ];
        
        // Add MIME type for files
        if (!$isFolder) {
            $fileInfo['mimeType'] = mime_content_type($itemPath) ?: 'application/octet-stream';
        }
        
        // Add user-specific information
        $fileInfo['readable'] = is_readable($itemPath);
        $fileInfo['writable'] = is_writable($itemPath);
        
        $files[] = $fileInfo;
    }
    
    // Sort: folders first, then alphabetically
    usort($files, function($a, $b) {
        if ($a['isFolder'] && !$b['isFolder']) return -1;
        if (!$a['isFolder'] && $b['isFolder']) return 1;
        return strcasecmp($a['name'], $b['name']);
    });
    
    echo json_encode([
        'success' => true,
        'path' => $requestedPath,
        'files' => $files,
        'count' => count($files),
        'user' => $currentUser,
        'isAdmin' => $isAdmin,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?> 