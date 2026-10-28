<?php
// ====================================================================
// staff.php - Canteen Staff & Kitchen Operations Dashboard
// Features: Protected Login (admin / admin), Live Order Status Management,
//           Menu Item Stock Availability Toggle, Demonstration Reset
// ====================================================================

// Connect to database and initialize unique project session & cart normalizer
require_once "db.php";

// --------------------------------------------------------------------
// Section 1: Handle Staff Logout
// --------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['is_staff_logged_in']);
    $_SESSION['flash_message'] = "Logged out of staff panel successfully.";
    header("Location: staff.php");
    exit;
}

// --------------------------------------------------------------------
// Section 2: Handle Staff Login Authentication (admin / admin)
// --------------------------------------------------------------------
$login_error = "";
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    // Verify credentials: admin / admin
    if ($username === 'admin' && $password === 'admin') {
        $_SESSION['is_staff_logged_in'] = true;
        $_SESSION['flash_message'] = "Welcome back, Canteen Admin!";
        header("Location: staff.php");
        exit;
    } else {
        $login_error = "Invalid Username or Password! (Hint: use admin / admin)";
    }
}

// --------------------------------------------------------------------
// Section 3: If Not Logged In, Display Staff Login Screen
// --------------------------------------------------------------------
if (empty($_SESSION['is_staff_logged_in'])) {
    $page_title = "Staff Login - Canteen System";
    require_once "header.php";

    // Read flash message
    $flash_msg = "";
    if (isset($_SESSION['flash_message'])) {
        $flash_msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
    }
    ?>

    <div style="max-width: 440px; margin: 40px auto; background: #ffffff; border: 1px solid #f0e4d2; border-radius: 14px; padding: 32px; box-shadow: 0 4px 15px rgba(0,0,0,0.06);">
        <div style="text-align: center; margin-bottom: 25px;">
            <div style="font-size: 3rem; margin-bottom: 8px;">🔐</div>
            <h2 style="color: #2d2218; font-size: 1.5rem; margin-bottom: 6px;">Staff Portal Login</h2>
            <p style="color: #6b5a4a; font-size: 0.9rem;">Sign in to manage kitchen orders and food stock availability.</p>
        </div>

        <?php if (!empty($flash_msg)): ?>
            <div class="alert alert-success" style="padding: 10px 14px; font-size: 0.9rem;">
                ✅ <?php echo htmlspecialchars($flash_msg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($login_error)): ?>
            <div class="alert alert-danger" style="padding: 10px 14px; font-size: 0.9rem;">
                ⚠️ <?php echo htmlspecialchars($login_error); ?>
            </div>
        <?php endif; ?>

        <form action="staff.php" method="post">
            <input type="hidden" name="action" value="login">

            <div style="margin-bottom: 16px;">
                <label for="username" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 6px; color: #2d2218;">
                    Username / Staff ID:
                </label>
                <input type="text" id="username" name="username" required placeholder="admin"
                       style="width: 100%; padding: 10px 12px; border: 1px solid #d9c7b0; border-radius: 8px; font-size: 0.95rem; color: #2d2218;">
            </div>

            <div style="margin-bottom: 20px;">
                <label for="password" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 6px; color: #2d2218;">
                    Password:
                </label>
                <input type="password" id="password" name="password" required placeholder="•••••"
                       style="width: 100%; padding: 10px 12px; border: 1px solid #d9c7b0; border-radius: 8px; font-size: 0.95rem; color: #2d2218;">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 11px; font-size: 1rem;">
                Log In to Staff Panel &rarr;
            </button>
        </form>

        <div style="margin-top: 20px; padding: 12px; background: #fdfaf6; border: 1px dashed #d9c7b0; border-radius: 8px; text-align: center; font-size: 0.85rem; color: #6b5a4a;">
            <strong>Default Credentials:</strong><br>
            Username: <code style="background: #f6e3c8; color: #8a4b08; padding: 2px 6px; border-radius: 4px; font-weight: 700;">admin</code> &nbsp;|&nbsp; 
            Password: <code style="background: #f6e3c8; color: #8a4b08; padding: 2px 6px; border-radius: 4px; font-weight: 700;">admin</code>
        </div>
    </div>

    <?php
    require_once "footer.php";
    exit;
}

