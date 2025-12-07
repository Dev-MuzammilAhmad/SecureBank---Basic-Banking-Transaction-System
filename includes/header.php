<?php
/**
 * Common Header Template
 * Includes navigation bar based on user role
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$userName = isset($_SESSION['name']) ? $_SESSION['name'] : '';

// Get first name only for cleaner display
$firstName = $userName ? explode(' ', $userName)[0] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - SecureBank' : 'SecureBank'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo isset($cssPath) ? $cssPath : '../assets/css/'; ?>custom.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo isset($cssPath) ? $cssPath : '../assets/'; ?>images/favicon.ico">
    
    <style>
        .user-profile-dropdown {
            position: relative;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            margin-right: 8px;
            vertical-align: middle;
        }
        
        .user-name-display {
            color: white;
            font-weight: 500;
            font-size: 0.95rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .user-name-display:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .dropdown-arrow {
            margin-left: 6px;
            font-size: 0.75rem;
            opacity: 0.8;
        }
        
        .navbar-custom .dropdown-menu {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            margin-top: 0.5rem;
        }
        
        .navbar-custom .dropdown-item {
            padding: 0.6rem 1.25rem;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }
        
        .navbar-custom .dropdown-item:hover {
            background-color: rgba(5, 150, 105, 0.1);
            color: var(--primary-green);
        }
        
        .navbar-custom .dropdown-divider {
            margin: 0.5rem 0;
        }
        
        .role-badge {
            font-size: 0.7rem;
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            margin-left: 0.5rem;
        }
    </style>
</head>
<body>
    
<!-- Navigation Bar -->
<?php if ($isLoggedIn): ?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="<?php echo $userRole === 'Admin' ? '../admin/dashboard.php' : '../customer/dashboard.php'; ?>">
            🏦 SecureBank
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <?php if ($userRole === 'Admin'): ?>
                    <!-- Admin Navigation -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                           href="../admin/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'active' : ''; ?>" 
                           href="../admin/manage_users.php">Manage Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'view_transactions.php' ? 'active' : ''; ?>" 
                           href="../admin/view_transactions.php">Transactions</a>
                    </li>
                <?php else: ?>
                    <!-- Customer Navigation -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                           href="../customer/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'transfer.php' ? 'active' : ''; ?>" 
                           href="../customer/transfer.php">Transfer</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'active' : ''; ?>" 
                           href="../customer/transactions.php">Transactions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" 
                           href="../customer/profile.php">Profile</a>
                    </li>
                <?php endif; ?>
                
                <!-- Professional User Dropdown -->
                <li class="nav-item dropdown user-profile-dropdown ms-3">
                    <a class="nav-link dropdown-toggle p-0" href="#" id="userDropdown" role="button" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="user-name-display">
                            <span class="user-avatar">
                                <?php echo strtoupper(substr($firstName, 0, 1)); ?>
                            </span>
                            <span><?php echo htmlspecialchars($firstName); ?></span>
                            <span class="dropdown-arrow">▼</span>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li>
                            <span class="dropdown-item-text">
                                <strong><?php echo htmlspecialchars($userName); ?></strong>
                                <span class="role-badge"><?php echo $userRole; ?></span>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php if ($userRole === 'Customer'): ?>
                            <li><a class="dropdown-item" href="../customer/profile.php">👤 My Profile</a></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="../logout.php">🚪 Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>

<!-- Main Content Wrapper -->
<div class="content-wrapper">