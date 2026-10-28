<?php
// ====================================================================
// queue.php - Live Canteen Order Queue & Token Tracking Screen
// Features: Live queue boards, status groupings, search tracker, jQuery refresh
// ====================================================================

$page_title = "Live Canteen Queue Board";

// Connect to database
require_once "db.php";

// Optional search filter via GET
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch all active or recent orders
if (!empty($search_query)) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number LIKE :q OR customer_name LIKE :q ORDER BY order_id DESC");
    $stmt->execute([':q' => "%$search_query%"]);
    $orders = $stmt->fetchAll();
} else {
    $orders = $pdo->query("SELECT * FROM orders ORDER BY order_id DESC LIMIT 40")->fetchAll();
}

// Group orders into status buckets for the board display
$received_orders  = [];
$preparing_orders = [];
$ready_orders     = [];
$completed_orders = [];

foreach ($orders as $ord) {
    $status = strtolower($ord['order_status']);
    if ($status === 'received') {
        $received_orders[] = $ord;
    } elseif ($status === 'preparing') {
        $preparing_orders[] = $ord;
    } elseif ($status === 'ready') {
        $ready_orders[] = $ord;
    } else {
        $completed_orders[] = $ord;
    }
}

// Include shared header
require_once "header.php";
?>

<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 22px;">
    <div>
        <h1 class="page-title">Live Canteen Order Queue</h1>
        <p class="page-description">Watch your token number transition from Received &rarr; Preparing &rarr; Ready for Pickup.</p>
    </div>

    <!-- Manual & Auto Refresh Controls (jQuery) -->
    <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 0.85rem; color: #8a7968;" id="queue-last-updated">Auto-refreshes every 5s</span>
        <button type="button" class="btn btn-secondary btn-sm" id="btn-refresh-queue" data-search="<?php echo htmlspecialchars($search_query); ?>">
            🔄 Refresh Now
        </button>
    </div>
</div>

<!-- Search & Track Order Number Form -->
<div style="background-color: #ffffff; border: 1px solid #f0e4d2; border-radius: 14px; padding: 18px; margin-bottom: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
    <form action="queue.php" method="get" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <label for="search" style="font-weight: 700; font-size: 0.95rem; color: #2d2218;">
            🔍 Track Your Order:
        </label>
        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search_query); ?>" 
               placeholder="Enter Order Token (e.g. ORD-1001) or Name" 
               style="flex: 1; min-width: 220px; padding: 10px 14px; border: 1px solid #d9c7b0; border-radius: 8px; font-size: 0.95rem; text-transform: uppercase;">
        <button type="submit" class="btn btn-primary btn-sm" style="padding: 10px 18px;">Track</button>
        <?php if (!empty($search_query)): ?>
            <a href="queue.php" class="btn btn-secondary btn-sm" style="padding: 10px 14px;">Clear Filter</a>
        <?php endif; ?>
    </form>
</div>

