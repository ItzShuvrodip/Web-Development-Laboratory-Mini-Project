<?php
// ====================================================================
// cart.php - Canteen Cart Operations & Total Calculation
// Features: Session Cart management, Quantity adjustments, Total calculation
// ====================================================================

// Connect to database and initialize unique project session & cart normalizer
require_once "db.php";

// Normalize cart items defensively
normalize_session_cart($pdo);

// --------------------------------------------------------------------
// Section 1: Process Cart Operations (Add, Update, Remove, Clear)
// --------------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';

    // Operation A: ADD item to cart
    if ($action === 'add') {
        $item_id  = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        $quantity = min(max(1, $quantity), 20); // Clamp between 1 and 20

        // Verify item in database and ensure it is currently available
        $stmt = $pdo->prepare("SELECT item_id, name, price, is_available FROM menu_items WHERE item_id = :id");
        $stmt->execute([':id' => $item_id]);
        $item = $stmt->fetch();

        if ($item) {
            if ((int)$item['is_available'] === 1) {
                // If item already exists in the session cart, increase its quantity
                $existingQty = (isset($_SESSION['cart'][$item_id]) && is_array($_SESSION['cart'][$item_id]) && isset($_SESSION['cart'][$item_id]['quantity']))
                    ? (int)$_SESSION['cart'][$item_id]['quantity']
                    : (is_numeric($_SESSION['cart'][$item_id] ?? null) ? (int)$_SESSION['cart'][$item_id] : 0);
                
                $newQty = $existingQty + $quantity;
                $_SESSION['cart'][$item_id] = [
                    'item_id'  => (int)$item['item_id'],
                    'name'     => (string)$item['name'],
                    'price'    => (float)$item['price'],
                    'quantity' => min(max(1, $newQty), 20)
                ];
                $_SESSION['flash_message'] = "Added " . $item['name'] . " to your cart!";
            } else {
                $_SESSION['flash_error'] = "Sorry, " . $item['name'] . " is currently out of stock!";
            }
        }

        // Post-Redirect-Get pattern to avoid duplicate submissions on browser refresh
        header("Location: index.php");
        exit;
    }

    // Operation B: UPDATE item quantity in cart
    if ($action === 'update') {
        $item_id  = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

        if (isset($_SESSION['cart'][$item_id])) {
            if ($quantity > 0) {
                $clampedQty = min(max(1, $quantity), 20);
                if (is_array($_SESSION['cart'][$item_id])) {
                    $_SESSION['cart'][$item_id]['quantity'] = $clampedQty;
                } else {
                    $_SESSION['cart'][$item_id] = [
                        'item_id'  => $item_id,
                        'name'     => 'Item #' . $item_id,
                        'price'    => 0.0,
                        'quantity' => $clampedQty
                    ];
                }
                $_SESSION['flash_message'] = "Quantity updated!";
            } else {
                // If quantity set to 0 or negative, remove the item
                unset($_SESSION['cart'][$item_id]);
                $_SESSION['flash_message'] = "Item removed from cart.";
            }
        }

        header("Location: cart.php");
        exit;
    }

    // Operation C: REMOVE specific item from cart
    if ($action === 'remove') {
        $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        if (isset($_SESSION['cart'][$item_id])) {
            $name = (is_array($_SESSION['cart'][$item_id]) && isset($_SESSION['cart'][$item_id]['name']))
                ? $_SESSION['cart'][$item_id]['name']
                : 'Item';
            unset($_SESSION['cart'][$item_id]);
            $_SESSION['flash_message'] = "Removed " . $name . " from cart.";
        }
        header("Location: cart.php");
        exit;
    }

    // Operation D: CLEAR entire cart
    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        $_SESSION['flash_message'] = "Cart cleared.";
        header("Location: cart.php");
        exit;
    }
}

// --------------------------------------------------------------------
// Section 2: Calculate Subtotals and Grand Total
// --------------------------------------------------------------------
$grand_total = 0.0;
$total_items_count = 0;

foreach ($_SESSION['cart'] as $item_id => $cart_row) {
    if (!is_array($cart_row)) {
        continue;
    }
    $price = isset($cart_row['price']) ? (float)$cart_row['price'] : 0.0;
    $qty   = isset($cart_row['quantity']) ? (int)$cart_row['quantity'] : 0;
    $line_total = $price * $qty;
    $grand_total += $line_total;
    $total_items_count += $qty;
}

// Set page title for header.php
$page_title = "Your Pre-Order Cart";

// Include shared header
require_once "header.php";

