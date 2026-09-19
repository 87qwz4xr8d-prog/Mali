-- อัปเกรดฐานที่มีอยู่แล้ว: เพิ่มรายละเอียดรายการรับ-จ่าย
-- Import ใน phpMyAdmin หลังติดตั้งชุดเดิมแล้ว

ALTER TABLE transactions
    ADD COLUMN payee VARCHAR(120) NULL AFTER description,
    ADD COLUMN payment_method VARCHAR(50) NULL AFTER payee,
    ADD COLUMN reference_no VARCHAR(100) NULL AFTER payment_method;
