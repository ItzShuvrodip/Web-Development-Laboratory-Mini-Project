<?php
// ==============================================================================
// order_success.php - Order Confirmation Receipt with Pickup Token & Item Details
// ==============================================================================
$page_title = "Order Placed Successfully!";
require_once "db.php"; // Connect to DB and access active session

// === 1. FETCH PLACED ORDER & ASSOCIATED ITEMS ===
$order_id = (int)($_GET['order_id'] ?? 0); // Read order_id from query parameter
$order = $pdo->query("SELECT * FROM orders WHERE order_id = $order_id")->fetch(); // Fetch main order record
if (!$order) { header("Location: index.php"); exit; } // If invalid ID, redirect back to menu

// Fetch all item lines associated with this order_id
$items = $pdo->query("SELECT * FROM order_items WHERE order_id = $order_id")->fetchAll();
require_once "header.php"; // Load common navigation header
?>

<!-- === 2. PRINTABLE RECEIPT CARD === -->
<div class="receipt-card">
    <div class="receipt-icon">🎉</div>
    <h1 class="receipt-title">Order Placed Successfully!</h1>
    <p class="text-muted">Your canteen pre-order has been registered in the system.</p>

    <!-- Prominent Order Token Box -->
    <div class="mb-lg">
        <span class="token-label">Your Pickup Token Number</span>
        <div class="order-token"><?= htmlspecialchars($order['order_number']) ?></div> <!-- E.g. ORD-1001 -->
    </div>

    <!-- Live Status Pill (Received / Preparing / Ready / Completed) -->
    <div class="mb-xl">
        <span class="text-muted">Current Status:</span>
        <span class="status-pill status-<?= strtolower($order['order_status']) ?>">● <?= htmlspecialchars($order['order_status']) ?></span>
    </div>

    <!-- Customer Metadata Block -->
    <div class="receipt-meta-box">
        <div class="flex-between mb-xs">
            <span class="text-muted">Customer:</span>
            <strong><?= htmlspecialchars($order['customer_name']) ?> (<?= htmlspecialchars($order['contact_no']) ?>)</strong>
        </div>
        <div class="flex-between">
            <span class="text-muted">Order Time:</span>
            <span><?= htmlspecialchars($order['created_at']) ?></span>
        </div>
    </div>

    <!-- Ordered Items Breakdown Table -->
    <div class="table-responsive">
        <table class="data-table mb-lg">
            <thead><tr><th>Item</th><th>Qty</th><th class="text-right">Price</th></tr></thead>
            <tbody>
                <?php foreach ($items as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['item_name']) ?></td>
                        <td><?= (int)$row['quantity'] ?></td>
                        <td class="text-right">₹<?= number_format($row['line_total'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="receipt-total-row">
                    <td colspan="2" class="text-right">Total Paid / Payable:</td>
                    <td class="receipt-total-amount">₹<?= number_format($order['total_amount'], 2) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Quick Navigation Links (Track in Live Queue or Order Again) -->
    <div class="flex-center">
        <a href="queue.php?search=<?= urlencode($order['order_number']) ?>" class="btn btn-primary">⏱️ Track in Live Queue</a>
        <a href="index.php" class="btn btn-secondary">🍽️ Order Another Item</a>
    </div>
</div>

<?php require_once "footer.php"; ?> <!-- Load common footer -->
