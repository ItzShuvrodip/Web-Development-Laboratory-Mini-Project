<?php
// ==============================================================================
// place_order.php - Server Validation, Unique Token Generation & DB Transaction
// ==============================================================================
require_once "db.php"; // Connect to DB and access active session

// Reject direct GET requests or orders with empty carts
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['cart'])) {
    header("Location: index.php"); exit;
}

// === 1. READ & VALIDATE CUSTOMER INPUTS ===
$name  = trim($_POST['customer_name'] ?? ''); // Read customer full name
$phone = trim($_POST['contact_no'] ?? '');    // Read admission / contact number

if ($name === '' || $phone === '') {
    $_SESSION['error'] = "Customer Name and Contact Number are required.";
    header("Location: cart.php"); exit;
}

// Persist customer inputs in session for convenience on subsequent orders
$_SESSION['customer_name'] = $name;
$_SESSION['contact_no']    = $phone;

// === 2. RE-VERIFY ITEM AVAILABILITY IN DATABASE BEFORE INSERTION ===
// (Prevents order if an item went out of stock while the customer was in checkout)
$total = 0;
$verified = [];
foreach ($_SESSION['cart'] as $id => $c) {
    $item = $pdo->query("SELECT * FROM menu_items WHERE item_id = " . (int)$id)->fetch();
    if (!$item || (int)$item['is_available'] !== 1) {
        $_SESSION['error'] = "Sorry, '{$c['name']}' is out of stock!";
        header("Location: cart.php"); exit;
    }
    $line = (float)$item['price'] * (int)$c['quantity']; // Calculate subtotal for this item
    $total += $line;                                      // Accumulate order grand total
    $verified[] = [(int)$id, $item['name'], (float)$item['price'], (int)$c['quantity'], $line];
}

// === 3. GENERATE GUARANTEED UNIQUE ORDER TOKEN ===
// [SPOT-CHANGE]: Format of token (e.g. 'ORD-XXXX') can be customized here
do {
    $token = "ORD-" . rand(1000, 9999); // Generate random 4-digit token
    $exists = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_number = '$token'")->fetchColumn(); // Check DB uniqueness
} while ($exists > 0); // Repeat until guaranteed unique

// === 4. DATABASE TRANSACTION (Inserts Order & Order Items Atomically) ===
try {
    $pdo->beginTransaction(); // Start transaction: all inserts must succeed together

    // Insert into 'orders' table; initial status defaults to 'Received'
    $stmt = $pdo->prepare("INSERT INTO orders (order_number, customer_name, contact_no, total_amount, order_status) VALUES (?, ?, ?, ?, 'Received')");
    $stmt->execute([$token, $name, $phone, $total]);
    $order_id = $pdo->lastInsertId(); // Retrieve generated order_id

    // Insert each item into 'order_items' table referencing the order_id
    $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, item_id, item_name, unit_price, quantity, line_total) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($verified as $row) {
        $itemStmt->execute(array_merge([$order_id], $row));
    }

    $pdo->commit(); // Commit transaction to permanently save changes

    $_SESSION['cart'] = []; // Clear shopping cart upon successful order
    header("Location: order_success.php?order_id=" . $order_id); // Redirect to receipt page
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack(); // Roll back database if any query failed
    $_SESSION['error'] = "Order failed: " . $e->getMessage();
    header("Location: cart.php"); exit;
}
