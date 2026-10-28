<?php
// ====================================================================
// db.php - Database Connection Configuration using PDO
// Handles MariaDB / MySQL connection with automatic local fallback
// ====================================================================

// Ensure session is started with a unique cookie name so it never collides
// with any other localhost projects or tabs running in the same browser
if (session_status() === PHP_SESSION_NONE) {
    session_name('CAMPUS_CANTEEN_SESS');
    session_start();
}

// Set MariaDB / MySQL connection credentials
$host     = "127.0.0.1";    // Local machine address
$user     = "root";         // Default database username
$password = "";             // Default database password (empty)
$dbname   = "canteen_db";   // Target database name

// Standard ports to try (3306 standard default, 3307 alternative)
$ports_to_try = [3306, 3307];
$pdo = null;
$db_driver_name = "";

// --------------------------------------------------------------------
// Step 1: Attempt connection to MariaDB / MySQL using PDO
// We use a fast 0.1s socket pre-check so that if MySQL is not running,
// it instantly falls back to SQLite without delaying the web page!
// --------------------------------------------------------------------
foreach ($ports_to_try as $port) {
    // Check if MySQL port is actively listening on this computer
    $socket = @fsockopen($host, $port, $errno, $errstr, 0.1);
    if ($socket) {
        fclose($socket); // Port is open, MySQL is running!
        try {
            // First connect without dbname to ensure canteen_db database exists
            $serverDsn = "mysql:host=$host;port=$port;charset=utf8mb4";
            $tempPdo = new PDO($serverDsn, $user, $password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // Automatically create canteen_db if it does not exist yet
            $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // Now connect directly to the canteen_db database
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Return arrays as $row['col_name']
                PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real database prepared statements
            ]);

            $db_driver_name = "MariaDB / MySQL (Port $port)";
            break; // Successfully connected to MySQL/MariaDB!
        } catch (PDOException $e) {
            $pdo = null;
        }
    }
}

// --------------------------------------------------------------------
// Step 2: Portable Fallback for Instant Demonstration (SQLite)
// If MySQL/MariaDB is not currently active, use a local SQLite file
// so the demonstration runs smoothly out of the box!
// --------------------------------------------------------------------
if ($pdo === null) {
    try {
        $sqlite_file = __DIR__ . DIRECTORY_SEPARATOR . 'canteen.db';
        $dsn = "sqlite:" . $sqlite_file;
        $pdo = new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $db_driver_name = "SQLite (Local File: canteen.db)";
    } catch (PDOException $e) {
        die("<h3>Database Connection Error:</h3><p>" . htmlspecialchars($e->getMessage()) . "</p>");
    }
}

// --------------------------------------------------------------------
// Step 3: Automatically initialize tables based on active driver
// --------------------------------------------------------------------
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

if ($driver === 'mysql') {
    // MySQL / MariaDB Table creation
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS menu_items (
            item_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            category VARCHAR(50) NOT NULL,
            price DECIMAL(8, 2) NOT NULL,
            description VARCHAR(255) DEFAULT '',
            is_available TINYINT(1) NOT NULL DEFAULT 1
        );
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            order_id INT AUTO_INCREMENT PRIMARY KEY,
            order_number VARCHAR(20) NOT NULL UNIQUE,
            customer_name VARCHAR(100) NOT NULL,
            contact_no VARCHAR(20) NOT NULL,
            total_amount DECIMAL(10, 2) NOT NULL,
            order_status VARCHAR(20) NOT NULL DEFAULT 'Received',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            order_item_id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            item_id INT NOT NULL,
            item_name VARCHAR(100) NOT NULL,
            unit_price DECIMAL(8, 2) NOT NULL,
            quantity INT NOT NULL,
            line_total DECIMAL(10, 2) NOT NULL
        );
    ");
} else {
    // SQLite Table creation
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS menu_items (
            item_id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            category TEXT NOT NULL,
            price REAL NOT NULL,
            description TEXT DEFAULT '',
            is_available INTEGER NOT NULL DEFAULT 1
        );
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            order_id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_number TEXT NOT NULL UNIQUE,
            customer_name TEXT NOT NULL,
            contact_no TEXT NOT NULL,
            total_amount REAL NOT NULL,
            order_status TEXT NOT NULL DEFAULT 'Received',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            order_item_id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            item_id INTEGER NOT NULL,
            item_name TEXT NOT NULL,
            unit_price REAL NOT NULL,
            quantity INTEGER NOT NULL,
            line_total REAL NOT NULL
        );
    ");
}

