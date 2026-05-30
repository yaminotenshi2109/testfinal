-- ============================================================
--  HỆ THỐNG QUẢN LÝ KÝ TÚC XÁ (KTX)
--  Database: ktx
--  Chuẩn: 3NF | Engine: InnoDB | Charset: utf8mb4
--  Tác giả: KTX Management System
--  Ngày tạo: 2026-05-13
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;
SET time_zone = "+07:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `ktx`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `ktx`;


-- ============================================================
-- BẢNG 1: users
-- Mô tả : Tài khoản hệ thống (admin & sinh viên)
-- Người phụ trách: Thành viên 1
-- Quan hệ : 1-N với students, notifications
--           referenced bởi room_registrations, utility_readings,
--           violation_records, maintenance_requests
-- ============================================================
CREATE TABLE `users` (
  `id`           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `username`     VARCHAR(50)      NOT NULL,
  `email`        VARCHAR(100)     NOT NULL,
  `password_hash`VARCHAR(255)     NOT NULL COMMENT 'Dùng password_hash() PHP',
  `role`         ENUM('admin','student') NOT NULL DEFAULT 'student',
  `status`       ENUM('active','inactive','banned') NOT NULL DEFAULT 'active',
  `created_at`   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
                                  ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email`    (`email`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Tài khoản hệ thống – chuẩn 3NF: không lưu dữ liệu sinh viên ở đây';


-- ============================================================
-- BẢNG 2: students
-- Mô tả : Hồ sơ chi tiết sinh viên (tách khỏi users – 3NF)
-- Người phụ trách: Thành viên 1
-- Quan hệ : 1-1 với users | 1-N với room_registrations,
--           contracts, violation_records
-- ============================================================
CREATE TABLE `students` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`        INT UNSIGNED  NOT NULL,
  `student_code`   VARCHAR(20)   NOT NULL COMMENT 'Mã số sinh viên',
  `full_name`      VARCHAR(100)  NOT NULL,
  `gender`         ENUM('male','female') NOT NULL,
  `dob`            DATE          NOT NULL COMMENT 'Ngày sinh',
  `faculty`        VARCHAR(100)  NOT NULL COMMENT 'Khoa/Viện',
  `program`        VARCHAR(100)  NOT NULL COMMENT 'Chương trình học (CLC, đại trà...)',
  `priority_level` TINYINT       NOT NULL DEFAULT 0
                   COMMENT '0=bình thường, 1=chính sách, 2=ưu tiên cao',
  `phone`          VARCHAR(15)   NOT NULL,
  `hometown`       VARCHAR(200)  NOT NULL,
  `id_card`        VARCHAR(20)   NOT NULL COMMENT 'Số CCCD/CMND',
  `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                 ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_students_user_id`      (`user_id`),
  UNIQUE KEY `uq_students_student_code` (`student_code`),
  UNIQUE KEY `uq_students_id_card`      (`id_card`),
  CONSTRAINT `fk_students_user`
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Hồ sơ sinh viên – tách riêng khỏi users để đạt 3NF';


-- ============================================================
-- BẢNG 3: buildings
-- Mô tả : Danh sách tòa nhà KTX
-- Người phụ trách: Thành viên 1
-- Quan hệ : 1-N với rooms
-- ============================================================
CREATE TABLE `buildings` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`         VARCHAR(100)  NOT NULL COMMENT 'Tên tòa nhà (A1, B2...)',
  `total_floors` TINYINT       NOT NULL DEFAULT 1,
  `gender_type`  ENUM('male','female','mixed') NOT NULL
                 COMMENT 'Giới tính được ở trong tòa',
  `manager_name` VARCHAR(100)  NOT NULL COMMENT 'Tên quản lý tòa',
  `manager_phone`VARCHAR(15)   NOT NULL,
  `address`      VARCHAR(255)  NOT NULL,
  `status`       ENUM('active','maintenance','closed') NOT NULL DEFAULT 'active',
  `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_buildings_name` (`name`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Tòa nhà KTX – gender_type kiểm soát room allocation logic';


-- ============================================================
-- BẢNG 4: rooms
-- Mô tả : Phòng trong từng tòa nhà
-- Người phụ trách: Thành viên 2
-- Quan hệ : N-1 với buildings | 1-N với room_registrations,
--           contracts, utility_readings, room_amenities,
--           maintenance_requests
-- ============================================================
CREATE TABLE `rooms` (
  `id`               INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `building_id`      INT UNSIGNED   NOT NULL,
  `room_number`      VARCHAR(10)    NOT NULL COMMENT 'Số phòng (101, 202...)',
  `floor`            TINYINT        NOT NULL DEFAULT 1,
  `room_type`        ENUM('standard','deluxe','ac_standard','ac_deluxe')
                     NOT NULL DEFAULT 'standard',
  `capacity`         TINYINT        NOT NULL DEFAULT 4
                     COMMENT 'Số giường tối đa',
  `current_occupants`TINYINT        NOT NULL DEFAULT 0
                     COMMENT 'Số sinh viên đang ở – cập nhật khi duyệt hợp đồng',
  `price_per_month`  DECIMAL(10,2)  NOT NULL COMMENT 'Giá thuê cơ bản/tháng (VND)',
  `has_ac`           TINYINT(1)     NOT NULL DEFAULT 0,
  `status`           ENUM('available','full','maintenance','inactive')
                     NOT NULL DEFAULT 'available',
  `created_at`       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rooms_building_number` (`building_id`, `room_number`),
  CONSTRAINT `fk_rooms_building`
    FOREIGN KEY (`building_id`) REFERENCES `buildings`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Phòng KTX – current_occupants tự động cập nhật qua trigger hoặc PHP';


-- ============================================================
-- BẢNG 5: room_registrations
-- Mô tả : Đăng ký phòng của sinh viên theo học kỳ
-- Người phụ trách: Thành viên 2
-- Quan hệ : N-1 với students, rooms, users(reviewed_by)
--           1-1 với contracts (khi được duyệt)
-- ============================================================
CREATE TABLE `room_registrations` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `student_id`    INT UNSIGNED  NOT NULL,
  `room_id`       INT UNSIGNED  NOT NULL,
  `semester`      ENUM('HK1','HK2','HKH') NOT NULL
                  COMMENT 'Học kỳ 1 / Học kỳ 2 / Học kỳ hè',
  `academic_year` SMALLINT      NOT NULL COMMENT 'Năm học (VD: 2025)',
  `status`        ENUM('pending','approved','rejected','cancelled')
                  NOT NULL DEFAULT 'pending',
  `registered_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at`   DATETIME      NULL DEFAULT NULL,
  `reviewed_by`   INT UNSIGNED  NULL DEFAULT NULL
                  COMMENT 'user_id của admin duyệt',
  `reject_reason` VARCHAR(500)  NULL DEFAULT NULL,
  `notes`         TEXT          NULL COMMENT 'Ghi chú của sinh viên',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reg_student_semester`
    (`student_id`, `semester`, `academic_year`)
    COMMENT 'Mỗi sinh viên chỉ đăng ký 1 phòng/học kỳ',
  KEY `idx_reg_room`       (`room_id`),
  KEY `idx_reg_status`     (`status`),
  KEY `idx_reg_reviewer`   (`reviewed_by`),
  CONSTRAINT `fk_reg_student`
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_reg_room`
    FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_reg_reviewer`
    FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Đơn đăng ký phòng – unique constraint ngăn đăng ký 2 lần/học kỳ';


-- ============================================================
-- BẢNG 6: contracts
-- Mô tả : Hợp đồng thuê phòng (tạo sau khi duyệt đăng ký)
-- Người phụ trách: Thành viên 2
-- Quan hệ : 1-1 với room_registrations | N-1 với students, rooms
--           1-N với invoices, violation_records
-- ============================================================
CREATE TABLE `contracts` (
  `id`                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `registration_id`   INT UNSIGNED  NOT NULL,
  `student_id`        INT UNSIGNED  NOT NULL,
  `room_id`           INT UNSIGNED  NOT NULL,
  `start_date`        DATE          NOT NULL,
  `end_date`          DATE          NOT NULL,
  `monthly_fee`       DECIMAL(10,2) NOT NULL COMMENT 'Snapshot giá tại thời điểm ký',
  `status`            ENUM('active','expired','terminated','under_review')
                      NOT NULL DEFAULT 'active',
  `signed_at`         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `terminated_at`     DATETIME      NULL DEFAULT NULL,
  `terminated_reason` VARCHAR(500)  NULL DEFAULT NULL,
  `created_at`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_contracts_registration` (`registration_id`),
  KEY `idx_contracts_student` (`student_id`),
  KEY `idx_contracts_room`    (`room_id`),
  KEY `idx_contracts_status`  (`status`),
  CONSTRAINT `fk_contracts_registration`
    FOREIGN KEY (`registration_id`) REFERENCES `room_registrations`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_contracts_student`
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_contracts_room`
    FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `chk_contracts_dates`
    CHECK (`end_date` > `start_date`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Hợp đồng thuê phòng – monthly_fee snapshot tránh phụ thuộc bắc cầu (3NF)';


-- ============================================================
-- BẢNG 7: invoices
-- Mô tả : Hóa đơn hàng tháng (tiền phòng + điện + nước + AC)
-- Người phụ trách: Thành viên 3
-- Quan hệ : N-1 với contracts
-- Ghi chú  : total_amount là cột tính toán lưu sẵn (denormalized
--            cho phép vì yêu cầu báo cáo nhanh – ghi chú trong ERD)
-- ============================================================
CREATE TABLE `invoices` (
  `id`              INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `contract_id`     INT UNSIGNED   NOT NULL,
  `month`           TINYINT        NOT NULL COMMENT '1-12',
  `year`            SMALLINT       NOT NULL,
  `base_rent`       DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `electricity_fee` DECIMAL(10,2)  NOT NULL DEFAULT 0.00
                    COMMENT 'Tính từ utility_readings',
  `water_fee`       DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `ac_fee`          DECIMAL(10,2)  NOT NULL DEFAULT 0.00
                    COMMENT 'Phụ phí điều hòa (nếu có)',
  `other_fee`       DECIMAL(10,2)  NOT NULL DEFAULT 0.00
                    COMMENT 'Phí khác (vệ sinh, internet...)',
  `total_amount`    DECIMAL(10,2)  NOT NULL DEFAULT 0.00
                    COMMENT 'Tổng = base + điện + nước + AC + other',
  `status`          ENUM('unpaid','paid','overdue','cancelled')
                    NOT NULL DEFAULT 'unpaid',
  `due_date`        DATE           NOT NULL,
  `paid_at`         DATETIME       NULL DEFAULT NULL,
  `payment_method`  ENUM('cash','transfer','vnpay','momo')
                    NULL DEFAULT NULL,
  `created_at`      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP
                                   ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_invoices_contract_month`
    (`contract_id`, `month`, `year`)
    COMMENT 'Mỗi hợp đồng chỉ có 1 hóa đơn/tháng',
  KEY `idx_invoices_status`  (`status`),
  KEY `idx_invoices_due`     (`due_date`),
  CONSTRAINT `fk_invoices_contract`
    FOREIGN KEY (`contract_id`) REFERENCES `contracts`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `chk_invoices_month`
    CHECK (`month` BETWEEN 1 AND 12),
  CONSTRAINT `chk_invoices_total`
    CHECK (`total_amount` >= 0)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Hóa đơn tháng – Billing Engine tính và ghi vào đây';


-- ============================================================
-- BẢNG 8: utility_readings
-- Mô tả : Chỉ số điện/nước theo tháng cho từng phòng
-- Người phụ trách: Thành viên 3
-- Quan hệ : N-1 với rooms, users(recorded_by)
-- ============================================================
CREATE TABLE `utility_readings` (
  `id`           INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `room_id`      INT UNSIGNED   NOT NULL,
  `month`        TINYINT        NOT NULL,
  `year`         SMALLINT       NOT NULL,
  `elec_prev`    DECIMAL(10,2)  NOT NULL DEFAULT 0.00
                 COMMENT 'Chỉ số điện đầu kỳ (kWh)',
  `elec_curr`    DECIMAL(10,2)  NOT NULL DEFAULT 0.00
                 COMMENT 'Chỉ số điện cuối kỳ (kWh)',
  `water_prev`   DECIMAL(10,2)  NOT NULL DEFAULT 0.00
                 COMMENT 'Chỉ số nước đầu kỳ (m³)',
  `water_curr`   DECIMAL(10,2)  NOT NULL DEFAULT 0.00
                 COMMENT 'Chỉ số nước cuối kỳ (m³)',
  `elec_rate`    DECIMAL(8,2)   NOT NULL DEFAULT 3500.00
                 COMMENT 'Đơn giá điện VND/kWh tại thời điểm ghi',
  `water_rate`   DECIMAL(8,2)   NOT NULL DEFAULT 15000.00
                 COMMENT 'Đơn giá nước VND/m³ tại thời điểm ghi',
  `recorded_by`  INT UNSIGNED   NOT NULL,
  `recorded_at`  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes`        VARCHAR(300)   NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_utility_room_month` (`room_id`, `month`, `year`),
  KEY `idx_utility_recorder` (`recorded_by`),
  CONSTRAINT `fk_utility_room`
    FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_utility_recorder`
    FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `chk_utility_elec`
    CHECK (`elec_curr` >= `elec_prev`),
  CONSTRAINT `chk_utility_water`
    CHECK (`water_curr` >= `water_prev`),
  CONSTRAINT `chk_utility_month`
    CHECK (`month` BETWEEN 1 AND 12)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Chỉ số điện nước – elec_rate/water_rate snapshot tránh phụ thuộc bắc cầu';


-- ============================================================
-- BẢNG 9: violation_records
-- Mô tả : Vi phạm nội quy KTX
-- Người phụ trách: Thành viên 3
-- Quan hệ : N-1 với students, contracts, users(recorded_by)
-- ============================================================
CREATE TABLE `violation_records` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `student_id`     INT UNSIGNED  NOT NULL,
  `contract_id`    INT UNSIGNED  NOT NULL,
  `violation_type` VARCHAR(100)  NOT NULL
                   COMMENT 'Loại vi phạm: tiếng ồn, hút thuốc, khách qua đêm...',
  `description`    TEXT          NOT NULL,
  `penalty_points` TINYINT       NOT NULL DEFAULT 1
                   COMMENT 'Điểm trừ (1-10). Tổng >= ngưỡng → under_review',
  `recorded_by`    INT UNSIGNED  NOT NULL,
  `recorded_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status`         ENUM('active','appealed','dismissed') NOT NULL DEFAULT 'active',
  `appeal_note`    VARCHAR(500)  NULL,
  PRIMARY KEY (`id`),
  KEY `idx_violation_student`  (`student_id`),
  KEY `idx_violation_contract` (`contract_id`),
  KEY `idx_violation_recorder` (`recorded_by`),
  CONSTRAINT `fk_violation_student`
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_violation_contract`
    FOREIGN KEY (`contract_id`) REFERENCES `contracts`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_violation_recorder`
    FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `chk_violation_points`
    CHECK (`penalty_points` BETWEEN 1 AND 10)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Vi phạm – PHP tổng hợp penalty_points để quyết định under_review';


-- ============================================================
-- BẢNG 10: room_amenities
-- Mô tả : Trang thiết bị trong phòng
-- Người phụ trách: Thành viên 2
-- Quan hệ : N-1 với rooms
-- ============================================================
CREATE TABLE `room_amenities` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `room_id`      INT UNSIGNED  NOT NULL,
  `amenity_name` VARCHAR(100)  NOT NULL COMMENT 'Giường, tủ, bàn, quạt, điều hòa...',
  `quantity`     TINYINT       NOT NULL DEFAULT 1,
  `condition`    ENUM('new','good','fair','damaged','broken')
                 NOT NULL DEFAULT 'good',
  `last_checked` DATE          NULL COMMENT 'Ngày kiểm tra gần nhất',
  `notes`        VARCHAR(300)  NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_amenity_room_name` (`room_id`, `amenity_name`),
  CONSTRAINT `fk_amenity_room`
    FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Trang thiết bị phòng – ON DELETE CASCADE: xóa phòng xóa luôn amenities';


-- ============================================================
-- BẢNG 11: maintenance_requests
-- Mô tả : Yêu cầu sửa chữa / bảo trì
-- Người phụ trách: Thành viên 3
-- Quan hệ : N-1 với rooms, users(reported_by), users(resolved_by)
-- ============================================================
CREATE TABLE `maintenance_requests` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `room_id`      INT UNSIGNED  NOT NULL,
  `reported_by`  INT UNSIGNED  NOT NULL COMMENT 'user_id sinh viên báo cáo',
  `title`        VARCHAR(200)  NOT NULL,
  `description`  TEXT          NOT NULL,
  `priority`     ENUM('low','medium','high','urgent')
                 NOT NULL DEFAULT 'medium',
  `status`       ENUM('open','in_progress','resolved','closed','rejected')
                 NOT NULL DEFAULT 'open',
  `reported_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at`  DATETIME      NULL DEFAULT NULL,
  `resolved_by`  INT UNSIGNED  NULL DEFAULT NULL
                 COMMENT 'user_id admin xử lý',
  `resolution`   VARCHAR(500)  NULL COMMENT 'Mô tả cách xử lý',
  PRIMARY KEY (`id`),
  KEY `idx_maint_room`     (`room_id`),
  KEY `idx_maint_reporter` (`reported_by`),
  KEY `idx_maint_resolver` (`resolved_by`),
  KEY `idx_maint_status`   (`status`),
  CONSTRAINT `fk_maint_room`
    FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_maint_reporter`
    FOREIGN KEY (`reported_by`) REFERENCES `users`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_maint_resolver`
    FOREIGN KEY (`resolved_by`) REFERENCES `users`(`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Yêu cầu bảo trì – 2 FK vào users (reporter & resolver)';


-- ============================================================
-- BẢNG 12: notifications
-- Mô tả : Thông báo hệ thống gửi đến người dùng
-- Người phụ trách: Thành viên 1
-- Quan hệ : N-1 với users
-- ============================================================
CREATE TABLE `notifications` (
  `id`       INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`  INT UNSIGNED  NOT NULL,
  `title`    VARCHAR(200)  NOT NULL,
  `message`  TEXT          NOT NULL,
  `type`     ENUM('registration','contract','invoice',
                  'violation','maintenance','system')
             NOT NULL DEFAULT 'system',
  `is_read`  TINYINT(1)    NOT NULL DEFAULT 0,
  `sent_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at`  DATETIME      NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user`    (`user_id`),
  KEY `idx_notif_is_read` (`is_read`),
  KEY `idx_notif_type`    (`type`),
  CONSTRAINT `fk_notif_user`
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Thông báo – ON DELETE CASCADE: xóa user xóa luôn thông báo của họ';


-- ============================================================
-- TRIGGER: cập nhật rooms.current_occupants tự động
-- Kích hoạt khi: hợp đồng active mới được tạo (INSERT)
-- ============================================================
DELIMITER $$

CREATE TRIGGER `trg_contract_insert_inc_occupants`
AFTER INSERT ON `contracts`
FOR EACH ROW
BEGIN
  IF NEW.status = 'active' THEN
    UPDATE `rooms`
    SET `current_occupants` = `current_occupants` + 1
    WHERE `id` = NEW.room_id;

    UPDATE `rooms`
    SET `status` = 'full'
    WHERE `id` = NEW.room_id
      AND `current_occupants` >= `capacity`;
  END IF;
END$$

-- Giảm occupants khi hợp đồng kết thúc hoặc bị hủy
CREATE TRIGGER `trg_contract_update_dec_occupants`
AFTER UPDATE ON `contracts`
FOR EACH ROW
BEGIN
  IF OLD.status = 'active'
     AND NEW.status IN ('expired','terminated') THEN
    UPDATE `rooms`
    SET `current_occupants` = GREATEST(0, `current_occupants` - 1)
    WHERE `id` = NEW.room_id;

    UPDATE `rooms`
    SET `status` = 'available'
    WHERE `id` = NEW.room_id
      AND `current_occupants` < `capacity`
      AND `status` = 'full';
  END IF;
END$$

-- TRIGGER: tự động đặt contracts.status = 'under_review'
-- khi tổng penalty_points của sinh viên đạt ngưỡng 10
CREATE TRIGGER `trg_violation_check_threshold`
AFTER INSERT ON `violation_records`
FOR EACH ROW
BEGIN
  DECLARE total_points INT DEFAULT 0;
  DECLARE violation_threshold INT DEFAULT 10;

  SELECT COALESCE(SUM(penalty_points), 0)
  INTO total_points
  FROM `violation_records`
  WHERE `student_id` = NEW.student_id
    AND `status` = 'active';

  IF total_points >= violation_threshold THEN
    UPDATE `contracts`
    SET `status` = 'under_review',
        `updated_at` = NOW()
    WHERE `student_id` = NEW.student_id
      AND `status` = 'active';

    INSERT INTO `notifications`
      (`user_id`, `title`, `message`, `type`)
    SELECT s.user_id,
           'Cảnh báo vi phạm',
           CONCAT('Tổng điểm vi phạm đã đạt ', total_points,
                  ' điểm. Hợp đồng của bạn đang được xem xét.'),
           'violation'
    FROM `students` s
    WHERE s.id = NEW.student_id;
  END IF;
END$$

DELIMITER ;


-- ============================================================
-- DỮ LIỆU MẪU (SEED DATA)
-- ============================================================

-- Admin account (password: Admin@123)
INSERT INTO `users` (`username`,`email`,`password_hash`,`role`) VALUES
('admin','admin@ktx.edu.vn',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'admin');

-- Sinh viên mẫu (password: Student@123)
INSERT INTO `users` (`username`,`email`,`password_hash`,`role`) VALUES
('sv001','nguyen.van.a@student.edu.vn',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'student'),
('sv002','tran.thi.b@student.edu.vn',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'student'),
('sv003','le.van.c@student.edu.vn',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'student');

INSERT INTO `students`
  (`user_id`,`student_code`,`full_name`,`gender`,`dob`,
   `faculty`,`program`,`priority_level`,`phone`,`hometown`,`id_card`)
VALUES
(2,'SV20210001','Nguyễn Văn A','male','2003-05-10',
 'Công nghệ thông tin','Chất lượng cao',1,
 '0901234567','Nghệ An','001234567890'),
(3,'SV20210002','Trần Thị B','female','2003-08-22',
 'Kinh tế','Đại trà',0,
 '0912345678','Hà Tĩnh','001234567891'),
(4,'SV20210003','Lê Văn C','male','2002-12-01',
 'Điện tử viễn thông','Chất lượng cao',2,
 '0923456789','Thanh Hóa','001234567892');

INSERT INTO `buildings`
  (`name`,`total_floors`,`gender_type`,`manager_name`,
   `manager_phone`,`address`)
VALUES
('A1',8,'male','Nguyễn Văn Hùng','0901111111',
 'KTX Khu A, Đường Nguyễn Trãi, Q.Thanh Xuân, Hà Nội'),
('B2',10,'female','Trần Thị Mai','0902222222',
 'KTX Khu B, Đường Nguyễn Trãi, Q.Thanh Xuân, Hà Nội'),
('C3',6,'mixed','Phạm Văn Đức','0903333333',
 'KTX Khu C, Đường Nguyễn Trãi, Q.Thanh Xuân, Hà Nội');

INSERT INTO `rooms`
  (`building_id`,`room_number`,`floor`,`room_type`,
   `capacity`,`price_per_month`,`has_ac`)
VALUES
(1,'101',1,'standard',4,600000,0),
(1,'102',1,'ac_standard',4,850000,1),
(1,'201',2,'deluxe',2,1200000,0),
(2,'101',1,'standard',6,500000,0),
(2,'201',2,'ac_deluxe',2,1500000,1);

INSERT INTO `room_amenities`
  (`room_id`,`amenity_name`,`quantity`,`condition`,`last_checked`)
VALUES
(1,'Giường tầng',2,'good','2025-09-01'),
(1,'Tủ quần áo',4,'good','2025-09-01'),
(1,'Bàn học',4,'fair','2025-09-01'),
(2,'Giường tầng',2,'new','2025-09-01'),
(2,'Điều hòa',1,'new','2025-09-01');

INSERT INTO `room_registrations`
  (`student_id`,`room_id`,`semester`,`academic_year`,
   `status`,`reviewed_at`,`reviewed_by`)
VALUES
(1,1,'HK1',2025,'approved',NOW(),1),
(2,4,'HK1',2025,'approved',NOW(),1),
(3,2,'HK1',2025,'pending',NULL,NULL);

INSERT INTO `contracts`
  (`registration_id`,`student_id`,`room_id`,
   `start_date`,`end_date`,`monthly_fee`,`status`)
VALUES
(1,1,1,'2025-09-01','2026-01-31',600000,'active'),
(2,2,4,'2025-09-01','2026-01-31',500000,'active');

INSERT INTO `utility_readings`
  (`room_id`,`month`,`year`,`elec_prev`,`elec_curr`,
   `water_prev`,`water_curr`,`elec_rate`,`water_rate`,`recorded_by`)
VALUES
(1,9,2025,100,145,20,23,3500,15000,1),
(4,9,2025,200,268,40,46,3500,15000,1);

INSERT INTO `invoices`
  (`contract_id`,`month`,`year`,`base_rent`,
   `electricity_fee`,`water_fee`,`ac_fee`,`total_amount`,
   `status`,`due_date`)
VALUES
(1,9,2025,600000,
 (145-100)*3500,
 (23-20)*15000,
 0,
 600000+(145-100)*3500+(23-20)*15000,
 'unpaid','2025-10-10'),
(2,9,2025,500000,
 (268-200)*3500,
 (46-40)*15000,
 0,
 500000+(268-200)*3500+(46-40)*15000,
 'paid','2025-10-10');

INSERT INTO `violation_records`
  (`student_id`,`contract_id`,`violation_type`,
   `description`,`penalty_points`,`recorded_by`)
VALUES
(1,1,'Tiếng ồn',
 'Phát nhạc to sau 22:00 ngày 2025-09-15',3,1);

INSERT INTO `notifications`
  (`user_id`,`title`,`message`,`type`)
VALUES
(2,'Đăng ký phòng được duyệt',
 'Đăng ký phòng 101 tòa A1 học kỳ 1/2025 đã được duyệt. Vui lòng đến ký hợp đồng.',
 'registration'),
(3,'Hóa đơn tháng 9/2025',
 'Hóa đơn tháng 9/2025 đã được tạo. Hạn nộp: 10/10/2025. Tổng: 738,000 VND.',
 'invoice');

INSERT INTO `maintenance_requests`
  (`room_id`,`reported_by`,`title`,`description`,`priority`)
VALUES
(1,2,'Quạt trần hỏng','Quạt phòng 101 không quay, đã thử công tắc.','high'),
(4,3,'Vòi nước rỉ','Vòi rửa tay nhà vệ sinh chảy nước liên tục.','medium');


SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- ============================================================
-- GHI CHÚ CHUẨN HÓA 3NF
-- ============================================================
-- 1NF : Tất cả cột có giá trị nguyên tố, không nhóm lặp.
--       ENUMs thay thế multi-value trong 1 cột.
-- 2NF : Không có phụ thuộc từng phần vào khóa ghép.
--       Mỗi bảng có PK đơn (id AUTO_INCREMENT).
-- 3NF : Không có phụ thuộc bắc cầu (transitive dependency):
--       - invoices.monthly_fee KHÔNG tham chiếu rooms.price_per_month,
--         mà dùng contracts.monthly_fee (snapshot tại thời điểm ký).
--       - utility_readings lưu elec_rate/water_rate tại thời điểm ghi,
--         không phụ thuộc vào bảng cấu hình giá riêng biệt.
--       - students tách khỏi users: thông tin sinh viên KHÔNG phụ thuộc
--         vào username hay email (thuộc tính của users).
-- ============================================================
