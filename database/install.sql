-- ============================================================
--  Kitukutu School System — Database Install / Migration
--  Run this ONCE in phpMyAdmin or via MySQL CLI.
--  Tafadhali hakikisha umechagua database `kitukutu_db`
--  kabla ya ku-run.
-- ============================================================

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `token`       VARCHAR(64) NOT NULL,
    `otp`         VARCHAR(6) NOT NULL DEFAULT '',
    `user_type`   ENUM('admin','teacher') NOT NULL,
    `user_id`     INT NOT NULL,
    `expires_at`  DATETIME NOT NULL,
    `created_at`  DATETIME DEFAULT NOW(),
    UNIQUE KEY  `uq_token` (`token`),
    INDEX       `idx_expires` (`expires_at`),
    INDEX       `idx_otp` (`otp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
