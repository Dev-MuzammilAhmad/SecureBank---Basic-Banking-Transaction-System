<?php
require_once '../includes/session_check.php';
require_once '../config/database.php';
checkAdmin();

$conn = getDBConnection();

// Get first name only
$firstName = explode(' ', $_SESSION['name'])[0];

// Check if user ID is provided
if (!isset($_GET['id'])) {
    header("Location: manage_users.php?error=Invalid user ID");
    exit();
}

$user_id = intval($_GET['id']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $balance = floatval($_POST['balance']);
    
    // Validation
    if (empty($name) || empty($email) || empty($role)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Check if email exists for other users
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Email already exists";
        } else {
            // Update user
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE user_id = ?");
            $stmt->bind_param("sssi", $name, $email, $role, $user_id);
            
            if ($stmt->execute()) {
                // Update account balance
                $stmt = $conn->prepare("UPDATE accounts SET current_balance = ? WHERE user_id = ?");
                $stmt->bind_param("di", $balance, $user_id);
                $stmt->execute();
                
                // Update password if provided
                if (!empty($_POST['password'])) {
                    $password = $_POST['password'];
                    if (strlen($password) >= 6) {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                        $stmt->bind_param("si", $password_hash, $user_id);
                        $stmt->execute();
                    }
                }
                
                header("Location: manage_users.php?success=User updated successfully");
                exit();
            } else {
                $error = "Failed to update user";
            }
        }
    }
}

// Get user details
$stmt = $conn->prepare("
    SELECT u.*, a.current_balance, a.account_number
    FROM users u
    LEFT JOIN accounts a ON u.user_id = a.user_id
    WHERE u.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage_users.php?error=User not found");
    exit();
}

$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - SecureBank Admin</title>
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
        
        .navbar .dropdown-toggle::after {
            display: none;
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
                        <a class="nav-link active" href="manage_users.php">Manage Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_transactions.php">All Transactions</a>
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
                                <small>Admin Account</small>
                            </li>
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
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card card-custom shadow-lg">
                    <div class="card-body p-4">
                        <h3 class="card-title mb-4 text-center" style="color: var(--primary-blue);">Edit User</h3>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">User ID</label>
                                <input type="text" class="form-control" value="<?php echo $user['user_id']; ?>" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Account Number</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['account_number']); ?>" disabled>
                            </div>

                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">New Password (leave empty to keep current)</label>
                                <input type="password" class="form-control" id="password" name="password">
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>

                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="Customer" <?php echo $user['role'] === 'Customer' ? 'selected' : ''; ?>>Customer</option>
                                    <option value="Admin" <?php echo $user['role'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="balance" class="form-label">Account Balance ($)</label>
                                <input type="number" class="form-control" id="balance" name="balance" 
                                       value="<?php echo $user['current_balance']; ?>" step="0.01" min="0" required>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary-custom btn-lg">Update User</button>
                                <a href="manage_users.php" class="btn btn-outline-secondary">Cancel</a>
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