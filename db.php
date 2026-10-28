<?php
// ==============================================================================
// db.php - Database Configuration, Auto-Connection & Default Menu Seeding
// ==============================================================================

// === 1. SESSION INITIALIZATION ===
if (session_status() === PHP_SESSION_NONE) session_start(); // Start session for user cart & login tracking
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = []; // Ensure cart is an empty array if unset

// === 2. DEFAULT MENU SAMPLE DATA (Name, Category, Price, Description, Available) ===
// [SPOT-CHANGE]: Add/edit food items, categories, or prices here
$sample_items = [
    ['Crispy Veg Samosa (2 pcs)', 'Snacks', 25.00, 'Golden fried potato samosas served with tangy mint chutney', 1],
    ['South Indian Masala Dosa', 'Meals', 60.00, 'Crispy dosa filled with spiced potato masala, served with hot sambar', 1],
    ['Paneer Butter Roll', 'Snacks', 70.00, 'Fresh wheat wrap stuffed with spiced cottage cheese and onions', 1],
    ['Special Veg Thali', 'Meals', 110.00, '2 Rotis, Steamed Rice, Dal Tadka, Paneer Sabzi, Salad & Sweet', 1],
    ['Veg Hakka Noodles', 'Meals', 80.00, 'Stir-fried noodles with crunchy capsicum, cabbage and carrots', 0], // 0 = Out of stock demo
    ['Cold Coffee with Ice Cream', 'Beverages', 45.00, 'Thick creamy espresso blend topped with vanilla scoop', 1],
    ['Masala Cutting Chai', 'Beverages', 15.00, 'Freshly brewed aromatic tea with ginger and cardamom', 1],
    ['Fresh Lemon Soda', 'Beverages', 30.00, 'Chilled sparkling soda with fresh lime, mint and rock salt', 1]
];

// === 3. DATABASE AUTO-DETECTION (Connects to MySQL if running; otherwise SQLite) ===
try {
    // [SPOT-CHANGE]: Edit MySQL host/user/password here if needed
    $pdo = new PDO("mysql:host=127.0.0.1;charset=utf8mb4", "root", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS canteen_db"); // Automatically create database if missing
    $pdo->exec("USE canteen_db");                           // Select canteen database
    $db_driver_name = "MySQL";                              // Active driver indicator
} catch (Exception $e) {
    // Fallback: If MySQL is not running, seamlessly use local SQLite file 'canteen.db'
    $pdo = new PDO("sqlite:" . __DIR__ . "/canteen.db", null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $db_driver_name = "SQLite";                             // Fallback driver indicator
}

// === 4. TABLE CREATION SCHEMA ===
$is_mysql = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql'); // Check driver type for auto-increment syntax
$ai = $is_mysql ? "INT AUTO_INCREMENT PRIMARY KEY" : "INTEGER PRIMARY KEY AUTOINCREMENT"; // Auto-increment definition

// Table 1: menu_items - Stores food menu catalogue
$pdo->exec("CREATE TABLE IF NOT EXISTS menu_items (item_id $ai, name TEXT NOT NULL, category TEXT NOT NULL, price REAL NOT NULL, description TEXT, is_available INTEGER DEFAULT 1)");

// Table 2: orders - Stores customer orders, tokens, and current status
$pdo->exec("CREATE TABLE IF NOT EXISTS orders (order_id $ai, order_number TEXT UNIQUE, customer_name TEXT, contact_no TEXT, total_amount REAL, order_status TEXT DEFAULT 'Received', created_at " . ($is_mysql ? "TIMESTAMP DEFAULT CURRENT_TIMESTAMP" : "DATETIME DEFAULT CURRENT_TIMESTAMP") . ")");

// Table 3: order_items - Stores each line item linked to an order
$pdo->exec("CREATE TABLE IF NOT EXISTS order_items (order_item_id $ai, order_id INT, item_id INT, item_name TEXT, unit_price REAL, quantity INT, line_total REAL)");

// === 5. SEED INITIAL MENU IF TABLE IS EMPTY ===
if (!(int)$pdo->query("SELECT COUNT(*) FROM menu_items")->fetchColumn()) {
    $stmt = $pdo->prepare("INSERT INTO menu_items (name, category, price, description, is_available) VALUES (?, ?, ?, ?, ?)");
    foreach ($sample_items as $item) $stmt->execute($item); // Insert each sample item into DB
}
