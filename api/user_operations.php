<?php
/**
 * ============================================
 * User Operations API Handler
 * ============================================
 * Handles CRUD operations for users
 * Only accessible by Admin
 * ============================================
 */

require_once '../includes/session_check.php';
require_once '../config/database.php';

// Check if user is admin
checkAdmin();

// Get operation type
$operation = isset($_GET['operation']) ? $_GET['operation'] : (isset($_POST['operation']) ? $_POST['operation'] : '');

// Set JSON header for API responses
header('Content-Type: application/json');

// Route to appropriate handler
switch ($operation) {
    case 'create':
        createUser();
        break;
    
    case 'read':
        readUser();
        break;
    
    case 'update':
        updateUser();
        break;
    
    case 'delete':
        deleteUser();
        break;
    
    case 'list':
        listUsers();
        break;
    
    case 'search':
        searchUsers();
        break;
    
    case 'statistics':
        getUserStatistics();
        break;
    
    default:
        sendResponse(false, 'Invalid operation', null, 400);
        break;
}

/**
 * Create new user
 */
function createUser() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method', null, 405);
        return;
    }
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'Customer';
    $initial_balance = floatval($_POST['initial_balance'] ?? 10000);
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        sendResponse(false, 'All fields are required');
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(false, 'Invalid email format');
        return;
    }
    
    if (strlen($password) < 6) {
        sendResponse(false, 'Password must be at least 6 characters');
        return;
    }
    
    if (!in_array($role, ['Admin', 'Customer'])) {
        sendResponse(false, 'Invalid role');
        return;
    }
    
    $conn = getDBConnection();
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        sendResponse(false, 'Email already exists');
        $conn->close();
        return;
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $password_hash, $role);
        $stmt->execute();
        
        $user_id = $conn->insert_id;
        
        // Create account
        $account_number = generateAccountNumber($user_id);
        $stmt = $conn->prepare("INSERT INTO accounts (user_id, account_number, current_balance) VALUES (?, ?, ?)");
        $stmt->bind_param("isd", $user_id, $account_number, $initial_balance);
        $stmt->execute();
        
        $conn->commit();
        
        logUserActivity('create_user', "Created user: $email");
        
        sendResponse(true, 'User created successfully', [
            'user_id' => $user_id,
            'account_number' => $account_number
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        sendResponse(false, 'Failed to create user: ' . $e->getMessage());
    }
    
    $conn->close();
}

/**
 * Read user details
 */
function readUser() {
    $user_id = intval($_GET['user_id'] ?? 0);
    
    if ($user_id <= 0) {
        sendResponse(false, 'Invalid user ID');
        return;
    }
    
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("
        SELECT u.*, a.account_number, a.current_balance, a.created_at as account_created
        FROM users u
        LEFT JOIN accounts a ON u.user_id = a.user_id
        WHERE u.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendResponse(false, 'User not found', null, 404);
        $conn->close();
        return;
    }
    
    $user = $result->fetch_assoc();
    unset($user['password_hash']); // Don't send password hash
    
    sendResponse(true, 'User retrieved successfully', $user);
    $conn->close();
}

/**
 * Update user
 */
function updateUser() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method', null, 405);
        return;
    }
    
    $user_id = intval($_POST['user_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $balance = isset($_POST['balance']) ? floatval($_POST['balance']) : null;
    $password = $_POST['password'] ?? '';
    
    // Validation
    if ($user_id <= 0) {
        sendResponse(false, 'Invalid user ID');
        return;
    }
    
    if (empty($name) || empty($email) || empty($role)) {
        sendResponse(false, 'Name, email, and role are required');
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(false, 'Invalid email format');
        return;
    }
    
    $conn = getDBConnection();
    
    // Check if email exists for other users
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        sendResponse(false, 'Email already exists');
        $conn->close();
        return;
    }
    
    $conn->begin_transaction();
    
    try {
        // Update user
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE user_id = ?");
        $stmt->bind_param("sssi", $name, $email, $role, $user_id);
        $stmt->execute();
        
        // Update balance if provided
        if ($balance !== null) {
            $stmt = $conn->prepare("UPDATE accounts SET current_balance = ? WHERE user_id = ?");
            $stmt->bind_param("di", $balance, $user_id);
            $stmt->execute();
        }
        
        // Update password if provided
        if (!empty($password)) {
            if (strlen($password) >= 6) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                $stmt->bind_param("si", $password_hash, $user_id);
                $stmt->execute();
            }
        }
        
        $conn->commit();
        
        logUserActivity('update_user', "Updated user ID: $user_id");
        
        sendResponse(true, 'User updated successfully');
        
    } catch (Exception $e) {
        $conn->rollback();
        sendResponse(false, 'Failed to update user: ' . $e->getMessage());
    }
    
    $conn->close();
}

