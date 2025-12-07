<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Server-side validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        header("Location: ../register.php?error=All fields are required");
        exit();
    }
    
    if (strlen($name) < 3) {
        header("Location: ../register.php?error=Name must be at least 3 characters");
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../register.php?error=Invalid email format");
        exit();
    }
    
    if (strlen($password) < 6) {
        header("Location: ../register.php?error=Password must be at least 6 characters");
        exit();
    }
    
    if ($password !== $confirm_password) {
        header("Location: ../register.php?error=Passwords do not match");
        exit();
    }
    
    $conn = getDBConnection();
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        header("Location: ../register.php?error=Email already registered");
        exit();
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'Customer')");
    $stmt->bind_param("sss", $name, $email, $password_hash);
    
    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        
        // Generate unique account number
        $account_number = 'ACC' . str_pad($user_id + 1000000000, 10, '0', STR_PAD_LEFT);
        
        // Create account with initial balance
        $stmt = $conn->prepare("INSERT INTO accounts (user_id, account_number, current_balance) VALUES (?, ?, 10000.00)");
        $stmt->bind_param("is", $user_id, $account_number);
        $stmt->execute();
        
        header("Location: ../index.php?success=Registration successful! Please login.");
        exit();
    } else {
        header("Location: ../register.php?error=Registration failed. Please try again.");
        exit();
    }
    
    $stmt->close();
    $conn->close();
} else {
    header("Location: ../register.php");
    exit();
}
?>