// --------------------------------------------------------------------
// Section 4: Process Authenticated Staff Actions via POST
// --------------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';

    // Action 1: Update an Order's Status
    if ($action === 'update_order_status') {
        $order_id   = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
        $new_status = isset($_POST['new_status']) ? trim($_POST['new_status']) : '';

        $allowed_statuses = ['Received', 'Preparing', 'Ready', 'Completed'];

        if ($order_id > 0 && in_array($new_status, $allowed_statuses)) {
            $stmtGet = $pdo->prepare("SELECT order_number FROM orders WHERE order_id = :id");
            $stmtGet->execute([':id' => $order_id]);
            $ordData = $stmtGet->fetch();
            $token = $ordData ? $ordData['order_number'] : "#$order_id";

            $stmt = $pdo->prepare("UPDATE orders SET order_status = :status WHERE order_id = :id");
            $stmt->execute([
                ':status' => $new_status,
                ':id'     => $order_id
            ]);
            $_SESSION['flash_message'] = "Order $token status updated to '$new_status'!";
        }
        header("Location: staff.php");
        exit;
    }

    // Action 2: Toggle Menu Item Availability
    if ($action === 'toggle_availability') {
        $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        if ($item_id > 0) {
            $itemStmt = $pdo->prepare("SELECT name, is_available FROM menu_items WHERE item_id = :id");
            $itemStmt->execute([':id' => $item_id]);
            $item = $itemStmt->fetch();

            if ($item) {
                $newAvail = ((int)$item['is_available'] === 1) ? 0 : 1;
                $updateStmt = $pdo->prepare("UPDATE menu_items SET is_available = :avail WHERE item_id = :id");
                $updateStmt->execute([':avail' => $newAvail, ':id' => $item_id]);

                $statusLabel = ($newAvail === 1) ? "Available (In Stock)" : "Out of Stock";
                $_SESSION['flash_message'] = "'" . $item['name'] . "' marked as $statusLabel!";
            }
        }
        header("Location: staff.php");
        exit;
    }

    // Action 3: Reset / Seed Sample Data
    if ($action === 'reset_sample_data') {
        $pdo->exec("DELETE FROM order_items");
        $pdo->exec("DELETE FROM orders");
        $pdo->exec("DELETE FROM menu_items");

        // Reset auto-increment sequence counters
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            @$pdo->exec("DELETE FROM sqlite_sequence WHERE name IN ('menu_items', 'orders', 'order_items')");
        } else {
            @$pdo->exec("ALTER TABLE order_items AUTO_INCREMENT = 1");
            @$pdo->exec("ALTER TABLE orders AUTO_INCREMENT = 1");
            @$pdo->exec("ALTER TABLE menu_items AUTO_INCREMENT = 1");
        }

        // Re-insert standard sample items
        $insertStmt = $pdo->prepare("
            INSERT INTO menu_items (name, category, price, description, is_available) 
            VALUES (:name, :category, :price, :description, :is_available)
        ");

        $sampleItems = [
            ['name' => 'Crispy Veg Samosa (2 pcs)', 'category' => 'Snacks', 'price' => 25.00, 'description' => 'Golden fried potato samosas served with tangy mint chutney', 'is_available' => 1],
            ['name' => 'South Indian Masala Dosa', 'category' => 'Meals', 'price' => 60.00, 'description' => 'Crispy dosa filled with spiced potato masala, served with hot sambar', 'is_available' => 1],
            ['name' => 'Paneer Butter Roll', 'category' => 'Snacks', 'price' => 70.00, 'description' => 'Fresh wheat wrap stuffed with spiced cottage cheese and onions', 'is_available' => 1],
            ['name' => 'Special Veg Thali', 'category' => 'Meals', 'price' => 110.00, 'description' => '2 Rotis, Steamed Rice, Dal Tadka, Paneer Sabzi, Salad & Sweet', 'is_available' => 1],
            ['name' => 'Veg Hakka Noodles', 'category' => 'Meals', 'price' => 80.00, 'description' => 'Stir-fried noodles with crunchy capsicum, cabbage and carrots', 'is_available' => 0],
            ['name' => 'Cold Coffee with Ice Cream', 'category' => 'Beverages', 'price' => 45.00, 'description' => 'Thick creamy espresso blend topped with vanilla scoop', 'is_available' => 1],
            ['name' => 'Masala Cutting Chai', 'category' => 'Beverages', 'price' => 15.00, 'description' => 'Freshly brewed aromatic tea with ginger and cardamom', 'is_available' => 1],
            ['name' => 'Fresh Lemon Soda', 'category' => 'Beverages', 'price' => 30.00, 'description' => 'Chilled sparkling soda with fresh lime, mint and rock salt', 'is_available' => 1]
        ];

        foreach ($sampleItems as $item) {
            $insertStmt->execute($item);
        }

        $_SESSION['flash_message'] = "Database reset to fresh demonstration state with default menu items!";
        header("Location: staff.php");
        exit;
    }
}

// --------------------------------------------------------------------
// Section 5: Authenticated Staff Dashboard View
// --------------------------------------------------------------------
$orders = $pdo->query("SELECT * FROM orders ORDER BY order_id DESC LIMIT 30")->fetchAll();
$menu_items = $pdo->query("SELECT * FROM menu_items ORDER BY category, name")->fetchAll();

$page_title = "Canteen Staff & Kitchen Panel";
require_once "header.php";