// --------------------------------------------------------------------
// Step 4: Seed default sample menu items if table is empty
// --------------------------------------------------------------------
$checkItems = $pdo->query("SELECT COUNT(*) as count FROM menu_items")->fetch();
if ((int)$checkItems['count'] === 0) {
    $insertStmt = $pdo->prepare("
        INSERT INTO menu_items (name, category, price, description, is_available) 
        VALUES (:name, :category, :price, :description, :is_available)
    ");

    $sampleItems = [
        ['name' => 'Crispy Veg Samosa (2 pcs)', 'category' => 'Snacks', 'price' => 25.00, 'description' => 'Golden fried potato samosas served with tangy mint chutney', 'is_available' => 1],
        ['name' => 'South Indian Masala Dosa', 'category' => 'Meals', 'price' => 60.00, 'description' => 'Crispy dosa filled with spiced potato masala, served with hot sambar', 'is_available' => 1],
        ['name' => 'Paneer Butter Roll', 'category' => 'Snacks', 'price' => 70.00, 'description' => 'Fresh wheat wrap stuffed with spiced cottage cheese and onions', 'is_available' => 1],
        ['name' => 'Special Veg Thali', 'category' => 'Meals', 'price' => 110.00, 'description' => '2 Rotis, Steamed Rice, Dal Tadka, Paneer Sabzi, Salad & Sweet', 'is_available' => 1],
        ['name' => 'Veg Hakka Noodles', 'category' => 'Meals', 'price' => 80.00, 'description' => 'Stir-fried noodles with crunchy capsicum, cabbage and carrots', 'is_available' => 0],
        ['name' => 'Cold Coffee with Ice Cream', 'category' => 'Beverages', 'price' => 45.00, 'description' => 'Thick creamy espresso blend topped with vanilla scoop', 'is_available' => 1],
        ['name' => 'Masala Cutting Chai', 'category' => 'Beverages', 'price' => 15.00, 'description' => 'Freshly brewed aromatic tea with ginger and cardamom', 'is_available' => 1],
        ['name' => 'Fresh Lemon Soda', 'category' => 'Beverages', 'price' => 30.00, 'description' => 'Chilled sparkling soda with fresh lime, mint and rock salt', 'is_available' => 1]
    ];

    foreach ($sampleItems as $item) {
        $insertStmt->execute($item);
    }
}

// --------------------------------------------------------------------
// Step 5: Defensive Session Cart Normalizer
// Automatically fixes any integer or corrupted entries in $_SESSION['cart']
// (e.g. if testing another localhost project or after browser session drift)
// --------------------------------------------------------------------
function normalize_session_cart($pdo) {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
        return;
    }

    $sanitized = [];
    foreach ($_SESSION['cart'] as $key => $entry) {
        $itemId = (int)$key;

        // If stored as integer quantity (legacy/external format: $cart[id] = qty)
        if (is_numeric($entry) || is_int($entry)) {
            $qty = min(max(1, (int)$entry), 20);
            if ($itemId > 0 && $pdo) {
                $stmt = $pdo->prepare("SELECT item_id, name, price, is_available FROM menu_items WHERE item_id = :id");
                $stmt->execute([':id' => $itemId]);
                $item = $stmt->fetch();
                if ($item) {
                    $sanitized[$itemId] = [
                        'item_id'  => (int)$item['item_id'],
                        'name'     => (string)$item['name'],
                        'price'    => (float)$item['price'],
                        'quantity' => $qty
                    ];
                }
            }
        } elseif (is_array($entry)) {
            $targetId = isset($entry['item_id']) ? (int)$entry['item_id'] : $itemId;
            if ($targetId <= 0) continue;

            $qty = isset($entry['quantity']) ? min(max(1, (int)$entry['quantity']), 20) : 1;
            $name = isset($entry['name']) ? trim((string)$entry['name']) : '';
            $price = isset($entry['price']) ? (float)$entry['price'] : 0.0;

            // If name or price is missing or invalid, re-fetch from database
            if ($name === '' || $price <= 0.0) {
                if ($pdo) {
                    $stmt = $pdo->prepare("SELECT item_id, name, price FROM menu_items WHERE item_id = :id");
                    $stmt->execute([':id' => $targetId]);
                    $dbItem = $stmt->fetch();
                    if ($dbItem) {
                        $name = (string)$dbItem['name'];
                        $price = (float)$dbItem['price'];
                    }
                }
            }

            if ($name !== '') {
                $sanitized[$targetId] = [
                    'item_id'  => $targetId,
                    'name'     => $name,
                    'price'    => $price,
                    'quantity' => $qty
                ];
            }
        }
    }

    $_SESSION['cart'] = $sanitized;
}

// Automatically sanitize and normalize cart on every request
normalize_session_cart($pdo);
?>
