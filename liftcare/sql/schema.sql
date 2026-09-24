-- ============================================================
-- LiftCare — ระบบบำรุงรักษารถกระเช้าไฟฟ้า
-- MySQL / MariaDB (utf8mb4) — นำเข้าผ่าน phpMyAdmin ได้
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `liftcare`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_thai_520_w2;
USE `liftcare`;

DROP TABLE IF EXISTS `work_order_parts`;
DROP TABLE IF EXISTS `work_order_checklist`;
DROP TABLE IF EXISTS `work_orders`;
DROP TABLE IF EXISTS `pm_plan_items`;
DROP TABLE IF EXISTS `pm_plans`;
DROP TABLE IF EXISTS `spare_parts`;
DROP TABLE IF EXISTS `vehicles`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `settings`;

-- ผู้ใช้งานระบบ
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(120) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `role` ENUM('admin','manager','technician','viewer') NOT NULL DEFAULT 'technician',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_thai_520_w2;

-- ลูกค้า / ไซต์งาน
CREATE TABLE `customers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(30) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `contact_name` VARCHAR(120) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `email` VARCHAR(120) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_customers_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_thai_520_w2;

-- ทะเบียนรถกระเช้าไฟฟ้า
CREATE TABLE `vehicles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_code` VARCHAR(40) NOT NULL COMMENT 'รหัสทรัพย์สิน',
  `plate_no` VARCHAR(40) DEFAULT NULL COMMENT 'เลขทะเบียน/ป้าย',
  `brand` VARCHAR(80) NOT NULL,
  `model` VARCHAR(80) NOT NULL,
  `serial_no` VARCHAR(80) DEFAULT NULL,
  `lift_type` ENUM('boom','scissor','vertical','trailer','other') NOT NULL DEFAULT 'boom',
  `power_type` ENUM('electric','hybrid','diesel','other') NOT NULL DEFAULT 'electric',
  `max_height_m` DECIMAL(6,2) DEFAULT NULL COMMENT 'ความสูงทำงานสูงสุด (ม.)',
  `capacity_kg` DECIMAL(8,2) DEFAULT NULL COMMENT 'พิกัดน้ำหนัก (กก.)',
  `year_made` SMALLINT UNSIGNED DEFAULT NULL,
  `hour_meter` DECIMAL(10,1) NOT NULL DEFAULT 0 COMMENT 'ชั่วโมงใช้งานสะสม',
  `status` ENUM('ready','in_use','maintenance','repair','retired') NOT NULL DEFAULT 'ready',
  `customer_id` INT UNSIGNED DEFAULT NULL,
  `location` VARCHAR(150) DEFAULT NULL,
  `purchase_date` DATE DEFAULT NULL,
  `warranty_expire` DATE DEFAULT NULL,
  `next_pm_date` DATE DEFAULT NULL,
  `next_pm_hours` DECIMAL(10,1) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `photo` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_vehicles_asset` (`asset_code`),
  KEY `idx_vehicles_status` (`status`),
  KEY `idx_vehicles_customer` (`customer_id`),
  CONSTRAINT `fk_vehicles_customer`
    FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_thai_520_w2;

-- อะไหล่
CREATE TABLE `spare_parts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sku` VARCHAR(40) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `category` VARCHAR(80) DEFAULT NULL,
  `unit` VARCHAR(20) NOT NULL DEFAULT 'ชิ้น',
  `qty_on_hand` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `reorder_level` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `unit_cost` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `location` VARCHAR(80) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_parts_sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_thai_520_w2;

-- แผนบำรุงรักษาเชิงป้องกัน (PM)
CREATE TABLE `pm_plans` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(40) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `lift_type` ENUM('boom','scissor','vertical','trailer','other','all') NOT NULL DEFAULT 'all',
  `interval_days` INT UNSIGNED DEFAULT NULL,
  `interval_hours` DECIMAL(10,1) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pm_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_thai_520_w2;

