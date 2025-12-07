<?php
/**
 * ============================================
 * Database Configuration File
 * ============================================
 * Course: Web Engineering Lab - CSC-314(L)
 * Session: Fall 2025
 * Project: Basic Banking Transaction System
 * ============================================
 */

// Prevent direct access
if (!defined('DB_ACCESS')) {
    define('DB_ACCESS', true);
}

// ============================================
// DATABASE CREDENTIALS
// ============================================

// Database Host
define('DB_HOST', 'localhost');

// Database Username (default for XAMPP/WAMP is 'root')
define('DB_USER', 'root');

// Database Password (default for XAMPP is empty, WAMP might have 'root')
define('DB_PASS', '');

// Database Name
define('DB_NAME', 'banking_system');

// Database Port (default MySQL port)
define('DB_PORT', 3306);

// Database Charset
define('DB_CHARSET', 'utf8mb4');

// ============================================
// CONFIGURATION OPTIONS
// ============================================

// Error Reporting (set to false in production)
define('DEBUG_MODE', true);

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour in seconds
define('SESSION_NAME', 'BANKING_SESSION');

// Application Settings
define('APP_NAME', 'SecureBank');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/banking-system');

// ============================================
// DATABASE CONNECTION FUNCTION
// ============================================

/**
 * Get Database Connection
 * @return mysqli|false Returns mysqli connection object or false on failure
 */
function getDBConnection() {
    // Create connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    // Check connection
    if ($conn->connect_error) {
        if (DEBUG_MODE) {
            die("Connection failed: " . $conn->connect_error);
        } else {
            die("Database connection error. Please contact administrator.");
        }
    }
    
    // Set charset
    $conn->set_charset(DB_CHARSET);
    
    return $conn;
}

/**
 * Close Database Connection
 * @param mysqli $conn Database connection object
 */
function closeDBConnection($conn) {
    if ($conn && $conn instanceof mysqli) {
        $conn->close();
    }
}

/**
 * Sanitize Input Data
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate Email
 * @param string $email Email address to validate
 * @return bool True if valid, false otherwise
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Redirect to another page
 * @param string $url URL to redirect to
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Display error message
 * @param string $message Error message
 */
function showError($message) {
    return '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                ' . htmlspecialchars($message) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}

/**
 * Display success message
 * @param string $message Success message
 */
function showSuccess($message) {
    return '<div class="alert alert-success alert-dismissible fade show" role="alert">
                ' . htmlspecialchars($message) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}

/**
 * Format currency
 * @param float $amount Amount to format
 * @return string Formatted currency
 */
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Format date
 * @param string $date Date to format
 * @param string $format Date format
 * @return string Formatted date
 */
function formatDate($date, $format = 'M d, Y H:i') {
    return date($format, strtotime($date));
}

/**
 * Generate unique account number
 * @param int $userId User ID
 * @return string Account number
 */
function generateAccountNumber($userId) {
    return 'ACC' . str_pad($userId + 1000000000, 10, '0', STR_PAD_LEFT);
}

/**
 * Log activity (for debugging)
 * @param string $message Message to log
 * @param string $type Log type (info, error, warning)
 */
function logActivity($message, $type = 'info') {
    if (DEBUG_MODE) {
        $logFile = __DIR__ . '/../logs/activity.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$type] $message\n";
        
        // Create logs directory if it doesn't exist
        if (!file_exists(__DIR__ . '/../logs')) {
            mkdir(__DIR__ . '/../logs', 0755, true);
        }
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}

/**
 * Execute prepared statement
 * @param mysqli $conn Database connection
 * @param string $query SQL query with placeholders
 * @param string $types Parameter types (e.g., "ssi" for string, string, integer)
 * @param array $params Parameters array
 * @return mysqli_result|bool Query result
 */
function executePreparedQuery($conn, $query, $types, $params) {
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        logActivity("Prepare failed: " . $conn->error, 'error');
        return false;
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $result = $stmt->execute();
    
    if (!$result) {
        logActivity("Execute failed: " . $stmt->error, 'error');
        return false;
    }
    
    return $stmt->get_result();
}

// ============================================
// SESSION CONFIGURATION
// ============================================

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Set session name
    session_name(SESSION_NAME);
    
    // Configure session parameters
    ini_set('session.cookie_lifetime', SESSION_LIFETIME);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    
    // Start session
    session_start();
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) {
        // Regenerate session ID every 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// ============================================
// ERROR HANDLING
// ============================================

if (DEBUG_MODE) {
    // Show all errors in development
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // Hide errors in production
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
}

// ============================================
// TIMEZONE CONFIGURATION
// ============================================

date_default_timezone_set('Asia/Karachi'); // Set to Pakistan timezone

// ============================================
// TEST DATABASE CONNECTION
// ============================================

// Test connection on file include (only in debug mode)
if (DEBUG_MODE && basename($_SERVER['PHP_SELF']) !== 'database.php') {
    $testConn = getDBConnection();
    if ($testConn) {
        logActivity("Database connected successfully", 'info');
        closeDBConnection($testConn);
    } else {
        logActivity("Database connection failed", 'error');
    }
}

// ============================================
// CONSTANTS FOR TRANSACTION LIMITS
// ============================================

define('MIN_TRANSFER_AMOUNT', 0.01);
define('MAX_TRANSFER_AMOUNT', 100000.00);
define('MIN_BALANCE_REQUIRED', 0.00);
define('INITIAL_BALANCE', 10000.00);

// ============================================
// END OF CONFIGURATION FILE
// ============================================
?>