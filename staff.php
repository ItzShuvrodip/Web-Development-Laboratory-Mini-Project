<?php
// ==============================================================================
// staff.php - Kitchen Staff & Operations Portal (Status & Stock Management)
// ==============================================================================
require_once "db.php"; // Connect to DB and access active session

// === 1. STAFF LOGOUT ACTION ===
if (($_GET['action'] ?? '') === 'logout') {
    unset($_SESSION['is_staff_logged_in']); // Clear staff session flag
    $_SESSION['flash'] = "Logged out successfully.";
    header("Location: staff.php"); exit;
}

// === 2. STAFF LOGIN AUTHENTICATION (admin / admin) ===
// [SPOT-CHANGE]: Change staff username and password here
if (($_POST['action'] ?? '') === 'login') {
    if (($_POST['username'] ?? '') === 'admin' && ($_POST['password'] ?? '') === 'admin') {
        $_SESSION['is_staff_logged_in'] = true; // Mark session as logged in
        $_SESSION['flash'] = "Welcome back, Admin!";
    } else {
        $_SESSION['error'] = "Invalid credentials! (Use admin / admin)";
    }
    header("Location: staff.php"); exit;
}

// === 3. LOGIN FORM (DISPLAYED IF NOT AUTHENTICATED) ===
if (empty($_SESSION['is_staff_logged_in'])) {
    $page_title = "Staff Login - Canteen System";
    require_once "header.php";
    ?>
    <div class="login-card">
        <div class="text-center mb-lg">
            <div class="login-icon">🔐</div>
            <h2>Staff Portal Login</h2>
            <p class="text-muted text-sm">Sign in to manage kitchen orders and food stock.</p>
        </div>
        <form action="staff.php" method="post">
            <input type="hidden" name="action" value="login">
            <div class="mb-sm">
                <label class="form-label">Username:</label>
                <input type="text" name="username" required placeholder="admin" class="form-input">
            </div>
            <div class="mb-lg">
                <label class="form-label">Password:</label>
                <input type="password" name="password" required placeholder="admin" class="form-input">
            </div>
            <button type="submit" class="btn btn-primary w-100">Log In to Staff Panel &rarr;</button>
        </form>
        <div class="login-hint">
            Default Credentials: <b>admin</b> / <b>admin</b>
        </div>
    </div>
    <?php require_once "footer.php"; exit;
}

// === 4. PROCESS AUTHENTICATED STAFF ACTIONS ===
$action = $_POST['action'] ?? '';

// Action 1: Update order status (Received -> Preparing -> Ready -> Completed)
if ($action === 'update_order_status') {
    $oid = (int)($_POST['order_id'] ?? 0);          // Target order ID
    $status = $_POST['new_status'] ?? 'Received';   // Selected status from dropdown
    $stmt = $pdo->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    $stmt->execute([$status, $oid]);
    $_SESSION['flash'] = "Order status updated to '$status'!";
    header("Location: staff.php"); exit;
}

// Action 2: Toggle menu item stock availability (1 -> 0 or 0 -> 1)
if ($action === 'toggle_availability') {
    $iid = (int)($_POST['item_id'] ?? 0);           // Target food item ID
    // 1 - is_available flips 1 to 0 and 0 to 1 in a single atomic SQL update
    $pdo->exec("UPDATE menu_items SET is_available = 1 - is_available WHERE item_id = $iid");
    $_SESSION['flash'] = "Menu item availability toggled!";
    header("Location: staff.php"); exit;
}

// Action 3: Reset demo data (restores default sample items & clears orders)
if ($action === 'reset_sample_data') {
    $pdo->exec("DELETE FROM order_items; DELETE FROM orders; DELETE FROM menu_items;"); // Clean all tables
    $stmt = $pdo->prepare("INSERT INTO menu_items (name, category, price, description, is_available) VALUES (?, ?, ?, ?, ?)");
    foreach ($sample_items as $item) $stmt->execute($item); // Re-insert initial $sample_items from db.php
    $_SESSION['flash'] = "Database reset to default menu and empty orders!";
    header("Location: staff.php"); exit;
}

