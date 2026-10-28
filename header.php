<?php
// ====================================================================
// header.php - Common Header and Navigation Bar
// Ensures session_start() is called before any HTML output
// ====================================================================

// Connect to database and initialize unique project session & cart normalizer
if (!isset($pdo)) {
    require_once __DIR__ . '/db.php';
}

// Ensure the cart session array exists
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Calculate the total number of items currently in the cart
$total_cart_items = 0;
foreach ($_SESSION['cart'] as $cartItem) {
    if (is_array($cartItem) && isset($cartItem['quantity'])) {
        $total_cart_items += (int)$cartItem['quantity'];
    } elseif (is_numeric($cartItem)) {
        $total_cart_items += (int)$cartItem;
    }
}

// Determine active page name for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF'] ?? 'index.php');
if ($current_page === '' || $current_page === 'index.php') {
    $current_page = 'index.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : "Canteen Pre-Order & Queue System"; ?></title>
    
    <!-- Custom CSS Stylesheet -->
    <link rel="stylesheet" href="style.css">
    
    <!-- jQuery Library (Local copy with CDN fallback) -->
    <script src="js/jquery-3.7.1.min.js"></script>
    <script>
        // Fallback to CDN if local file is missing
        if (typeof jQuery === 'undefined') {
            document.write('<script src="https://code.jquery.com/jquery-3.7.1.min.js"><\/script>');
        }
    </script>
</head>
<body>

    <!-- Main Navigation Header -->
    <header class="main-header">
        <div class="container header-content">
            <div>
                <div class="brand-title">Campus Canteen</div>
                <div class="brand-sub">Pre-Order &amp; Live Queue Management System</div>
            </div>

            <!-- Navigation Links -->
            <nav>
                <ul class="nav-links">
                    <li>
                        <a href="index.php" class="<?php echo ($current_page === 'index.php') ? 'active' : ''; ?>">
                            🍽️ Menu
                        </a>
                    </li>
                    <li>
                        <a href="cart.php" class="<?php echo ($current_page === 'cart.php') ? 'active' : ''; ?>">
                            🛒 Cart 
                            <span class="cart-badge" id="nav-cart-badge"><?php echo $total_cart_items; ?></span>
                        </a>
                    </li>
                    <li>
                        <a href="queue.php" class="<?php echo ($current_page === 'queue.php') ? 'active' : ''; ?>">
                            ⏱️ Live Queue
                        </a>
                    </li>
                    <li>
                        <a href="staff.php" class="<?php echo ($current_page === 'staff.php') ? 'active' : ''; ?>">
                            ⚙️ Staff Panel
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <!-- Database and Environment Status Banner -->
        <div class="db-status-bar">
            <span><strong>Database Status:</strong> Connected via <?php echo htmlspecialchars($db_driver_name); ?></span>
            <span>● Canteen Ordering Live</span>
        </div>
