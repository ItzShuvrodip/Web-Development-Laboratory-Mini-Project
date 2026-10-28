<?php
// ==============================================================================
// queue.php - Live Canteen Order Queue & Token Tracking Screen
// ==============================================================================
$page_title = "Live Canteen Queue Board";
require_once "db.php"; // Connect to DB and access active session

// === 1. FETCH ORDERS (WITH OPTIONAL SEARCH FILTER) ===
$search = trim($_GET['search'] ?? ''); // Read optional search filter
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number LIKE ? OR customer_name LIKE ? ORDER BY order_id DESC LIMIT 40");
    $stmt->execute(["%$search%", "%$search%"]);
    $orders = $stmt->fetchAll();
} else {
    $orders = $pdo->query("SELECT * FROM orders ORDER BY order_id DESC LIMIT 40")->fetchAll();
}

// === 2. BUCKET ORDERS INTO STATUS GROUPS FOR THE LIVE COLUMNS ===
$buckets = ['ready' => [], 'preparing' => [], 'received' => []];
foreach ($orders as $o) {
    $st = strtolower($o['order_status']);
    if (isset($buckets[$st])) $buckets[$st][] = $o; // Sort order into matching status bucket
}

// Column configuration mapping (Title, Subtext, Top border color, ID prefix, Label)
$cols = [
    'ready'     => ['title' => '🔔 READY FOR PICKUP', 'sub' => 'Proceed to counter with token:', 'border' => '#2f9e44', 'id' => 'ready', 'label' => 'READY'],
    'preparing' => ['title' => '🍳 PREPARING',        'sub' => 'Kitchen staff is currently cooking:', 'border' => '#e8590c', 'id' => 'prep', 'label' => 'Cooking'],
    'received'  => ['title' => '📋 ORDER RECEIVED',    'sub' => 'Waiting for kitchen queue slot:', 'border' => '#1864ab', 'id' => 'received', 'label' => 'Queued']
];

require_once "header.php"; // Load common navigation header
?>

<!-- === 3. HEADER & REFRESH STATUS CONTROLS === -->
<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 22px;">
    <div>
        <h1 class="page-title">Live Canteen Order Queue</h1>
        <p class="page-description">Watch your token number transition from Received &rarr; Preparing &rarr; Ready for Pickup.</p>
    </div>
    <!-- Live polling status indicator & manual refresh button -->
    <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 0.85rem; color: #8a7968;" id="queue-last-updated">Auto-refreshes every 5s</span>
        <button type="button" class="btn btn-secondary btn-sm" id="btn-refresh-queue" data-search="<?= htmlspecialchars($search) ?>">🔄 Refresh Now</button>
    </div>
</div>

<!-- === 4. TRACK ORDER SEARCH FORM === -->
<div style="background-color: #fff; border: 1px solid #f0e4d2; border-radius: 14px; padding: 18px; margin-bottom: 25px;">
    <form action="queue.php" method="get" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <label for="search" style="font-weight: 700; font-size: 0.95rem;">🔍 Track Your Order:</label>
        <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Enter Order Token (e.g. ORD-1001) or Name" style="flex: 1; min-width: 220px; padding: 10px 14px; border: 1px solid #d9c7b0; border-radius: 8px; text-transform: uppercase;">
        <button type="submit" class="btn btn-primary btn-sm" style="padding: 10px 18px;">Track</button>
        <?php if ($search !== ''): ?>
            <a href="queue.php" class="btn btn-secondary btn-sm" style="padding: 10px 14px;">Clear Filter</a>
        <?php endif; ?>
    </form>
</div>

<!-- === 5. LIVE VISUAL QUEUE BOARDS (Auto-updated by js/main.js every 5s) === -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 30px;" id="queue-columns-container" data-search="<?= htmlspecialchars($search) ?>">
    <?php foreach ($cols as $key => $col): $list = $buckets[$key]; ?>
        <div style="background-color: #fff; border: 1px solid #f0e4d2; border-top: 5px solid <?= $col['border'] ?>; border-radius: 14px; padding: 18px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px dashed #e8c9a0; padding-bottom: 10px;">
                <h3 style="font-size: 1.15rem; margin: 0;"><?= $col['title'] ?></h3>
                <span class="status-pill status-<?= $key ?>" id="<?= $col['id'] ?>-count"><?= count($list) ?></span> <!-- Count pill -->
            </div>
            <p style="font-size: 0.85rem; color: #6b5a4a; margin-bottom: 14px;"><?= $col['sub'] ?></p>
            <!-- Order list container updated via AJAX -->
            <div id="<?= $col['id'] ?>-orders-list" style="display: flex; flex-direction: column; gap: 10px;">
                <?php if (empty($list)): ?>
                    <div class="empty-col-msg">No orders in this stage</div>
                <?php else: ?>
                    <?php foreach ($list as $ord): ?>
                        <div style="border-radius: 10px; padding: 12px 14px; display: flex; justify-content: space-between; align-items: center; background-color: <?= $key === 'ready' ? '#d3f9d8' : ($key === 'preparing' ? '#fff9db' : '#e7f5ff') ?>;">
                            <div>
                                <strong style="font-size: 1.2rem; display: block; letter-spacing: 1px;"><?= htmlspecialchars($ord['order_number']) ?></strong>
                                <span style="font-size: 0.85rem;"><?= htmlspecialchars($ord['customer_name']) ?></span>
                            </div>
                            <span class="status-pill status-<?= $key ?>"><?= $col['label'] ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- === 6. DETAILED RECENT ORDERS AUDIT LOG TABLE === -->
<div class="cart-table-card">
    <h3 style="margin-top: 0; margin-bottom: 14px;">Recent Orders Log</h3>
    <div class="table-responsive">
        <table class="data-table">
            <thead><tr><th>Order Number</th><th>Customer Name</th><th>Time Placed</th><th>Total Amount</th><th>Current Status</th></tr></thead>
            <tbody id="all-orders-tbody"> <!-- Tbody target updated dynamically via jQuery AJAX -->
                <?php if (empty($orders)): ?>
                    <tr><td colspan="5" style="text-align: center; color: #8a7968; padding: 24px;">No orders found.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): $st = strtolower($o['order_status']); ?>
                        <tr>
                            <td><strong style="color: #e8590c;"><?= htmlspecialchars($o['order_number']) ?></strong></td>
                            <td><?= htmlspecialchars($o['customer_name']) ?></td>
                            <td><?= htmlspecialchars($o['created_at']) ?></td>
                            <td><strong>₹<?= number_format($o['total_amount'], 2) ?></strong></td>
                            <td><span class="status-pill status-<?= $st ?>"><?= htmlspecialchars($o['order_status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once "footer.php"; ?> <!-- Load common footer -->
