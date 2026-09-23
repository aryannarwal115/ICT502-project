-- =====================================================
-- Trip Planner Database Schema
-- Run this in phpMyAdmin or via: mysql -u root -p < database.sql
-- =====================================================

CREATE DATABASE IF NOT EXISTS trip_planner CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE trip_planner;

-- ---------------------------------------------------
-- users
-- ---------------------------------------------------
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_email (email),
    INDEX idx_users_role (role)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- trips
-- ---------------------------------------------------
CREATE TABLE trips (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    cover_image VARCHAR(255) DEFAULT NULL,
    notes TEXT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('planned','ongoing','completed','cancelled') NOT NULL DEFAULT 'planned',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_trips_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_trips_user (user_id),
    INDEX idx_trips_status (status)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- destinations
-- ---------------------------------------------------
CREATE TABLE destinations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trip_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    country VARCHAR(100) NOT NULL,
    location VARCHAR(255) DEFAULT NULL,
    notes TEXT,
    arrival_date DATE NOT NULL,
    departure_date DATE NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_destinations_trip FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    INDEX idx_destinations_trip (trip_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- activities
-- ---------------------------------------------------
CREATE TABLE activities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trip_id INT UNSIGNED NOT NULL,
    destination_id INT UNSIGNED DEFAULT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    location VARCHAR(255) DEFAULT NULL,
    activity_date DATE NOT NULL,
    start_time TIME DEFAULT NULL,
    end_time TIME DEFAULT NULL,
    estimated_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activities_trip FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    CONSTRAINT fk_activities_destination FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE SET NULL,
    INDEX idx_activities_trip (trip_id),
    INDEX idx_activities_date (activity_date)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- expenses
-- ---------------------------------------------------
CREATE TABLE expenses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trip_id INT UNSIGNED NOT NULL,
    category ENUM('flight','transport','accommodation','activity','food','other') NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    paid_by INT UNSIGNED DEFAULT NULL,
    split_with TEXT DEFAULT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'USD',
    expense_date DATE DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_expenses_trip FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    INDEX idx_expenses_trip (trip_id),
    INDEX idx_expenses_category (category)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- trip_shares
-- ---------------------------------------------------
CREATE TABLE trip_shares (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trip_id INT UNSIGNED NOT NULL UNIQUE,
    token CHAR(64) NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_shares_trip FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    INDEX idx_shares_token (token)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- trip_checklist_items (packing lists + trip-prep tasks)
-- ---------------------------------------------------
CREATE TABLE trip_checklist_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trip_id INT UNSIGNED NOT NULL,
    type ENUM('packing','prep') NOT NULL DEFAULT 'packing',
    item VARCHAR(150) NOT NULL,
    is_done TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_checklist_trip FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    INDEX idx_checklist_trip (trip_id, type)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- trip_travelers (lightweight participants for expense splitting)
-- ---------------------------------------------------
CREATE TABLE trip_travelers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trip_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_travelers_trip FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    INDEX idx_travelers_trip (trip_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- activity_comments (notes/discussion on individual activities)
-- ---------------------------------------------------
CREATE TABLE activity_comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    activity_id INT UNSIGNED NOT NULL,
    trip_id INT UNSIGNED NOT NULL,
    author VARCHAR(100) NOT NULL DEFAULT 'Me',
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comments_activity FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE,
    INDEX idx_comments_activity (activity_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- trip_documents (tickets, bookings, passport scans, etc.)
-- ---------------------------------------------------
CREATE TABLE trip_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trip_id INT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    file_size INT UNSIGNED NOT NULL DEFAULT 0,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_documents_trip FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    INDEX idx_documents_trip (trip_id)
) ENGINE=InnoDB;

-- =====================================================
-- SAMPLE DEVELOPMENT DATA
-- =====================================================

-- Demo admin account: email admin@tripplanner.local / password: Admin@123
-- Demo user account:  email demo@tripplanner.local  / password: Demo@123
-- (hashes below generated with PHP password_hash(), bcrypt)
INSERT INTO users (name, email, password_hash, role, status) VALUES
('Site Admin', 'admin@tripplanner.local', '$2y$10$uw9i0u0xf8Z1Hu9RmxUu/e7tQrZwJb3tNhuJ.KGhEqV3ZqhqZhP.a', 'admin', 'active'),
('Demo User', 'demo@tripplanner.local', '$2y$10$ykmYq/8/YWpTzAnccblRdOeBiYuG.ank3cKoDUXl2eY2mh1uRXaRW', 'user', 'active');

INSERT INTO trips (user_id, name, description, start_date, end_date, status) VALUES
(2, 'Rajasthan Heritage Trip', 'A cultural tour through royal Rajasthan.', '2026-11-10', '2026-11-18', 'planned');

INSERT INTO destinations (trip_id, name, country, location, arrival_date, departure_date, sort_order) VALUES
(1, 'Jaipur', 'India', 'Rajasthan, India', '2026-11-10', '2026-11-13', 1),
(1, 'Udaipur', 'India', 'Rajasthan, India', '2026-11-13', '2026-11-18', 2);

INSERT INTO activities (trip_id, destination_id, name, description, location, activity_date, start_time, end_time, estimated_cost) VALUES
(1, 1, 'Amber Fort Tour', 'Guided tour of Amber Fort', 'Jaipur', '2026-11-11', '09:00:00', '12:00:00', 20.00),
(1, 2, 'Lake Pichola Boat Ride', 'Evening boat ride', 'Udaipur', '2026-11-14', '17:00:00', '18:30:00', 15.00);

INSERT INTO expenses (trip_id, category, description, amount, currency, expense_date) VALUES
(1, 'flight', 'Round trip flight', 250.00, 'USD', '2026-11-10'),
(1, 'accommodation', 'Hotel Jaipur (3 nights)', 180.00, 'USD', '2026-11-10'),
(1, 'accommodation', 'Hotel Udaipur (5 nights)', 300.00, 'USD', '2026-11-13'),
(1, 'food', 'Meals estimate', 120.00, 'USD', '2026-11-10');
