<?php
// ==============================================================================
// index.php - Canteen Food Menu, Dynamic Category Filtering & Cart Additions
// ==============================================================================
$page_title = "Canteen Menu - Pre-Order System";
require_once "db.php"; // Connect to DB and start session

// Fetch all menu items ordered by category and name for structured display
$items = $pdo->query("SELECT * FROM menu_items ORDER BY category, name")->fetchAll();
// Extract unique category names (e.g. Snacks, Meals, Beverages) dynamically for filter buttons
$categories = array_unique(array_column($items, 'category'));

require_once "header.php"; // Load navigation header
?>

<!-- === 1. PAGE HEADER & INTRO === -->
<h1 class="page-title">Today's Canteen Menu</h1>
<p class="page-description">Browse freshly prepared meals, snacks, and beverages. Select quantity and pre-order to skip the physical queue!</p>

<!-- === 2. CATEGORY FILTER TABS (Handled in js/main.js via jQuery) === -->
<div class="filter-tabs">
    <button type="button" class="filter-btn active" data-category="all">All Items (<?= count($items) ?>)</button>
    <?php foreach ($categories as $cat): ?>
        <button type="button" class="filter-btn" data-category="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></button>
    <?php endforeach; ?>
</div>

<!-- === 3. FOOD MENU ITEMS GRID === -->
<div class="menu-grid">
    <?php foreach ($items as $item): $avail = (int)$item['is_available'] === 1; ?>
        <!-- Individual Menu Item Card; class 'out-of-stock' applies faded styling if unavailable -->
        <div class="menu-card <?= $avail ? '' : 'out-of-stock' ?>" data-category="<?= htmlspecialchars($item['category']) ?>">
            <div>
                <div class="card-header">
                    <span class="item-category"><?= htmlspecialchars($item['category']) ?></span> <!-- Food Category pill -->
                    <!-- Availability Badge (Green = In Stock, Red = Out of Stock) -->
                    <span class="badge <?= $avail ? 'badge-available' : 'badge-unavailable' ?>">
                        <?= $avail ? '● Available' : '✕ Out of Stock' ?>
                    </span>
                </div>
                <h3 class="item-name"><?= htmlspecialchars($item['name']) ?></h3> <!-- Item Name -->
                <p class="item-desc"><?= htmlspecialchars($item['description']) ?></p> <!-- Item Description -->
            </div>
            <div class="card-footer">
                <div class="item-price">₹<?= number_format($item['price'], 2) ?></div> <!-- Unit Price in Rupees -->
                <?php if ($avail): ?>
                    <!-- Form submitting selected item and quantity to cart.php -->
                    <form action="cart.php" method="post" style="display: flex; gap: 6px;">
                        <input type="hidden" name="action" value="add"> <!-- Action parameter: add -->
                        <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>"> <!-- Item ID parameter -->
                        <input type="number" name="quantity" value="1" min="1" max="20" class="qty-input"> <!-- [SPOT-CHANGE]: min/max qty clamp -->
                        <button type="submit" class="btn btn-primary btn-sm">+ Add</button>
                    </form>
                <?php else: ?>
                    <!-- Disabled button shown when item is currently marked out of stock by staff -->
                    <button type="button" class="btn btn-disabled btn-sm" disabled>Out of Stock</button>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- === 4. QUICK CART SUMMARY BAR (Shown only if cart contains items) === -->
<?php if ($total_cart_items > 0): ?>
    <div style="margin-top: 30px; text-align: center; background: #fff; padding: 18px 22px; border-radius: 14px; border: 1px solid #f0e4d2; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <strong>🛒 You have <?= $total_cart_items ?> item(s) in your pre-order cart.</strong>
        <a href="cart.php" class="btn btn-primary">Review Cart &amp; Submit Order &rarr;</a>
    </div>
<?php endif; ?>

<?php require_once "footer.php"; ?> <!-- Load page footer & scripts -->
