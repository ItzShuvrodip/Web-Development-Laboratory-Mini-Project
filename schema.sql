-- ====================================================================
-- CANTEEN PRE-ORDER AND QUEUE SYSTEM
-- Database Setup Script for MariaDB / MySQL
-- ====================================================================

-- 1. Create the Database if it does not already exist
CREATE DATABASE IF NOT EXISTS canteen_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. Select the Database to use
USE canteen_db;

-- --------------------------------------------------------------------
-- Table 1: menu_items
-- Stores all canteen food items, prices, categories and availability
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS menu_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,     -- Unique ID for each food item
    name VARCHAR(100) NOT NULL,                 -- Name of the item (e.g. Masala Dosa)
    category VARCHAR(50) NOT NULL,              -- Food Category (Snacks, Meals, Beverages)
    price DECIMAL(8, 2) NOT NULL,               -- Price in Rupees (e.g. 50.00)
    description VARCHAR(255) DEFAULT '',        -- Short description of the item
    is_available TINYINT(1) NOT NULL DEFAULT 1  -- 1 = Available, 0 = Out of Stock
);

-- --------------------------------------------------------------------
-- Table 2: orders
-- Stores each placed customer order with an order number and status
-- Statuses: 'Received', 'Preparing', 'Ready', 'Completed'
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,    -- Primary key auto increment
    order_number VARCHAR(20) NOT NULL UNIQUE,   -- Formatted Order No (e.g. ORD-1001)
    customer_name VARCHAR(100) NOT NULL,        -- Customer / Student Name
    contact_no VARCHAR(20) NOT NULL,            -- Phone or Admission Number
    total_amount DECIMAL(10, 2) NOT NULL,       -- Calculated Grand Total
    order_status VARCHAR(20) NOT NULL DEFAULT 'Received', -- 'Received', 'Preparing', 'Ready', 'Completed'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP        -- Order timestamp
);

-- --------------------------------------------------------------------
-- Table 3: order_items
-- Stores each individual item line inside an order (relational link)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,                      -- Foreign key referencing orders.order_id
    item_id INT NOT NULL,                       -- Foreign key referencing menu_items.item_id
    item_name VARCHAR(100) NOT NULL,            -- Preserved name at time of ordering
    unit_price DECIMAL(8, 2) NOT NULL,          -- Price per unit at time of ordering
    quantity INT NOT NULL,                      -- Number of units ordered
    line_total DECIMAL(10, 2) NOT NULL,         -- unit_price * quantity
    FOREIGN KEY (order_id) REFERENCES orders (order_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES menu_items (item_id)
);

-- --------------------------------------------------------------------
-- Initial Sample Data for Canteen Menu Items
-- --------------------------------------------------------------------
INSERT INTO menu_items (name, category, price, description, is_available) VALUES
('Crispy Veg Samosa (2 pcs)', 'Snacks', 25.00, 'Golden fried potato samosas served with mint chutney', 1),
('South Indian Masala Dosa', 'Meals', 60.00, 'Crispy dosa with spiced potato filling, sambar and coconut chutney', 1),
('Paneer Butter Roll', 'Snacks', 70.00, 'Soft wrap stuffed with cottage cheese and mint sauce', 1),
('Special Veg Thali', 'Meals', 110.00, '2 Rotis, Rice, Dal, Paneer Gravy, Salad and Gulab Jamun', 1),
('Veg Hakka Noodles', 'Meals', 80.00, 'Wok-tossed noodles with fresh seasonal vegetables', 0), -- Example out-of-stock item
('Cold Coffee with Ice Cream', 'Beverages', 45.00, 'Thick chilled coffee topped with vanilla ice cream', 1),
('Masala Cutting Chai', 'Beverages', 15.00, 'Cardamom and ginger infused hot tea', 1),
('Fresh Lemon Soda', 'Beverages', 30.00, 'Chilled refreshing sweet and salted lime soda', 1);
