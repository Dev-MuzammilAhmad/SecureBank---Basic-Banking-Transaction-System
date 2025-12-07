<?php
/**
 * ============================================
 * Session Check and Authentication Middleware
 * ============================================
 * Course: Web Engineering Lab - CSC-314(L)
 * Session: Fall 2025
 * Project: Basic Banking Transaction System
 * ============================================
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once __DIR__ . '/../config/database.php';

/**
 * Check if user is logged in
 * Redirects to login page if not authenticated
 */
function checkLogin() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        // Store the intended destination
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // Redirect to login page
        header("Location: ../index.php?error=Please login to continue");
        exit();
    }
    
    // Check if session has expired (optional - 1 hour timeout)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
        // Session expired
        session_unset();
        session_destroy();
        header("Location: ../index.php?error=Session expired. Please login again");
        exit();
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

/**
 * Check if user is an Admin
 * Redirects to customer dashboard if not admin
 */
function checkAdmin() {
    // First check if user is logged in
    checkLogin();
    
    // Check if user has admin role
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
        header("Location: ../customer/dashboard.php?error=Access denied. Admin privileges required");
        exit();
    }
}

/**
 * Check if user is a Customer
 * Redirects to admin dashboard if not customer
 */
function checkCustomer() {
    // First check if user is logged in
    checkLogin();
    
    // Check if user has customer role
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Customer') {
        header("Location: ../admin/dashboard.php?error=Access denied. Customer account required");
        exit();
    }
}

/**
 * Check if user is logged in (returns boolean)
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if current user is admin (returns boolean)
 * @return bool True if admin, false otherwise
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';
}

/**
 * Check if current user is customer (returns boolean)
 * @return bool True if customer, false otherwise
 */
function isCustomer() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'Customer';
}

/**
 * Get current user ID
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * Get current user name
 * @return string|null User name or null if not logged in
 */
function getCurrentUserName() {
    return isset($_SESSION['name']) ? $_SESSION['name'] : null;
}

/**
 * Get current user email
 * @return string|null User email or null if not logged in
 */
function getCurrentUserEmail() {
    return isset($_SESSION['email']) ? $_SESSION['email'] : null;
}

/**
 * Get current user role
 * @return string|null User role or null if not logged in
 */
function getCurrentUserRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

/**
 * Refresh user session data from database
 * Useful after profile updates
 */
function refreshUserSession() {
    if (!isLoggedIn()) {
        return false;
    }
    
    $conn = getDBConnection();
    $userId = getCurrentUserId();
    
    $stmt = $conn->prepare("
        SELECT u.user_id, u.name, u.email, u.role, a.account_id, a.current_balance, a.account_number
        FROM users u
        LEFT JOIN accounts a ON u.user_id = a.user_id
        WHERE u.user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Update session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['account_id'] = $user['account_id'];
        $_SESSION['current_balance'] = $user['current_balance'];
        $_SESSION['account_number'] = $user['account_number'];
        
        $stmt->close();
        $conn->close();
        return true;
    }
    
    $stmt->close();
    $conn->close();
    return false;
}

/**
 * Logout user and destroy session
 */
function logoutUser() {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header("Location: ../index.php?success=Logged out successfully");
    exit();
}

/**
 * Prevent access to page if already logged in
 * Redirects to appropriate dashboard based on role
 */
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        if (isAdmin()) {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: customer/dashboard.php");
        }
        exit();
    }
}

/**
 * Set success message in session
 * @param string $message Success message
 */
function setSuccessMessage($message) {
    $_SESSION['success_message'] = $message;
}

/**
 * Set error message in session
 * @param string $message Error message
 */
function setErrorMessage($message) {
    $_SESSION['error_message'] = $message;
}

/**
 * Get and clear success message
 * @return string|null Success message or null
 */
function getSuccessMessage() {
    if (isset($_SESSION['success_message'])) {
        $message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        return $message;
    }
    return null;
}

/**
 * Get and clear error message
 * @return string|null Error message or null
 */
function getErrorMessage() {
    if (isset($_SESSION['error_message'])) {
        $message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
        return $message;
    }
    return null;
}

/**
 * Check if user has permission to access a resource
 * @param int $resourceUserId User ID of the resource owner
 * @return bool True if has permission, false otherwise
 */
function hasPermission($resourceUserId) {
    // Admins have access to everything
    if (isAdmin()) {
        return true;
    }
    
    // Users can only access their own resources
    return getCurrentUserId() == $resourceUserId;
}

/**
 * Verify CSRF token (if implemented)
 * @param string $token Token to verify
 * @return bool True if valid, false otherwise
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate CSRF token
 * @return string CSRF token
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Log user activity (for audit trail)
 * @param string $action Action performed
 * @param string $details Additional details
 */
function logUserActivity($action, $details = '') {
    if (!isLoggedIn()) {
        return;
    }
    
    $conn = getDBConnection();
    $userId = getCurrentUserId();
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    
    $stmt = $conn->prepare("
        INSERT INTO activity_log (user_id, action, details, ip_address, user_agent, timestamp)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    if ($stmt) {
        $stmt->bind_param("issss", $userId, $action, $details, $ipAddress, $userAgent);
        $stmt->execute();
        $stmt->close();
    }
    
    $conn->close();
}

/**
 * Rate limiting check
 * @param string $action Action to check
 * @param int $maxAttempts Maximum attempts allowed
 * @param int $timeWindow Time window in seconds
 * @return bool True if allowed, false if rate limit exceeded
 */
function checkRateLimit($action, $maxAttempts = 5, $timeWindow = 300) {
    $key = 'rate_limit_' . $action . '_' . $_SERVER['REMOTE_ADDR'];
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 1, 'start_time' => time()];
        return true;
    }
    
    $elapsed = time() - $_SESSION[$key]['start_time'];
    
    if ($elapsed > $timeWindow) {
        // Reset counter
        $_SESSION[$key] = ['count' => 1, 'start_time' => time()];
        return true;
    }
    
    if ($_SESSION[$key]['count'] >= $maxAttempts) {
        return false;
    }
    
    $_SESSION[$key]['count']++;
    return true;
}

/**
 * Display session-based alert messages
 * @return string HTML for alert messages
 */
function displaySessionMessages() {
    $html = '';
    
    // Success message
    $success = getSuccessMessage();
    if ($success) {
        $html .= '<div class="alert alert-success alert-dismissible fade show" role="alert">';
        $html .= htmlspecialchars($success);
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        $html .= '</div>';
    }
    
    // Error message
    $error = getErrorMessage();
    if ($error) {
        $html .= '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        $html .= htmlspecialchars($error);
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        $html .= '</div>';
    }
    
    return $html;
}

// ============================================
// SESSION SECURITY ENHANCEMENTS
// ============================================

// Regenerate session ID periodically for security
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 1800) {
    // Regenerate session every 30 minutes
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Store user IP and user agent for security
if (isLoggedIn()) {
    if (!isset($_SESSION['user_ip'])) {
        $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    } else {
        // Check if IP or user agent changed (possible session hijacking)
        if ($_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR'] || 
            $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            // Possible session hijacking - logout user
            logoutUser();
        }
    }
}

// ============================================
// END OF SESSION CHECK FILE
// ============================================
?>