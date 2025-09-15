-- Step 1: Create the Database
CREATE DATABASE IF NOT EXISTS ServiceHub;
USE ServiceHub;

-- Step 2: Create Core Tables
-- Table 1: users
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone_number VARCHAR(15),
    address TEXT,
    NIC VARCHAR(15) UNIQUE,
    user_type ENUM('customer', 'provider', 'admin') NOT NULL,
    disable_status BOOLEAN DEFAULT FALSE,
    registered_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Step 3: Authentication & Security Tables
-- Table 2: otp
CREATE TABLE otp (
    otp_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    purpose ENUM('registration', 'password_reset', 'email_verification', 'updateCustomerProfile', 'updateProviderProfile') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expired_at DATETIME NOT NULL,
    pending_data LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(pending_data)),
    INDEX idx_email_purpose (email, purpose),
    INDEX idx_expired_at (expired_at)
);

-- Step 4: Communication Table
-- Table 3: message
CREATE TABLE message (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100),
    phone_number VARCHAR(15),
    message TEXT,
    subject VARCHAR(255),
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Step 5: Service & Subscription Structure
-- Table 4: provider
CREATE TABLE provider (
    provider_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    description TEXT,
    qualifications TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Table 5: service_category
CREATE TABLE service_category (
    service_category_id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL
);

-- Table 6: subscription_plan
CREATE TABLE subscription_plan (
    sub_id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(100) NOT NULL,
    description TEXT
);

-- Step 6: Booking Tables
-- Table 7: service_booking
CREATE TABLE service_booking (
    service_book_id INT AUTO_INCREMENT PRIMARY KEY,
    service_category_id INT NOT NULL,
    user_id INT NOT NULL,
    provider_id INT DEFAULT NULL,
    customer_name VARCHAR(100) DEFAULT NULL,
    serbooking_status ENUM('pending', 'waiting', 'process', 'request', 'complete', 'cancel') NOT NULL DEFAULT 'pending',
    serbooking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    service_date DATE,
    service_time TIME,
    service_address TEXT,
    phoneNo VARCHAR(15),
    amount DECIMAL(10,2) DEFAULT 500.00,
    service_amount DECIMAL(10,2) DEFAULT NULL,
    cancel_reason TEXT DEFAULT NULL,
    FOREIGN KEY (service_category_id) REFERENCES service_category(service_category_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (provider_id) REFERENCES provider(provider_id)
);

-- Table 8: subscription_booking
CREATE TABLE subscription_booking (
    subbook_id INT AUTO_INCREMENT PRIMARY KEY,
    sub_id INT NOT NULL,
    user_id INT NOT NULL,
    provider_id INT DEFAULT NULL,
    customer_name VARCHAR(100) DEFAULT NULL,
    subbooking_status ENUM('pending', 'waiting', 'process', 'request', 'complete', 'cancel') NOT NULL DEFAULT 'pending',
    subbooking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sub_date DATE,
    sub_time TIME,
    sub_address TEXT,
    phoneNo VARCHAR(15),
    amount DECIMAL(10,2) DEFAULT 1000.00,
    service_amount DECIMAL(10,2) DEFAULT NULL,
    cancel_reason TEXT DEFAULT NULL,
    FOREIGN KEY (sub_id) REFERENCES subscription_plan(sub_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (provider_id) REFERENCES provider(provider_id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- Step 7: Provider Allocation Tables
-- Table 9: service_provider_allocation
CREATE TABLE service_provider_allocation (
    allocation_id INT AUTO_INCREMENT PRIMARY KEY,
    service_book_id INT NOT NULL,
    provider_id INT NOT NULL,
    allocated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_book_id) REFERENCES service_booking(service_book_id),
    FOREIGN KEY (provider_id) REFERENCES provider(provider_id)
);

-- Table 10: subscription_provider_allocation
CREATE TABLE subscription_provider_allocation (
    allocation_id INT AUTO_INCREMENT PRIMARY KEY,
    subbook_id INT NOT NULL,
    provider_id INT NOT NULL,
    allocated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subbook_id) REFERENCES subscription_booking(subbook_id),
    FOREIGN KEY (provider_id) REFERENCES provider(provider_id)
);

-- Step 8: Review Tables
-- Table 11: service_review
CREATE TABLE service_review (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    allocation_id INT NOT NULL,
    provider_name VARCHAR(100),
    service_name VARCHAR(100),
    amount DECIMAL(10,2),
    rating INT CHECK (rating BETWEEN 1 AND 5),
    feedback_text TEXT,
    reviewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (allocation_id) REFERENCES service_provider_allocation(allocation_id)
);

-- Table 12: subscription_review
CREATE TABLE subscription_review (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    allocation_id INT NOT NULL,
    provider_name VARCHAR(100),
    service_name VARCHAR(100),
    amount DECIMAL(10,2),
    rating INT CHECK (rating BETWEEN 1 AND 5),
    feedback_text TEXT,
    reviewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (allocation_id) REFERENCES subscription_provider_allocation(allocation_id)
);

-- Step 9: Notification System
-- Table 13: notification
CREATE TABLE notification (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    provider_id INT NULL,
    service_booking_id INT NULL,
    subscription_booking_id INT NULL,
    description TEXT NOT NULL,
    customer_action ENUM('none', 'hidden', 'active') DEFAULT 'none',
    provider_action ENUM('none', 'hidden', 'active') DEFAULT 'none',
    admin_action ENUM('none', 'hidden', 'active') DEFAULT 'none',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (provider_id) REFERENCES provider(provider_id) ON DELETE SET NULL,
    FOREIGN KEY (service_booking_id) REFERENCES service_booking(service_book_id) ON DELETE SET NULL,
    FOREIGN KEY (subscription_booking_id) REFERENCES subscription_booking(subbook_id) ON DELETE SET NULL,
    INDEX idx_notification_actions (user_id, customer_action, provider_action, admin_action),
    INDEX idx_notification_references (provider_id, service_booking_id, subscription_booking_id)
);

-- Step 11: Insert Initial Data

-- 11.1: Insert Initial Users
INSERT INTO users (user_id, name, email, password, phone_number, address, NIC, user_type, disable_status, registered_date) VALUES
(1, 'Admin', 'admin@servicehub.com', '$2y$10$LH05IjJLJ7ucgGGUTc1.hO2jer7recQjtG99Djw6D1TUKbaYU9v0u', '0779595564', 'Mannar', '200230601070', 'admin', 0, '2025-07-21 22:26:53'),
(2, 'Monitor Customer', 'customer@gmail.com', '$2y$10$klVXXBXxs7c6Ays53dSE/OUtok7W67kf3b2oozafupMOBvY6BHhTC', '0778200752', 'Mannar', '200234070145', 'customer', 0, '2025-07-21 22:44:58'),
(3, 'Monitor Provider', 'provider@gmail.com', '$2y$10$FDnM/mnZMeEgco69S3OlOOdUKCC8T5XS9hFQDNqjpJj9hHXDnH1VG', '0779595500', 'Jaffana', '200231070145', 'provider', 0, '2025-07-21 22:46:45');

-- 11.2: Insert Service Categories
INSERT INTO service_category (service_name) VALUES
('Plumbing Services'),
('Carpentry Services'),
('Electrical Services'),
('Painting Services'),
('Electronic Services'),
('Cleaning Service');

-- 11.3: Insert Subscription Plans
INSERT INTO subscription_plan (category, description) VALUES
('Weekly Plan', 'Vehicle Wash'),
('Monthly Plan', 'Deep Cleaning'),
('Yearly Plan', 'Utility Check');

-- Set the auto increment values to continue after initial data
ALTER TABLE users AUTO_INCREMENT = 4; 