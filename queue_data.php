<?php
// ====================================================================
// queue_data.php - JSON Endpoint for Live Queue Polling
// Used by jQuery AJAX to fetch latest order queue without reloading page
// ====================================================================

// Set header for JSON response
header('Content-Type: application/json; charset=utf-8');

// Connect to database
require_once "db.php";

// Optional search filter
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    if (!empty($search_query)) {
        $stmt = $pdo->prepare("
            SELECT order_id, order_number, customer_name, total_amount, order_status, created_at 
            FROM orders 
            WHERE order_number LIKE :q OR customer_name LIKE :q 
            ORDER BY order_id DESC 
            LIMIT 40
        ");
        $stmt->execute([':q' => "%$search_query%"]);
        $orders = $stmt->fetchAll();
    } else {
        $sql = "SELECT order_id, order_number, customer_name, total_amount, order_status, created_at 
                FROM orders 
                ORDER BY order_id DESC 
                LIMIT 40";
        $orders = $pdo->query($sql)->fetchAll();
    }

    echo json_encode([
        'status' => 'success',
        'count'  => count($orders),
        'orders' => $orders
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
