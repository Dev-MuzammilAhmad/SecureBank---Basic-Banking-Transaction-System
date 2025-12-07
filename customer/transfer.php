<?php
require_once '../includes/session_check.php';
require_once '../config/database.php';
checkCustomer();

$conn = getDBConnection();

// Get first name only
$firstName = explode(' ', $_SESSION['name'])[0];

// Get all users except current user
$stmt = $conn->prepare("SELECT u.user_id, u.name, a.account_number 
                        FROM users u 
                        JOIN accounts a ON u.user_id = a.user_id 
                        WHERE u.user_id != ? AND u.role = 'Customer'
                        ORDER BY u.name");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$users = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Money - SecureBank</title>
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
                        <a class="nav-link active" href="transfer.php">Transfer Money</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transactions.php">Transactions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profile</a>
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
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card card-custom shadow-lg">
                    <div class="card-body p-4">
                        <h3 class="card-title mb-4 text-center" style="color: var(--primary-blue);">Transfer Money</h3>
                        
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($_GET['error']); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Current Balance Display -->
                        <div class="alert alert-info mb-4">
                            <strong>Available Balance:</strong> $<?php echo number_format($_SESSION['current_balance'], 2); ?>
                        </div>

                        <form action="../api/transfer_handler.php" method="POST" onsubmit="return validateTransfer()">
                            <div class="mb-4">
                                <label for="receiver_id" class="form-label">Select Recipient</label>
                                <select class="form-select" id="receiver_id" name="receiver_id" required>
                                    <option value="">-- Choose Recipient --</option>
                                    <?php while ($user = $users->fetch_assoc()): ?>
                                        <option value="<?php echo $user['user_id']; ?>">
                                            <?php echo htmlspecialchars($user['name']) . ' (' . htmlspecialchars($user['account_number']) . ')'; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="amount" class="form-label">Amount ($)</label>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       step="0.01" min="0.01" max="100000" required>
                                <small class="text-muted">Maximum transfer: $100,000.00</small>
                            </div>

                            <div class="mb-4">
                                <label for="description" class="form-label">Description (Optional)</label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="3" placeholder="Add a note..."></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary-custom btn-lg">
                                    Transfer Money
                                </button>
                                <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Transfer Tips -->
                <div class="card card-custom mt-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3" style="color: var(--primary-blue);">Transfer Tips</h6>
                        <ul class="mb-0 small text-muted">
                            <li>Ensure you have sufficient balance before initiating a transfer</li>
                            <li>Double-check recipient details before confirming</li>
                            <li>Maximum single transfer limit is $100,000</li>
                            <li>Transfers are processed instantly</li>
                        </ul>
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
    <script src="../assets/js/validation.js"></script>
</body>
</html>
<?php
$conn->close();
?>