<?php
// ====================================================================
// index.php - Canteen Food Menu & Pre-Order Selection
// Features: Menu display, Category filtering, Item availability check
// ====================================================================

// Set page title for header.php
$page_title = "Canteen Menu - Pre-Order System";

// Include database connection
require_once "db.php";

// Fetch all food menu items from the database using PDO
// Ordered by category and name so the display is structured and intuitive
$query = "SELECT item_id, name, category, price, description, is_available FROM menu_items ORDER BY category, name";
$menu_items = $pdo->query($query)->fetchAll();

// Collect unique categories dynamically from the menu items for filter tabs
$categories = [];
foreach ($menu_items as $item) {
    if (!in_array($item['category'], $categories)) {
        $categories[] = $item['category'];
    }
}

// Include the shared navigation header
require_once "header.php";

// Read and clear one-time flash messages
$flash_success = "";
$flash_error = "";
if (isset($_SESSION['flash_message'])) {
    $flash_success = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
if (isset($_SESSION['flash_error'])) {
    $flash_error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}
?>

<!-- Page Heading and Description -->
<h1 class="page-title">Today's Canteen Menu</h1>
<p class="page-description">Browse freshly prepared meals, snacks, and beverages. Select quantity and pre-order to skip the physical queue!</p>

<!-- Success Feedback Banner -->
<?php if (!empty($flash_success)): ?>
    <div class="alert alert-success">
        ✅ <?php echo htmlspecialchars($flash_success); ?> 
        <a href="cart.php" style="margin-left: 10px; font-weight: bold; color: inherit;">View Cart &amp; Checkout &rarr;</a>
    </div>
<?php endif; ?>

<!-- Error Feedback Banner -->
<?php if (!empty($flash_error)): ?>
    <div class="alert alert-danger">
        ⚠️ <?php echo htmlspecialchars($flash_error); ?>
    </div>
<?php endif; ?>

<!-- Category Filter Tabs (Powered by jQuery) -->
<div class="filter-tabs">
    <button type="button" class="filter-btn active" data-category="all">All Items (<?php echo count($menu_items); ?>)</button>
    <?php foreach ($categories as $cat): ?>
        <button type="button" class="filter-btn" data-category="<?php echo htmlspecialchars($cat); ?>">
            <?php echo htmlspecialchars($cat); ?>
        </button>
    <?php endforeach; ?>
</div>

<!-- Menu Grid -->
<div class="menu-grid">
    <?php foreach ($menu_items as $item): ?>
        <?php 
            // Determine if the item is currently available in the canteen
            $isAvailable = ((int)$item['is_available'] === 1);
        ?>
        <div class="menu-card <?php echo $isAvailable ? '' : 'out-of-stock'; ?>" data-category="<?php echo htmlspecialchars($item['category']); ?>">
            <div>
                <div class="card-header">
                    <span class="item-category"><?php echo htmlspecialchars($item['category']); ?></span>
                    
                    <!-- Availability Badge -->
                    <?php if ($isAvailable): ?>
                        <span class="badge badge-available">● Available</span>
                    <?php else: ?>
                        <span class="badge badge-unavailable">✕ Out of Stock</span>
                    <?php endif; ?>
                </div>

                <!-- Item Name & Description -->
                <h3 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                <p class="item-desc"><?php echo htmlspecialchars($item['description']); ?></p>
            </div>

            <div class="card-footer">
                <!-- Price in Indian Rupees -->
                <div class="item-price">₹<?php echo number_format($item['price'], 2); ?></div>

                <!-- Add to Cart Form -->
                <?php if ($isAvailable): ?>
                    <form action="cart.php" method="post" style="display: flex; gap: 6px; align-items: center;">
                        <!-- Hidden inputs pass the action and item ID -->
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="item_id" value="<?php echo (int)$item['item_id']; ?>">

                        <!-- Quantity input (minimum 1, maximum 20) -->
                        <input type="number" name="quantity" value="1" min="1" max="20" class="qty-input" title="Quantity">

                        <button type="submit" class="btn btn-primary btn-sm add-to-cart-btn">
                            + Add
                        </button>
                    </form>
                <?php else: ?>
                    <!-- Disabled button when item is out of stock -->
                    <button type="button" class="btn btn-disabled btn-sm" disabled>
                        Out of Stock
                    </button>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Quick Link to Cart if items exist -->
<?php if ($total_cart_items > 0): ?>
    <div style="margin-top: 30px; text-align: center; background: #ffffff; padding: 18px 22px; border-radius: 14px; border: 1px solid #f0e4d2; box-shadow: 0 2px 8px rgba(0,0,0,0.06); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div style="color: #2d2218;">
            <strong>🛒 You have <?php echo $total_cart_items; ?> item(s) in your pre-order cart.</strong>
        </div>
        <a href="cart.php" class="btn btn-primary">
            Review Cart &amp; Submit Order &rarr;
        </a>
    </div>
<?php endif; ?>

<?php
// Include the shared page footer
require_once "footer.php";
?>
