-- Migration script to update existing database to match UML requirements
-- Run this after setup.sql if you have an existing database

USE freelance_db;

-- Add missing columns to users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS role VARCHAR(20) DEFAULT 'user' AFTER type,
ADD COLUMN IF NOT EXISTS isActive BOOLEAN DEFAULT TRUE AFTER is_deletable;

-- Create client_profiles table if not exists
CREATE TABLE IF NOT EXISTS client_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    companyName VARCHAR(200),
    totalSpent DECIMAL(10,2) DEFAULT 0.00,
    postedProjects INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_client_profile (user_id)
);

-- Add missing columns to freelancer_profiles
ALTER TABLE freelancer_profiles 
ADD COLUMN IF NOT EXISTS totalEarned DECIMAL(10,2) DEFAULT 0.00 AFTER hourly_rate,
ADD COLUMN IF NOT EXISTS completedProjects INT DEFAULT 0 AFTER totalEarned;

-- Create admin_profiles table if not exists
CREATE TABLE IF NOT EXISTS admin_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    permissionLevel INT DEFAULT 1,
    lastLogin DATETIME,
    actionsLog TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_admin_profile (user_id)
);

-- Create reports table if not exists
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    data JSON,
    generatedBy INT NOT NULL,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generatedBy) REFERENCES users(id)
);

-- Create audit_logs table if not exists
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userId INT,
    action VARCHAR(255) NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    ipAddress VARCHAR(45),
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE SET NULL
);

-- Create disputes table if not exists
CREATE TABLE IF NOT EXISTS disputes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    projectId INT NOT NULL,
    raisedBy INT NOT NULL,
    against INT NOT NULL,
    reason TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    resolvedBy INT,
    resolvedAt DATETIME,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (projectId) REFERENCES jobs(id),
    FOREIGN KEY (raisedBy) REFERENCES users(id),
    FOREIGN KEY (against) REFERENCES users(id),
    FOREIGN KEY (resolvedBy) REFERENCES users(id) ON DELETE SET NULL
);

-- Create payments table if not exists
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    projectId INT NOT NULL,
    clientId INT NOT NULL,
    freelancerId INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    paymentMethod VARCHAR(50),
    transactionId VARCHAR(255),
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    completedAt DATETIME,
    FOREIGN KEY (projectId) REFERENCES jobs(id),
    FOREIGN KEY (clientId) REFERENCES users(id),
    FOREIGN KEY (freelancerId) REFERENCES users(id)
);
-- Ensure payments table has platform_fee for platform revenue tracking
ALTER TABLE payments ADD COLUMN IF NOT EXISTS platform_fee DECIMAL(10,2) DEFAULT 0;

-- Create categories table if not exists
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    isActive BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create system_config table if not exists
CREATE TABLE IF NOT EXISTS system_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Initialize existing users with profiles
INSERT IGNORE INTO client_profiles (user_id, companyName, totalSpent, postedProjects)
SELECT id, NULL, 0.00, (SELECT COUNT(*) FROM jobs WHERE client_id = users.id)
FROM users WHERE type = 'client';

INSERT IGNORE INTO admin_profiles (user_id, permissionLevel, lastLogin)
SELECT id, 10, NOW()
FROM users WHERE type = 'admin';

-- Update existing freelancer profiles with default values
UPDATE freelancer_profiles 
SET totalEarned = COALESCE(totalEarned, 0.00),
    completedProjects = COALESCE(completedProjects, 0)
WHERE totalEarned IS NULL OR completedProjects IS NULL;

-- Add bid_amount to applications for bidding support
ALTER TABLE applications
ADD COLUMN IF NOT EXISTS bid_amount DECIMAL(10,2) DEFAULT NULL AFTER proposal;

