-- Mali: เฉพาะตาราง + ข้อมูลเริ่มต้น (ไม่มี CREATE DATABASE)
-- ใช้เมื่อโฮสต์สร้างฐานข้อมูลให้แล้ว (เช่น cPanel)
-- วิธีใช้: เลือกฐานข้อมูลใน phpMyAdmin ก่อน แล้ว Import ไฟล์นี้

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS attachments;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS budget_allocations;
DROP TABLE IF EXISTS budget_months;
DROP TABLE IF EXISTS loans;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL DEFAULT 'ผู้ใช้',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
    setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE loans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    principal DECIMAL(12,2) NOT NULL,
    interest_rate DECIMAL(5,2) NOT NULL DEFAULT 33.00,
    term_months INT UNSIGNED NOT NULL,
    expected_payment DECIMAL(12,2) NOT NULL,
    balance DECIMAL(12,2) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE budget_months (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    budget_ym CHAR(7) NOT NULL UNIQUE COMMENT 'รูปแบบ YYYY-MM',
    opening_balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    salary_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    available_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    savings_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    carry_forward DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status ENUM('draft','active','closed') NOT NULL DEFAULT 'draft',
    wizard_step TINYINT UNSIGNED NOT NULL DEFAULT 1,
    notes TEXT NULL,
    opened_at DATETIME NULL,
    closed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE budget_allocations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    month_id INT UNSIGNED NOT NULL,
    category ENUM('loan','mother','personal','other','savings') NOT NULL,
    loan_id INT UNSIGNED NULL,
    label VARCHAR(120) NOT NULL,
    planned_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    spent_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_alloc_month FOREIGN KEY (month_id) REFERENCES budget_months(id) ON DELETE CASCADE,
    CONSTRAINT fk_alloc_loan FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE SET NULL,
    INDEX idx_alloc_month (month_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    month_id INT UNSIGNED NOT NULL,
    type ENUM('income','expense') NOT NULL,
    category ENUM('salary','loan','mother','personal','other','savings','carry') NOT NULL,
    loan_id INT UNSIGNED NULL,
    allocation_id INT UNSIGNED NULL,
    txn_date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    description VARCHAR(255) NOT NULL DEFAULT '',
    payee VARCHAR(120) NULL,
    payment_method VARCHAR(50) NULL,
    reference_no VARCHAR(100) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_txn_month FOREIGN KEY (month_id) REFERENCES budget_months(id) ON DELETE CASCADE,
    CONSTRAINT fk_txn_loan FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE SET NULL,
    CONSTRAINT fk_txn_alloc FOREIGN KEY (allocation_id) REFERENCES budget_allocations(id) ON DELETE SET NULL,
    INDEX idx_txn_month (month_id),
    INDEX idx_txn_date (txn_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE attachments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_size INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_att_txn FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    INDEX idx_att_txn (transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (username, password_hash, display_name) VALUES
('admin', '$2y$10$uA50mhRswiy13H632kapx.e16yUbBnRiQDFnXWtkSgzA/g.bFk10K', 'ผู้ดูแลระบบ');

INSERT INTO settings (setting_key, setting_value) VALUES
('salary_amount', '31364'),
('savings_rate', '5'),
('personal_budget', '5000'),
('mother_budget', '10000'),
('currency', 'THB'),
('app_name', 'Mali');

INSERT INTO loans (name, principal, interest_rate, term_months, expected_payment, balance, sort_order) VALUES
('Line BK', 29800.00, 33.00, 12, 2500.00, 29800.00, 1),
('Finnix', 13500.00, 33.00, 6, 1500.00, 13500.00, 2),
('Promise', 20000.00, 33.00, 12, 1500.00, 20000.00, 3),
('TTB Global House', 156000.00, 33.00, 72, 5000.00, 156000.00, 4),
('TTB Credits Card', 94000.00, 33.00, 6, 5000.00, 94000.00, 5);
