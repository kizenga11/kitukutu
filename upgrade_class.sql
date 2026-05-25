-- =============================================================
--  upgrade_class.sql
--  Adds Form One – Form Four (vidato) support to Kitukutu TSS
--  Run ONCE from phpMyAdmin or mysql CLI.
--  All statements are idempotent (safe to re-run).
-- =============================================================

-- ──────────────────────────────────────────────────────────────
--  1. Add form_level column to students
-- ──────────────────────────────────────────────────────────────
SET @col_exists = (SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='students' AND COLUMN_NAME='form_level'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE students
   ADD form_level ENUM(''Form One'',''Form Two'',''Form Three'',''Form Four'')
   NOT NULL DEFAULT ''Form One'' AFTER `sex`',
  'SELECT ''form_level already exists in students'' AS msg'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ──────────────────────────────────────────────────────────────
--  2. Add form_level column to marks
-- ──────────────────────────────────────────────────────────────
SET @col_exists = (SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='marks' AND COLUMN_NAME='form_level'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE marks ADD form_level VARCHAR(20) AFTER `exam_id`',
  'SELECT ''form_level already exists in marks'' AS msg'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ──────────────────────────────────────────────────────────────
--  3. Add form_level column to exam_results_summary
-- ──────────────────────────────────────────────────────────────
SET @col_exists = (SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='exam_results_summary' AND COLUMN_NAME='form_level'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE exam_results_summary ADD form_level VARCHAR(20) AFTER `exam_id`',
  'SELECT ''form_level already exists in exam_results_summary'' AS msg'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ──────────────────────────────────────────────────────────────
--  4. Create exam_form_levels table (pivot: exam → form levels)
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `exam_form_levels` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `exam_id` INT NOT NULL,
  `form_level` VARCHAR(20) NOT NULL,
  FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────────
--  5. Update existing students to Form One (new school, 2026)
-- ──────────────────────────────────────────────────────────────
UPDATE students SET form_level = 'Form One' WHERE form_level IS NULL OR form_level = '';

-- ──────────────────────────────────────────────────────────────
--  6. Add form_level column to teacher_assignments
-- ──────────────────────────────────────────────────────────────
SET @col_exists = (SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='teacher_assignments' AND COLUMN_NAME='form_level'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE teacher_assignments ADD form_level ENUM(''Form One'',''Form Two'',''Form Three'',''Form Four'') NOT NULL DEFAULT ''Form One'' AFTER `subject_id`',
  'SELECT ''form_level already exists in teacher_assignments'' AS msg'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ──────────────────────────────────────────────────────────────
--  7. Update teacher_assignments unique key to include form_level
-- ──────────────────────────────────────────────────────────────
SET @key_exists = (SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='teacher_assignments' AND CONSTRAINT_NAME='teacher_id'
);
SET @sql = IF(@key_exists > 0,
  'ALTER TABLE teacher_assignments DROP INDEX teacher_id, ADD UNIQUE KEY teacher_id (teacher_id,subject_id,form_level)',
  'SELECT ''teacher_assignments unique key already updated'' AS msg'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ──────────────────────────────────────────────────────────────
--  8. Add form_level column to subject_settings
-- ──────────────────────────────────────────────────────────────
SET @col_exists = (SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='subject_settings' AND COLUMN_NAME='form_level'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE subject_settings ADD form_level ENUM(''Form One'',''Form Two'',''Form Three'',''Form Four'') NOT NULL DEFAULT ''Form One'' AFTER `subject_id`',
  'SELECT ''form_level already exists in subject_settings'' AS msg'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add unique key to prevent duplicates
SET @key_exists = (SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='subject_settings' AND CONSTRAINT_NAME='teacher_subj_form'
);
SET @sql = IF(@key_exists = 0,
  'ALTER TABLE subject_settings ADD UNIQUE KEY teacher_subj_form (teacher_id,subject_id,form_level,academic_year_id,term_id)',
  'SELECT ''subject_settings unique key already exists'' AS msg'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ──────────────────────────────────────────────────────────────
--  9. Fill form_level for existing marks (old marks have NULL)
-- ──────────────────────────────────────────────────────────────
UPDATE marks m JOIN students s ON s.id = m.student_id
SET m.form_level = s.form_level
WHERE m.form_level IS NULL OR m.form_level = '';

-- =============================================================
--  Done
-- =============================================================