/**
 * Delete user
 */
function deleteUser() {
    $user_id = intval($_GET['user_id'] ?? $_POST['user_id'] ?? 0);
    
    if ($user_id <= 0) {
        sendResponse(false, 'Invalid user ID');
        return;
    }
    
    // Prevent deleting own account
    if ($user_id === $_SESSION['user_id']) {
        sendResponse(false, 'Cannot delete your own account');
        return;
    }
    
    $conn = getDBConnection();
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendResponse(false, 'User not found', null, 404);
        $conn->close();
        return;
    }
    
    $user = $result->fetch_assoc();
    
    // Delete user (cascade will delete account and update transactions)
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        logUserActivity('delete_user', "Deleted user: {$user['name']} (ID: $user_id)");
        sendResponse(true, 'User deleted successfully');
    } else {
        sendResponse(false, 'Failed to delete user');
    }
    
    $conn->close();
}

/**
 * List all users
 */
function listUsers() {
    $role = $_GET['role'] ?? '';
    $limit = intval($_GET['limit'] ?? 100);
    $offset = intval($_GET['offset'] ?? 0);
    
    $conn = getDBConnection();
    
    $query = "
        SELECT u.user_id, u.name, u.email, u.role, u.created_at,
               a.account_number, a.current_balance
        FROM users u
        LEFT JOIN accounts a ON u.user_id = a.user_id
    ";
    
    if (!empty($role)) {
        $query .= " WHERE u.role = ?";
    }
    
    $query .= " ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($role)) {
        $stmt->bind_param("sii", $role, $limit, $offset);
    } else {
        $stmt->bind_param("ii", $limit, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    sendResponse(true, 'Users retrieved successfully', [
        'users' => $users,
        'count' => count($users)
    ]);
    
    $conn->close();
}

/**
 * Search users
 */
function searchUsers() {
    $search = trim($_GET['search'] ?? '');
    
    if (empty($search)) {
        sendResponse(false, 'Search term is required');
        return;
    }
    
    $conn = getDBConnection();
    
    $searchTerm = "%$search%";
    $stmt = $conn->prepare("
        SELECT u.user_id, u.name, u.email, u.role,
               a.account_number, a.current_balance
        FROM users u
        LEFT JOIN accounts a ON u.user_id = a.user_id
        WHERE u.name LIKE ? OR u.email LIKE ? OR a.account_number LIKE ?
        ORDER BY u.name
        LIMIT 20
    ");
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    sendResponse(true, 'Search completed', [
        'users' => $users,
        'count' => count($users)
    ]);
    
    $conn->close();
}

/**
 * Get user statistics
 */
function getUserStatistics() {
    $user_id = intval($_GET['user_id'] ?? 0);
    
    $conn = getDBConnection();
    
    if ($user_id > 0) {
        // Statistics for specific user
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_transactions,
                SUM(CASE WHEN sender_id = ? THEN amount ELSE 0 END) as total_sent,
                SUM(CASE WHEN receiver_id = ? THEN amount ELSE 0 END) as total_received
            FROM transactions
            WHERE sender_id = ? OR receiver_id = ?
        ");
        $stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();
    } else {
        // System-wide statistics
        $stats = [
            'total_users' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
            'total_customers' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'Customer'")->fetch_assoc()['count'],
            'total_admins' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'Admin'")->fetch_assoc()['count'],
            'total_transactions' => $conn->query("SELECT COUNT(*) as count FROM transactions")->fetch_assoc()['count'],
            'total_balance' => $conn->query("SELECT SUM(current_balance) as total FROM accounts")->fetch_assoc()['total']
        ];
    }
    
    sendResponse(true, 'Statistics retrieved successfully', $stats);
    $conn->close();
}

/**
 * Send JSON response
 */
function sendResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    
    $response = [
        'success' => $success,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit();
}

/**
 * ============================================
 * END OF USER OPERATIONS API
 * ============================================
 */
?>