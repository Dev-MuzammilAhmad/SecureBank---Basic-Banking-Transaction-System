<?php
/**
 * ============================================
 * Transfer Handler - Money Transfer Processing
 * ============================================
 * Course: Web Engineering Lab - CSC-314(L)
 * Session: Fall 2025
 * Project: Basic Banking Transaction System
 * ============================================
 */

session_start();
require_once '../config/database.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    header("Location: ../index.php?error=Please login as customer to transfer money");
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../customer/transfer.php?error=Invalid request method");
    exit();
}

// Get form data
$sender_id = $_SESSION['user_id'];
$receiver_id = intval($_POST['receiver_id']);
$amount = floatval($_POST['amount']);
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

// ============================================
// SERVER-SIDE VALIDATION
// ============================================

// Validate receiver ID
if (empty($receiver_id) || $receiver_id <= 0) {
    header("Location: ../customer/transfer.php?error=Please select a valid recipient");
    exit();
}

// Validate amount
if ($amount <= 0) {
    header("Location: ../customer/transfer.php?error=Transfer amount must be greater than zero");
    exit();
}

// Check minimum transfer amount
if ($amount < 0.01) {
    header("Location: ../customer/transfer.php?error=Minimum transfer amount is $0.01");
    exit();
}

// Check maximum transfer amount
if ($amount > 100000) {
    header("Location: ../customer/transfer.php?error=Maximum transfer amount is $100,000");
    exit();
}

// Prevent self-transfer
if ($sender_id === $receiver_id) {
    header("Location: ../customer/transfer.php?error=You cannot transfer money to yourself");
    exit();
}

// Sanitize description
if (!empty($description)) {
    $description = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
    if (strlen($description) > 255) {
        $description = substr($description, 0, 255);
    }
}

// ============================================
// DATABASE TRANSACTION PROCESSING
// ============================================

$conn = getDBConnection();

// Start database transaction for atomicity
$conn->begin_transaction();

try {
    // Lock and get sender's account
    $stmt = $conn->prepare("SELECT current_balance FROM accounts WHERE user_id = ? FOR UPDATE");
    $stmt->bind_param("i", $sender_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Sender account not found");
    }
    
    $sender_account = $result->fetch_assoc();
    $sender_balance = floatval($sender_account['current_balance']);
    
    // Check sufficient balance
    if ($sender_balance < $amount) {
        throw new Exception("Insufficient balance. Your current balance is $" . number_format($sender_balance, 2));
    }
    
    // Lock and get receiver's account
    $stmt = $conn->prepare("SELECT current_balance FROM accounts WHERE user_id = ? FOR UPDATE");
    $stmt->bind_param("i", $receiver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Recipient account not found");
    }
    
    $receiver_account = $result->fetch_assoc();
    $receiver_balance = floatval($receiver_account['current_balance']);
    
    // Calculate new balances
    $new_sender_balance = $sender_balance - $amount;
    $new_receiver_balance = $receiver_balance + $amount;
    
    // Update sender's balance
    $stmt = $conn->prepare("UPDATE accounts SET current_balance = ? WHERE user_id = ?");
    $stmt->bind_param("di", $new_sender_balance, $sender_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update sender's balance");
    }
    
    // Update receiver's balance
    $stmt = $conn->prepare("UPDATE accounts SET current_balance = ? WHERE user_id = ?");
    $stmt->bind_param("di", $new_receiver_balance, $receiver_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update receiver's balance");
    }
    
    // Record the transaction
    $stmt = $conn->prepare("INSERT INTO transactions (sender_id, receiver_id, amount, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iids", $sender_id, $receiver_id, $amount, $description);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to record transaction");
    }
    
    $transaction_id = $conn->insert_id;
    
    // Commit the transaction
    $conn->commit();
    
    // Update session balance
    $_SESSION['current_balance'] = $new_sender_balance;
    
    // Log the successful transfer (optional)
    if (function_exists('logUserActivity')) {
        logUserActivity('transfer_money', "Transferred $" . number_format($amount, 2) . " to user ID: $receiver_id");
    }
    
    // Close statement and connection
    $stmt->close();
    $conn->close();
    
    // Redirect with success message
    $success_message = "Transfer of $" . number_format($amount, 2) . " completed successfully! Transaction ID: #" . $transaction_id;
    header("Location: ../customer/dashboard.php?success=" . urlencode($success_message));
    exit();
    
} catch (Exception $e) {
    // Rollback on any error
    $conn->rollback();
    
    // Log the error (optional)
    if (function_exists('logUserActivity')) {
        logUserActivity('transfer_failed', "Transfer failed: " . $e->getMessage());
    }
    
    // Close connection
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
    
    // Redirect with error message
    header("Location: ../customer/transfer.php?error=" . urlencode($e->getMessage()));
    exit();
}

/**
 * ============================================
 * END OF TRANSFER HANDLER
 * ============================================
 */
?>