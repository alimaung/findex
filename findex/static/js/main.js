// FINDEX Content Service Platform - Main JavaScript

// Global Variables
const FINDEX = {
    apiEndpoints: {
        documentStats: '/api/document-stats/',
        batchEdit: '/api/batch-edit/',
    },
    settings: {
        animationDuration: 300,
        toastDuration: 5000,
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeComponents();
    setupEventListeners();
    loadDynamicContent();
});

// Initialize Components
function initializeComponents() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Initialize date pickers
    initializeDatePickers();

    // Initialize file upload zones
    initializeFileUpload();

    // Initialize search functionality
    initializeSearch();
}

// Setup Event Listeners
function setupEventListeners() {
    // Global form validation
    setupFormValidation();

    // Document actions
    setupDocumentActions();

    // Batch operations
    setupBatchOperations();

    // Modal handlers
    setupModalHandlers();

    // Keyboard shortcuts
    setupKeyboardShortcuts();
}

// Date Picker Initialization
function initializeDatePickers() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        // Set max date to today for output dates
        if (input.name === 'output_date') {
            input.max = new Date().toISOString().split('T')[0];
        }
    });
}

// File Upload Functionality
function initializeFileUpload() {
    const uploadZones = document.querySelectorAll('.upload-zone, .file-drop-zone');
    
    uploadZones.forEach(zone => {
        // Drag and drop handlers
        zone.addEventListener('dragover', handleDragOver);
        zone.addEventListener('dragleave', handleDragLeave);
        zone.addEventListener('drop', handleFileDrop);
        
        // Click to upload
        zone.addEventListener('click', function() {
            const fileInput = zone.querySelector('input[type="file"]') || 
                            document.querySelector('input[type="file"]');
            if (fileInput) {
                fileInput.click();
            }
        });
    });
}

function handleDragOver(e) {
    e.preventDefault();
    e.currentTarget.classList.add('dragover');
}

function handleDragLeave(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('dragover');
}

function handleFileDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        const fileInput = e.currentTarget.querySelector('input[type="file"]') ||
                         document.querySelector('input[type="file"]');
        if (fileInput) {
            fileInput.files = files;
            // Trigger change event
            const event = new Event('change', { bubbles: true });
            fileInput.dispatchEvent(event);
        }
    }
}

// Search Functionality
function initializeSearch() {
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.querySelector('input[name="search_query"]');
    
    if (searchInput) {
        // Real-time search suggestions
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 3) {
                    // Implement search suggestions here
                    console.log('Searching for:', this.value);
                }
            }, 300);
        });
    }

    // Advanced search toggle
    const advancedToggle = document.getElementById('advancedSearchToggle');
    const advancedFields = document.getElementById('advancedSearchFields');
    
    if (advancedToggle && advancedFields) {
        advancedToggle.addEventListener('click', function() {
            const isHidden = advancedFields.classList.contains('d-none');
            
            if (isHidden) {
                advancedFields.classList.remove('d-none');
                this.innerHTML = '<i class="fas fa-minus me-1"></i>Erweiterte Suche ausblenden';
            } else {
                advancedFields.classList.add('d-none');
                this.innerHTML = '<i class="fas fa-plus me-1"></i>Erweiterte Suche';
            }
        });
    }
}

// Form Validation
function setupFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                
                // Focus on first invalid field
                const firstInvalid = form.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                    showToast('Bitte füllen Sie alle Pflichtfelder korrekt aus.', 'error');
                }
            }
            
            form.classList.add('was-validated');
        });
    });

    // Custom validation for title fields
    const titleFields = document.querySelectorAll('input[name$="_title"]');
    if (titleFields.length > 0) {
        titleFields.forEach(field => {
            field.addEventListener('blur', validateTitleFields);
        });
    }
}

function validateTitleFields() {
    const titleDe = document.querySelector('input[name="title_de"]');
    const titleEn = document.querySelector('input[name="title_en"]');
    const titleFr = document.querySelector('input[name="title_fr"]');
    
    if (titleDe && titleEn && titleFr) {
        const hasAnyTitle = titleDe.value.trim() || titleEn.value.trim() || titleFr.value.trim();
        
        [titleDe, titleEn, titleFr].forEach(field => {
            if (hasAnyTitle) {
                field.setCustomValidity('');
            } else {
                field.setCustomValidity('Mindestens ein Titel muss eingegeben werden.');
            }
        });
    }
}

// Document Actions
function setupDocumentActions() {
    // Download buttons
    document.addEventListener('click', function(e) {
        if (e.target.matches('.btn-download') || e.target.closest('.btn-download')) {
            const btn = e.target.matches('.btn-download') ? e.target : e.target.closest('.btn-download');
            const documentId = btn.dataset.documentId;
            
            if (documentId) {
                downloadDocument(documentId);
            }
        }
    });

    // Delete buttons
    document.addEventListener('click', function(e) {
        if (e.target.matches('.btn-delete') || e.target.closest('.btn-delete')) {
            e.preventDefault();
            const btn = e.target.matches('.btn-delete') ? e.target : e.target.closest('.btn-delete');
            const documentTitle = btn.dataset.documentTitle || 'dieses Dokument';
            
            if (confirm(`Möchten Sie "${documentTitle}" wirklich löschen?`)) {
                // Proceed with deletion
                window.location.href = btn.href;
            }
        }
    });
}

