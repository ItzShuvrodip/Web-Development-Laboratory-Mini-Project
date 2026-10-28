<?php
// ====================================================================
// place_order.php - Final Order Submission & Database Insertion
// Features: Server-side validation, Item availability re-check,
//           Guaranteed unique token generation, PDO Prepared Statements
// ====================================================================

// Connect to database and initialize unique project session & cart normalizer
require_once "db.php";

// Rule: Only process when requested via HTTP POST method
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header("Location: index.php");
    exit;
}

// Step 1: Validate that the cart is not empty
if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['flash_error'] = "Your cart is empty. Please select items from the menu first.";
    header("Location: index.php");
    exit;
}

// Step 2: Read and trim user inputs
$customer_name = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '';
$contact_no    = isset($_POST['contact_no']) ? trim($_POST['contact_no']) : '';

// Server-side check for mandatory fields
if ($customer_name === '' || $contact_no === '') {
    $_SESSION['flash_error'] = "Both Customer Name and Admission/Contact Number are required.";
    header("Location: cart.php");
    exit;
}

// Save customer details in session for convenience on future orders
$_SESSION['customer_name'] = $customer_name;
$_SESSION['contact_no']    = $contact_no;

// Step 3: Re-verify that each item in the cart is STILL available in the database
// (Prevents placing order if staff marked an item out-of-stock while customer was in cart)
$checkAvailStmt = $pdo->prepare("SELECT item_id, name, price, is_available FROM menu_items WHERE item_id = :id");
$verified_cart = [];
$total_amount = 0.0;

foreach ($_SESSION['cart'] as $item_id => $cartItem) {
    $targetId = (is_array($cartItem) && isset($cartItem['item_id'])) ? (int)$cartItem['item_id'] : (int)$item_id;
    if ($targetId <= 0) continue;

    $checkAvailStmt->execute([':id' => $targetId]);
    $dbItem = $checkAvailStmt->fetch();

    if (!$dbItem) {
        $_SESSION['flash_error'] = "One of the items in your cart is no longer on the menu.";
        header("Location: cart.php");
        exit;
    }

    if ((int)$dbItem['is_available'] !== 1) {
        $_SESSION['flash_error'] = "Sorry, '" . $dbItem['name'] . "' just ran out of stock. Please remove it from your cart before placing your order.";
        header("Location: cart.php");
        exit;
    }

    $rawQty = (is_array($cartItem) && isset($cartItem['quantity'])) ? (int)$cartItem['quantity'] : (int)$cartItem;
    $qty = min(max(1, $rawQty), 20);
    $unitPrice = (float)$dbItem['price']; // Use current database price
    $lineTotal = $unitPrice * $qty;
    $total_amount += $lineTotal;

    $verified_cart[] = [
        'item_id'    => (int)$dbItem['item_id'],
        'item_name'  => $dbItem['name'],
        'unit_price' => $unitPrice,
        'quantity'   => $qty,
        'line_total' => $lineTotal
    ];
}

// Step 4: Generate a guaranteed UNIQUE formatted Order Token
// Uses loop verification against the database to prevent duplicate key errors
do {
    $order_number = "ORD-" . rand(1000, 9999);
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE order_number = :num");
    $checkStmt->execute([':num' => $order_number]);
    $exists = (int)$checkStmt->fetchColumn();
} while ($exists > 0);

// Initial order status
$initial_status = "Received";

try {
    // Begin a database transaction for data integrity
    $pdo->beginTransaction();

    // Insert the main order record into 'orders' table
    $orderSql = "INSERT INTO orders (order_number, customer_name, contact_no, total_amount, order_status) 
                 VALUES (:order_num, :cust_name, :contact, :total, :status)";
    
    $stmt = $pdo->prepare($orderSql);
    $stmt->execute([
        ':order_num'  => $order_number,
        ':cust_name'  => $customer_name,
        ':contact'    => $contact_no,
        ':total'      => $total_amount,
        ':status'     => $initial_status
    ]);

    // Retrieve the auto-generated primary key (order_id)
    $order_id = $pdo->lastInsertId();

    // Insert each individual cart item into 'order_items' table
    $itemSql = "INSERT INTO order_items (order_id, item_id, item_name, unit_price, quantity, line_total)
                VALUES (:order_id, :item_id, :item_name, :unit_price, :quantity, :line_total)";
    $itemStmt = $pdo->prepare($itemSql);

    foreach ($verified_cart as $line) {
        $itemStmt->execute([
            ':order_id'   => $order_id,
            ':item_id'    => $line['item_id'],
            ':item_name'  => $line['item_name'],
            ':unit_price' => $line['unit_price'],
            ':quantity'   => $line['quantity'],
            ':line_total' => $line['line_total']
        ]);
    }

    // Commit all changes to the database
    $pdo->commit();

    // Step 5: Clear the session cart now that order is confirmed!
    $_SESSION['cart'] = [];

    // Store the last placed order details in session
    $_SESSION['last_order'] = [
        'order_id'     => $order_id,
        'order_number' => $order_number
    ];

    // Redirect to Order Success receipt page
    header("Location: order_success.php?order_id=" . urlencode($order_id));
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = "Order submission failed: " . $e->getMessage();
    header("Location: cart.php");
    exit;
}
?>
