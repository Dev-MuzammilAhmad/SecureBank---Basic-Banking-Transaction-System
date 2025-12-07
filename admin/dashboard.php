<?php
require_once '../includes/session_check.php';
require_once '../config/database.php';
checkAdmin();

$conn = getDBConnection();

// Get first name only
$firstName = explode(' ', $_SESSION['name'])[0];

// Get statistics
$total_customers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'Customer'")->fetch_assoc()['count'];
$total_transactions = $conn->query("SELECT COUNT(*) as count FROM transactions")->fetch_assoc()['count'];
$total_balance = $conn->query("SELECT SUM(current_balance) as total FROM accounts")->fetch_assoc()['total'];
$today_transactions = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE DATE(timestamp) = CURDATE()")->fetch_assoc()['count'];

// Get recent transactions
$recent_transactions = $conn->query("
    SELECT t.*, 
           sender.name as sender_name, 
           receiver.name as receiver_name
    FROM transactions t
    JOIN users sender ON t.sender_id = sender.user_id
    JOIN users receiver ON t.receiver_id = receiver.user_id
    ORDER BY t.timestamp DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SecureBank</title>
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
        
        /* Hide Bootstrap's default dropdown arrow */
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
            <a class="navbar-brand fw-bold" href="#">🏦 SecureBank</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">Manage Users</a>
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
        <h2 class="mb-4" style="color: var(--primary-blue);">Admin Dashboard</h2>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="stats-card">
                    <h6 class="mb-2">Total Customers</h6>
                    <h2 class="mb-0"><?php echo $total_customers; ?></h2>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, var(--primary-green) 0%, var(--secondary-green) 100%);">
                    <h6 class="mb-2">Total Transactions</h6>
                    <h2 class="mb-0"><?php echo $total_transactions; ?></h2>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="stats-card">
                    <h6 class="mb-2">Total Balance</h6>
                    <h2 class="mb-0">$<?php echo number_format($total_balance, 2); ?></h2>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #7c3aed 0%, #a78bfa 100%);">
                    <h6 class="mb-2">Today's Transactions</h6>
                    <h2 class="mb-0"><?php echo $today_transactions; ?></h2>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-custom">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Quick Actions</h5>
                        <div class="row">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <a href="manage_users.php" class="btn btn-primary-custom w-100 py-3">
                                    Manage Users
                                </a>
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <a href="create_user.php" class="btn btn-outline-primary w-100 py-3" style="border-color: var(--primary-green);">
                                    Add New User
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="view_transactions.php" class="btn btn-outline-primary w-100 py-3" style="border-color: var(--primary-green); ">
                                    View All Transactions
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="row">
            <div class="col-12">
                <div class="card card-custom">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Recent Transactions</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-custom">
                                    <tr>
                                        <th>ID</th>
                                        <th>Date & Time</th>
                                        <th>Sender</th>
                                        <th>Receiver</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recent_transactions->num_rows > 0): ?>
                                        <?php while ($trans = $recent_transactions->fetch_assoc()): ?>
                                            <tr>
                                                <td>#<?php echo $trans['trans_id']; ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($trans['timestamp'])); ?></td>
                                                <td><?php echo htmlspecialchars($trans['sender_name']); ?></td>
                                                <td><?php echo htmlspecialchars($trans['receiver_name']); ?></td>
                                                <td class="text-success fw-bold">$<?php echo number_format($trans['amount'], 2); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No transactions yet</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($recent_transactions->num_rows > 0): ?>
                            <div class="text-center mt-3">
                                <a href="view_transactions.php" class="btn btn-sm btn-outline-primary">View All Transactions</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-5 py-4 text-center text-muted">
        <p>&copy; 2025 SecureBank. All rights reserved. | Web Engineering Lab - CSC-314(L)</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conn->close();
?>