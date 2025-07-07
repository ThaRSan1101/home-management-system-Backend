-- Create the database
CREATE DATABASE IF NOT EXISTS ServiceHub;
USE ServiceHub;

-- 1. users
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

-- 2. service (categories)
CREATE TABLE service (
    service_category_id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL
);

-- 3. provider
CREATE TABLE provider (
    provider_id INT AUTO_INCREMENT PRIMARY KEY,
    description TEXT,
    service TEXT,
    qualifications TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    service_category_id INT,
    user_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (service_category_id) REFERENCES service(service_category_id)
);

-- 4. service_booking
CREATE TABLE service_booking (
    service_book_id INT AUTO_INCREMENT PRIMARY KEY,
    service_category_id INT NOT NULL,
    user_id INT NOT NULL,
    serbooking_status ENUM('pending', 'accepted', 'rejected', 'completed') DEFAULT 'pending',
    serbooking_date DATE,
    service_date DATE,
    service_time TIME,
    service_address TEXT,
    email VARCHAR(100),
    FOREIGN KEY (service_category_id) REFERENCES service(service_category_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- 5. subscription_plan
CREATE TABLE subscription_plan (
    sub_id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(100),
    description TEXT
);

-- 6. subscription_booking
CREATE TABLE subscription_booking (
    subbook_id INT AUTO_INCREMENT PRIMARY KEY,
    sub_id INT NOT NULL,
    email VARCHAR(100),
    user_id INT NOT NULL,
    subbooking_status ENUM('pending', 'active', 'cancelled') DEFAULT 'pending',
    subbooking_date DATE,
    sub_date DATE,
    sub_address TEXT,
    FOREIGN KEY (sub_id) REFERENCES subscription_plan(sub_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- 7. allocation
CREATE TABLE allocation (
    allocation_id INT AUTO_INCREMENT PRIMARY KEY,
    service_book_id INT,
    subbook_id INT,
    provider_id INT NOT NULL,
    FOREIGN KEY (service_book_id) REFERENCES service_booking(service_book_id),
    FOREIGN KEY (subbook_id) REFERENCES subscription_booking(subbook_id),
    FOREIGN KEY (provider_id) REFERENCES provider(provider_id)
);

-- 8. booking_bill_payment
CREATE TABLE booking_bill_payment (
    booking_bill_id INT AUTO_INCREMENT PRIMARY KEY,
    service_book_id INT,
    subbook_id INT,
    serbooking_date DATE,
    subbooking_date DATE,
    amount DECIMAL(10,2),
    service_category_id INT,
    sub_id INT,
    user_id INT,
    FOREIGN KEY (service_book_id) REFERENCES service_booking(service_book_id),
    FOREIGN KEY (subbook_id) REFERENCES subscription_booking(subbook_id),
    FOREIGN KEY (service_category_id) REFERENCES service(service_category_id),
    FOREIGN KEY (sub_id) REFERENCES subscription_plan(sub_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- 9. service_bill_payment
CREATE TABLE service_bill_payment (
    service_bill_id INT AUTO_INCREMENT PRIMARY KEY,
    service_book_id INT,
    subbook_id INT,
    provider_id INT,
    service_date DATE,
    sub_date DATE,
    amount DECIMAL(10,2),
    service_category_id INT,
    sub_id INT,
    FOREIGN KEY (service_book_id) REFERENCES service_booking(service_book_id),
    FOREIGN KEY (subbook_id) REFERENCES subscription_booking(subbook_id),
    FOREIGN KEY (provider_id) REFERENCES provider(provider_id),
    FOREIGN KEY (service_category_id) REFERENCES service(service_category_id),
    FOREIGN KEY (sub_id) REFERENCES subscription_plan(sub_id)
);

-- 10. message (Contact Us)
CREATE TABLE message (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100),
    phone_number VARCHAR(15),
    message TEXT,
    subject VARCHAR(255),
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 11. otp table (fixed)
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

-- 12. password_reset table
CREATE TABLE password_reset (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    code VARCHAR(100) NOT NULL,
    expires_at DATETIME NOT NULL,
    UNIQUE KEY unique_email (email)
);

-- 13. review table
CREATE TABLE review (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    customer_id INT NOT NULL,
    provider_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    feedback_text TEXT,
    reviewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES service_booking(service_book_id),
    FOREIGN KEY (customer_id) REFERENCES users(user_id),
    FOREIGN KEY (provider_id) REFERENCES provider(provider_id)
);

-- Insert initial data

-- Insert service categories
INSERT INTO service (service_name) VALUES 
('Cleaning'),
('Plumbing'),
('Electrical'),
('Carpentry'),
('Painting'),
('Gardening'),
('Security'),
('Maintenance');

-- Insert subscription plans
INSERT INTO subscription_plan (category, description) VALUES 
('Basic', 'Monthly cleaning service'),
('Premium', 'Weekly cleaning and maintenance'),
('VIP', 'Daily cleaning and 24/7 support');

-- Insert admin user (password: admin123)
INSERT INTO users (name, email, password, phone_number, address, user_type) VALUES 
('Admin User', 'admin@servicehub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0771234567', 'Colombo', 'admin'); 