<!-- Live Visual Queue Display Columns -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 30px;" id="queue-columns-container" data-search="<?php echo htmlspecialchars($search_query); ?>">
    
    <!-- 1. READY FOR PICKUP COLUMN -->
    <div style="background-color: #ffffff; border: 1px solid #b2f2bb; border-top: 5px solid #2f9e44; border-radius: 14px; padding: 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px dashed #e8c9a0; padding-bottom: 10px;">
            <h3 style="color: #1b5e20; font-size: 1.15rem; margin: 0;">🔔 READY FOR PICKUP</h3>
            <span class="status-pill status-ready" id="ready-count"><?php echo count($ready_orders); ?></span>
        </div>
        <p style="font-size: 0.85rem; color: #6b5a4a; margin-bottom: 14px;">Proceed to the counter with your token:</p>

        <div id="ready-orders-list" style="display: flex; flex-direction: column; gap: 10px;">
            <?php if (empty($ready_orders)): ?>
                <div class="empty-col-msg">No orders currently waiting at counter</div>
            <?php else: ?>
                <?php foreach ($ready_orders as $ord): ?>
                    <div style="background-color: #d3f9d8; border: 1px solid #b2f2bb; border-radius: 10px; padding: 12px 14px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="font-size: 1.25rem; color: #1b5e20; display: block; letter-spacing: 1px;"><?php echo htmlspecialchars($ord['order_number']); ?></strong>
                            <span style="font-size: 0.85rem; color: #2d2218;"><?php echo htmlspecialchars($ord['customer_name']); ?></span>
                        </div>
                        <span class="status-pill status-ready">READY</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. PREPARING IN KITCHEN COLUMN -->
    <div style="background-color: #ffffff; border: 1px solid #ffe066; border-top: 5px solid #e8590c; border-radius: 14px; padding: 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px dashed #e8c9a0; padding-bottom: 10px;">
            <h3 style="color: #8a6100; font-size: 1.15rem; margin: 0;">🍳 PREPARING</h3>
            <span class="status-pill status-preparing" id="prep-count"><?php echo count($preparing_orders); ?></span>
        </div>
        <p style="font-size: 0.85rem; color: #6b5a4a; margin-bottom: 14px;">Kitchen staff is currently cooking/packing:</p>

        <div id="prep-orders-list" style="display: flex; flex-direction: column; gap: 10px;">
            <?php if (empty($preparing_orders)): ?>
                <div class="empty-col-msg">No orders currently being prepared</div>
            <?php else: ?>
                <?php foreach ($preparing_orders as $ord): ?>
                    <div style="background-color: #fff9db; border: 1px solid #ffe066; border-radius: 10px; padding: 12px 14px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="font-size: 1.2rem; color: #8a6100; display: block; letter-spacing: 1px;"><?php echo htmlspecialchars($ord['order_number']); ?></strong>
                            <span style="font-size: 0.85rem; color: #2d2218;"><?php echo htmlspecialchars($ord['customer_name']); ?></span>
                        </div>
                        <span class="status-pill status-preparing">Cooking</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- 3. RECEIVED (IN QUEUE) COLUMN -->
    <div style="background-color: #ffffff; border: 1px solid #a5d8ff; border-top: 5px solid #1864ab; border-radius: 14px; padding: 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px dashed #e8c9a0; padding-bottom: 10px;">
            <h3 style="color: #1864ab; font-size: 1.15rem; margin: 0;">📋 ORDER RECEIVED</h3>
            <span class="status-pill status-received" id="received-count"><?php echo count($received_orders); ?></span>
        </div>
        <p style="font-size: 0.85rem; color: #6b5a4a; margin-bottom: 14px;">Waiting for kitchen queue slot:</p>

        <div id="received-orders-list" style="display: flex; flex-direction: column; gap: 10px;">
            <?php if (empty($received_orders)): ?>
                <div class="empty-col-msg">No orders in queue</div>
            <?php else: ?>
                <?php foreach ($received_orders as $ord): ?>
                    <div style="background-color: #e7f5ff; border: 1px solid #a5d8ff; border-radius: 10px; padding: 12px 14px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="font-size: 1.2rem; color: #1864ab; display: block; letter-spacing: 1px;"><?php echo htmlspecialchars($ord['order_number']); ?></strong>
                            <span style="font-size: 0.85rem; color: #2d2218;"><?php echo htmlspecialchars($ord['customer_name']); ?></span>
                        </div>
                        <span class="status-pill status-received">Queued</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Full Detailed Order Log Table -->
<div class="cart-table-card">
    <h3 style="margin-top: 0; margin-bottom: 14px; color: #2d2218;">Recent Orders Log</h3>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order Number</th>
                    <th>Customer Name</th>
                    <th>Time Placed</th>
                    <th>Total Amount</th>
                    <th>Current Status</th>
                </tr>
            </thead>
            <tbody id="all-orders-tbody">
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #8a7968; padding: 24px;">No orders found matching criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <?php 
                            $st = strtolower($o['order_status']);
                        ?>
                        <tr>
                            <td>
                                <strong style="color: #e8590c; letter-spacing: 0.5px;"><?php echo htmlspecialchars($o['order_number']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($o['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($o['created_at']); ?></td>
                            <td><strong>₹<?php echo number_format($o['total_amount'], 2); ?></strong></td>
                            <td>
                                <span class="status-pill status-<?php echo htmlspecialchars($st); ?>">
                                    <?php echo htmlspecialchars($o['order_status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Include shared footer
require_once "footer.php";
?>