// Batch Operations
function setupBatchOperations() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const documentCheckboxes = document.querySelectorAll('.document-checkbox');
    const batchActionBar = document.getElementById('batchActionBar');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            documentCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBatchActionBar();
        });
    }

    documentCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBatchActionBar);
    });

    function updateBatchActionBar() {
        const selectedCount = document.querySelectorAll('.document-checkbox:checked').length;
        
        if (batchActionBar) {
            if (selectedCount > 0) {
                batchActionBar.classList.remove('d-none');
                const countSpan = batchActionBar.querySelector('.selected-count');
                if (countSpan) {
                    countSpan.textContent = selectedCount;
                }
            } else {
                batchActionBar.classList.add('d-none');
            }
        }
    }
}

// Modal Handlers
function setupModalHandlers() {
    // Batch edit modal
    const batchEditModal = document.getElementById('batchEditModal');
    if (batchEditModal) {
        batchEditModal.addEventListener('show.bs.modal', function() {
            const selectedDocuments = getSelectedDocuments();
            console.log('Selected documents for batch edit:', selectedDocuments);
        });
    }

    // Upload progress modal
    setupUploadProgress();
}

function setupUploadProgress() {
    const uploadForms = document.querySelectorAll('form[enctype="multipart/form-data"]');
    
    uploadForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const fileInput = form.querySelector('input[type="file"]');
            
            if (fileInput && fileInput.files.length > 0) {
                // Show progress modal if it exists
                const progressModal = document.getElementById('uploadProgressModal');
                if (progressModal) {
                    const modal = new bootstrap.Modal(progressModal);
                    modal.show();
                }
            }
        });
    });
}

// Keyboard Shortcuts
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + U for upload (if user has permission)
        if ((e.ctrlKey || e.metaKey) && e.key === 'u') {
            const uploadBtn = document.querySelector('a[href*="upload"]');
            if (uploadBtn) {
                e.preventDefault();
                window.location.href = uploadBtn.href;
            }
        }

        // Ctrl/Cmd + F for search
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            const searchInput = document.querySelector('input[name="search_query"]');
            if (searchInput) {
                e.preventDefault();
                searchInput.focus();
            }
        }

        // Escape to close modals
        if (e.key === 'Escape') {
            const activeModal = document.querySelector('.modal.show');
            if (activeModal) {
                const modal = bootstrap.Modal.getInstance(activeModal);
                if (modal) {
                    modal.hide();
                }
            }
        }
    });
}

// Load Dynamic Content
function loadDynamicContent() {
    // Load document statistics
    loadDocumentStats();
    
    // Load recent activities if on dashboard
    if (window.location.pathname.includes('dashboard')) {
        loadRecentActivities();
    }
}

// API Functions
async function loadDocumentStats() {
    try {
        const response = await fetch(FINDEX.apiEndpoints.documentStats);
        if (response.ok) {
            const stats = await response.json();
            updateStatsDisplay(stats);
        }
    } catch (error) {
        console.error('Error loading document stats:', error);
    }
}

function updateStatsDisplay(stats) {
    // Update total documents
    const totalElement = document.getElementById('totalDocuments');
    if (totalElement) {
        animateNumber(totalElement, stats.total);
    }

    // Update active documents
    const activeElement = document.getElementById('activeDocuments');
    if (activeElement) {
        animateNumber(activeElement, stats.active);
    }
}

// Utility Functions
function animateNumber(element, targetNumber) {
    const startNumber = parseInt(element.textContent) || 0;
    const duration = 1000;
    const startTime = Date.now();

    function updateNumber() {
        const currentTime = Date.now();
        const progress = Math.min((currentTime - startTime) / duration, 1);
        const currentNumber = Math.floor(startNumber + (targetNumber - startNumber) * progress);
        
        element.textContent = currentNumber;
        
        if (progress < 1) {
            requestAnimationFrame(updateNumber);
        }
    }

    requestAnimationFrame(updateNumber);
}

function showToast(message, type = 'info', duration = FINDEX.settings.toastDuration) {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;

    // Add to toast container or create one
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }

    toastContainer.appendChild(toast);

    // Show toast
    const bsToast = new bootstrap.Toast(toast, { delay: duration });
    bsToast.show();

    // Remove element after hiding
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function getSelectedDocuments() {
    const checkboxes = document.querySelectorAll('.document-checkbox:checked');
    return Array.from(checkboxes).map(checkbox => ({
        id: checkbox.value,
        title: checkbox.dataset.title || ''
    }));
}

function downloadDocument(documentId) {
    // Show loading state
    showToast('Download wird vorbereitet...', 'info', 2000);
    
    // Create download link
    const link = document.createElement('a');
    link.href = `/documents/${documentId}/download/`;
    link.click();
}

// Export for external use
window.FINDEX = FINDEX;
window.showToast = showToast;
window.formatFileSize = formatFileSize; 