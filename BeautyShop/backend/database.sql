-- =====================================================
-- LUMINÉ BEAUTY SHOP - MySQL Database Schema
-- =====================================================
-- Create database
CREATE DATABASE IF NOT EXISTS luminebeauty CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE luminebeauty;

-- =====================================================
-- BOOKINGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id VARCHAR(50) UNIQUE NOT NULL COMMENT 'Unique booking reference (LUM-xxx)',
    client_name VARCHAR(100) NOT NULL,
    client_phone VARCHAR(20) NOT NULL COMMENT 'Kenyan phone format: 254...',
    client_email VARCHAR(100),
    service VARCHAR(50) NOT NULL,
    service_name VARCHAR(100) NOT NULL,
    preferred_date DATE NOT NULL,
    preferred_time TIME NOT NULL,
    special_requests TEXT,
    amount INT NOT NULL COMMENT 'Amount in KES',
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    password_hash VARCHAR(255) NOT NULL COMMENT 'SHA256 hashed password',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    confirmed_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    
    INDEX idx_booking_id (booking_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_phone (client_phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- PAYMENTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    mpesa_phone VARCHAR(20) NOT NULL COMMENT 'Phone number that will pay',
    amount INT NOT NULL COMMENT 'Amount in KES',
    request_id VARCHAR(100) COMMENT 'M-Pesa STK request ID',
    merchant_request_id VARCHAR(100) COMMENT 'M-Pesa merchant request ID',
    checkout_request_id VARCHAR(100) COMMENT 'M-Pesa checkout request ID',
    mpesa_receipt VARCHAR(100) COMMENT 'M-Pesa receipt number',
    transaction_date VARCHAR(50) COMMENT 'M-Pesa transaction timestamp',
    confirmed_amount INT COMMENT 'Actual confirmed amount from M-Pesa',
    status ENUM('pending', 'processing', 'completed', 'failed', 'reversed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    confirmed_at TIMESTAMP NULL,
    
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    INDEX idx_booking_id (booking_id),
    INDEX idx_status (status),
    INDEX idx_receipt (mpesa_receipt),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- SERVICES TABLE (Reference)
-- =====================================================
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_key VARCHAR(50) UNIQUE NOT NULL,
    service_name VARCHAR(100) NOT NULL,
    description TEXT,
    price INT NOT NULL,
    duration_minutes INT,
    active BOOLEAN DEFAULT 1,
    
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default services
INSERT INTO services (service_key, service_name, description, price, duration_minutes, active) VALUES
('makeup', 'Makeup & Styling', 'Professional makeup application for events, parties, and everyday looks. Expert color matching.', 1500, 60, 1),
('nails', 'Nail Care', 'Manicure, pedicure, nail art, and gel extensions. Premium products used.', 800, 45, 1),
('hair', 'Hair Treatment', 'Hair cutting, styling, treatments, and extensions. Expert color services.', 1200, 90, 1),
('facial', 'Skincare & Facial', 'Advanced facial treatments, chemical peels, and skincare routines.', 2000, 75, 1),
('spa', 'Spa & Massage', 'Relaxing body massages, aromatherapy, and wellness treatments.', 2500, 60, 1),
('waxing', 'Threading & Waxing', 'Eyebrow threading, face waxing, and body hair removal services.', 300, 20, 1);

-- =====================================================
-- ACTIVITY LOG TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action_type VARCHAR(50),
    message TEXT,
    data JSON,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_type (action_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- CUSTOMER FEEDBACK TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT,
    rating INT COMMENT '1-5 stars',
    comment TEXT,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
    INDEX idx_booking_id (booking_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- ADMIN USERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(100),
    password_hash VARCHAR(255),
    role ENUM('admin', 'manager', 'staff') DEFAULT 'staff',
    active BOOLEAN DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- VIEWS
-- =====================================================

-- View: Pending Payments
CREATE OR REPLACE VIEW pending_payments AS
SELECT 
    b.booking_id,
    b.client_name,
    b.client_phone,
    b.service_name,
    b.preferred_date,
    b.preferred_time,
    p.amount,
    p.status,
    p.created_at
FROM bookings b
JOIN payments p ON b.id = p.booking_id
WHERE b.status = 'pending' AND p.status = 'pending';

-- View: Daily Bookings
CREATE OR REPLACE VIEW daily_bookings AS
SELECT 
    preferred_date,
    COUNT(*) as total_bookings,
    SUM(amount) as total_revenue,
    COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_count,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count
FROM bookings
GROUP BY preferred_date;

-- View: Service Statistics
CREATE OR REPLACE VIEW service_stats AS
SELECT 
    service,
    service_name,
    COUNT(*) as total_bookings,
    COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_bookings,
    SUM(amount) as total_revenue
FROM bookings
GROUP BY service, service_name;

-- =====================================================
-- INITIAL DATA
-- =====================================================

-- Insert admin user (default credentials - CHANGE IN PRODUCTION!)
-- Email: admin@luminebeauty.ke
-- Password: Admin@123 (hash)
INSERT INTO admins (email, name, password_hash, role) VALUES 
(
    'admin@luminebeauty.ke',
    'Admin',
    '3d6f0db469e9e9b15ef10e8536148bb04e90e9a8b4e5b3b2e9f1c4d5e6f7g8h9',
    'admin'
) ON DUPLICATE KEY UPDATE id=id;

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================
CREATE INDEX idx_bookings_date ON bookings(preferred_date);
CREATE INDEX idx_bookings_phone ON bookings(client_phone);
CREATE INDEX idx_payments_phone ON payments(mpesa_phone);
CREATE INDEX idx_bookings_service ON bookings(service);

-- =====================================================
-- TRIGGERS (Optional: Auto-increment timestamps)
-- =====================================================

CREATE TRIGGER update_booking_timestamp 
BEFORE UPDATE ON bookings
FOR EACH ROW
SET NEW.confirmed_at = IF(NEW.status = 'confirmed' AND OLD.status != 'confirmed', NOW(), NEW.confirmed_at);

-- =====================================================
-- NOTES
-- =====================================================
/*
Setup Instructions:
1. Create database: mysql -u root -p < database.sql
2. Update config.php with database credentials
3. Update M-Pesa credentials in config.php
4. Configure callback URL at Safaricom developer portal
5. Set up HTTPS for callback (required by Safaricom)
6. Test in sandbox mode before going to production

Default Admin Credentials:
Email: admin@luminebeauty.ke
Password: Admin@123 (CHANGE THIS!)

M-Pesa Sandbox Test Credentials:
Consumer Key: YOUR_KEY
Consumer Secret: YOUR_SECRET
Shortcode: 174379
Passkey: bfb279f9aa9bdbcf158e97dd71a467cd
*/
