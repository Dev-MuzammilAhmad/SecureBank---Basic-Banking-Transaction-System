-- ============================================
-- Basic Banking Transaction System
-- Database Schema with Sample Data
-- Course: Web Engineering Lab - CSC-314(L)
-- Session: Fall 2025
-- ============================================

-- Drop database if exists (for clean installation)
DROP DATABASE IF EXISTS banking_system;

-- Create database
CREATE DATABASE banking_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE banking_system;

-- ============================================
-- TABLE 1: USERS
-- Stores user account information
-- ============================================

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Customer') DEFAULT 'Customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE 2: ACCOUNTS
-- Stores account details and balances
-- ============================================

CREATE TABLE accounts (
    account_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    account_number VARCHAR(20) UNIQUE NOT NULL,
    current_balance DECIMAL(15, 2) DEFAULT 10000.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_account_number (account_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE 3: TRANSACTIONS
-- Stores all money transfer records
-- ============================================

CREATE TABLE transactions (
    trans_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_sender (sender_id),
    INDEX idx_receiver (receiver_id),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- INSERT SAMPLE DATA
-- ============================================

-- Insert Admin User
-- Password: password123 (hashed using bcrypt)
INSERT INTO users (name, email, password_hash, role) VALUES
('Admin User', 'admin@bank.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin');

-- Insert Sample Customers
-- All passwords are: password123
INSERT INTO users (name, email, password_hash, role) VALUES
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Customer'),
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Customer'),
('Bob Wilson', 'bob@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Customer'),
('Alice Johnson', 'alice@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Customer'),
('Charlie Brown', 'charlie@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Customer');

-- Create Accounts for all users
INSERT INTO accounts (user_id, account_number, current_balance) VALUES
(1, 'ACC1000000001', 50000.00),  -- Admin account
(2, 'ACC1000000002', 15000.00),  -- John Doe
(3, 'ACC1000000003', 20000.00),  -- Jane Smith
(4, 'ACC1000000004', 12000.00),  -- Bob Wilson
(5, 'ACC1000000005', 18500.00),  -- Alice Johnson
(6, 'ACC1000000006', 25000.00);  -- Charlie Brown

-- Insert Sample Transactions
INSERT INTO transactions (sender_id, receiver_id, amount, description) VALUES
(2, 3, 500.00, 'Payment for dinner'),
(3, 4, 1000.00, 'Loan repayment'),
(4, 2, 750.00, 'Birthday gift'),
(5, 6, 2000.00, 'Monthly rent'),
(6, 5, 500.00, 'Grocery shopping'),
(2, 5, 1500.00, 'Freelance project payment'),
(3, 2, 300.00, 'Book purchase'),
(4, 6, 2500.00, 'Car repair contribution'),
(5, 3, 800.00, 'Utility bill sharing'),
(6, 4, 1200.00, 'Event tickets');

-- ============================================
-- VIEWS (Optional - for reporting)
-- ============================================

-- View: User Account Summary
CREATE VIEW user_account_summary AS
SELECT 
    u.user_id,
    u.name,
    u.email,
    u.role,
    a.account_number,
    a.current_balance,
    u.created_at
FROM users u
LEFT JOIN accounts a ON u.user_id = a.user_id
ORDER BY u.created_at DESC;

-- View: Transaction Details
CREATE VIEW transaction_details AS
SELECT 
    t.trans_id,
    t.amount,
    t.description,
    t.timestamp,
    sender.name AS sender_name,
    sender.email AS sender_email,
    sender_acc.account_number AS sender_account,
    receiver.name AS receiver_name,
    receiver.email AS receiver_email,
    receiver_acc.account_number AS receiver_account
FROM transactions t
JOIN users sender ON t.sender_id = sender.user_id
JOIN users receiver ON t.receiver_id = receiver.user_id
LEFT JOIN accounts sender_acc ON sender.user_id = sender_acc.user_id
LEFT JOIN accounts receiver_acc ON receiver.user_id = receiver_acc.user_id
ORDER BY t.timestamp DESC;

-- ============================================
-- STORED PROCEDURES (Optional)
-- ============================================

-- Procedure: Get User Balance
DELIMITER //
CREATE PROCEDURE GetUserBalance(IN userId INT)
BEGIN
    SELECT 
        u.name,
        a.account_number,
        a.current_balance
    FROM users u
    JOIN accounts a ON u.user_id = a.user_id
    WHERE u.user_id = userId;
END //
DELIMITER ;

-- Procedure: Get User Transaction History
DELIMITER //
CREATE PROCEDURE GetUserTransactionHistory(IN userId INT)
BEGIN
    SELECT 
        t.trans_id,
        t.amount,
        t.description,
        t.timestamp,
        CASE 
            WHEN t.sender_id = userId THEN 'Sent'
            ELSE 'Received'
        END AS transaction_type,
        CASE 
            WHEN t.sender_id = userId THEN receiver.name
            ELSE sender.name
        END AS party_name
    FROM transactions t
    JOIN users sender ON t.sender_id = sender.user_id
    JOIN users receiver ON t.receiver_id = receiver.user_id
    WHERE t.sender_id = userId OR t.receiver_id = userId
    ORDER BY t.timestamp DESC;
END //
DELIMITER ;

-- ============================================
-- TRIGGERS (For audit logging - Optional)
-- ============================================

-- Create audit log table
CREATE TABLE audit_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(50),
    operation VARCHAR(20),
    record_id INT,
    old_value TEXT,
    new_value TEXT,
    changed_by INT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Trigger: Log balance changes
DELIMITER //
CREATE TRIGGER after_balance_update
AFTER UPDATE ON accounts
FOR EACH ROW
BEGIN
    IF NEW.current_balance != OLD.current_balance THEN
        INSERT INTO audit_log (table_name, operation, record_id, old_value, new_value)
        VALUES ('accounts', 'UPDATE', NEW.account_id, OLD.current_balance, NEW.current_balance);
    END IF;
END //
DELIMITER ;

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================

-- Additional indexes for better query performance
CREATE INDEX idx_transaction_date ON transactions(timestamp);
CREATE INDEX idx_user_created ON users(created_at);
CREATE INDEX idx_balance ON accounts(current_balance);

-- ============================================
-- SAMPLE QUERIES FOR TESTING
-- ============================================

-- Query 1: Get all customers with their balances
-- SELECT * FROM user_account_summary WHERE role = 'Customer';

-- Query 2: Get total transaction volume
-- SELECT SUM(amount) as total_volume FROM transactions;

-- Query 3: Get user's transaction count
-- SELECT 
--     u.name,
--     COUNT(t.trans_id) as transaction_count
-- FROM users u
-- LEFT JOIN transactions t ON u.user_id = t.sender_id OR u.user_id = t.receiver_id
-- GROUP BY u.user_id, u.name;

-- Query 4: Top 5 users by balance
-- SELECT 
--     u.name,
--     a.current_balance
-- FROM users u
-- JOIN accounts a ON u.user_id = a.user_id
-- ORDER BY a.current_balance DESC
-- LIMIT 5;

-- ============================================
-- DEFAULT LOGIN CREDENTIALS
-- ============================================

-- Admin:
-- Email: admin@bank.com
-- Password: password123

-- Customers:
-- Email: john@example.com, jane@example.com, bob@example.com, etc.
-- Password: password123 (for all)

-- ============================================
-- DATABASE STATISTICS
-- ============================================

SELECT 'Database Created Successfully!' AS Status;
SELECT COUNT(*) AS Total_Users FROM users;
SELECT COUNT(*) AS Total_Accounts FROM accounts;
SELECT COUNT(*) AS Total_Transactions FROM transactions;
SELECT SUM(current_balance) AS Total_System_Balance FROM accounts;

-- ============================================
-- END OF SQL FILE
-- ============================================