<?php
require_once '../includes/session_check.php';
require_once '../config/database.php';
checkCustomer();

$conn = getDBConnection();

// Get updated balance
$stmt = $conn->prepare("SELECT current_balance, account_number FROM accounts WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$account = $result->fetch_assoc();
$_SESSION['current_balance'] = $account['current_balance'];

// Get first name only
$firstName = explode(' ', $_SESSION['name'])[0];

// Get recent transactions (last 5)
$stmt = $conn->prepare("
    SELECT t.*, 
           sender.name as sender_name, 
           receiver.name as receiver_name
    FROM transactions t
    JOIN users sender ON t.sender_id = sender.user_id
    JOIN users receiver ON t.receiver_id = receiver.user_id
    WHERE t.sender_id = ? OR t.receiver_id = ?
    ORDER BY t.timestamp DESC
    LIMIT 5
");
$stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$transactions = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - SecureBank</title>
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
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transfer.php">Transfer Money</a>
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
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Balance Card -->
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="stats-card">
                    <h5 class="mb-3">Account Balance</h5>
                    <h2 class="mb-2">$<?php echo number_format($account['current_balance'], 2); ?></h2>
                    <p class="mb-0"><small>Account: <?php echo htmlspecialchars($account['account_number']); ?></small></p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-md-6 col-lg-8 mb-4">
                <div class="card card-custom">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Quick Actions</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <a href="transfer.php" class="btn btn-primary-custom w-100 py-3">
                                    <h5 class="mb-0">Transfer Money</h5>
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="transactions.php" class="btn btn-outline-primary w-100 py-3" >
                                    <h5 class="mb-0">View Transactions</h5>
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
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>From/To</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($transactions->num_rows > 0): ?>
                                        <?php while ($trans = $transactions->fetch_assoc()): ?>
                                            <?php 
                                            $is_sender = ($trans['sender_id'] == $_SESSION['user_id']);
                                            $type = $is_sender ? 'Sent' : 'Received';
                                            $party = $is_sender ? $trans['receiver_name'] : $trans['sender_name'];
                                            $badge_class = $is_sender ? 'badge-danger-custom' : 'badge-success-custom';
                                            $amount_prefix = $is_sender ? '-' : '+';
                                            ?>
                                            <tr>
                                                <td><?php echo date('M d, Y H:i', strtotime($trans['timestamp'])); ?></td>
                                                <td><span class="badge <?php echo $badge_class; ?>"><?php echo $type; ?></span></td>
                                                <td><?php echo htmlspecialchars($party); ?></td>
                                                <td class="<?php echo $is_sender ? 'text-danger' : 'text-success'; ?>">
                                                    <?php echo $amount_prefix; ?>$<?php echo number_format($trans['amount'], 2); ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No transactions yet</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($transactions->num_rows > 0): ?>
                            <div class="text-center mt-3">
                                <a href="transactions.php" class="btn btn-sm btn-outline-primary">View All Transactions</a>
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