CREATE TABLE `pm_plan_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pm_plan_id` INT UNSIGNED NOT NULL,
  `seq_no` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `item_name` VARCHAR(200) NOT NULL,
  `check_type` ENUM('visual','measure','test','replace','lubricate','other') NOT NULL DEFAULT 'visual',
  `standard_value` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pm_items_plan` (`pm_plan_id`),
  CONSTRAINT `fk_pm_items_plan`
    FOREIGN KEY (`pm_plan_id`) REFERENCES `pm_plans` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_thai_520_w2;

-- ใบงานซ่อมบำรุง (Job / Work Order)
CREATE TABLE `work_orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `wo_no` VARCHAR(40) NOT NULL,
  `vehicle_id` INT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED DEFAULT NULL,
  `pm_plan_id` INT UNSIGNED DEFAULT NULL,
  `wo_type` ENUM('pm','cm','inspection','upgrade','other') NOT NULL DEFAULT 'cm',
  `priority` ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status` ENUM('open','assigned','in_progress','waiting_parts','completed','cancelled') NOT NULL DEFAULT 'open',
  `title` VARCHAR(200) NOT NULL,
  `problem_desc` TEXT DEFAULT NULL,
  `solution_desc` TEXT DEFAULT NULL,
  `requested_by` VARCHAR(120) DEFAULT NULL,
  `assigned_to` INT UNSIGNED DEFAULT NULL,
  `request_date` DATE NOT NULL,
  `due_date` DATE DEFAULT NULL,
  `start_at` DATETIME DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `hour_meter` DECIMAL(10,1) DEFAULT NULL,
  `labor_cost` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `parts_cost` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `other_cost` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_wo_no` (`wo_no`),
  KEY `idx_wo_status` (`status`),
  KEY `idx_wo_vehicle` (`vehicle_id`),
  KEY `idx_wo_assigned` (`assigned_to`),
  CONSTRAINT `fk_wo_vehicle`
    FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_wo_customer`
    FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_wo_pm`
    FOREIGN KEY (`pm_plan_id`) REFERENCES `pm_plans` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_wo_tech`
    FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_wo_creator`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_thai_520_w2;

CREATE TABLE `work_order_checklist` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `work_order_id` INT UNSIGNED NOT NULL,
  `item_name` VARCHAR(200) NOT NULL,
  `result` ENUM('pass','fail','na','pending') NOT NULL DEFAULT 'pending',
  `actual_value` VARCHAR(100) DEFAULT NULL,
  `remark` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_woc_wo` (`work_order_id`),
  CONSTRAINT `fk_woc_wo`
    FOREIGN KEY (`work_order_id`) REFERENCES `work_orders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_thai_520_w2;

CREATE TABLE `work_order_parts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `work_order_id` INT UNSIGNED NOT NULL,
  `part_id` INT UNSIGNED NOT NULL,
  `qty` DECIMAL(12,2) NOT NULL DEFAULT 1,
  `unit_cost` DECIMAL(12,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_wop_wo` (`work_order_id`),
  CONSTRAINT `fk_wop_wo`
    FOREIGN KEY (`work_order_id`) REFERENCES `work_orders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_wop_part`
    FOREIGN KEY (`part_id`) REFERENCES `spare_parts` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_thai_520_w2;

