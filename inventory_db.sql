-- ============================================
-- Basic Inventory Management System Database
-- Run this in phpMyAdmin or MySQL CLI
-- ============================================

CREATE DATABASE IF NOT EXISTS inventory_db;
USE inventory_db;

-- Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products Table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category_id INT,
    quantity INT NOT NULL DEFAULT 0,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    min_stock INT NOT NULL DEFAULT 5,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Sample Categories
INSERT INTO categories (name) VALUES
('Electronics'),
('Clothing'),
('Food & Beverages'),
('Stationery'),
('Household');

-- Sample Products
INSERT INTO products (name, category_id, quantity, price, min_stock, description) VALUES
('Wireless Mouse', 1, 25, 12.99, 5, 'USB wireless mouse'),
('USB Keyboard', 1, 15, 19.99, 5, 'Standard USB keyboard'),
('T-Shirt (L)', 2, 3, 8.50, 10, 'Cotton t-shirt large size'),
('Coffee Beans 500g', 3, 2, 7.25, 8, 'Arabica coffee beans'),
('Ball Pen (Pack)', 4, 50, 2.00, 10, 'Pack of 10 blue ball pens'),
('Notebook A4', 4, 8, 3.50, 10, '100 page lined notebook'),
('Dish Soap 500ml', 5, 4, 2.75, 6, 'Liquid dish washing soap'),
('Headphones', 1, 12, 35.00, 4, 'Over-ear wired headphones'),
('Jeans (M)', 2, 1, 24.99, 5, 'Denim jeans medium size'),
('Green Tea 20 bags', 3, 30, 4.50, 8, 'Natural green tea bags');
