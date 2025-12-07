/**
 * ============================================
 * Form Validation JavaScript
 * ============================================
 * Course: Web Engineering Lab - CSC-314(L)
 * Session: Fall 2025
 * Project: Basic Banking Transaction System
 * ============================================
 */

/**
 * Email validation using regex
 * @param {string} email - Email address to validate
 * @returns {boolean} True if valid, false otherwise
 */
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Password strength validation
 * @param {string} password - Password to validate
 * @returns {object} Object with isValid and message
 */
function validatePasswordStrength(password) {
    const minLength = 6;
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumbers = /\d/.test(password);
    const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);
    
    if (password.length < minLength) {
        return {
            isValid: false,
            message: `Password must be at least ${minLength} characters long`
        };
    }
    
    // Optional: Enforce stronger password requirements
    /*
    if (!hasUpperCase || !hasLowerCase || !hasNumbers) {
        return {
            isValid: false,
            message: 'Password must contain uppercase, lowercase, and numbers'
        };
    }
    */
    
    return {
        isValid: true,
        message: 'Password is valid'
    };
}

/**
 * Registration form validation
 * @returns {boolean} True if valid, false otherwise
 */
function validateRegistration() {
    // Get form fields
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    // Validate name
    if (name.length < 3) {
        alert('Name must be at least 3 characters long');
        document.getElementById('name').focus();
        return false;
    }
    
    // Check for valid characters in name (letters, spaces, hyphens only)
    if (!/^[a-zA-Z\s-]+$/.test(name)) {
        alert('Name can only contain letters, spaces, and hyphens');
        document.getElementById('name').focus();
        return false;
    }
    
    // Validate email
    if (!validateEmail(email)) {
        alert('Please enter a valid email address');
        document.getElementById('email').focus();
        return false;
    }
    
    // Validate password
    const passwordCheck = validatePasswordStrength(password);
    if (!passwordCheck.isValid) {
        alert(passwordCheck.message);
        document.getElementById('password').focus();
        return false;
    }
    
    // Confirm password match
    if (password !== confirmPassword) {
        alert('Passwords do not match');
        document.getElementById('confirm_password').focus();
        return false;
    }
    
    return true;
}

/**
 * Login form validation
 * @returns {boolean} True if valid, false otherwise
 */
function validateLogin() {
    // Get form fields
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    
    // Validate email
    if (!validateEmail(email)) {
        alert('Please enter a valid email address');
        document.getElementById('email').focus();
        return false;
    }
    
    // Validate password presence
    if (password.length < 6) {
        alert('Password must be at least 6 characters long');
        document.getElementById('password').focus();
        return false;
    }
    
    return true;
}

/**
 * Transfer form validation
 * @returns {boolean} True if valid, false otherwise
 */
function validateTransfer() {
    // Get form fields
    const receiverId = document.getElementById('receiver_id').value;
    const amount = parseFloat(document.getElementById('amount').value);
    
    // Validate recipient selection
    if (!receiverId || receiverId === '') {
        alert('Please select a recipient');
        document.getElementById('receiver_id').focus();
        return false;
    }
    
    // Validate amount
    if (isNaN(amount) || amount <= 0) {
        alert('Please enter a valid amount greater than 0');
        document.getElementById('amount').focus();
        return false;
    }
    
    // Check minimum transfer amount
    if (amount < 0.01) {
        alert('Minimum transfer amount is $0.01');
        document.getElementById('amount').focus();
        return false;
    }
    
    // Check maximum transfer amount
    if (amount > 100000) {
        alert('Maximum transfer amount is $100,000');
        document.getElementById('amount').focus();
        return false;
    }
    
    // Confirm transfer
    const confirmMsg = `Are you sure you want to transfer $${amount.toFixed(2)}?`;
    return confirm(confirmMsg);
}

/**
 * User creation/edit form validation (Admin)
 * @returns {boolean} True if valid, false otherwise
 */
function validateUserForm() {
    // Get form fields
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password') ? document.getElementById('password').value : '';
    const role = document.getElementById('role').value;
    
    // Validate name
    if (name.length < 3) {
        alert('Name must be at least 3 characters long');
        document.getElementById('name').focus();
        return false;
    }
    
    // Validate email
    if (!validateEmail(email)) {
        alert('Please enter a valid email address');
        document.getElementById('email').focus();
        return false;
    }
    
    // Validate password (if present)
    if (password && password.length > 0) {
        const passwordCheck = validatePasswordStrength(password);
        if (!passwordCheck.isValid) {
            alert(passwordCheck.message);
            document.getElementById('password').focus();
            return false;
        }
    }
    
    // Validate role
    if (!role || (role !== 'Admin' && role !== 'Customer')) {
        alert('Please select a valid role');
        document.getElementById('role').focus();
        return false;
    }
    
    return true;
}

/**
 * Profile edit form validation
 * @returns {boolean} True if valid, false otherwise
 */
