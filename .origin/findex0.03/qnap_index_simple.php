<?php
// Simplified QNAP File Browser for PHP 5.6
session_start();

// Check authentication manually to avoid redirect issues
if (!isset($_SESSION['qnap_logged_in']) || $_SESSION['qnap_logged_in'] !== true) {
    header('Location: qnap_login_simple.php');
    exit;
}

// Update last activity
$_SESSION['last_activity'] = time();

// Get user info from session
$current_user = isset($_SESSION['qnap_username']) ? $_SESSION['qnap_username'] : 'Unknown';
$is_admin = isset($_SESSION['qnap_is_admin']) ? $_SESSION['qnap_is_admin'] : false;
$user_info = isset($_SESSION['qnap_user_info']) ? $_SESSION['qnap_user_info'] : array();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QNAP File Browser</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            line-height: 1.6;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-badge {
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .admin-badge {
            background: #ff6b6b;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .controls {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .controls-row {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-box {
            flex: 1;
            min-width: 200px;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .search-box:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .view-toggle {
            display: flex;
            background: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .view-btn {
            padding: 0.75rem 1rem;
            border: none;
            background: transparent;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .view-btn.active {
            background: #667eea;
            color: white;
        }
        
        .breadcrumb {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .breadcrumb-item {
            display: inline-block;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .breadcrumb-item:hover {
            text-decoration: underline;
        }
        
        .breadcrumb-separator {
            margin: 0 0.5rem;
            color: #999;
        }
        
        .file-browser {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .loading {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .error {
            text-align: center;
            padding: 3rem;
            color: #c33;
        }
        
        /* List View Styles */
        .list-view .file-item {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s ease;
        }
        
        .list-view .file-item:hover {
            background: #f8f9fa;
        }
        
        .list-view .file-item:last-child {
            border-bottom: none;
        }
        
        .file-icon {
            width: 24px;
            height: 24px;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        
        .file-name {
            flex: 1;
            font-weight: 500;
            color: #333;
            text-decoration: none;
        }
        
        .file-name:hover {
            color: #667eea;
        }
        
        .file-size {
            color: #666;
            font-size: 0.9rem;
            margin-left: 1rem;
            min-width: 80px;
            text-align: right;
        }
        
        .file-date {
            color: #666;
            font-size: 0.9rem;
            margin-left: 1rem;
            min-width: 120px;
        }
        
        /* Grid View Styles */
        .grid-view {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            padding: 1.5rem;
        }
        
        .grid-view .file-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.5rem;
            border: 2px solid #f0f0f0;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .grid-view .file-item:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .grid-view .file-icon {
            width: 48px;
            height: 48px;
            margin: 0 0 1rem 0;
        }
        
        .grid-view .file-name {
            text-align: center;
            font-size: 0.9rem;
            word-break: break-word;
        }
        
        .grid-view .file-size {
            margin: 0.5rem 0 0 0;
            font-size: 0.8rem;
        }
        
        .hidden {
            display: none;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .controls-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                min-width: auto;
            }
            
            .file-date {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🗂️ QNAP File Browser</h1>
            <div class="user-info">
                <div class="user-badge">
                    👤 <?php echo htmlspecialchars($current_user); ?>
                    <?php if ($is_admin): ?>
                        <span class="admin-badge">ADMIN</span>
                    <?php endif; ?>
                </div>
                <a href="qnap_logout_simple.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="controls">
            <div class="controls-row">
                <input type="text" class="search-box" placeholder="Search files and folders..." id="searchBox">
                <div class="view-toggle">
                    <button class="view-btn active" id="listViewBtn">📋 List</button>
                    <button class="view-btn" id="gridViewBtn">⊞ Grid</button>
                </div>
            </div>
        </div>
        
        <div class="breadcrumb" id="breadcrumb">
            <a href="#" class="breadcrumb-item" data-path="">🏠 Home</a>
        </div>
        
        <div class="file-browser">
            <div class="loading" id="loading">Loading files...</div>
            <div class="list-view" id="fileList"></div>
        </div>
    </div>
    
    <script>
        let currentPath = '';
        let currentView = 'list';
        let allFiles = [];
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadDirectory('');
            setupEventListeners();
        });
        
        function setupEventListeners() {
            // Search functionality
            document.getElementById('searchBox').addEventListener('input', function() {
                filterFiles(this.value);
            });
            
            // View toggle
            document.getElementById('listViewBtn').addEventListener('click', function() {
                switchView('list');
            });
            
            document.getElementById('gridViewBtn').addEventListener('click', function() {
                switchView('grid');
            });
        }
        
        function loadDirectory(path) {
            currentPath = path;
            document.getElementById('loading').style.display = 'block';
            document.getElementById('fileList').style.display = 'none';
            
            fetch('qnap_browse_simple.php?path=' + encodeURIComponent(path))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        allFiles = data.files;
                        displayFiles(allFiles);
                        updateBreadcrumb(path);
                    } else {
                        showError(data.error || 'Failed to load directory');
                    }
                })
                .catch(error => {
                    showError('Network error: ' + error.message);
                })
                .finally(() => {
                    document.getElementById('loading').style.display = 'none';
                });
        }
        
        function displayFiles(files) {
            const fileList = document.getElementById('fileList');
            fileList.innerHTML = '';
            fileList.style.display = 'block';
            
            if (files.length === 0) {
                fileList.innerHTML = '<div style="text-align: center; padding: 3rem; color: #666;">No files found</div>';
                return;
            }
            
            files.forEach(file => {
                const fileItem = createFileItem(file);
                fileList.appendChild(fileItem);
            });
        }
        
        function createFileItem(file) {
            const item = document.createElement(currentView === 'list' ? 'div' : 'a');
            item.className = 'file-item';
            
            if (file.type === 'directory') {
                item.style.cursor = 'pointer';
                item.addEventListener('click', function() {
                    const newPath = currentPath ? currentPath + '/' + file.name : file.name;
                    loadDirectory(newPath);
                });
            }
            
            const icon = getFileIcon(file);
            const iconElement = document.createElement('div');
            iconElement.className = 'file-icon';
            iconElement.innerHTML = icon;
            
            const nameElement = document.createElement('div');
            nameElement.className = 'file-name';
            nameElement.textContent = file.name;
            
            item.appendChild(iconElement);
            item.appendChild(nameElement);
            
            if (currentView === 'list') {
                const sizeElement = document.createElement('div');
                sizeElement.className = 'file-size';
                sizeElement.textContent = file.size || '';
                
                const dateElement = document.createElement('div');
                dateElement.className = 'file-date';
                dateElement.textContent = file.modified || '';
                
                item.appendChild(sizeElement);
                item.appendChild(dateElement);
            } else {
                if (file.size) {
                    const sizeElement = document.createElement('div');
                    sizeElement.className = 'file-size';
                    sizeElement.textContent = file.size;
                    item.appendChild(sizeElement);
                }
            }
            
            return item;
        }
        
        function getFileIcon(file) {
            if (file.type === 'directory') {
                return '📁';
            }
            
            const ext = file.name.split('.').pop().toLowerCase();
            const iconMap = {
                'txt': '📄', 'doc': '📄', 'docx': '📄', 'pdf': '📄',
                'jpg': '🖼️', 'jpeg': '🖼️', 'png': '🖼️', 'gif': '🖼️', 'bmp': '🖼️',
                'mp3': '🎵', 'wav': '🎵', 'flac': '🎵', 'aac': '🎵',
                'mp4': '🎬', 'avi': '🎬', 'mkv': '🎬', 'mov': '🎬',
                'zip': '📦', 'rar': '📦', '7z': '📦', 'tar': '📦',
                'exe': '⚙️', 'msi': '⚙️', 'deb': '⚙️', 'rpm': '⚙️'
            };
            
            return iconMap[ext] || '📄';
        }
        
        function updateBreadcrumb(path) {
            const breadcrumb = document.getElementById('breadcrumb');
            breadcrumb.innerHTML = '<a href="#" class="breadcrumb-item" data-path="">🏠 Home</a>';
            
            if (path) {
                const parts = path.split('/');
                let currentPath = '';
                
                parts.forEach(part => {
                    currentPath += (currentPath ? '/' : '') + part;
                    breadcrumb.innerHTML += '<span class="breadcrumb-separator">›</span>';
                    breadcrumb.innerHTML += '<a href="#" class="breadcrumb-item" data-path="' + currentPath + '">' + part + '</a>';
                });
            }
            
            // Add click handlers to breadcrumb items
            breadcrumb.querySelectorAll('.breadcrumb-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    loadDirectory(this.getAttribute('data-path'));
                });
            });
        }
        
        function switchView(view) {
            currentView = view;
            
            // Update button states
            document.getElementById('listViewBtn').classList.toggle('active', view === 'list');
            document.getElementById('gridViewBtn').classList.toggle('active', view === 'grid');
            
            // Update file list classes
            const fileList = document.getElementById('fileList');
            fileList.className = view + '-view';
            
            // Re-display files with new view
            displayFiles(allFiles);
        }
        
        function filterFiles(searchTerm) {
            if (!searchTerm) {
                displayFiles(allFiles);
                return;
            }
            
            const filtered = allFiles.filter(file => 
                file.name.toLowerCase().includes(searchTerm.toLowerCase())
            );
            displayFiles(filtered);
        }
        
        function showError(message) {
            const fileList = document.getElementById('fileList');
            fileList.innerHTML = '<div class="error">Error: ' + message + '</div>';
            fileList.style.display = 'block';
        }
    </script>
</body>
</html> 