-- Step 1: Create the Database
CREATE DATABASE IF NOT EXISTS ServiceHub;
USE ServiceHub;

-- Step 2: Create Core Tables
-- 2.1 users
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
-- 3.1 otp
CREATE TABLE otp (
    otp_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    purpose ENUM('registration', 'password_reset', 'email_verification') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expired_at DATETIME NOT NULL,
    INDEX idx_email_purpose (email, purpose),
    INDEX idx_expired_at (expired_at)
);

-- Step 4: Communication Table
-- 4.1 message
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
-- 5.1 provider
CREATE TABLE provider (
    provider_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    description TEXT,
    qualifications TEXT,
    status ENUM('active', 'inactive') DEFAULT 'inactive',
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
-- 5.2 service_category
CREATE TABLE service_category (
    service_category_id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL
);
-- 5.3 subscription_plan
CREATE TABLE subscription_plan (
    sub_id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(100) NOT NULL,
    description TEXT
);

-- Step 6: Booking Tables
-- 6.1 service_booking
CREATE TABLE service_booking (
    service_book_id INT AUTO_INCREMENT PRIMARY KEY,
    service_category_id INT NOT NULL,
    user_id INT NOT NULL,
    serbooking_status ENUM('pending', 'process', 'complete', 'cancel') NOT NULL DEFAULT 'pending',
    serbooking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    service_date DATE,
    service_time TIME,
    service_address TEXT,
    phoneNo VARCHAR(15),
    amount DECIMAL(10,2),
    cancel_reason TEXT DEFAULT NULL,
    FOREIGN KEY (service_category_id) REFERENCES service_category(service_category_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
-- 6.2 subscription_booking
CREATE TABLE subscription_booking (
    subbook_id INT AUTO_INCREMENT PRIMARY KEY,
    sub_id INT NOT NULL,
    user_id INT NOT NULL,
    subbooking_status ENUM('pending', 'process', 'complete', 'cancel') NOT NULL DEFAULT 'pending',
    subbooking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sub_date DATE,
    sub_time TIME,
    sub_address TEXT,
    phoneNo VARCHAR(15),
    amount DECIMAL(10,2),
    cancel_reason TEXT DEFAULT NULL,
    FOREIGN KEY (sub_id) REFERENCES subscription_plan(sub_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Step 7: Provider Allocation Tables
-- 7.1 service_provider_allocation
CREATE TABLE service_provider_allocation (
    allocation_id INT AUTO_INCREMENT PRIMARY KEY,
    service_book_id INT NOT NULL,
    provider_id INT NOT NULL,
    allocated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_book_id) REFERENCES service_booking(service_book_id),
    FOREIGN KEY (provider_id) REFERENCES provider(provider_id)
);
-- 7.2 subscription_provider_allocation
CREATE TABLE subscription_provider_allocation (
    allocation_id INT AUTO_INCREMENT PRIMARY KEY,
    subbook_id INT NOT NULL,
    provider_id INT NOT NULL,
    allocated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subbook_id) REFERENCES subscription_booking(subbook_id),
    FOREIGN KEY (provider_id) REFERENCES provider(provider_id)
);

-- Step 8: Billing Tables
-- 8.1 service_bill_payment
CREATE TABLE service_bill_payment (
    service_bill_id INT AUTO_INCREMENT PRIMARY KEY,
    allocation_id INT NOT NULL,
    service_description TEXT,
    amount DECIMAL(10,2),
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (allocation_id) REFERENCES service_provider_allocation(allocation_id)
);
-- 8.2 subscription_bill_payment
CREATE TABLE subscription_bill_payment (
    subscription_bill_id INT AUTO_INCREMENT PRIMARY KEY,
    allocation_id INT NOT NULL,
    service_description TEXT,
    amount DECIMAL(10,2),
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (allocation_id) REFERENCES subscription_provider_allocation(allocation_id)
);

-- Step 9: Review Tables
-- 9.1 service_review
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
-- 9.2 subscription_review
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

-- Step 10: Insert Initial Data
-- Service Categories
INSERT INTO service_category (service_name) VALUES
('Plumbing Services'),
('Carpentry Services'),
('Electrical Services'),
('Painting Services'),
('Electronic Services'),
('Cleaning Service');

-- Subscription Plans
INSERT INTO subscription_plan (category, description) VALUES
('Weekly Plan', 'Vehicle Wash'),
('Monthly Plan', 'Deep Cleaning'),
('Yearly Plan', 'Utility Check'); 