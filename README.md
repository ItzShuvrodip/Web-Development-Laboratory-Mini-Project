# Web Development Laboratory Mini Project - Campus Canteen Pre-Order and Queue Management System

A full-stack canteen food pre-ordering and kitchen queue management web application built using **HTML5, CSS3, JavaScript, jQuery, PHP Sessions, and MariaDB/MySQL (via PDO)** over the Apache Server.

---

## 1. System Overview

This web application provides a complete solution for campus canteen pre-ordering, cart management, token generation, and kitchen queue tracking:

### Key Features:
1. **Interactive Canteen Menu (`index.php`):**
   - Displays all food items with prices, categories, and descriptions.
   - **Item Availability Tracking:** Available items display a green `● Available` badge and an active `+ Add` button. Out-of-stock items display a red `✕ Out of Stock` badge and a disabled button.
   - **Category Filtering (jQuery):** Client-side tab filtering for "All", "Snacks", "Meals", and "Beverages" without page reloads.
2. **Cart Operations via PHP Session (`cart.php`):**
   - **Add to Cart:** Stores selected menu items with quantities in `$_SESSION['cart']`.
   - **Update Quantity:** Dynamically adjusts item quantities (clamped between 1 and 20).
   - **Remove Item:** Unsets individual items from the session cart.
   - **Clear Cart:** Empties the entire cart with confirmation.
   - **Calculated Total:** Automatically computes item subtotals (`price * quantity`) and the grand total.
3. **Final Order Submission & Token Generation (`place_order.php`):**
   - Server-side validation of customer name and contact details.
   - Re-verifies live item availability in the database before order placement (preventing purchases if an item ran out of stock while in the cart).
   - Generates a guaranteed unique order token (e.g., `ORD-7421`).
   - Assigns initial status: **`Received`**.
   - Inserts order details into `orders` and `order_items` tables using **PDO Transactions and Prepared Statements**.
   - Resets and clears the PHP session cart upon successful order placement.
4. **Order Confirmation Receipt (`order_success.php`):**
   - Displays printable receipt showing the prominent token number, timestamp, item breakdown, and current status.
5. **Live Queue & Status Tracking Board (`queue.php` & `queue_data.php`):**
   - Real-time columns for **`Received`**, **`Preparing`**, and **`Ready for Pickup`**.
   - Order Search Tracker: Customers can enter their Order Number or Name to track their status.
   - **jQuery AJAX Auto-Polling:** Continuously updates the board every 5 seconds without page reload, preserving any active search filter.
6. **Canteen Staff & Kitchen Panel (`staff.php`):**
   - **Password-Protected Access:** Secured staff portal requiring login credentials (**ID:** `admin`, **Password:** `admin`).
   - Change order statuses in real time: `Received` &rarr; `Preparing` &rarr; `Ready` &rarr; `Completed`.
   - Toggle item stock availability: 1-click button to toggle any food item between "Available" and "Out of Stock".
   - 1-click demonstration data reset button and secure logout.

---

## 2. Directory Structure

```text
WebD Project/
├── db.php             # Database connection using PDO (MariaDB / MySQL with SQLite fallback)
├── header.php         # Common navigation bar & session cart counter
├── footer.php         # Common footer and script loader
├── index.php          # Canteen food menu, category tabs & item availability
├── cart.php           # Session cart management, calculated totals & customer form
├── place_order.php    # Server-side validation, PDO insert & token generation
├── order_success.php  # Order confirmation receipt with token badge
├── queue.php          # Live token queue display & order tracking board
├── queue_data.php     # JSON endpoint used by jQuery to poll queue updates
├── staff.php          # Kitchen staff control panel (status & stock toggle)
├── schema.sql         # Relational database table definitions & sample data
├── style.css          # Semantic, responsive CSS styling
├── js/
│   ├── jquery-3.7.1.min.js  # Local jQuery library
│   └── main.js              # jQuery category filtering and queue auto-refresh
├── run_server.bat     # 1-click batch launcher for local testing
└── README.md          # Comprehensive project documentation
```

---

## 3. How to Run the Application

### Option A: 1-Click Launch (Recommended for Fast Local Demo)
1. Double-click the file **`run_server.bat`** located in this folder.
2. It automatically starts PHP on port `8080` and opens **`http://localhost:8080/index.php`** in your default web browser.

### Option B: Running in XAMPP / Apache Server
1. Copy or move this project folder into your Apache document root:
   ```text
   C:\xampp\htdocs\canteen\
   ```
2. Open **XAMPP Control Panel** and start:
   - **Apache** (runs on port 8080 or port 80)
   - **MySQL / MariaDB** (runs on port 3306 or 3307)
3. Open your browser and navigate to:
   ```text
   http://localhost:8080/canteen/index.php
   ```
4. *(Optional)* Import `schema.sql` into **phpMyAdmin** under database `canteen_db`. *(Note: `db.php` will automatically create the database and tables if they do not exist yet!)*

---

## 4. Code & Architecture Highlights

### A. PHP Sessions (`session_start()` and `$_SESSION`)
- **State Management:** HTTP is stateless. To persist cart selections across multiple page views (`index.php`, `cart.php`, `place_order.php`), PHP sessions are utilized.
- **Header Placement & Session Isolation:** `session_name('CAMPUS_CANTEEN_SESS')` and `session_start()` are called at the very top of `db.php` and `header.php` before any HTML output, isolating cookies from other localhost projects and preventing "headers already sent" warnings.
- **Cart Array & Normalization:** `$_SESSION['cart']` stores an associative array indexed by `item_id`, holding item name, unit price, and quantity. A defensive normalizer automatically validates and heals corrupted entries.
- **Flash Messages:** User feedback alerts (`$_SESSION['flash_message']`, `$_SESSION['flash_error']`) are displayed once and automatically cleared, preventing persistent alerts on refresh.

### B. Form Handling: `GET` vs `POST`
- **`GET` method (`queue.php`):** Used for searching and tracking orders (`queue.php?search=ORD-1001`). `GET` places parameters in the URL query string, making search states bookmarkable and reloadable.
- **`POST` method (`cart.php`, `place_order.php`, `staff.php`):** Used when modifying server-side data (adding items, changing quantities, submitting orders, updating statuses). `POST` transmits parameters inside the HTTP request body.

### C. Server-Side Validation & Security
- **`htmlspecialchars()`:** Every user-supplied value (customer name, contact number, status text) is sanitized before rendering to HTML to prevent **Cross-Site Scripting (XSS)**.
- **`PDO Prepared Statements`:** All SQL queries that handle dynamic parameters utilize parameterized prepared statements (`$stmt = $pdo->prepare(...)` and `$stmt->execute([...])`), preventing **SQL Injection**.

### D. Relational Database Design
- **`menu_items` table:** Stores catalog of food items (`item_id`, `name`, `category`, `price`, `description`, `is_available`).
- **`orders` table:** Stores transactions with a primary key (`order_id`), unique readable token (`order_number`), customer metadata, grand total, and status (`Received`, `Preparing`, `Ready`, `Completed`).
- **`order_items` table:** Relational join table linking `orders` with `menu_items` via `order_id` as foreign key, preserving item price and line total at the exact moment of order placement.

### E. Client-Side jQuery Functionality
- Category tab switching on `index.php` using `.on('click', ...)` and `.fadeIn(200)`.
- Live queue polling in `main.js`: polls `queue_data.php` every 5 seconds using `$.ajax`, dynamically updating token columns and handling empty states seamlessly without full page refreshes.