CREATE TABLE `settings` (
  `setting_key` VARCHAR(60) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_thai_520_w2;

-- -------------------- ข้อมูลเริ่มต้น --------------------
-- รหัสผ่านเริ่มต้นทุกบัญชี: admin123
INSERT INTO `users` (`username`, `password_hash`, `full_name`, `email`, `phone`, `role`) VALUES
('admin', '$2y$10$bOsXIvMF8BX1ylQnSVrROuO8mBjfKKAkdIWoaP7xV0lKNuTAXWMoi', 'ผู้ดูแลระบบ', 'admin@liftcare.local', '0800000000', 'admin'),
('manager', '$2y$10$bOsXIvMF8BX1ylQnSVrROuO8mBjfKKAkdIWoaP7xV0lKNuTAXWMoi', 'หัวหน้าช่าง', 'manager@liftcare.local', '0811111111', 'manager'),
('tech01', '$2y$10$bOsXIvMF8BX1ylQnSVrROuO8mBjfKKAkdIWoaP7xV0lKNuTAXWMoi', 'ช่างสมชาย ใจดี', 'tech01@liftcare.local', '0822222222', 'technician');

INSERT INTO `customers` (`code`, `name`, `contact_name`, `phone`, `address`) VALUES
('C-001', 'บริษัท ก่อสร้างมั่นคง จำกัด', 'คุณวิชัย', '029990001', 'กรุงเทพฯ'),
('C-002', 'ห้างหุ้นส่วน คลังสินค้าชัยวัฒน์', 'คุณนภา', '038880002', 'ชลบุรี'),
('C-003', 'โครงการอาคารสูงริเวอร์ไซด์', 'คุณอารี', '024440003', 'นนทบุรี');

INSERT INTO `vehicles`
(`asset_code`,`plate_no`,`brand`,`model`,`serial_no`,`lift_type`,`power_type`,`max_height_m`,`capacity_kg`,`year_made`,`hour_meter`,`status`,`customer_id`,`location`,`purchase_date`,`next_pm_date`,`next_pm_hours`)
VALUES
('BL-E-001','กค-1001','JLG','E450AJ','JLG450-001','boom','electric',15.70,230,2021,1250.5,'ready',1,'โกดัง A','2021-03-15','2026-10-15',1500),
('BL-E-002','กค-1002','Genie','Z-45/25J DC','GEN4525-088','boom','electric',15.90,227,2022,860.0,'in_use',2,'ไซต์ชลบุรี','2022-06-01','2026-09-30',1000),
('BL-E-003','กค-1003','Haulotte','HA16 RTJ PRO','HAU16-221','boom','electric',16.00,230,2020,2105.0,'maintenance',NULL,'อู่ซ่อมกลาง','2020-11-20','2026-09-20',2200),
('SC-E-001','กค-2001','Skyjack','SJIII 3226','SJ3226-441','scissor','electric',9.75,227,2023,320.0,'ready',3,'นนทบุรี','2023-01-10','2026-11-01',500),
('BL-E-004','กค-1004','Dingli','BA16ERT','DING16-019','boom','electric',16.00,230,2024,95.0,'ready',NULL,'โกดัง A','2024-08-01','2026-12-01',250);

INSERT INTO `spare_parts` (`sku`,`name`,`category`,`unit`,`qty_on_hand`,`reorder_level`,`unit_cost`,`location`) VALUES
('BAT-48V-100','แบตเตอรี่ขับเคลื่อน 48V 100Ah','ไฟฟ้า','ชุด',4,2,18500,'ชั้น B1'),
('HYD-FIL-10','ไส้กรองไฮดรอลิก #10','ไฮดรอลิก','ชิ้น',18,5,450,'ชั้น A2'),
('TIRE-SOLID-24','ยางตัน 24x12-12','ล้อ/ยาง','เส้น',8,4,3200,'ชั้น C1'),
('CTRL-JC-01','จอยสติ๊กควบคุมกระเช้า','ไฟฟ้า','ชิ้น',3,1,8900,'ชั้น B3'),
('OIL-HYD-20','น้ำมันไฮดรอลิก ISO VG46 20L','น้ำมัน','ถัง',12,4,1650,'ชั้น A1');

INSERT INTO `pm_plans` (`code`,`name`,`lift_type`,`interval_days`,`interval_hours`,`description`) VALUES
('PM-BOOM-M','PM รายเดือน รถกระเช้าบูม','boom',30,100,'ตรวจสภาพทั่วไป ระบบไฟฟ้า และความปลอดภัย'),
('PM-BOOM-Q','PM รายไตรมาส รถกระเช้าบูม','boom',90,300,'ตรวจระบบไฮดรอลิก โครงสร้าง และแบตเตอรี่'),
('PM-SCISSOR-M','PM รายเดือน กรรไกรไฟฟ้า','scissor',30,80,'ตรวจแพลตฟอร์ม ระบบยก และเบรก');

INSERT INTO `pm_plan_items` (`pm_plan_id`,`seq_no`,`item_name`,`check_type`,`standard_value`) VALUES
(1,1,'ตรวจระดับน้ำมันไฮดรอลิก','visual','อยู่ในช่วงปกติ'),
(1,2,'ตรวจสภาพยางและแรงดัน','visual','ไม่สึกหรอเกินกำหนด'),
(1,3,'ทดสอบปุ่ม Emergency Stop','test','ตัดไฟได้ทันที'),
(1,4,'ตรวจไฟแสดงสถานะและแตร','test','ทำงานปกติ'),
(1,5,'หล่อลื่นจุดหมุนบูม','lubricate',NULL),
(2,1,'วัดแรงดันไฮดรอลิกระบบหลัก','measure','ตามคู่มือรุ่น'),
(2,2,'ตรวจรอยร้าวโครงสร้างบูม','visual','ไม่มีรอยร้าว'),
(2,3,'ตรวจความจุและแรงดันแบตเตอรี่','measure','≥ 80%'),
(2,4,'เปลี่ยนไส้กรองไฮดรอลิก (ถ้าครบระยะ)','replace',NULL),
(3,1,'ตรวจรางและโรลเลอร์แพลตฟอร์ม','visual','ลื่นตัว ไม่ติด'),
(3,2,'ทดสอบระบบเบรกล้อ','test','ล็อกได้'),
(3,3,'ตรวจสายไฟและคอนแทคเตอร์','visual','ไม่มีจุดไหม้');

INSERT INTO `work_orders`
(`wo_no`,`vehicle_id`,`customer_id`,`pm_plan_id`,`wo_type`,`priority`,`status`,`title`,`problem_desc`,`requested_by`,`assigned_to`,`request_date`,`due_date`,`hour_meter`,`labor_cost`,`created_by`)
VALUES
('WO-2026-0001',3,NULL,1,'pm','medium','in_progress','PM รายเดือน BL-E-003','ตามแผน PM รายเดือน','ระบบ',3,'2026-09-20','2026-09-25',2105.0,0,1),
('WO-2026-0002',2,2,NULL,'cm','high','open','กระเช้าเงียบผิดปกติตอนยกบูม','มีเสียงดังผิดปกติจากระบบไฮดรอลิกขณะยก','คุณนภา',3,'2026-09-22','2026-09-24',860.0,0,2),
('WO-2026-0003',1,1,2,'inspection','medium','assigned','ตรวจรับประกันประจำปี','ตรวจสภาพก่อนต่อประกัน','คุณวิชัย',2,'2026-09-18','2026-09-28',1250.5,0,1),
('WO-2026-0004',4,3,NULL,'cm','urgent','waiting_parts','จอยสติ๊กควบคุมหลวม','ควบคุมทิศทางได้ไม่นิ่ง','คุณอารี',3,'2026-09-21','2026-09-23',320.0,0,2);

INSERT INTO `work_order_checklist` (`work_order_id`,`item_name`,`result`) VALUES
(1,'ตรวจระดับน้ำมันไฮดรอลิก','pass'),
(1,'ตรวจสภาพยางและแรงดัน','pass'),
(1,'ทดสอบปุ่ม Emergency Stop','pending'),
(1,'ตรวจไฟแสดงสถานะและแตร','pending'),
(1,'หล่อลื่นจุดหมุนบูม','pending');

INSERT INTO `settings` (`setting_key`,`setting_value`) VALUES
('app_name','LiftCare'),
('app_subtitle','ระบบบำรุงรักษารถกระเช้าไฟฟ้า'),
('company_name','LiftCare Maintenance'),
('company_phone','02-000-0000'),
('wo_prefix','WO'),
('timezone','Asia/Bangkok');

SET FOREIGN_KEY_CHECKS = 1;
