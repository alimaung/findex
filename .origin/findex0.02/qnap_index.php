<?php
// Require QNAP authentication
require_once 'qnap_auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QNAP TS-251+ File Index</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .user-info {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.1);
            padding: 10px 15px;
            border-radius: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-badge {
            background: #f39c12;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: bold;
        }

        .logout-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 15px;
            cursor: pointer;
            font-size: 12px;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        .breadcrumb {
            background: #ecf0f1;
            padding: 15px 30px;
            border-bottom: 1px solid #bdc3c7;
            font-family: 'Courier New', monospace;
        }

        .breadcrumb-item {
            display: inline-block;
            color: #3498db;
            text-decoration: none;
            margin-right: 5px;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .breadcrumb-item:hover {
            background-color: #d5dbdb;
        }

        .breadcrumb-item.current {
            color: #2c3e50;
            font-weight: bold;
        }

        .toolbar {
            background: #f8f9fa;
            padding: 15px 30px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .view-toggle {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        .search-box {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            width: 250px;
        }

        .file-list {
            padding: 30px;
        }

        .file-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
            transition: background-color 0.3s;
            cursor: pointer;
        }

        .file-item:hover {
            background-color: #f8f9fa;
        }

        .file-item:last-child {
            border-bottom: none;
        }

        .file-icon {
            width: 40px;
            height: 40px;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 20px;
        }

        .folder-icon {
            background: #f39c12;
            color: white;
        }

        .file-icon-default {
            background: #95a5a6;
            color: white;
        }

        .file-icon-image {
            background: #e74c3c;
            color: white;
        }

        .file-icon-video {
            background: #9b59b6;
            color: white;
        }

        .file-icon-audio {
            background: #1abc9c;
            color: white;
        }

        .file-icon-document {
            background: #3498db;
            color: white;
        }

        .file-icon-archive {
            background: #e67e22;
            color: white;
        }

        .file-info {
            flex: 1;
        }

        .file-name {
            font-size: 16px;
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .file-details {
            font-size: 12px;
            color: #7f8c8d;
        }

        .file-size {
            font-size: 14px;
            color: #95a5a6;
            min-width: 80px;
            text-align: right;
        }

        .grid-view {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            padding: 30px;
        }

        .grid-item {
            background: white;
            border: 1px solid #ecf0f1;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }

        .grid-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .grid-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 30px;
        }

        .loading {
            text-align: center;
            padding: 50px;
            color: #7f8c8d;
        }

        .error {
            text-align: center;
            padding: 50px;
            color: #e74c3c;
        }

        .hidden {
            display: none;
        }

        .info-panel {
            background: #e8f5e8;
            border: 1px solid #c3e6cb;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 30px;
            color: #155724;
        }

        .info-panel h3 {
            margin-bottom: 10px;
            color: #0f5132;
        }

        .info-panel code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }

        @media (max-width: 768px) {
            .toolbar {
                flex-direction: column;
                gap: 15px;
            }

            .search-box {
                width: 100%;
            }

            .grid-view {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 15px;
                padding: 20px;
            }

            .user-info {
                position: static;
                margin-top: 15px;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="user-info">
                👤 <?php echo htmlspecialchars(getQNAPUser()); ?>
                <?php if (isQNAPAdmin()): ?>
                    <span class="admin-badge">ADMIN</span>
                <?php endif; ?>
                <button class="logout-btn" onclick="logout()">🚪 Logout</button>
            </div>
            <h1>📁 QNAP TS-251+ File Index</h1>
            <p>Authenticated NAS Directory Browser</p>
        </div>

        <div class="breadcrumb" id="breadcrumb">
            <span class="breadcrumb-item current" onclick="navigateToPath('/')">🏠 /Web</span>
        </div>

        <div class="toolbar">
            <div class="view-toggle">
                <button class="btn btn-primary" id="listViewBtn" onclick="setView('list')">📋 List View</button>
                <button class="btn btn-secondary" id="gridViewBtn" onclick="setView('grid')">🔲 Grid View</button>
            </div>
            <input type="text" class="search-box" id="searchBox" placeholder="Search files and folders..." onkeyup="filterFiles()">
        </div>

        <div class="info-panel" id="infoPanel">
            <h3>🔐 QNAP Authenticated Browser</h3>
            <p>Welcome <strong><?php echo htmlspecialchars(getQNAPUser()); ?></strong>! You are securely connected using your QNAP account. 
            <?php if (isQNAPAdmin()): ?>
                As an administrator, you have full access to the directory structure.
            <?php else: ?>
                Browse through the available directories with your user permissions.
            <?php endif; ?>
            </p>
        </div>

        <div id="fileContainer">
            <div class="loading" id="loading">
                <p>🔄 Loading directory contents...</p>
            </div>
            
            <div class="file-list hidden" id="listView">
                <!-- Files will be populated here -->
            </div>
            
            <div class="grid-view hidden" id="gridView">
                <!-- Files will be populated here -->
            </div>
            
            <div class="error hidden" id="errorView">
                <p>❌ Unable to load directory contents</p>
                <p>This file browser requires server-side support to read directory contents.</p>
            </div>
        </div>
    </div>

    <script>
        let currentPath = '/Web';
        let currentView = 'list';
        let allFiles = [];

        const fileIcons = {
            folder: '📁', image: '🖼️', video: '🎬', audio: '🎵',
            document: '📄', pdf: '📕', archive: '📦', code: '💻', default: '📄'
        };

        const fileTypes = {
            'jpg': 'image', 'jpeg': 'image', 'png': 'image', 'gif': 'image', 'bmp': 'image', 'svg': 'image', 'webp': 'image',
            'mp4': 'video', 'avi': 'video', 'mkv': 'video', 'mov': 'video', 'wmv': 'video', 'flv': 'video', 'webm': 'video',
            'mp3': 'audio', 'wav': 'audio', 'flac': 'audio', 'aac': 'audio', 'ogg': 'audio', 'wma': 'audio',
            'txt': 'document', 'doc': 'document', 'docx': 'document', 'rtf': 'document', 'pdf': 'pdf',
            'zip': 'archive', 'rar': 'archive', '7z': 'archive', 'tar': 'archive', 'gz': 'archive',
            'html': 'code', 'css': 'code', 'js': 'code', 'php': 'code', 'py': 'code', 'java': 'code', 'cpp': 'code', 'c': 'code'
        };

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'qnap_logout.php';
            }
        }

        function getFileType(filename) {
            const ext = filename.split('.').pop().toLowerCase();
            return fileTypes[ext] || 'default';
        }

        function getFileIcon(filename, isFolder = false) {
            if (isFolder) return fileIcons.folder;
            const type = getFileType(filename);
            return fileIcons[type] || fileIcons.default;
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function updateBreadcrumb(path) {
            const breadcrumb = document.getElementById('breadcrumb');
            const parts = path.split('/').filter(part => part !== '');
            
            let html = '<span class="breadcrumb-item" onclick="navigateToPath(\'/Web\')">🏠 /Web</span>';
            let currentPath = '/Web';
            
            for (let i = 0; i < parts.length; i++) {
                if (parts[i] !== 'Web') {
                    currentPath += '/' + parts[i];
                    const isLast = i === parts.length - 1;
                    html += ` <span style="color: #bdc3c7;">></span> `;
                    html += `<span class="breadcrumb-item ${isLast ? 'current' : ''}" onclick="navigateToPath('${currentPath}')">${parts[i]}</span>`;
                }
            }
            
            breadcrumb.innerHTML = html;
        }

        function setView(view) {
            currentView = view;
            const listView = document.getElementById('listView');
            const gridView = document.getElementById('gridView');
            const listBtn = document.getElementById('listViewBtn');
            const gridBtn = document.getElementById('gridViewBtn');

            if (view === 'list') {
                listView.classList.remove('hidden');
                gridView.classList.add('hidden');
                listBtn.classList.remove('btn-secondary');
                listBtn.classList.add('btn-primary');
                gridBtn.classList.remove('btn-primary');
                gridBtn.classList.add('btn-secondary');
            } else {
                listView.classList.add('hidden');
                gridView.classList.remove('hidden');
                gridBtn.classList.remove('btn-secondary');
                gridBtn.classList.add('btn-primary');
                listBtn.classList.remove('btn-primary');
                listBtn.classList.add('btn-secondary');
            }
        }

        function renderFiles(files) {
            const listView = document.getElementById('listView');
            const gridView = document.getElementById('gridView');
            
            listView.innerHTML = '';
            gridView.innerHTML = '';

            files.sort((a, b) => {
                if (a.isFolder && !b.isFolder) return -1;
                if (!a.isFolder && b.isFolder) return 1;
                return a.name.localeCompare(b.name);
            });

            files.forEach(file => {
                const listItem = document.createElement('div');
                listItem.className = 'file-item';
                listItem.onclick = () => {
                    if (file.isFolder) {
                        navigateToPath(currentPath + '/' + file.name);
                    } else {
                        window.open(currentPath + '/' + file.name, '_blank');
                    }
                };

                const fileType = file.isFolder ? 'folder' : getFileType(file.name);
                const iconClass = file.isFolder ? 'folder-icon' : `file-icon-${fileType}`;

                listItem.innerHTML = `
                    <div class="file-icon ${iconClass}">
                        ${getFileIcon(file.name, file.isFolder)}
                    </div>
                    <div class="file-info">
                        <div class="file-name">${file.name}</div>
                        <div class="file-details">
                            ${file.isFolder ? 'Folder' : 'File'} • Modified: ${file.modified || 'Unknown'}
                        </div>
                    </div>
                    <div class="file-size">
                        ${file.isFolder ? '' : formatFileSize(file.size || 0)}
                    </div>
                `;
                listView.appendChild(listItem);

                const gridItem = document.createElement('div');
                gridItem.className = 'grid-item';
                gridItem.onclick = listItem.onclick;

                gridItem.innerHTML = `
                    <div class="grid-icon ${iconClass}">
                        ${getFileIcon(file.name, file.isFolder)}
                    </div>
                    <div class="file-name">${file.name}</div>
                    <div class="file-details">
                        ${file.isFolder ? 'Folder' : formatFileSize(file.size || 0)}
                    </div>
                `;
                gridView.appendChild(gridItem);
            });
        }

        function filterFiles() {
            const searchTerm = document.getElementById('searchBox').value.toLowerCase();
            const filteredFiles = allFiles.filter(file => 
                file.name.toLowerCase().includes(searchTerm)
            );
            renderFiles(filteredFiles);
        }

        function navigateToPath(path) {
            currentPath = path;
            updateBreadcrumb(path);
            loadDirectory(path);
        }

        function loadDirectory(path) {
            const loading = document.getElementById('loading');
            const listView = document.getElementById('listView');
            const gridView = document.getElementById('gridView');
            const errorView = document.getElementById('errorView');
            const infoPanel = document.getElementById('infoPanel');

            loading.classList.remove('hidden');
            listView.classList.add('hidden');
            gridView.classList.add('hidden');
            errorView.classList.add('hidden');

            fetch(`qnap_browse.php?path=${encodeURIComponent(path)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    loading.classList.add('hidden');
                    
                    if (data.success) {
                        allFiles = data.files;
                        renderFiles(data.files);
                        setView(currentView);
                        
                        infoPanel.innerHTML = `
                            <h3>📂 ${path}</h3>
                            <p>Showing ${data.count} items. Authenticated as <strong><?php echo htmlspecialchars(getQNAPUser()); ?></strong>
                            <?php if (isQNAPAdmin()): ?> (Administrator)<?php endif; ?></p>
                        `;
                    } else {
                        throw new Error(data.error || 'Unknown error occurred');
                    }
                })
                .catch(error => {
                    loading.classList.add('hidden');
                    errorView.classList.remove('hidden');
                    
                    if (error.message.includes('401') || error.message.includes('403')) {
                        window.location.href = 'qnap_login.php?message=' + encodeURIComponent('Session expired. Please login again.');
                        return;
                    }
                    
                    errorView.innerHTML = `
                        <p>❌ Unable to load directory contents</p>
                        <p>Error: ${error.message}</p>
                    `;
                    
                    console.error('Error loading directory:', error);
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateBreadcrumb(currentPath);
            loadDirectory(currentPath);
        });

        window.addEventListener('popstate', function(event) {
            if (event.state && event.state.path) {
                navigateToPath(event.state.path);
            }
        });

        let inactivityTimer;
        function resetInactivityTimer() {
            clearTimeout(inactivityTimer);
            inactivityTimer = setTimeout(() => {
                if (confirm('You have been inactive for a while. Do you want to stay logged in?')) {
                    resetInactivityTimer();
                } else {
                    window.location.href = 'qnap_logout.php';
                }
            }, 25 * 60 * 1000);
        }

        document.addEventListener('click', resetInactivityTimer);
        document.addEventListener('keypress', resetInactivityTimer);
        resetInactivityTimer();
    </script>
</body>
</html> 