function validateProfileEdit() {
    // Get form fields
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const newPassword = document.getElementById('new_password') ? document.getElementById('new_password').value : '';
    const confirmPassword = document.getElementById('confirm_password') ? document.getElementById('confirm_password').value : '';
    
    // Validate name
    if (name.length < 3) {
        alert('Name must be at least 3 characters long');
        document.getElementById('name').focus();
        return false;
    }
    
    // Validate email
    if (!validateEmail(email)) {
        alert('Please enter a valid email address');
        document.getElementById('email').focus();
        return false;
    }
    
    // If changing password, validate it
    if (newPassword && newPassword.length > 0) {
        const passwordCheck = validatePasswordStrength(newPassword);
        if (!passwordCheck.isValid) {
            alert(passwordCheck.message);
            document.getElementById('new_password').focus();
            return false;
        }
        
        // Check password confirmation
        if (newPassword !== confirmPassword) {
            alert('Passwords do not match');
            document.getElementById('confirm_password').focus();
            return false;
        }
        
        // Require current password when changing password
        const currentPassword = document.getElementById('current_password').value;
        if (!currentPassword) {
            alert('Please enter your current password to change it');
            document.getElementById('current_password').focus();
            return false;
        }
    }
    
    return true;
}

/**
 * Real-time validation helpers
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // Email field real-time validation
    const emailInputs = document.querySelectorAll('input[type="email"]');
    emailInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value && !validateEmail(this.value)) {
                this.classList.add('is-invalid');
                showFieldError(this, 'Please enter a valid email address');
            } else {
                this.classList.remove('is-invalid');
                hideFieldError(this);
            }
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid') && validateEmail(this.value)) {
                this.classList.remove('is-invalid');
                hideFieldError(this);
            }
        });
    });
    
    // Password confirmation real-time validation
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordInput = document.getElementById('password');
    
    if (confirmPasswordInput && passwordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            if (this.value !== passwordInput.value) {
                this.classList.add('is-invalid');
                showFieldError(this, 'Passwords do not match');
            } else {
                this.classList.remove('is-invalid');
                hideFieldError(this);
            }
        });
    }
    
    // Amount field formatting
    const amountInputs = document.querySelectorAll('input[type="number"][step="0.01"]');
    amountInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value) {
                const value = parseFloat(this.value);
                if (!isNaN(value)) {
                    this.value = value.toFixed(2);
                }
            }
        });
    });
    
    // Prevent form double submission
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                setTimeout(() => {
                    submitBtn.disabled = false;
                }, 3000);
            }
        });
    });
});

/**
 * Show field error message
 * @param {HTMLElement} field - Input field element
 * @param {string} message - Error message
 */
function showFieldError(field, message) {
    // Remove existing error
    hideFieldError(field);
    
    // Create error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback d-block';
    errorDiv.textContent = message;
    errorDiv.setAttribute('data-error-for', field.id);
    
    // Insert after field
    field.parentNode.insertBefore(errorDiv, field.nextSibling);
}

/**
 * Hide field error message
 * @param {HTMLElement} field - Input field element
 */
function hideFieldError(field) {
    const errorDiv = field.parentNode.querySelector(`[data-error-for="${field.id}"]`);
    if (errorDiv) {
        errorDiv.remove();
    }
}

/**
 * Sanitize input (prevent XSS)
 * @param {string} input - User input
 * @returns {string} Sanitized input
 */
function sanitizeInput(input) {
    const div = document.createElement('div');
    div.textContent = input;
    return div.innerHTML;
}

/**
 * Format number as currency
 * @param {number} amount - Amount to format
 * @returns {string} Formatted currency string
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

/**
 * Validate positive number
 * @param {string} value - Value to validate
 * @returns {boolean} True if valid positive number
 */
function isPositiveNumber(value) {
    const num = parseFloat(value);
    return !isNaN(num) && num > 0;
}

/**
 * Show loading indicator on button
 * @param {HTMLElement} button - Button element
 */
function showButtonLoading(button) {
    button.disabled = true;
    button.setAttribute('data-original-text', button.innerHTML);
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
}

/**
 * Hide loading indicator on button
 * @param {HTMLElement} button - Button element
 */
function hideButtonLoading(button) {
    button.disabled = false;
    const originalText = button.getAttribute('data-original-text');
    if (originalText) {
        button.innerHTML = originalText;
    }
}

/**
 * Confirm action with custom message
 * @param {string} message - Confirmation message
 * @returns {boolean} True if confirmed
 */
function confirmAction(message) {
    return confirm(message);
}

/**
 * Check if form has unsaved changes
 * @param {HTMLFormElement} form - Form element
 * @returns {boolean} True if form has changes
 */
function hasUnsavedChanges(form) {
    const formData = new FormData(form);
    const currentData = {};
    
    for (let [key, value] of formData.entries()) {
        currentData[key] = value;
    }
    
    // Store initial form data
    if (!form.hasAttribute('data-initial-state')) {
        form.setAttribute('data-initial-state', JSON.stringify(currentData));
        return false;
    }
    
    const initialData = JSON.parse(form.getAttribute('data-initial-state'));
    return JSON.stringify(currentData) !== JSON.stringify(initialData);
}

/**
 * Warn user about unsaved changes before leaving page
 */
window.addEventListener('beforeunload', function(e) {
    const forms = document.querySelectorAll('form');
    let hasChanges = false;
    
    forms.forEach(form => {
        if (hasUnsavedChanges(form)) {
            hasChanges = true;
        }
    });
    
    if (hasChanges) {
        e.preventDefault();
        e.returnValue = '';
        return '';
    }
});

/**
 * ============================================
 * END OF VALIDATION.JS
 * ============================================
 */