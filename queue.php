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

// Column configuration mapping (Title, Subtext, ID prefix, Label)
$cols = [
    'ready'     => ['title' => '🔔 READY FOR PICKUP', 'sub' => 'Proceed to counter with token:', 'id' => 'ready', 'label' => 'READY'],
    'preparing' => ['title' => '🍳 PREPARING',        'sub' => 'Kitchen staff is currently cooking:', 'id' => 'prep', 'label' => 'Cooking'],
    'received'  => ['title' => '📋 ORDER RECEIVED',    'sub' => 'Waiting for kitchen queue slot:', 'id' => 'received', 'label' => 'Queued']
];

require_once "header.php"; // Load common navigation header
?>

<!-- === 3. HEADER & REFRESH STATUS CONTROLS === -->
<div class="flex-between mb-lg">
    <div>
        <h1 class="page-title">Live Canteen Order Queue</h1>
        <p class="page-description">Watch your token number transition from Received &rarr; Preparing &rarr; Ready for Pickup.</p>
    </div>
    <!-- Live polling status indicator & manual refresh button -->
    <div class="flex-gap-md">
        <span class="text-subtle" id="queue-last-updated">Auto-refreshes every 5s</span>
        <button type="button" class="btn btn-secondary btn-sm" id="btn-refresh-queue" data-search="<?= htmlspecialchars($search) ?>">🔄 Refresh Now</button>
    </div>
</div>

<!-- === 4. TRACK ORDER SEARCH FORM === -->
<div class="search-card">
    <form action="queue.php" method="get" class="flex-gap-md">
        <label for="search" class="font-bold">🔍 Track Your Order:</label>
        <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Enter Order Token (e.g. ORD-1001) or Name" class="search-input">
        <button type="submit" class="btn btn-primary btn-sm">Track</button>
        <?php if ($search !== ''): ?>
            <a href="queue.php" class="btn btn-secondary btn-sm">Clear Filter</a>
        <?php endif; ?>
    </form>
</div>

<!-- === 5. LIVE VISUAL QUEUE BOARDS (Auto-updated by js/main.js every 5s) === -->
<div class="queue-grid" id="queue-columns-container" data-search="<?= htmlspecialchars($search) ?>">
    <?php foreach ($cols as $key => $col): $list = $buckets[$key]; ?>
        <div class="queue-col queue-col-<?= $key ?>">
            <div class="queue-col-header">
                <h3 class="queue-col-title"><?= $col['title'] ?></h3>
                <span class="status-pill status-<?= $key ?>" id="<?= $col['id'] ?>-count"><?= count($list) ?></span> <!-- Count pill -->
            </div>
            <p class="text-muted text-sm mb-md"><?= $col['sub'] ?></p>
            <!-- Order list container updated via AJAX -->
            <div id="<?= $col['id'] ?>-orders-list" class="queue-orders-list">
                <?php if (empty($list)): ?>
                    <div class="empty-col-msg">No orders in this stage</div>
                <?php else: ?>
                    <?php foreach ($list as $ord): ?>
                        <div class="queue-card queue-card-<?= $key ?>">
                            <div>
                                <strong class="token-text token-<?= $key ?>"><?= htmlspecialchars($ord['order_number']) ?></strong>
                                <span class="text-sm"><?= htmlspecialchars($ord['customer_name']) ?></span>
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
    <h3 class="mb-md">Recent Orders Log</h3>
    <div class="table-responsive">
        <table class="data-table">
            <thead><tr><th>Order Number</th><th>Customer Name</th><th>Time Placed</th><th>Total Amount</th><th>Current Status</th></tr></thead>
            <tbody id="all-orders-tbody"> <!-- Tbody target updated dynamically via jQuery AJAX -->
                <?php if (empty($orders)): ?>
                    <tr><td colspan="5" class="table-empty-td">No orders found.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): $st = strtolower($o['order_status']); ?>
                        <tr>
                            <td><strong class="token-orange"><?= htmlspecialchars($o['order_number']) ?></strong></td>
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
