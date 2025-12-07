/**
 * ============================================
 * Main JavaScript File
 * ============================================
 * General utilities and interactions
 * Course: Web Engineering Lab - CSC-314(L)
 * ============================================
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('SecureBank Application Loaded');
    
    // Initialize all features
    initializeAlerts();
    initializeConfirmations();
    initializeTooltips();
    initializeFormHelpers();
    initializeTableFeatures();
    initializeLoadingStates();
    initializeCopyToClipboard();
});

/**
 * Auto-dismiss alerts after 5 seconds
 */
function initializeAlerts() {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
}

/**
 * Add confirmation dialogs to delete buttons
 */
function initializeConfirmations() {
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure you want to delete this?';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

/**
 * Initialize Bootstrap tooltips
 */
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Form helper functions
 */
function initializeFormHelpers() {
    // Auto-format currency inputs
    const currencyInputs = document.querySelectorAll('input[type="number"][step="0.01"]');
    currencyInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value) {
                this.value = parseFloat(this.value).toFixed(2);
            }
        });
    });
    
    // Prevent double form submission
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
                
                // Re-enable after 3 seconds as fallback
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = submitBtn.getAttribute('data-original-text') || 'Submit';
                }, 3000);
            }
        });
    });
    
    // Store original button text
    const submitButtons = document.querySelectorAll('button[type="submit"]');
    submitButtons.forEach(btn => {
        btn.setAttribute('data-original-text', btn.innerHTML);
    });
}

/**
 * Table features
 */
function initializeTableFeatures() {
    // Add search functionality to tables
    const searchInputs = document.querySelectorAll('.table-search');
    searchInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const tableId = this.getAttribute('data-table');
            const table = document.getElementById(tableId);
            if (!table) return;
            
            const filter = this.value.toUpperCase();
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent || row.innerText;
                if (text.toUpperCase().indexOf(filter) > -1) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
    
    // Sortable tables
    const sortableHeaders = document.querySelectorAll('.sortable');
    sortableHeaders.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            sortTable(this);
        });
    });
}

/**
 * Sort table by column
 */
function sortTable(header) {
    const table = header.closest('table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const headerIndex = Array.from(header.parentNode.children).indexOf(header);
    const currentOrder = header.getAttribute('data-order') || 'asc';
    const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
    
    // Sort rows
    rows.sort((a, b) => {
        const aValue = a.children[headerIndex].textContent.trim();
        const bValue = b.children[headerIndex].textContent.trim();
        
        // Try to parse as number
        const aNum = parseFloat(aValue.replace(/[^0-9.-]/g, ''));
        const bNum = parseFloat(bValue.replace(/[^0-9.-]/g, ''));
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return newOrder === 'asc' ? aNum - bNum : bNum - aNum;
        }
        
        // String comparison
        return newOrder === 'asc' 
            ? aValue.localeCompare(bValue)
            : bValue.localeCompare(aValue);
    });
    
    // Update DOM
    rows.forEach(row => tbody.appendChild(row));
    
    // Update sort indicators
    table.querySelectorAll('th').forEach(th => th.removeAttribute('data-order'));
    header.setAttribute('data-order', newOrder);
    
    // Add visual indicator
    const icon = newOrder === 'asc' ? '▲' : '▼';
    header.innerHTML = header.textContent.replace(/[▲▼]/g, '').trim() + ' ' + icon;
}

/**
 * Loading states
 */
function initializeLoadingStates() {
    // Show loading spinner on page navigation
    const links = document.querySelectorAll('a[href]:not([target="_blank"]):not([href^="#"])');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!this.classList.contains('no-loading')) {
                showPageLoader();
            }
        });
    });
}

/**
 * Show page loader
 */
function showPageLoader() {
    const loader = document.createElement('div');
    loader.id = 'page-loader';
    loader.innerHTML = `
        <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                    background: rgba(255,255,255,0.9); z-index: 9999; 
                    display: flex; align-items: center; justify-content: center;">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    document.body.appendChild(loader);
}

/**
 * Copy to clipboard functionality
 */
function initializeCopyToClipboard() {
    const copyButtons = document.querySelectorAll('[data-copy]');
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const textToCopy = this.getAttribute('data-copy');
            copyToClipboard(textToCopy);
            
            // Show feedback
            const originalText = this.innerHTML;
            this.innerHTML = '✓ Copied!';
            this.classList.add('btn-success');
            
            setTimeout(() => {
                this.innerHTML = originalText;
                this.classList.remove('btn-success');
            }, 2000);
        });
    });
}

/**
 * Copy text to clipboard
 */
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text);
    } else {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
    }
}

/**
 * Format number as currency
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

/**
 * Format date
 */
function formatDate(dateString, format = 'short') {
    const date = new Date(dateString);
    
    if (format === 'short') {
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    } else if (format === 'long') {
        return date.toLocaleDateString('en-US', {
            weekday: 'long',
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        });
    } else if (format === 'time') {
        return date.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });
    } else if (format === 'datetime') {
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    return date.toLocaleDateString();
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Remove after hidden
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

/**
 * Create toast container
 */
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

/**
 * Debounce function for search inputs
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Validate balance before transfer
 */
function validateBalance(amount, currentBalance) {
    const transferAmount = parseFloat(amount);
    const balance = parseFloat(currentBalance);
    
    if (isNaN(transferAmount) || transferAmount <= 0) {
        showToast('Please enter a valid amount', 'warning');
        return false;
    }
    
    if (transferAmount > balance) {
        showToast('Insufficient balance for this transfer', 'danger');
        return false;
    }
    
    return true;
}

/**
 * Real-time balance display update
 */
function updateBalanceDisplay(newBalance) {
    const balanceElements = document.querySelectorAll('.balance-display');
    balanceElements.forEach(element => {
        element.textContent = formatCurrency(newBalance);
        
        // Add animation
        element.classList.add('balance-updated');
        setTimeout(() => {
            element.classList.remove('balance-updated');
        }, 1000);
    });
}

/**
 * Print functionality
 */
function printPage() {
    window.print();
}

/**
 * Export table to CSV
 */
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = Array.from(cols).map(col => {
            return '"' + col.textContent.trim().replace(/"/g, '""') + '"';
        });
        csv.push(rowData.join(','));
    });
    
    downloadCSV(csv.join('\n'), filename);
}

/**
 * Download CSV file
 */
function downloadCSV(csv, filename) {
    const blob = new Blob([csv], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.click();
}

/**
 * Check if user is idle (for auto-logout)
 */
let idleTime = 0;
function checkIdleTime() {
    setInterval(() => {
        idleTime++;
        if (idleTime > 30) { // 30 minutes
            alert('You have been idle for too long. Please login again.');
            window.location.href = '../logout.php';
        }
    }, 60000); // Check every minute
}

// Reset idle time on user activity
document.addEventListener('mousemove', () => idleTime = 0);
document.addEventListener('keypress', () => idleTime = 0);
document.addEventListener('click', () => idleTime = 0);

// Start idle checker if user is logged in
if (document.body.classList.contains('logged-in')) {
    checkIdleTime();
}

// Global utility functions
window.formatCurrency = formatCurrency;
window.formatDate = formatDate;
window.showToast = showToast;
window.copyToClipboard = copyToClipboard;
window.exportTableToCSV = exportTableToCSV;
window.printPage = printPage;

console.log('SecureBank: All utilities loaded successfully');

/**
 * ============================================
 * END OF MAIN.JS
 * ============================================
 */