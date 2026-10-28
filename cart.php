<?php
// ==============================================================================
// cart.php - Session-Based Shopping Cart Operations & Order Checkout Screen
// ==============================================================================
require_once "db.php"; // Connect to DB and start active session

$action = $_POST['action'] ?? '';                       // Read submitted form action (add, update, remove, clear)
$id     = (int)($_POST['item_id'] ?? 0);                // Read target item ID
$qty    = max(1, min((int)($_POST['quantity'] ?? 1), 20)); // [SPOT-CHANGE]: Clamp quantity between 1 and 20

// === 1. PROCESS CART ACTIONS (Via HTTP POST) ===

// Action A: Add item to cart
if ($action === 'add') {
    $item = $pdo->query("SELECT * FROM menu_items WHERE item_id = $id AND is_available = 1")->fetch(); // Check item exists & available
    if ($item) {
        $cur = $_SESSION['cart'][$id]['quantity'] ?? 0; // Current quantity in cart if already added
        // Store item details & updated quantity in session cart array
        $_SESSION['cart'][$id] = ['item_id' => $id, 'name' => $item['name'], 'price' => (float)$item['price'], 'quantity' => min(20, $cur + $qty)];
        $_SESSION['flash'] = "Added {$item['name']} to your cart! <a href='cart.php' style='margin-left:8px;font-weight:bold;color:inherit;'>View Cart &amp; Checkout &rarr;</a>";
    } else {
        $_SESSION['error'] = "Item is out of stock!";
    }
    header("Location: index.php"); exit; // Post-Redirect-Get pattern to avoid duplicate addition on refresh
}

// Action B: Update existing item quantity
if ($action === 'update' && isset($_SESSION['cart'][$id])) {
    $newQty = (int)($_POST['quantity'] ?? 0);
    if ($newQty > 0) $_SESSION['cart'][$id]['quantity'] = min(20, $newQty); // [SPOT-CHANGE]: Update to new clamped qty
    else unset($_SESSION['cart'][$id]);                                     // If 0, remove item from cart
    $_SESSION['flash'] = "Quantity updated!";
    header("Location: cart.php"); exit;
}

// Action C: Remove single item from cart
if ($action === 'remove') {
    unset($_SESSION['cart'][$id]); // Delete item from session array
    $_SESSION['flash'] = "Item removed.";
    header("Location: cart.php"); exit;
}

// Action D: Clear entire cart
if ($action === 'clear') {
    $_SESSION['cart'] = []; // Empty the entire cart array
    $_SESSION['flash'] = "Cart cleared.";
    header("Location: cart.php"); exit;
}

// === 2. CALCULATE GRAND TOTAL ===
$grand_total = 0;
foreach ($_SESSION['cart'] as $item) $grand_total += $item['price'] * $item['quantity']; // Sum up: unit_price * quantity

$page_title = "Your Pre-Order Cart";
require_once "header.php"; // Load common navigation header
?>

<h1 class="page-title">Your Pre-Order Cart</h1>
<p class="page-description">Review your selected items, adjust quantities, and submit your final order to receive a queue token number.</p>

<!-- === 3. CONDITIONAL DISPLAY: EMPTY CART vs ACTIVE CART === -->
<?php if (empty($_SESSION['cart'])): ?>
    <!-- Empty Cart Card -->
    <div class="cart-table-card" style="text-align: center; padding: 50px 20px;">
        <div style="font-size: 3rem; margin-bottom: 12px;">🛒</div>
        <h3>Your cart is currently empty!</h3>
        <p style="color: #6b5a4a; margin: 10px 0 20px;">You haven't selected any canteen items yet.</p>
        <a href="index.php" class="btn btn-primary">&larr; Return to Menu to Add Items</a>
    </div>
<?php else: ?>
    <!-- Active Cart Items Table -->
    <div class="cart-table-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
            <h3>Selected Items (<?= count($_SESSION['cart']) ?>)</h3>
            <!-- Button to clear all items -->
            <form action="cart.php" method="post" onsubmit="return confirm('Empty your cart?');">
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="btn btn-secondary btn-sm">Clear All</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Item Name</th><th>Unit Price</th><th style="width: 170px;">Quantity</th><th>Subtotal</th><th style="text-align: center;">Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['cart'] as $cid => $c): $sub = $c['price'] * $c['quantity']; ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                            <td>₹<?= number_format($c['price'], 2) ?></td>
                            <td>
                                <!-- Form to change item quantity -->
                                <form action="cart.php" method="post" style="display: flex; gap: 5px;">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="item_id" value="<?= $cid ?>">
                                    <input type="number" name="quantity" value="<?= $c['quantity'] ?>" min="1" max="20" class="qty-input">
                                    <button type="submit" class="btn btn-secondary btn-sm">Update</button>
                                </form>
                            </td>
                            <td><strong>₹<?= number_format($sub, 2) ?></strong></td>
                            <td style="text-align: center;">
                                <!-- Form to remove specific item -->
                                <form action="cart.php" method="post">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="item_id" value="<?= $cid ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">&times; Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Calculated Grand Total Box -->
        <div class="cart-total-box">
            <span>Calculated Grand Total:</span>
            <span>₹<?= number_format($grand_total, 2) ?></span>
        </div>
    </div>

    <!-- === 4. CUSTOMER CHECKOUT & ORDER SUBMISSION FORM === -->
    <div class="cart-table-card" style="border-top: 3px solid #e8590c;">
        <h3 style="margin-bottom: 10px;">Final Order Submission</h3>
        <p style="color: #6b5a4a; margin-bottom: 20px; font-size: 0.95rem;">Please enter your name and student contact details to generate your official queue order number.</p>

        <!-- Posts customer details to place_order.php -->
        <form action="place_order.php" method="post">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Full Name / Student Name: <span style="color: red;">*</span></label>
                    <input type="text" name="customer_name" required value="<?= htmlspecialchars($_SESSION['customer_name'] ?? '') ?>" placeholder="e.g. Rahul Sharma" style="width: 100%; padding: 10px; border: 1px solid #d9c7b0; border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Admission No / Mobile No: <span style="color: red;">*</span></label>
                    <input type="text" name="contact_no" required value="<?= htmlspecialchars($_SESSION['contact_no'] ?? '') ?>" placeholder="e.g. 23JE0456 or 9876543210" style="width: 100%; padding: 10px; border: 1px solid #d9c7b0; border-radius: 8px;">
                </div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <a href="index.php" class="btn btn-secondary">&larr; Add More Items</a>
                <button type="submit" class="btn btn-primary" style="font-size: 1.05rem; padding: 10px 24px;">
                    ✅ Submit Order &amp; Generate Token (₹<?= number_format($grand_total, 2) ?>)
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php require_once "footer.php"; ?> <!-- Load common footer -->
