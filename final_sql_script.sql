-- =====================================================
-- Freelancer Job Board - Complete Database Setup
-- Run this script on a new MySQL/MariaDB installation
-- =====================================================

-- Create database
CREATE DATABASE IF NOT EXISTS freelance_db;
USE freelance_db;

-- =====================================================
-- CORE TABLES
-- =====================================================

-- Users table (UML: User abstract class)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(100) NOT NULL,
    type VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    is_admin BOOLEAN DEFAULT FALSE,
    is_deletable BOOLEAN DEFAULT TRUE,
    role VARCHAR(20) DEFAULT 'user',
    isActive BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_username (username),
    UNIQUE KEY unique_email (email)
);

-- Jobs table (UML: Project class)
CREATE TABLE jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    budget DECIMAL(10,2) NOT NULL,
    client_id INT NOT NULL,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id)
);

-- Applications table
CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    freelancer_id INT NOT NULL,
    proposal TEXT NOT NULL,
    bid_amount DECIMAL(10,2) DEFAULT NULL,
    completion_time VARCHAR(100),
    status VARCHAR(20) DEFAULT 'pending',
    job_status VARCHAR(20) DEFAULT 'not_started',
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    payment_method VARCHAR(50) DEFAULT NULL,
    payment_account_info VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id),
    FOREIGN KEY (freelancer_id) REFERENCES users(id)
);

-- Offers table (clients to freelancers)
CREATE TABLE offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    freelancer_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    budget DECIMAL(10,2) NOT NULL,
    completion_time VARCHAR(100),
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id),
    FOREIGN KEY (freelancer_id) REFERENCES users(id)
);

-- Payments table (UML: Payment class)
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    projectId INT NOT NULL,
    clientId INT NOT NULL,
    freelancerId INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    paymentMethod VARCHAR(50),
    transactionId VARCHAR(255),
    platform_fee DECIMAL(10,2) DEFAULT 0,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    completedAt DATETIME,
    FOREIGN KEY (projectId) REFERENCES jobs(id),
    FOREIGN KEY (clientId) REFERENCES users(id),
    FOREIGN KEY (freelancerId) REFERENCES users(id)
);

-- =====================================================
-- PROFILE TABLES
-- =====================================================

-- Client profiles table
CREATE TABLE client_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    companyName VARCHAR(200),
    totalSpent DECIMAL(10,2) DEFAULT 0.00,
    postedProjects INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_client_profile (user_id)
);

-- Freelancer profiles table
CREATE TABLE freelancer_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    skills TEXT,
    past_projects TEXT,
    portfolio_link VARCHAR(500),
    cv_filename VARCHAR(255),
    bio TEXT,
    hourly_rate DECIMAL(10,2),
    totalEarned DECIMAL(10,2) DEFAULT 0.00,
    completedProjects INT DEFAULT 0,
    availability VARCHAR(50) DEFAULT 'available',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_profile (user_id)
);

-- Admin profiles table
CREATE TABLE admin_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    permissionLevel INT DEFAULT 1,
    lastLogin DATETIME,
    actionsLog TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_admin_profile (user_id)
);

-- =====================================================
-- ADMIN & SYSTEM TABLES
-- =====================================================

-- Categories table (for Admin manageCategories)
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    isActive BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reports table (UML: Report class)
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    data JSON,
    generatedBy INT NOT NULL,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generatedBy) REFERENCES users(id)
);

-- Audit Logs table (UML: AuditLog class)
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userId INT,
    action VARCHAR(255) NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    ipAddress VARCHAR(45),
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE SET NULL
);

-- System configuration table (for Admin systemConfig)
CREATE TABLE system_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Insert sample users
-- Password hash for 'password' (default password for all test accounts)
-- Change these passwords after first login!
INSERT INTO users (username, email, password, type, name, phone, is_admin, is_deletable) VALUES
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Super Admin', '+1-555-0001', TRUE, FALSE),
('john', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'freelancer', 'John Doe', '+1-555-0002', FALSE, TRUE),
('client1', 'client1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'Client One', '+1-555-0003', FALSE, TRUE);

-- Insert sample admin profile
INSERT INTO admin_profiles (user_id, permissionLevel, lastLogin) VALUES
(1, 10, NOW());

-- Insert sample freelancer profile
INSERT INTO freelancer_profiles (user_id, skills, past_projects, portfolio_link, bio, hourly_rate, availability, totalEarned, completedProjects) VALUES
(2, 'PHP, MySQL, JavaScript, HTML/CSS, React, Node.js', 'E-commerce website for local business, Mobile app for restaurant ordering, Database design for healthcare system', 'https://johndoe-portfolio.com', 'Experienced full-stack developer with 5+ years of experience in web development. Passionate about creating efficient and user-friendly applications.', 75.00, 'available', 0.00, 0);

-- Insert sample client profile
INSERT INTO client_profiles (user_id, companyName, totalSpent, postedProjects) VALUES
(3, 'Client One Company', 0.00, 0);

-- =====================================================
-- NOTES:
-- =====================================================
-- 1. Default password for all test accounts: 'password'
--    (Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi)
--    Please change these passwords after first login!
--
-- 2. Test Accounts:
--    - Admin: admin@example.com / password
--    - Freelancer: john@example.com / password
--    - Client: client1@example.com / password
--
-- 3. The database is now ready to use!
--    Make sure to update the database credentials in:
--    app/config/database.php