// Flash alerts
$flash_success = "";
$flash_error = "";
if (isset($_SESSION['flash_message'])) {
    $flash_success = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
if (isset($_SESSION['flash_error'])) {
    $flash_error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

// Pre-fill customer details if saved from a previous order
$saved_name = isset($_SESSION['customer_name']) ? $_SESSION['customer_name'] : '';
$saved_contact = isset($_SESSION['contact_no']) ? $_SESSION['contact_no'] : '';
?>

<h1 class="page-title">Your Pre-Order Cart</h1>
<p class="page-description">Review your selected items, adjust quantities, and submit your final order to receive a queue token number.</p>

<!-- Success Feedback Banner -->
<?php if (!empty($flash_success)): ?>
    <div class="alert alert-success">
        ✅ <?php echo htmlspecialchars($flash_success); ?>
    </div>
<?php endif; ?>

<!-- Error Feedback Banner -->
<?php if (!empty($flash_error)): ?>
    <div class="alert alert-danger">
        ⚠️ <?php echo htmlspecialchars($flash_error); ?>
    </div>
<?php endif; ?>

<?php if (empty($_SESSION['cart'])): ?>
    <!-- Empty Cart State -->
    <div class="cart-table-card" style="text-align: center; padding: 50px 20px;">
        <div style="font-size: 3rem; margin-bottom: 15px;">🛒</div>
        <h3 style="margin-bottom: 8px; color: #2d2218;">Your cart is currently empty!</h3>
        <p style="color: #6b5a4a; margin-bottom: 20px;">You haven't selected any canteen items yet.</p>
        <a href="index.php" class="btn btn-primary">&larr; Return to Menu to Add Items</a>
    </div>

<?php else: ?>
    <!-- Active Cart Display -->
    <div class="cart-table-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
            <h3>Selected Items (<?php echo $total_items_count; ?>)</h3>
            
            <!-- Clear Entire Cart Form -->
            <form action="cart.php" method="post" onsubmit="return confirm('Are you sure you want to empty your cart?');">
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="btn btn-secondary btn-sm">Clear All</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Unit Price</th>
                        <th style="width: 170px;">Quantity</th>
                        <th>Subtotal</th>
                        <th style="text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['cart'] as $id => $item): ?>
                        <?php 
                            if (!is_array($item)) continue;
                            $itemName      = isset($item['name']) ? (string)$item['name'] : ('Item #' . $id);
                            $itemPrice     = isset($item['price']) ? (float)$item['price'] : 0.0;
                            $itemQty       = isset($item['quantity']) ? (int)$item['quantity'] : 1;
                            $item_subtotal = $itemPrice * $itemQty;
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($itemName); ?></strong>
                            </td>
                            <td>₹<?php echo number_format($itemPrice, 2); ?></td>
                            <td>
                                <!-- Update Quantity Form -->
                                <form action="cart.php" method="post" style="display: flex; gap: 5px; align-items: center;">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="item_id" value="<?php echo (int)$id; ?>">
                                    <input type="number" name="quantity" value="<?php echo (int)$itemQty; ?>" min="1" max="20" class="qty-input">
                                    <button type="submit" class="btn btn-secondary btn-sm" title="Update Quantity">Update</button>
                                </form>
                            </td>
                            <td>
                                <strong>₹<?php echo number_format($item_subtotal, 2); ?></strong>
                            </td>
                            <td style="text-align: center;">
                                <!-- Remove Single Item Form -->
                                <form action="cart.php" method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="item_id" value="<?php echo (int)$id; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" title="Remove Item">&times; Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Calculated Grand Total Display Box -->
        <div class="cart-total-box">
            <span>Calculated Grand Total:</span>
            <span>₹<?php echo number_format($grand_total, 2); ?></span>
        </div>
    </div>

    <!-- Final Order Submission Form -->
    <div class="cart-table-card" style="border-top: 3px solid #e8590c;">
        <h3 style="margin-bottom: 10px; color: #2d2218;">Final Order Submission</h3>
        <p style="color: #6b5a4a; margin-bottom: 20px; font-size: 0.95rem;">
            Please enter your name and student contact details to generate your official queue order number.
        </p>

        <form action="place_order.php" method="post" id="checkout-form">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div>
                    <label for="customer_name" style="display: block; font-weight: 600; margin-bottom: 5px; color: #2d2218;">
                        Full Name / Student Name: <span style="color: #e03131;">*</span>
                    </label>
                    <input type="text" id="customer_name" name="customer_name" required 
                           value="<?php echo htmlspecialchars($saved_name); ?>"
                           placeholder="e.g. Rahul Sharma" 
                           style="width: 100%; padding: 10px; border: 1px solid #d9c7b0; border-radius: 8px; font-size: 1rem; color: #2d2218;">
                </div>

                <div>
                    <label for="contact_no" style="display: block; font-weight: 600; margin-bottom: 5px; color: #2d2218;">
                        Admission No / Mobile No: <span style="color: #e03131;">*</span>
                    </label>
                    <input type="text" id="contact_no" name="contact_no" required 
                           value="<?php echo htmlspecialchars($saved_contact); ?>"
                           placeholder="e.g. 23JE0456 or 9876543210" 
                           style="width: 100%; padding: 10px; border: 1px solid #d9c7b0; border-radius: 8px; font-size: 1rem; color: #2d2218;">
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <a href="index.php" class="btn btn-secondary">&larr; Add More Items</a>

                <!-- Order Submit Button -->
                <button type="submit" class="btn btn-primary" style="font-size: 1.05rem; padding: 10px 24px;">
                    ✅ Submit Order &amp; Generate Token (₹<?php echo number_format($grand_total, 2); ?>)
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php
// Include shared footer
require_once "footer.php";
?>
