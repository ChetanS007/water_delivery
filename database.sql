-- Database Schema for Water Can and Bottle Delivery System

CREATE DATABASE IF NOT EXISTS water_delivery;
USE water_delivery;

-- 1. Users (Customers)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_type ENUM('Home','Shop') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    mobile VARCHAR(15) UNIQUE NOT NULL,
    address TEXT NOT NULL,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    password VARCHAR(255) NOT NULL,
    qr_code VARCHAR(255),
    status TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Delivery Boys
CREATE TABLE IF NOT EXISTS delivery_boys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100),
    mobile VARCHAR(15) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    status TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Admins
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100),
    mobile VARCHAR(15) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin','Superadmin') NOT NULL,
    status TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Products
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(100),
    price DECIMAL(10,2),
    stock_quantity INT DEFAULT 0,
    description TEXT,
    image_url VARCHAR(255),
    status TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. Orders
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    order_type ENUM('Daily','Weekly','Monthly','Alternate','Custom'),
    custom_days VARCHAR(255) NULL, -- Stores JSON or comma-separated days for Custom
    total_amount DECIMAL(10,2),
    status ENUM('Pending','Assigned','Delivered','Cancelled') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 6. Order Items
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT,
    price DECIMAL(10,2),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- 7. Delivery Assignments
CREATE TABLE IF NOT EXISTS delivery_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    delivery_boy_id INT,
    delivery_status ENUM('Pending','Delivered') DEFAULT 'Pending',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivered_at TIMESTAMP NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (delivery_boy_id) REFERENCES delivery_boys(id) ON DELETE SET NULL
);

-- 8. Cart
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT,
    quantity INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Seed defaults
INSERT INTO admins (full_name, mobile, password, role, status) VALUES 
('Super Admin', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Superadmin', 1);
-- Password is 'password'
