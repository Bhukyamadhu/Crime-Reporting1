CREATE DATABASE IF NOT EXISTS crime_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE crime_db;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS complaints (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    crime_type VARCHAR(80) NOT NULL,
    description TEXT NOT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    address VARCHAR(255) NULL,
    evidence VARCHAR(255) NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user_id (user_id),
    KEY idx_status (status),
    KEY idx_crime_type (crime_type),
    KEY idx_created_at (created_at),
    KEY idx_lat_lng (latitude, longitude),
    KEY idx_address (address),
    CONSTRAINT fk_complaints_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(120) NULL,
    email VARCHAR(190) NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS case_updates (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT UNSIGNED NOT NULL,
    status VARCHAR(40) NOT NULL,
    note VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_case_updates_complaint (complaint_id),
    CONSTRAINT fk_case_updates_complaint
        FOREIGN KEY (complaint_id) REFERENCES complaints(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    complaint_id INT UNSIGNED NULL,
    message VARCHAR(255) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notifications_user (user_id),
    KEY idx_notifications_created (created_at),
    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_notifications_complaint
        FOREIGN KEY (complaint_id) REFERENCES complaints(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO admins (username, name, email, password)
SELECT 'admin', 'System Admin', 'admin@crime.local', '$2y$10$jD.BOYxBMZwm394nafwiUurq65844/MAveoy.g4m/zZBKprlbJBXe'
WHERE NOT EXISTS (
    SELECT 1 FROM admins WHERE username = 'admin'
);
