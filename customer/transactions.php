<?php
require_once '../includes/session_check.php';
require_once '../config/database.php';
checkCustomer();

$conn = getDBConnection();

// Get first name only
$firstName = explode(' ', $_SESSION['name'])[0];

// Get all transactions for current user
$stmt = $conn->prepare("
    SELECT t.*, 
           sender.name as sender_name, 
           receiver.name as receiver_name,
           sender.email as sender_email,
           receiver.email as receiver_email
    FROM transactions t
    JOIN users sender ON t.sender_id = sender.user_id
    JOIN users receiver ON t.receiver_id = receiver.user_id
    WHERE t.sender_id = ? OR t.receiver_id = ?
    ORDER BY t.timestamp DESC
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
    <title>Transaction History - SecureBank</title>
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
                        <a class="nav-link active" href="transactions.php">Transactions</a>
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
        <h2 class="mb-4" style="color: var(--primary-blue);">Transaction History</h2>

        <div class="card card-custom">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-custom">
                            <tr>
                                <th>Transaction ID</th>
                                <th>Date & Time</th>
                                <th>Type</th>
                                <th>Party</th>
                                <th>Amount</th>
                                <th>Status</th>
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
                                    $amount_class = $is_sender ? 'text-danger' : 'text-success';
                                    ?>
                                    <tr>
                                        <td><strong>#<?php echo $trans['trans_id']; ?></strong></td>
                                        <td><?php echo date('M d, Y', strtotime($trans['timestamp'])); ?><br>
                                            <small class="text-muted"><?php echo date('h:i A', strtotime($trans['timestamp'])); ?></small>
                                        </td>
                                        <td><span class="badge <?php echo $badge_class; ?>"><?php echo $type; ?></span></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($party); ?></strong><br>
                                            <small class="text-muted"><?php echo $is_sender ? htmlspecialchars($trans['receiver_email']) : htmlspecialchars($trans['sender_email']); ?></small>
                                        </td>
                                        <td class="<?php echo $amount_class; ?> fw-bold">
                                            <?php echo $amount_prefix; ?>$<?php echo number_format($trans['amount'], 2); ?>
                                        </td>
                                        <td><span class="badge bg-success">Completed</span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="text-muted">
                                            <h5>No Transactions Yet</h5>
                                            <p>Your transaction history will appear here once you start making transfers.</p>
                                            <a href="transfer.php" class="btn btn-primary-custom mt-3">Make Your First Transfer</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($transactions->num_rows > 0): ?>
            <div class="card card-custom mt-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3" style="color: var(--primary-blue);">Transaction Summary</h6>
                    <?php
                    // Calculate summary
                    $stmt = $conn->prepare("
                        SELECT 
                            SUM(CASE WHEN sender_id = ? THEN amount ELSE 0 END) as total_sent,
                            SUM(CASE WHEN receiver_id = ? THEN amount ELSE 0 END) as total_received,
                            COUNT(*) as total_transactions
                        FROM transactions
                        WHERE sender_id = ? OR receiver_id = ?
                    ");
                    $stmt->bind_param("iiii", $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']);
                    $stmt->execute();
                    $summary = $stmt->get_result()->fetch_assoc();
                    ?>
                    <div class="row text-center">
                        <div class="col-md-4">
                            <p class="mb-1 text-muted">Total Sent</p>
                            <h4 class="text-danger">$<?php echo number_format($summary['total_sent'], 2); ?></h4>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1 text-muted">Total Received</p>
                            <h4 class="text-success">$<?php echo number_format($summary['total_received'], 2); ?></h4>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1 text-muted">Total Transactions</p>
                            <h4 style="color: var(--primary-blue);"><?php echo $summary['total_transactions']; ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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