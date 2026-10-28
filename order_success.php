<?php
// ====================================================================
// order_success.php - Order Confirmation Receipt
// Displays generated order number, initial status & item list
// ====================================================================

// Set page title
$page_title = "Order Placed Successfully!";

// Connect to database
require_once "db.php";

// Read order_id from GET query string
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    header("Location: index.php");
    exit;
}

// Fetch order header details
$orderStmt = $pdo->prepare("SELECT order_id, order_number, customer_name, contact_no, total_amount, order_status, created_at FROM orders WHERE order_id = :id");
$orderStmt->execute([':id' => $order_id]);
$order = $orderStmt->fetch();

if (!$order) {
    die("<h3>Order Not Found</h3><p><a href='index.php'>Return to Canteen Menu</a></p>");
}

// Fetch ordered items for this order
$itemsStmt = $pdo->prepare("SELECT item_name, unit_price, quantity, line_total FROM order_items WHERE order_id = :id");
$itemsStmt->execute([':id' => $order_id]);
$items = $itemsStmt->fetchAll();

// Include shared header
require_once "header.php";
?>

<div class="receipt-card">
    <div style="font-size: 3.5rem; margin-bottom: 5px;">🎉</div>
    <h1 style="color: #1b5e20; font-size: 1.8rem; margin-bottom: 5px;">Order Placed Successfully!</h1>
    <p style="color: #6b5a4a; font-size: 0.95rem;">
        Your canteen pre-order has been registered in the system.
    </p>

    <!-- Prominent Order Token / Number -->
    <div style="margin: 20px 0;">
        <span style="display: block; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; font-weight: 600;">
            Your Pickup Token Number
        </span>
        <div class="order-token">
            <?php echo htmlspecialchars($order['order_number']); ?>
        </div>
    </div>

    <!-- Live Status Indicator -->
    <div style="margin-bottom: 25px;">
        <span style="font-size: 0.95rem; color: #4b5563; margin-right: 8px;">Current Status:</span>
        <?php 
            $st = strtolower($order['order_status']);
        ?>
        <span class="status-pill status-<?php echo htmlspecialchars($st); ?>">
            ● <?php echo htmlspecialchars($order['order_status']); ?>
        </span>
    </div>

    <!-- Order Metadata Summary -->
    <div style="background-color: #fdfaf6; border: 1px dashed #d9c7b0; border-radius: 10px; padding: 15px; margin-bottom: 20px; text-align: left; font-size: 0.9rem;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; flex-wrap: wrap; gap: 4px;">
            <span style="color: #6b5a4a;">Customer:</span>
            <strong><?php echo htmlspecialchars($order['customer_name']); ?> (<?php echo htmlspecialchars($order['contact_no']); ?>)</strong>
        </div>
        <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 4px;">
            <span style="color: #6b5a4a;">Order Time:</span>
            <span><?php echo htmlspecialchars($order['created_at']); ?></span>
        </div>
    </div>

    <!-- Ordered Items Table -->
    <div class="table-responsive">
        <table class="data-table" style="margin-bottom: 20px; font-size: 0.9rem;">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th style="text-align: right;">Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                        <td><?php echo (int)$row['quantity']; ?></td>
                        <td style="text-align: right;">₹<?php echo number_format($row['line_total'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight: bold; background-color: #f6e3c8;">
                    <td colspan="2" style="text-align: right;">Total Paid / Payable:</td>
                    <td style="text-align: right; color: #e8590c; font-size: 1.15rem; font-weight: 800;">
                        ₹<?php echo number_format($order['total_amount'], 2); ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Action Buttons -->
    <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
        <a href="queue.php?search=<?php echo urlencode($order['order_number']); ?>" class="btn btn-primary">
            ⏱️ Track in Live Queue
        </a>
        <a href="index.php" class="btn btn-secondary">
            🍽️ Order Another Item
        </a>
    </div>
</div>

<?php
// Include shared footer
require_once "footer.php";
?>
