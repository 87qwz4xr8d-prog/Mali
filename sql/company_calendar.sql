-- ปฏิทินกลางบริษัท
-- นำเข้าทั้งไฟล์นี้ใน phpMyAdmin (แท็บ Import)
-- การนำเข้าซ้ำจะลบตารางเดิมในฐานข้อมูล company_calendar แล้วสร้างใหม่
-- รหัสผ่านของบัญชีตัวอย่างอยู่ใน README ไม่ได้เก็บเป็นข้อความธรรมดาในฐานข้อมูล

SET NAMES utf8mb4;
SET time_zone = '+07:00';

CREATE DATABASE IF NOT EXISTS `company_calendar`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `company_calendar`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `departments`;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `departments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `color` CHAR(7) NOT NULL DEFAULT '#0d6efd',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_departments_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(60) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(120) NOT NULL,
  `role` ENUM('admin', 'employee') NOT NULL DEFAULT 'employee',
  `department_id` INT UNSIGNED NOT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  KEY `idx_users_department` (`department_id`),
  CONSTRAINT `fk_users_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `events` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `start_at` DATETIME NOT NULL,
  `end_at` DATETIME NOT NULL,
  `department_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `google_event_id` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_events_range` (`start_at`, `end_at`),
  KEY `idx_events_department` (`department_id`),
  KEY `idx_events_user` (`user_id`),
  CONSTRAINT `fk_events_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `fk_events_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `chk_events_time` CHECK (`end_at` > `start_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `departments` (`id`, `name`, `color`) VALUES
  (1, 'ฝ่ายบุคคล', '#6f42c1'),
  (2, 'ฝ่ายการเงิน', '#198754'),
  (3, 'ฝ่ายเทคโนโลยีสารสนเทศ', '#0d6efd'),
  (4, 'ฝ่ายขาย', '#fd7e14'),
  (5, 'ฝ่ายปฏิบัติการ', '#dc3545');

-- admin / Admin@2569
-- พนักงานทุกคน / Staff@2569
INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `role`, `department_id`, `active`) VALUES
  (1, 'admin', '$2y$10$n0E6TpAZ2bbbg8sHtTRAfO7Zfy/d5vBhEvxzzZXP8aB4RjJ3vQKbS', 'กานดา ตั้งตรง', 'admin', 3, 1),
  (2, 'somchai', '$2y$10$zPU3l9SLMP6Hayi2GTV4b.YDGKAkfSBUPJy3MU7dchIzjyzhXur9i', 'สมชาย ใจดี', 'employee', 3, 1),
  (3, 'wanida', '$2y$10$zPU3l9SLMP6Hayi2GTV4b.YDGKAkfSBUPJy3MU7dchIzjyzhXur9i', 'วนิดา ศรีสุข', 'employee', 1, 1),
  (4, 'pranee', '$2y$10$zPU3l9SLMP6Hayi2GTV4b.YDGKAkfSBUPJy3MU7dchIzjyzhXur9i', 'ปราณี มั่นคง', 'employee', 2, 1),
  (5, 'anuwat', '$2y$10$zPU3l9SLMP6Hayi2GTV4b.YDGKAkfSBUPJy3MU7dchIzjyzhXur9i', 'อนุวัฒน์ ขายดี', 'employee', 4, 1),
  (6, 'manee', '$2y$10$zPU3l9SLMP6Hayi2GTV4b.YDGKAkfSBUPJy3MU7dchIzjyzhXur9i', 'มานี ทำงาน', 'employee', 5, 1);

INSERT INTO `events` (`title`, `description`, `start_at`, `end_at`, `department_id`, `user_id`) VALUES
  (
    'ทบทวนนโยบายบริษัท',
    'ประชุมผู้ดูแลเรื่องการใช้ปฏิทินกลางและการแชร์งานข้ามแผนก',
    TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL (0 - WEEKDAY(CURDATE())) DAY), '15:00:00'),
    TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL (0 - WEEKDAY(CURDATE())) DAY), '16:00:00'),
    3,
    1
  ),
  (
    'นำเสนอข้อเสนอลูกค้า',
    'นำเสนอแพ็กเกจบริการให้ลูกค้าประจำไตรมาสนี้',
    TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL (1 - WEEKDAY(CURDATE())) DAY), '11:00:00'),
    TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL (1 - WEEKDAY(CURDATE())) DAY), '12:00:00'),
    4,
    5
  ),
  (
    'ประชุมทีมไอที',
    'สรุปงานสัปดาห์และแผนซัพพอร์ตภายใน',
    TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL (2 - WEEKDAY(CURDATE())) DAY), '09:30:00'),
    TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL (2 - WEEKDAY(CURDATE())) DAY), '10:30:00'),
    3,
    2
  ),
  (
    'สัมภาษณ์ผู้สมัคร',
    'สัมภาษณ์ตำแหน่งเจ้าหน้าที่บุคคลรอบแรก',
    TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL (3 - WEEKDAY(CURDATE())) DAY), '10:00:00'),
    TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL (3 - WEEKDAY(CURDATE())) DAY), '11:30:00'),
    1,
    3
  ),
  (
    'ปิดยอดค่าใช้จ่าย',
    'ตรวจเอกสารและปิดยอดค่าใช้จ่ายประจำสัปดาห์',
    TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL (4 - WEEKDAY(CURDATE())) DAY), '14:00:00'),
    TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL (4 - WEEKDAY(CURDATE())) DAY), '15:30:00'),
    2,
    4
  ),
  (
    'ตรวจความปลอดภัยหน้างาน',
    'ตรวจหน้างานสองวันต่อเนื่องกับหัวหน้ากะ',
    TIMESTAMP(CURDATE(), '08:00:00'),
    TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '12:00:00'),
    5,
    6
  ),
  (
    'ปฐมนิเทศพนักงานใหม่',
    'แนะนำระเบียบบริษัทและช่องทางบันทึกงานในปฏิทินกลาง',
    TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL (7 - WEEKDAY(CURDATE())) DAY), '09:00:00'),
    TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL (7 - WEEKDAY(CURDATE())) DAY), '12:00:00'),
    1,
    3
  ),
  (
    'บำรุงรักษาระบบ',
    'หน้าต่างซ่อมบำรุงเซิร์ฟเวอร์ไฟล์และสำรองข้อมูล',
    TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '13:00:00'),
    TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '16:00:00'),
    3,
    2
  );
