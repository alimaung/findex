<?php
// Simplified QNAP Browse Script for PHP 5.6
session_start();

// Check authentication
if (!isset($_SESSION['qnap_logged_in']) || $_SESSION['qnap_logged_in'] !== true) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'error' => 'Not authenticated'));
    exit;
}

// Update last activity
$_SESSION['last_activity'] = time();

// Get the requested path
$requested_path = isset($_GET['path']) ? $_GET['path'] : '';

// Security: Sanitize the path
$requested_path = str_replace(array('..', '\\'), '', $requested_path);
$requested_path = trim($requested_path, '/');

// Base directory (Web folder)
$base_dir = '/share/CACHEDEV1_DATA/Web';

// Construct full path
if (empty($requested_path)) {
    $full_path = $base_dir;
} else {
    $full_path = $base_dir . '/' . $requested_path;
}

// Security: Ensure path is within base directory
$real_base = realpath($base_dir);
$real_path = realpath($full_path);

if ($real_path === false || strpos($real_path, $real_base) !== 0) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'error' => 'Access denied'));
    exit;
}

// Check if directory exists and is readable
if (!is_dir($real_path) || !is_readable($real_path)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'error' => 'Directory not found or not accessible'));
    exit;
}

try {
    // Read directory contents
    $files = array();
    $items = scandir($real_path);
    
    if ($items === false) {
        throw new Exception('Failed to read directory');
    }
    
    foreach ($items as $item) {
        // Skip hidden files and current/parent directory references
        if ($item[0] === '.' && $item !== '..') {
            continue;
        }
        
        // Skip parent directory reference if we're at root
        if ($item === '..' && empty($requested_path)) {
            continue;
        }
        
        $item_path = $real_path . '/' . $item;
        
        // Get file info
        $file_info = array(
            'name' => $item,
            'type' => is_dir($item_path) ? 'directory' : 'file',
            'size' => '',
            'modified' => ''
        );
        
        // Get file size and modification time
        if (is_file($item_path)) {
            $size = filesize($item_path);
            if ($size !== false) {
                $file_info['size'] = formatFileSize($size);
            }
        }
        
        $mtime = filemtime($item_path);
        if ($mtime !== false) {
            $file_info['modified'] = date('Y-m-d H:i:s', $mtime);
        }
        
        $files[] = $file_info;
    }
    
    // Sort files: directories first, then by name
    usort($files, function($a, $b) {
        // Parent directory (..) always first
        if ($a['name'] === '..') return -1;
        if ($b['name'] === '..') return 1;
        
        // Directories before files
        if ($a['type'] !== $b['type']) {
            return $a['type'] === 'directory' ? -1 : 1;
        }
        
        // Then sort by name
        return strcasecmp($a['name'], $b['name']);
    });
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode(array(
        'success' => true,
        'path' => $requested_path,
        'files' => $files
    ));
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'error' => 'Server error: ' . $e->getMessage()));
}

/**
 * Format file size in human readable format
 */
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 B';
    
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $i = floor(log($bytes) / log(1024));
    
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}
?> 