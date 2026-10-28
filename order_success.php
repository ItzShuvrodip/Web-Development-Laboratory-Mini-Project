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
    <div style="font-size: 3.5rem; margin-bottom: 5px;">🎉</div>
    <h1 style="color: #1b5e20; font-size: 1.8rem; margin-bottom: 5px;">Order Placed Successfully!</h1>
    <p style="color: #6b5a4a; font-size: 0.95rem;">Your canteen pre-order has been registered in the system.</p>

    <!-- Prominent Order Token Box -->
    <div style="margin: 20px 0;">
        <span style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; font-weight: 600; display: block;">Your Pickup Token Number</span>
        <div class="order-token"><?= htmlspecialchars($order['order_number']) ?></div> <!-- E.g. ORD-1001 -->
    </div>

    <!-- Live Status Pill (Received / Preparing / Ready / Completed) -->
    <div style="margin-bottom: 25px;">
        <span style="font-size: 0.95rem; color: #4b5563; margin-right: 8px;">Current Status:</span>
        <span class="status-pill status-<?= strtolower($order['order_status']) ?>">● <?= htmlspecialchars($order['order_status']) ?></span>
    </div>

    <!-- Customer Metadata Block -->
    <div style="background-color: #fdfaf6; border: 1px dashed #d9c7b0; border-radius: 10px; padding: 15px; margin-bottom: 20px; text-align: left; font-size: 0.9rem;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
            <span style="color: #6b5a4a;">Customer:</span>
            <strong><?= htmlspecialchars($order['customer_name']) ?> (<?= htmlspecialchars($order['contact_no']) ?>)</strong>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <span style="color: #6b5a4a;">Order Time:</span>
            <span><?= htmlspecialchars($order['created_at']) ?></span>
        </div>
    </div>

    <!-- Ordered Items Breakdown Table -->
    <div class="table-responsive">
        <table class="data-table" style="margin-bottom: 20px; font-size: 0.9rem;">
            <thead><tr><th>Item</th><th>Qty</th><th style="text-align: right;">Price</th></tr></thead>
            <tbody>
                <?php foreach ($items as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['item_name']) ?></td>
                        <td><?= (int)$row['quantity'] ?></td>
                        <td style="text-align: right;">₹<?= number_format($row['line_total'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight: bold; background-color: #f6e3c8;">
                    <td colspan="2" style="text-align: right;">Total Paid / Payable:</td>
                    <td style="text-align: right; color: #e8590c; font-size: 1.15rem; font-weight: 800;">₹<?= number_format($order['total_amount'], 2) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Quick Navigation Links (Track in Live Queue or Order Again) -->
    <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
        <a href="queue.php?search=<?= urlencode($order['order_number']) ?>" class="btn btn-primary">⏱️ Track in Live Queue</a>
        <a href="index.php" class="btn btn-secondary">🍽️ Order Another Item</a>
    </div>
</div>

<?php require_once "footer.php"; ?> <!-- Load common footer -->
