-- =====================================================
-- Trip Planner — Feature Expansion Migration
-- Run this AFTER database.sql on an existing database:
--   mysql -u root -p trip_planner < migration_new_features.sql
-- Safe to re-run: uses IF NOT EXISTS / conditional column adds.
-- =====================================================
USE trip_planner;

-- ---------------------------------------------------
-- Checklists (packing lists + trip-prep tasks)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS trip_checklist_items (
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
-- Travelers (for expense splitting — lightweight, no login required)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS trip_travelers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trip_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_travelers_trip FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    INDEX idx_travelers_trip (trip_id)
) ENGINE=InnoDB;

-- Expense splitting columns
ALTER TABLE expenses ADD COLUMN paid_by INT UNSIGNED DEFAULT NULL AFTER amount;
ALTER TABLE expenses ADD COLUMN split_with TEXT DEFAULT NULL AFTER paid_by;
-- split_with stores a comma-separated list of trip_travelers.id the cost is split between.
-- paid_by references trip_travelers.id (nullable; NULL = not tracked for splitting).
-- NOTE: if you already ran this migration once, remove the two ALTER TABLE lines above
-- before re-running (older MySQL/MariaDB versions do not support ADD COLUMN IF NOT EXISTS).

-- ---------------------------------------------------
-- Activity comments / notes
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_comments (
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
-- Trip documents (tickets, bookings, passport scans, etc.)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS trip_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trip_id INT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    file_size INT UNSIGNED NOT NULL DEFAULT 0,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_documents_trip FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    INDEX idx_documents_trip (trip_id)
) ENGINE=InnoDB;
