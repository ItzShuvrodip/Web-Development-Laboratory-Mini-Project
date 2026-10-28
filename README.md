# Campus Canteen Pre-Order & Live Queue Management System
**Web Development Laboratory Mini Project**

A full-stack, streamlined campus canteen food pre-ordering and kitchen queue management web application built using **HTML5, CSS3, JavaScript, jQuery, PHP Sessions, and MariaDB/MySQL (with portable SQLite fallback via PDO)**.

---

## 1. System Overview & Key Features

1. **Interactive Menu (`index.php`):**
   - Displays food items categorized into Snacks, Meals, and Beverages.
   - **Live Stock Badges:** Green `● Available` badge with an active `+ Add` button, or red `✕ Out of Stock` badge with a disabled button.
   - **Category Filtering (jQuery):** Real-time client-side tab filtering without page reloads.

2. **Session Cart (`cart.php`):**
   - Stateful shopping cart powered by `$_SESSION['cart']`.
   - Operations: Add to cart, adjust quantities (clamped 1–20), remove item, or clear cart.
   - Dynamic grand total calculation: `unit_price * quantity`.

3. **Order Placement & Token Generation (`place_order.php`):**
   - Server-side validation of customer name and student contact/admission number.
   - Re-verifies live database stock availability before insertion.
   - Generates a guaranteed unique pickup token (e.g., `ORD-4821`).
   - Uses **PDO Transactions (`beginTransaction` / `commit`)** to atomically save order headers and line items.

4. **Order Confirmation Receipt (`order_success.php`):**
   - Displays receipt card showing the prominent token number, timestamp, itemized list, and live status.

5. **Live Queue & Status Board (`queue.php` & `queue_data.php`):**
   - 3 live visual stages: **`Ready for Pickup`**, **`Preparing`**, and **`Order Received`**.
   - Search Tracker: Track any order by token number or student name.
   - **jQuery AJAX Auto-Polling:** Continuously updates the boards every 5 seconds without page reload.

6. **Staff & Kitchen Portal (`staff.php`):**
   - Password-protected access (Default credentials: **`admin`** / **`admin`**).
   - Change order lifecycle status: `Received` &rarr; `Preparing` &rarr; `Ready` &rarr; `Completed`.
   - 1-Click stock availability toggle (In Stock / Out of Stock).
   - 1-Click demo data reset and secure logout.

---

## 2. Project File Structure

```text
WebD Project/
├── db.php             # Database connection (MySQL with SQLite fallback) & default seeding
├── header.php         # Navigation header, cart badge counter & global alert banners
├── footer.php         # Page footer and script loaders
├── index.php          # Food menu cards, category tabs & add-to-cart forms
├── cart.php           # Session cart actions (add, update, remove, clear) & customer form
├── place_order.php    # Server validation, PDO transaction insert & unique token generation
├── order_success.php  # Receipt view with prominent pickup token
├── queue.php          # Live 3-stage queue board & order search tracker
├── queue_data.php     # JSON endpoint polled by jQuery AJAX for live queue updates
├── staff.php          # Staff kitchen dashboard (order status & stock toggles)
├── schema.sql         # SQL schema definition for MySQL / phpMyAdmin
├── style.css          # Semantic CSS stylesheet (warm earthy café palette)
├── js/
│   ├── jquery-3.7.1.min.js # Local jQuery library
│   └── main.js             # Category filter, qty clamp, and live queue auto-poll
├── run_server.bat     # 1-click launcher for immediate local testing
└── README.md          # Project documentation & viva guide
```

---

## 3. How to Run the Application

### Option A: 1-Click Launch (Recommended for Fast Local Demo)
1. Double-click the file **`run_server.bat`**.
2. It automatically starts PHP on port `8080` and opens **`http://localhost:8080/index.php`** in your default web browser.

### Option B: Running in XAMPP
1. Move this project folder into `C:\xampp\htdocs\canteen\`.
2. Open **XAMPP Control Panel** and start **Apache** and **MySQL**.
3. Open your browser and navigate to:
   ```text
   http://localhost:8080/canteen/index.php
   ```
   *(Note: `db.php` automatically creates `canteen_db` and all tables if they do not exist yet!)*

---

## 4. Live Viva Modification Cheat Sheet (Spot Changes)

If the professor asks you to make **live changes on the spot**, here is where every key setting is located:

| Professor Request | File to Edit | Exact Line / Section |
| :--- | :--- | :--- |
| **Add a new menu item or change price** | `db.php` | Edit `$sample_items` array (lines 10–20) |
| **Change staff login credentials** | `staff.php` | Section 2: `if ($username === 'admin' && $password === 'admin')` |
| **Change max quantity limit in cart** | `cart.php` & `js/main.js` | Change `20` in `min(20, $qty)` and `Math.min(val, 20)` |
| **Change queue auto-refresh time** | `js/main.js` | Last line: `setInterval(fetchQueue, 5000);` (e.g. change `5000` to `3000` for 3s) |
| **Add a new order status (e.g. 'Delivered')** | `staff.php` | Section 7: `<select name="new_status">` options |
| **Change token format (e.g. 'TKN-')** | `place_order.php` | Section 3: `$token = "ORD-" . rand(1000, 9999);` |
| **Change database credentials** | `db.php` | Section 3: `new PDO("mysql:host=127.0.0.1...", "root", "")` |

---

## 5. Typical Viva Questions & Answers

1. **Q: How does the application maintain user cart data without storing it in the database immediately?**
   - **A:** We use PHP Sessions (`$_SESSION['cart']`). `session_start()` is initialized in `db.php` before any HTML output. The cart is stored as an associative array indexed by `item_id`.
2. **Q: How does the live queue update without refreshing the page?**
   - **A:** In `js/main.js`, a `setInterval` runs every 5 seconds calling `$.getJSON('queue_data.php')`. When the JSON response arrives, jQuery updates the DOM dynamically without reloading.
3. **Q: Why do you use PDO Transactions in `place_order.php`?**
   - **A:** An order consists of rows in two separate tables (`orders` and `order_items`). Using `$pdo->beginTransaction()` and `$pdo->commit()` guarantees atomicity: if an error occurs, `$pdo->rollBack()` cancels all partial changes so corrupt data is never stored.
4. **Q: How does the system switch between MySQL and SQLite?**
   - **A:** In `db.php`, we attempt to connect to MySQL with a `try...catch` block. If MySQL is unavailable (e.g. XAMPP is off), it catches the exception and connects to the local `canteen.db` SQLite file seamlessly.