// === 5. FETCH DATA FOR STAFF DASHBOARD ===
$orders = $pdo->query("SELECT * FROM orders ORDER BY order_id DESC LIMIT 30")->fetchAll();
$menu_items = $pdo->query("SELECT * FROM menu_items ORDER BY category, name")->fetchAll();

$page_title = "Staff & Kitchen Dashboard";
require_once "header.php"; // Load common navigation header
?>

<!-- === 6. STAFF DASHBOARD TOP BAR & CONTROLS === -->
<div class="flex-between mb-lg">
    <div>
        <h1 class="page-title mb-xs">Canteen Staff Control</h1>
        <p class="page-description mb-0">Logged in as <strong>admin</strong> &bull; Manage live orders and food stock availability.</p>
    </div>
    <div class="flex-gap-sm">
        <!-- 1-Click Reset Demo Data Button -->
        <form action="staff.php" method="post" onsubmit="return confirm('Reset all demo orders and restore default menu?');">
            <input type="hidden" name="action" value="reset_sample_data">
            <button type="submit" class="btn btn-secondary btn-sm">🔄 Reset Demo Data</button>
        </form>
        <!-- Logout Button -->
        <a href="staff.php?action=logout" class="btn btn-danger btn-sm" onclick="return confirm('Log out?');">🚪 Logout</a>
    </div>
</div>

<!-- === 7. KITCHEN ORDER STATUS MANAGER TABLE === -->
<div class="cart-table-card">
    <h3 class="mb-sm">1. Live Kitchen Order Status Manager</h3>
    <div class="table-responsive">
        <table class="data-table">
            <thead><tr><th>Order No</th><th>Customer</th><th>Total</th><th>Time</th><th>Current Status</th><th>Update Status</th></tr></thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="6" class="table-empty-td">No orders placed yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $ord): $st = strtolower($ord['order_status']); ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($ord['order_number']) ?></strong></td>
                            <td><?= htmlspecialchars($ord['customer_name']) ?><br><small class="text-muted"><?= htmlspecialchars($ord['contact_no']) ?></small></td>
                            <td>₹<?= number_format($ord['total_amount'], 2) ?></td>
                            <td><small><?= htmlspecialchars($ord['created_at']) ?></small></td>
                            <td><span class="status-pill status-<?= $st ?>"><?= htmlspecialchars($ord['order_status']) ?></span></td>
                            <td>
                                <!-- Form updating order lifecycle status -->
                                <form action="staff.php" method="post" class="flex-gap-xs">
                                    <input type="hidden" name="action" value="update_order_status">
                                    <input type="hidden" name="order_id" value="<?= $ord['order_id'] ?>">
                                    <select name="new_status" class="form-select">
                                        <!-- [SPOT-CHANGE]: Status lifecycle options -->
                                        <?php foreach (['Received', 'Preparing', 'Ready', 'Completed'] as $opt): ?>
                                            <option value="<?= $opt ?>" <?= $ord['order_status'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm">Set</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- === 8. MENU ITEM AVAILABILITY MANAGER TABLE === -->
<div class="cart-table-card">
    <h3 class="mb-sm">2. Menu Item Availability Manager</h3>
    <div class="table-responsive">
        <table class="data-table">
            <thead><tr><th>Item Name</th><th>Category</th><th>Price</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach ($menu_items as $m): $avail = (int)$m['is_available'] === 1; ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
                        <td><?= htmlspecialchars($m['category']) ?></td>
                        <td>₹<?= number_format($m['price'], 2) ?></td>
                        <td><span class="badge <?= $avail ? 'badge-available' : 'badge-unavailable' ?>"><?= $avail ? '● In Stock' : '✕ Out of Stock' ?></span></td>
                        <td>
                            <!-- 1-Click Form to toggle availability -->
                            <form action="staff.php" method="post">
                                <input type="hidden" name="action" value="toggle_availability">
                                <input type="hidden" name="item_id" value="<?= $m['item_id'] ?>">
                                <button type="submit" class="btn <?= $avail ? 'btn-danger' : 'btn-success' ?> btn-sm">
                                    <?= $avail ? 'Mark Out of Stock' : 'Mark Available' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once "footer.php"; ?> <!-- Load common footer -->
