-- ============================================================
--  Kitukutu School System — Parents Module Migration
--  Add tables: parents, parent_students, assignments, contributions
-- ============================================================

CREATE TABLE IF NOT EXISTS `parents` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `first_name`  VARCHAR(100) NOT NULL,
    `last_name`   VARCHAR(100) NOT NULL,
    `email`       VARCHAR(100) NOT NULL UNIQUE,
    `password`    VARCHAR(255) NOT NULL,
    `phone`       VARCHAR(20) DEFAULT '',
    `created_at`  DATETIME DEFAULT NOW(),
    INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `parent_students` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `parent_id`   INT NOT NULL,
    `student_id`  INT NOT NULL,
    `relationship` VARCHAR(50) DEFAULT '',
    UNIQUE KEY `uq_parent_student` (`parent_id`, `student_id`),
    FOREIGN KEY (`parent_id`)  REFERENCES `parents`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `assignments` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `title`           VARCHAR(255) NOT NULL,
    `description`     TEXT,
    `subject_id`      INT DEFAULT NULL,
    `form_level`      VARCHAR(20) DEFAULT NULL,
    `stream`          VARCHAR(50) DEFAULT NULL,
    `due_date`        DATE DEFAULT NULL,
    `posted_by_type`  ENUM('admin','teacher') NOT NULL,
    `posted_by_id`    INT NOT NULL,
    `created_at`      DATETIME DEFAULT NOW(),
    FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contributions` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `item_name`       VARCHAR(255) NOT NULL,
    `description`     TEXT,
    `amount`          DECIMAL(10,2) DEFAULT NULL,
    `type`            ENUM('contribution','debt','item') DEFAULT 'item',
    `form_level`      VARCHAR(20) DEFAULT NULL,
    `stream`          VARCHAR(50) DEFAULT NULL,
    `due_date`        DATE DEFAULT NULL,
    `posted_by_type`  ENUM('admin','teacher') NOT NULL,
    `posted_by_id`    INT NOT NULL,
    `created_at`      DATETIME DEFAULT NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `password_reset_tokens` MODIFY COLUMN `user_type` ENUM('admin','teacher','parent') NOT NULL;
