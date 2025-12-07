<?php
require_once '../includes/session_check.php';
require_once '../config/database.php';
checkCustomer();

$conn = getDBConnection();

// Get first name only
$firstName = explode(' ', $_SESSION['name'])[0];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($name) || empty($email)) {
        $error = "Name and email are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Check if email exists for other users
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->bind_param("si", $email, $_SESSION['user_id']);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Email already exists";
        } else {
            $updateSuccess = true;
            
            // Update basic info
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?");
            $stmt->bind_param("ssi", $name, $email, $_SESSION['user_id']);
            
            if (!$stmt->execute()) {
                $updateSuccess = false;
                $error = "Failed to update profile";
            }
            
            // Update password if provided
            if (!empty($new_password)) {
                if (strlen($new_password) < 6) {
                    $error = "New password must be at least 6 characters";
                    $updateSuccess = false;
                } elseif ($new_password !== $confirm_password) {
                    $error = "Passwords do not match";
                    $updateSuccess = false;
                } else {
                    // Verify current password
                    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
                    $stmt->bind_param("i", $_SESSION['user_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    
                    if (!password_verify($current_password, $user['password_hash'])) {
                        $error = "Current password is incorrect";
                        $updateSuccess = false;
                    } else {
                        // Update password
                        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                        $stmt->bind_param("si", $password_hash, $_SESSION['user_id']);
                        
                        if (!$stmt->execute()) {
                            $error = "Failed to update password";
                            $updateSuccess = false;
                        }
                    }
                }
            }
            
            if ($updateSuccess) {
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                $firstName = explode(' ', $name)[0];
                $success = "Profile updated successfully";
            }
        }
    }
}

// Get user details
$stmt = $conn->prepare("
    SELECT u.*, a.account_number, a.current_balance, a.created_at as account_created
    FROM users u
    JOIN accounts a ON u.user_id = a.user_id
    WHERE u.user_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get transaction statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_transactions,
        SUM(CASE WHEN sender_id = ? THEN amount ELSE 0 END) as total_sent,
        SUM(CASE WHEN receiver_id = ? THEN amount ELSE 0 END) as total_received
    FROM transactions
    WHERE sender_id = ? OR receiver_id = ?
");
$stmt->bind_param("iiii", $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - SecureBank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <style>
        .user-profile-menu {
            position: relative;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #059669, #10b981);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            margin-right: 8px;
        }
        
        .user-display {
            color: white;
            font-weight: 500;
            font-size: 0.95rem;
            cursor: pointer;
            padding: 0.4rem 0.75rem;
            border-radius: 8px;
            transition: background 0.3s ease;
            display: inline-flex;
            align-items: center;
        }
        
        .user-display:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .dropdown-arrow {
            margin-left: 6px;
            font-size: 0.75rem;
            opacity: 0.8;
        }
        
        .dropdown-menu {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            margin-top: 0.5rem;
            min-width: 200px;
        }
        
        .dropdown-item {
            padding: 0.6rem 1.25rem;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover {
            background-color: rgba(5, 150, 105, 0.1);
            color: #059669;
        }
        
        .user-info-header {
            padding: 0.75rem 1.25rem;
            background: linear-gradient(135deg, #0a2463, #1e3a8a);
            color: white;
            border-radius: 10px 10px 0 0;
            margin: -0.5rem -0.5rem 0.5rem -0.5rem;
        }
        
        .user-info-header strong {
            display: block;
            font-size: 0.95rem;
        }
        
        .user-info-header small {
            opacity: 0.9;
            font-size: 0.8rem;
        }
        /* Hide Bootstrap's default dropdown arrow */
.navbar .dropdown-toggle::after {
    display: none;
}
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="dashboard.php">🏦 SecureBank</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transfer.php">Transfer Money</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transactions.php">Transactions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">Profile</a>
                    </li>
                    
                    <!-- Professional User Menu -->
                    <li class="nav-item dropdown user-profile-menu ms-3">
                        <a class="nav-link dropdown-toggle p-0" href="#" id="userDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false" style="text-decoration: none;">
                            <span class="user-display">
                                <span class="user-avatar"><?php echo strtoupper(substr($firstName, 0, 1)); ?></span>
                                <span><?php echo htmlspecialchars($firstName); ?></span>
                                <span class="dropdown-arrow">▼</span>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li class="user-info-header">
                                <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong>
                                <small>Customer Account</small>
                            </li>
                            <li><a class="dropdown-item" href="profile.php">👤 My Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">🚪 Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-5">
        <h2 class="mb-4" style="color: var(--primary-blue);">My Profile</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Information Card -->
            <div class="col-lg-4 mb-4">
                <div class="card card-custom">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center" 
                                 style="width: 100px; height: 100px; font-size: 48px;">
                                👤
                            </div>
                        </div>
                        <h4 class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h4>
                        <p class="text-muted mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                        <p class="mb-2"><strong>Account Number:</strong><br><?php echo htmlspecialchars($user['account_number']); ?></p>
                        <p class="mb-2"><strong>Member Since:</strong><br><?php echo date('F Y', strtotime($user['account_created'])); ?></p>
                        <p class="mb-0"><span class="badge bg-primary"><?php echo $user['role']; ?></span></p>
                    </div>
                </div>

                <!-- Account Statistics -->
                <div class="card card-custom mt-4">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Account Statistics</h5>
                        <div class="mb-3">
                            <small class="text-muted">Current Balance</small>
                            <h4 class="text-success mb-0">$<?php echo number_format($user['current_balance'], 2); ?></h4>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <small class="text-muted">Total Transactions</small>
                            <h5 class="mb-0"><?php echo $stats['total_transactions']; ?></h5>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Total Sent</small>
                            <h5 class="text-danger mb-0">$<?php echo number_format($stats['total_sent'], 2); ?></h5>
                        </div>
                        <div class="mb-0">
                            <small class="text-muted">Total Received</small>
                            <h5 class="text-success mb-0">$<?php echo number_format($stats['total_received'], 2); ?></h5>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Profile Form -->
            <div class="col-lg-8">
                <div class="card card-custom">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Edit Profile</h5>
                        
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                            </div>

                            <hr class="my-4">
                            <h6 class="mb-3">Change Password (Optional)</h6>

                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                                    <small class="text-muted">Minimum 6 characters</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                </div>
                            </div>

                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary-custom btn-lg">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-5 py-4 text-center text-muted">
        <p>&copy; 2025 SecureBank. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conn->close();
?>