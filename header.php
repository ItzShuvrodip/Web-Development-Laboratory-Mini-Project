<?php
// ==============================================================================
// header.php - Common Page Header, Navigation Bar & Global Alert System
// ==============================================================================
require_once __DIR__ . '/db.php'; // Include database connection and active session

// Calculate total item count currently inside session cart for the navbar badge
$total_cart_items = array_sum(array_column($_SESSION['cart'] ?? [], 'quantity'));
$current_page = basename($_SERVER['PHP_SELF'] ?? 'index.php'); // Current filename for highlighting active nav tab
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? "Canteen Pre-Order & Queue System") ?></title>
    <link rel="stylesheet" href="style.css"> <!-- Main custom styling stylesheet -->
    <script src="js/jquery-3.7.1.min.js"></script> <!-- Local jQuery library -->
</head>
<body>
    <!-- Main Top Navigation Bar -->
    <header class="main-header">
        <div class="container header-content">
            <div>
                <a href="index.php" class="brand-title">Campus Canteen</a> <!-- Canteen Brand Name -->
                <div class="brand-sub">Pre-Order &amp; Live Queue Management System</div>
            </div>
            <!-- Navigation Links: active class is added dynamically based on current page filename -->
            <nav>
                <ul class="nav-links">
                    <li><a href="index.php" class="<?= $current_page === 'index.php' ? 'active' : '' ?>">🍽️ Menu</a></li>
                    <li><a href="cart.php" class="<?= $current_page === 'cart.php' ? 'active' : '' ?>">🛒 Cart <span class="cart-badge" id="nav-cart-badge"><?= $total_cart_items ?></span></a></li>
                    <li><a href="queue.php" class="<?= $current_page === 'queue.php' ? 'active' : '' ?>">⏱️ Live Queue</a></li>
                    <li><a href="staff.php" class="<?= $current_page === 'staff.php' ? 'active' : '' ?>">⚙️ Staff Panel</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <!-- Database Driver Status Bar (Shows MySQL or SQLite dynamically) -->
        <div class="db-status-bar">
            <span><strong>Database Status:</strong> Connected via <?= htmlspecialchars($db_driver_name) ?></span>
            <span>● Canteen Ordering Live</span>
        </div>

        <!-- Global Flash Success Alert (Automatically cleared after 1 display) -->
        <?php if (!empty($_SESSION['flash'])): ?>
            <div class="alert alert-success">✅ <?= $_SESSION['flash'] ?></div>
            <?php unset($_SESSION['flash']); ?> <!-- Clear flash message -->
        <?php endif; ?>

        <!-- Global Flash Error Alert (Automatically cleared after 1 display) -->
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger">⚠️ <?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?> <!-- Clear error message -->
        <?php endif; ?>
