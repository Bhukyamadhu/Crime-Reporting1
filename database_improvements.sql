-- Crime Reporting and Management System - Schema improvements
-- Run on database: crime_db

ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE complaints CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE users
    MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    MODIFY email VARCHAR(190) NOT NULL;

ALTER TABLE users
    ADD UNIQUE INDEX IF NOT EXISTS uq_users_email (email);

-- Ensure complaint columns expected by application
ALTER TABLE complaints
    MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    MODIFY user_id INT UNSIGNED NOT NULL,
    MODIFY crime_type VARCHAR(80) NOT NULL,
    MODIFY status VARCHAR(40) NOT NULL DEFAULT 'Pending',
    ADD COLUMN IF NOT EXISTS address VARCHAR(255) NULL AFTER longitude;

-- Query performance indexes (requested + additional useful indexes)
ALTER TABLE complaints
    ADD INDEX IF NOT EXISTS idx_user_id (user_id),
    ADD INDEX IF NOT EXISTS idx_status (status),
    ADD INDEX IF NOT EXISTS idx_crime_type (crime_type),
    ADD INDEX IF NOT EXISTS idx_created_at (created_at),
    ADD INDEX IF NOT EXISTS idx_lat_lng (latitude, longitude),
    ADD INDEX IF NOT EXISTS idx_address (address);

-- Foreign key
ALTER TABLE complaints
    ADD CONSTRAINT fk_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

-- Admin table compatible with both username/password and email/password login
CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(120) NULL,
    email VARCHAR(190) NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Case timeline table
CREATE TABLE IF NOT EXISTS case_updates (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT UNSIGNED NOT NULL,
    status VARCHAR(40) NOT NULL,
    note VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_case_updates_complaint (complaint_id),
    CONSTRAINT fk_case_updates_complaint
        FOREIGN KEY (complaint_id) REFERENCES complaints(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    complaint_id INT UNSIGNED NULL,
    message VARCHAR(255) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_user (user_id),
    INDEX idx_notifications_created (created_at),
    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_notifications_complaint
        FOREIGN KEY (complaint_id) REFERENCES complaints(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed one admin (Admin@123)
INSERT INTO admins (username, name, email, password)
SELECT 'admin', 'System Admin', 'admin@crime.local', '$2y$10$jD.BOYxBMZwm394nafwiUurq65844/MAveoy.g4m/zZBKprlbJBXe'
WHERE NOT EXISTS (
    SELECT 1 FROM admins WHERE username = 'admin'
);

-- Chat and richer notifications upgrades
ALTER TABLE notifications
    ADD COLUMN IF NOT EXISTS type VARCHAR(30) NOT NULL DEFAULT 'update' AFTER message;

CREATE TABLE IF NOT EXISTS messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT UNSIGNED NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message_text TEXT NOT NULL,
    attachment VARCHAR(255) NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_seen TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_messages_complaint (complaint_id),
    INDEX idx_messages_seen (is_seen),
    INDEX idx_messages_sent (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
