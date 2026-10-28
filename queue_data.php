<?php
// ==============================================================================
// queue_data.php - JSON Endpoint Polled by jQuery for Real-Time Queue Updates
// ==============================================================================
header('Content-Type: application/json; charset=utf-8'); // Set JSON response header
require_once "db.php"; // Connect to DB and access active session

$q = trim($_GET['search'] ?? ''); // Read optional search query (order token or name)

// Query orders: filter by token/name if search was provided, else fetch latest 40 orders
if ($q !== '') {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number LIKE ? OR customer_name LIKE ? ORDER BY order_id DESC LIMIT 40");
    $stmt->execute(["%$q%", "%$q%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY order_id DESC LIMIT 40"); // [SPOT-CHANGE]: change limit here if needed
}

// Echo JSON payload containing 'status' and array of 'orders'
echo json_encode(['status' => 'success', 'orders' => $stmt->fetchAll()]);