$flash_success = "";
if (isset($_SESSION['flash_message'])) {
    $flash_success = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 20px;">
    <div>
        <h1 class="page-title" style="margin-bottom: 4px;">Canteen Staff &amp; Kitchen Control</h1>
        <p class="page-description" style="margin-bottom: 0;">Logged in as <strong>admin</strong> &bull; Manage live orders and food stock availability.</p>
    </div>

    <!-- Staff Controls: Reset Demo Data & Logout -->
    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <form action="staff.php" method="post" onsubmit="return confirm('Reset all orders and restore default menu?');" style="margin: 0;">
            <input type="hidden" name="action" value="reset_sample_data">
            <button type="submit" class="btn btn-secondary btn-sm">
                🔄 Reset Demo Data
            </button>
        </form>

        <a href="staff.php?action=logout" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to log out?');">
            🚪 Logout
        </a>
    </div>
</div>

<!-- Success Message Banner -->
<?php if (!empty($flash_success)): ?>
    <div class="alert alert-success">
        ✅ <?php echo htmlspecialchars($flash_success); ?>
    </div>
<?php endif; ?>

<!-- Section 1: Kitchen Order Status Manager -->
<div class="cart-table-card">
    <h3 style="margin-bottom: 8px; color: #2d2218;">1. Live Kitchen Order Status Manager</h3>
    <p style="color: #6b5a4a; font-size: 0.9rem; margin-bottom: 15px;">
        Advance orders through the lifecycle: <strong>Received &rarr; Preparing &rarr; Ready &rarr; Completed</strong>.
    </p>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order No</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Time</th>
                    <th>Current Status</th>
                    <th>Update Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #8a7968; padding: 25px;">
                            No orders placed yet. Place an order from the <a href="index.php">Menu</a> to test!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $ord): ?>
                        <?php 
                            $st = strtolower($ord['order_status']);
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($ord['order_number']); ?></strong>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($ord['customer_name']); ?><br>
                                <small style="color: #6b5a4a;"><?php echo htmlspecialchars($ord['contact_no']); ?></small>
                            </td>
                            <td>₹<?php echo number_format($ord['total_amount'], 2); ?></td>
                            <td><small><?php echo htmlspecialchars($ord['created_at']); ?></small></td>
                            <td>
                                <span class="status-pill status-<?php echo htmlspecialchars($st); ?>">
                                    <?php echo htmlspecialchars($ord['order_status']); ?>
                                </span>
                            </td>
                            <td>
                                <!-- Status change form with PRG -->
                                <form action="staff.php" method="post" style="display: flex; gap: 5px; align-items: center;">
                                    <input type="hidden" name="action" value="update_order_status">
                                    <input type="hidden" name="order_id" value="<?php echo (int)$ord['order_id']; ?>">
                                    
                                    <select name="new_status" style="padding: 6px 8px; border-radius: 6px; border: 1px solid #d9c7b0; font-size: 0.85rem; color: #2d2218; background-color: #ffffff;">
                                        <option value="Received" <?php echo ($ord['order_status'] === 'Received') ? 'selected' : ''; ?>>Received</option>
                                        <option value="Preparing" <?php echo ($ord['order_status'] === 'Preparing') ? 'selected' : ''; ?>>Preparing</option>
                                        <option value="Ready" <?php echo ($ord['order_status'] === 'Ready') ? 'selected' : ''; ?>>Ready</option>
                                        <option value="Completed" <?php echo ($ord['order_status'] === 'Completed') ? 'selected' : ''; ?>>Completed</option>
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

<!-- Section 2: Menu Items Availability Manager -->
<div class="cart-table-card">
    <h3 style="margin-bottom: 8px; color: #2d2218;">2. Menu Item Availability Manager</h3>
    <p style="color: #6b5a4a; font-size: 0.9rem; margin-bottom: 15px;">
        Click <strong>"Toggle Availability"</strong> to mark food items in stock or out of stock in real-time.
    </p>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($menu_items as $m): ?>
                    <?php 
                        $isAvail = ((int)$m['is_available'] === 1);
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($m['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($m['category']); ?></td>
                        <td>₹<?php echo number_format($m['price'], 2); ?></td>
                        <td>
                            <?php if ($isAvail): ?>
                                <span class="badge badge-available">● In Stock (Available)</span>
                            <?php else: ?>
                                <span class="badge badge-unavailable">✕ Out of Stock</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <!-- Toggle Form with PRG -->
                            <form action="staff.php" method="post" style="display: inline;">
                                <input type="hidden" name="action" value="toggle_availability">
                                <input type="hidden" name="item_id" value="<?php echo (int)$m['item_id']; ?>">
                                
                                <?php if ($isAvail): ?>
                                    <button type="submit" class="btn btn-danger btn-sm">Mark Out of Stock</button>
                                <?php else: ?>
                                    <button type="submit" class="btn btn-success btn-sm">Mark Available</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Include shared footer
require_once "footer.php";
?>
