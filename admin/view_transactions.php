<?php
require_once '../includes/session_check.php';
require_once '../config/database.php';
checkAdmin();

$conn = getDBConnection();

// Get first name only
$firstName = explode(' ', $_SESSION['name'])[0];

// Get all transactions
$transactions = $conn->query("
    SELECT t.*, 
           sender.name as sender_name, 
           sender.email as sender_email,
           receiver.name as receiver_name,
           receiver.email as receiver_email
    FROM transactions t
    JOIN users sender ON t.sender_id = sender.user_id
    JOIN users receiver ON t.receiver_id = receiver.user_id
    ORDER BY t.timestamp DESC
");

// Get statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_transactions,
        SUM(amount) as total_volume,
        AVG(amount) as avg_transaction,
        MAX(amount) as max_transaction
    FROM transactions
")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Transactions - SecureBank Admin</title>
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
            <a class="navbar-brand fw-bold" href="dashboard.php">🏦 SecureBank </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">Manage Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="view_transactions.php">All Transactions</a>
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
    <div class="container-fluid mt-5 px-4">
        <h2 class="mb-4" style="color: var(--primary-blue);">All Transactions</h2>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <h6 class="mb-2">Total Transactions</h6>
                    <h3 class="mb-0"><?php echo $stats['total_transactions']; ?></h3>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, var(--primary-green) 0%, var(--secondary-green) 100%);">
                    <h6 class="mb-2">Total Volume</h6>
                    <h3 class="mb-0">$<?php echo number_format($stats['total_volume'], 2); ?></h3>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <h6 class="mb-2">Average Transaction</h6>
                    <h3 class="mb-0">$<?php echo number_format($stats['avg_transaction'], 2); ?></h3>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #7c3aed 0%, #a78bfa 100%);">
                    <h6 class="mb-2">Largest Transaction</h6>
                    <h3 class="mb-0">$<?php echo number_format($stats['max_transaction'], 2); ?></h3>
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="card card-custom">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-custom">
                            <tr>
                                <th>ID</th>
                                <th>Date & Time</th>
                                <th>Sender</th>
                                <th>Receiver</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($transactions->num_rows > 0): ?>
                                <?php while ($trans = $transactions->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong>#<?php echo $trans['trans_id']; ?></strong></td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($trans['timestamp'])); ?><br>
                                            <small class="text-muted"><?php echo date('h:i A', strtotime($trans['timestamp'])); ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($trans['sender_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($trans['sender_email']); ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($trans['receiver_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($trans['receiver_email']); ?></small>
                                        </td>
                                        <td class="text-success fw-bold">$<?php echo number_format($trans['amount'], 2); ?></td>
                                        <td><span class="badge bg-success">Completed</span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">No transactions found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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