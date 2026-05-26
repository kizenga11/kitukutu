<?php
/*
 * ============================================================
 *  DATABASE SETUP / MIGRATION SCRIPT
 *  Run this file ONCE from your browser after pushing to
 *  Railway (or any new environment) to apply DB changes.
 *
 *  Usage:   https://your-domain.com/setup.php
 *  Safety:  All statements use IF NOT EXISTS / idempotent
 *           checks, so it is safe to re-run.
 * ============================================================
 */

require_once __DIR__ . '/includes/config.php';

$results = []; // each item: ['msg'=>'...', 'type'=>'ok'|'err'|'skip']
$hasError = false;

// ──────────────────────────────────────────────────────────
//  Helper: check if a column exists
// ──────────────────────────────────────────────────────────
function columnExists($conn, $table, $column) {
    $q = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $q && mysqli_num_rows($q) > 0;
}

// ──────────────────────────────────────────────────────────
//  1. Add `name` column to `admins` (if missing)
// ──────────────────────────────────────────────────────────
if (!columnExists($conn, 'admins', 'name')) {
    $sql = "ALTER TABLE `admins` ADD `name` VARCHAR(100) AFTER `id`";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Added `name` column to `admins` table.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to add `name` column: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`name` column already exists in `admins`.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  2. Add first_name, second_name, last_name, sex, phone to `admins`
// ──────────────────────────────────────────────────────────
$personalColumns = [
    ['first_name',  'VARCHAR(50) AFTER `id`'],
    ['second_name', 'VARCHAR(50) AFTER `first_name`'],
    ['last_name',   'VARCHAR(50) AFTER `second_name`'],
    ['sex',         "ENUM('M','F') AFTER `last_name`"],
    ['phone',       'VARCHAR(20) AFTER `sex`'],
];

foreach ($personalColumns as $col) {
    $colName = $col[0];
    $colDef  = $col[1];
    if (!columnExists($conn, 'admins', $colName)) {
        $sql = "ALTER TABLE `admins` ADD `$colName` $colDef";
        if (mysqli_query($conn, $sql)) {
            $results[] = ['msg'=>"Added `$colName` column to `admins`.", 'type'=>'ok'];
        } else {
            $results[] = ['msg'=>'Failed to add `' . $colName . '`: ' . mysqli_error($conn), 'type'=>'err'];
            $hasError = true;
        }
    } else {
        $results[] = ['msg'=>"`$colName` column already exists in `admins`.", 'type'=>'skip'];
    }
}

// ──────────────────────────────────────────────────────────
//  3. Update `role` enum to include 'academic'
// ──────────────────────────────────────────────────────────
$roleCheck = mysqli_query($conn, "SHOW COLUMNS FROM `admins` LIKE 'role'");
if ($roleCheck) {
    $roleCol = mysqli_fetch_assoc($roleCheck);
    $currentType = $roleCol['Type'] ?? '';
    if (strpos($currentType, 'academic') === false) {
        $sql = "ALTER TABLE `admins` MODIFY `role` ENUM('admin','headmaster','academic') DEFAULT 'admin'";
        if (mysqli_query($conn, $sql)) {
            $results[] = ['msg'=>"Updated `role` enum to include 'academic'.", 'type'=>'ok'];
        } else {
            $results[] = ['msg'=>'Failed to update `role` enum: ' . mysqli_error($conn), 'type'=>'err'];
            $hasError = true;
        }
    } else {
        $results[] = ['msg'=>"`role` enum already includes 'academic'.", 'type'=>'skip'];
    }
} else {
    $results[] = ['msg'=>'Could not check `role` column.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  4. Add form_level to students
// ──────────────────────────────────────────────────────────
if (!columnExists($conn, 'students', 'form_level')) {
    $sql = "ALTER TABLE `students` ADD `form_level` ENUM('Form One','Form Two','Form Three','Form Four') NOT NULL DEFAULT 'Form One' AFTER `sex`";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Added `form_level` column to `students` table.', 'type'=>'ok'];
        // Set existing students to Form One
        mysqli_query($conn, "UPDATE students SET form_level='Form One' WHERE form_level IS NULL OR form_level=''");
    } else {
        $results[] = ['msg'=>'Failed to add `form_level` column: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`form_level` already exists in `students`.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  5. Add form_level to marks
// ──────────────────────────────────────────────────────────
if (!columnExists($conn, 'marks', 'form_level')) {
    $sql = "ALTER TABLE `marks` ADD `form_level` VARCHAR(20) AFTER `exam_id`";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Added `form_level` column to `marks` table.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to add `form_level` column to marks: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`form_level` already exists in `marks`.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  6. Add form_level to exam_results_summary
// ──────────────────────────────────────────────────────────
if (!columnExists($conn, 'exam_results_summary', 'form_level')) {
    $sql = "ALTER TABLE `exam_results_summary` ADD `form_level` VARCHAR(20) AFTER `exam_id`";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Added `form_level` column to `exam_results_summary` table.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to add `form_level` column to exam_results_summary: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`form_level` already exists in `exam_results_summary`.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  7. Create exam_form_levels table
// ──────────────────────────────────────────────────────────
$tblCheck = mysqli_query($conn, "SHOW TABLES LIKE 'exam_form_levels'");
if (mysqli_num_rows($tblCheck) == 0) {
    $sql = "CREATE TABLE IF NOT EXISTS `exam_form_levels` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `exam_id` INT NOT NULL,
        `form_level` VARCHAR(20) NOT NULL,
        FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Created `exam_form_levels` table.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to create exam_form_levels: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`exam_form_levels` table already exists.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  8. Add form_level to teacher_assignments
// ──────────────────────────────────────────────────────────
if (!columnExists($conn, 'teacher_assignments', 'form_level')) {
    $sql = "ALTER TABLE `teacher_assignments` ADD `form_level` ENUM('Form One','Form Two','Form Three','Form Four') NOT NULL DEFAULT 'Form One' AFTER `subject_id`";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Added `form_level` column to `teacher_assignments` table.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to add `form_level` column to teacher_assignments: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`form_level` already exists in `teacher_assignments`.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  9. Update teacher_assignments unique key for form_level
// ──────────────────────────────────────────────────────────
$keyCheck = mysqli_query($conn, "SELECT COUNT(*) as c FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='teacher_assignments' AND CONSTRAINT_NAME='teacher_id'");
$keyRow = mysqli_fetch_assoc($keyCheck);
if ($keyRow['c'] > 0) {
    $sql = "ALTER TABLE `teacher_assignments` DROP INDEX `teacher_id`, ADD UNIQUE KEY `teacher_id` (`teacher_id`,`subject_id`,`form_level`)";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Updated teacher_assignments unique key to include form_level.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to update unique key: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'teacher_assignments unique key already updated or not needed.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  10. Add form_level to subject_settings
// ──────────────────────────────────────────────────────────
if (!columnExists($conn, 'subject_settings', 'form_level')) {
    $sql = "ALTER TABLE `subject_settings` ADD `form_level` ENUM('Form One','Form Two','Form Three','Form Four') NOT NULL DEFAULT 'Form One' AFTER `subject_id`";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Added `form_level` column to `subject_settings` table.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to add `form_level` column to subject_settings: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`form_level` already exists in `subject_settings`.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  11. Add unique key to subject_settings
// ──────────────────────────────────────────────────────────
$keyCheck2 = mysqli_query($conn, "SELECT COUNT(*) as c FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='subject_settings' AND CONSTRAINT_NAME='teacher_subj_form'");
$keyRow2 = mysqli_fetch_assoc($keyCheck2);
if ($keyRow2['c'] == 0) {
    // First remove any duplicates if they exist, then add the key
    $sql = "ALTER TABLE `subject_settings` ADD UNIQUE KEY `teacher_subj_form` (`teacher_id`,`subject_id`,`form_level`,`academic_year_id`,`term_id`)";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Added unique key to subject_settings (teacher,subject,form,year,term).', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Could not add unique key (may have duplicates): ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'subject_settings unique key already exists.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  12. Fill form_level for existing marks
// ──────────────────────────────────────────────────────────
if (columnExists($conn, 'marks', 'form_level')) {
    $sql = "UPDATE marks m JOIN students s ON s.id = m.student_id SET m.form_level = s.form_level WHERE m.form_level IS NULL OR m.form_level = ''";
    if (mysqli_query($conn, $sql)) {
        $affected = mysqli_affected_rows($conn);
        $results[] = ['msg'=>"Updated $affected old marks with student form_level.", 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to update old marks form_level: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'marks.form_level column does not exist yet.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  13. Fill form_level for existing exam_results_summary
// ──────────────────────────────────────────────────────────
if (columnExists($conn, 'exam_results_summary', 'form_level')) {
    $sql = "UPDATE exam_results_summary ers JOIN students s ON s.id = ers.student_id SET ers.form_level = s.form_level WHERE ers.form_level IS NULL OR ers.form_level = ''";
    if (mysqli_query($conn, $sql)) {
        $affected = mysqli_affected_rows($conn);
        $results[] = ['msg'=>"Updated $affected old exam_results_summary with student form_level.", 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to update old exam_results_summary form_level: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'exam_results_summary.form_level column does not exist.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  14. Add columns to academic_years table
// ──────────────────────────────────────────────────────────
$ay_columns = [
    ['title', 'VARCHAR(100) DEFAULT NULL'],
    ['school_open_date', 'DATE DEFAULT NULL'],
    ['school_close_date', 'DATE DEFAULT NULL'],
    ['created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'],
];
foreach ($ay_columns as $ac) {
    if (!columnExists($conn, 'academic_years', $ac[0])) {
        $sql = "ALTER TABLE `academic_years` ADD `{$ac[0]}` {$ac[1]}";
        if (mysqli_query($conn, $sql)) {
            $results[] = ['msg'=>"Added `{$ac[0]}` column to `academic_years`.", 'type'=>'ok'];
        } else {
            $results[] = ['msg'=>"Failed to add `{$ac[0]}` column: " . mysqli_error($conn), 'type'=>'err'];
            $hasError = true;
        }
    } else {
        $results[] = ['msg'=>"`{$ac[0]}` already exists in `academic_years`.", 'type'=>'skip'];
    }
}

// ──────────────────────────────────────────────────────────
//  15. Add columns to terms table
// ──────────────────────────────────────────────────────────
$terms_columns = [
    ['academic_year_id', 'INT DEFAULT NULL'],
    ['opening_date', 'DATE DEFAULT NULL'],
    ['midterm_break_start', 'DATE DEFAULT NULL'],
    ['midterm_break_end', 'DATE DEFAULT NULL'],
    ['closing_date', 'DATE DEFAULT NULL'],
    ['teaching_days', 'INT DEFAULT 0'],
    ['created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'],
];
foreach ($terms_columns as $tc) {
    if (!columnExists($conn, 'terms', $tc[0])) {
        $sql = "ALTER TABLE `terms` ADD `{$tc[0]}` {$tc[1]}";
        if (mysqli_query($conn, $sql)) {
            $results[] = ['msg'=>"Added `{$tc[0]}` column to `terms`.", 'type'=>'ok'];
        } else {
            $results[] = ['msg'=>"Failed to add `{$tc[0]}` column: " . mysqli_error($conn), 'type'=>'err'];
            $hasError = true;
        }
    } else {
        $results[] = ['msg'=>"`{$tc[0]}` already exists in `terms`.", 'type'=>'skip'];
    }
}

// Add index on academic_year_id for terms
$idxCheck = mysqli_query($conn, "SHOW INDEX FROM `terms` WHERE Key_name='idx_terms_academic_year'");
if (mysqli_num_rows($idxCheck) == 0) {
    if (columnExists($conn, 'terms', 'academic_year_id')) {
        mysqli_query($conn, "ALTER TABLE `terms` ADD INDEX `idx_terms_academic_year` (`academic_year_id`)");
        $results[] = ['msg'=>'Added index `idx_terms_academic_year` on `terms`.', 'type'=>'ok'];
    }
} else {
    $results[] = ['msg'=>'Index `idx_terms_academic_year` already exists.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  16. Create school_events table
// ──────────────────────────────────────────────────────────
$tblCheck = mysqli_query($conn, "SHOW TABLES LIKE 'school_events'");
if (mysqli_num_rows($tblCheck) == 0) {
    $sql = "CREATE TABLE IF NOT EXISTS `school_events` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `academic_year_id` INT DEFAULT NULL,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT,
        `event_type` ENUM('exam','mock','meeting','sports','academic','holiday','trip','other') DEFAULT 'other',
        `start_date` DATE NOT NULL,
        `end_date` DATE DEFAULT NULL,
        `created_by` INT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_se_academic_year` (`academic_year_id`),
        INDEX `idx_se_event_type` (`event_type`),
        INDEX `idx_se_start_date` (`start_date`),
        INDEX `idx_se_end_date` (`end_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Created `school_events` table.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to create school_events: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`school_events` table already exists.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  17. Add is_active column to academic_years if missing
// ──────────────────────────────────────────────────────────
if (!columnExists($conn, 'academic_years', 'is_active')) {
    $sql = "ALTER TABLE `academic_years` ADD `is_active` TINYINT(1) DEFAULT 0";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Added `is_active` column to `academic_years`.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to add `is_active` column: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`is_active` already exists in `academic_years`.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  18. Update school_events event_type ENUM to add new types
// ──────────────────────────────────────────────────────────
$enumCheck = mysqli_query($conn, "SHOW COLUMNS FROM `school_events` LIKE 'event_type'");
if ($enumCheck && mysqli_num_rows($enumCheck) > 0) {
    $enumRow = mysqli_fetch_assoc($enumCheck);
    $currentEnum = $enumRow['Type'] ?? '';
    if (strpos($currentEnum, 'terminal') === false || strpos($currentEnum, 'annual') === false || strpos($currentEnum, 'national') === false) {
        $sql = "ALTER TABLE `school_events` MODIFY `event_type` ENUM('exam','mock','meeting','sports','academic','holiday','trip','terminal','annual','national','other') DEFAULT 'other'";
        if (mysqli_query($conn, $sql)) {
            $results[] = ['msg'=>'Updated `school_events.event_type` ENUM to include terminal, annual, national.', 'type'=>'ok'];
        } else {
            $results[] = ['msg'=>'Failed to update event_type ENUM: ' . mysqli_error($conn), 'type'=>'err'];
            $hasError = true;
        }
    } else {
        $results[] = ['msg'=>'`school_events.event_type` ENUM already includes terminal, annual, national.', 'type'=>'skip'];
    }
} else {
    $results[] = ['msg'=>'`school_events` table does not exist yet, skipping ENUM update.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  19. Create grade_scales table and seed default data
// ──────────────────────────────────────────────────────────
$tblCheck = mysqli_query($conn, "SHOW TABLES LIKE 'grade_scales'");
if (mysqli_num_rows($tblCheck) == 0) {
    $sql = "CREATE TABLE IF NOT EXISTS `grade_scales` (
        `id`           INT AUTO_INCREMENT PRIMARY KEY,
        `grade_letter` VARCHAR(2)     NOT NULL UNIQUE,
        `min_marks`    DECIMAL(5,2)   NOT NULL,
        `max_marks`    DECIMAL(5,2)   NOT NULL,
        `points`       INT            NOT NULL,
        `remark`       VARCHAR(50)    NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Created `grade_scales` table.', 'type'=>'ok'];
        $seeds = [
            ['A', 75.00, 100.00, 1, 'BORA SANA'],
            ['B', 65.00,  74.99, 2, 'VIZURI SANA'],
            ['C', 45.00,  64.99, 3, 'WASTANI'],
            ['D', 30.00,  44.99, 4, 'HAFIFU'],
            ['F',  0.00,  29.99, 5, 'FELI'],
        ];
        foreach ($seeds as $s) {
            mysqli_query($conn, "INSERT IGNORE INTO `grade_scales` (grade_letter, min_marks, max_marks, points, remark)
                                  VALUES ('$s[0]', $s[1], $s[2], $s[3], '$s[4]')");
        }
        $results[] = ['msg'=>'Seeded default grade scale (A–F) into `grade_scales`.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to create `grade_scales`: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`grade_scales` table already exists.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  20. Create curriculum table (school-level curriculum docs)
// ──────────────────────────────────────────────────────────
$tblCheck = mysqli_query($conn, "SHOW TABLES LIKE 'curriculum'");
if (mysqli_num_rows($tblCheck) == 0) {
    $sql = "CREATE TABLE IF NOT EXISTS `curriculum` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT,
        `file_path` VARCHAR(500) DEFAULT NULL,
        `link_url` VARCHAR(500) DEFAULT NULL,
        `doc_type` ENUM('file','link') DEFAULT 'file',
        `is_active` TINYINT(1) DEFAULT 1,
        `created_by` INT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Created `curriculum` table.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to create `curriculum`: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`curriculum` table already exists.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  21. Create subject_syllabus table
// ──────────────────────────────────────────────────────────
$tblCheck = mysqli_query($conn, "SHOW TABLES LIKE 'subject_syllabus'");
if (mysqli_num_rows($tblCheck) == 0) {
    $sql = "CREATE TABLE IF NOT EXISTS `subject_syllabus` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `subject_id` INT NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT,
        `file_path` VARCHAR(500) DEFAULT NULL,
        `link_url` VARCHAR(500) DEFAULT NULL,
        `doc_type` ENUM('file','link') DEFAULT 'file',
        `form_level` VARCHAR(20) DEFAULT NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_by` INT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_ss_subject` (`subject_id`),
        INDEX `idx_ss_form` (`form_level`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Created `subject_syllabus` table.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to create `subject_syllabus`: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`subject_syllabus` table already exists.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  22. Create scheme_of_work table
// ──────────────────────────────────────────────────────────
$tblCheck = mysqli_query($conn, "SHOW TABLES LIKE 'scheme_of_work'");
if (mysqli_num_rows($tblCheck) == 0) {
    $sql = "CREATE TABLE IF NOT EXISTS `scheme_of_work` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `subject_id` INT NOT NULL,
        `teacher_id` INT DEFAULT NULL,
        `academic_year_id` INT DEFAULT NULL,
        `term_id` INT DEFAULT NULL,
        `form_level` VARCHAR(20) DEFAULT NULL,
        `title` VARCHAR(255) NOT NULL,
        `total_weeks` INT DEFAULT 0,
        `status` ENUM('draft','submitted','approved') DEFAULT 'draft',
        `created_by` INT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_sw_subject` (`subject_id`),
        INDEX `idx_sw_teacher` (`teacher_id`),
        INDEX `idx_sw_year` (`academic_year_id`),
        INDEX `idx_sw_term` (`term_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Created `scheme_of_work` table.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to create `scheme_of_work`: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`scheme_of_work` table already exists.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  23. Create scheme_weeks table (weekly breakdown)
// ──────────────────────────────────────────────────────────
$tblCheck = mysqli_query($conn, "SHOW TABLES LIKE 'scheme_weeks'");
if (mysqli_num_rows($tblCheck) == 0) {
    $sql = "CREATE TABLE IF NOT EXISTS `scheme_weeks` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `scheme_id` INT NOT NULL,
        `week_number` INT NOT NULL,
        `start_date` DATE DEFAULT NULL,
        `end_date` DATE DEFAULT NULL,
        `topic` VARCHAR(255) DEFAULT NULL,
        `subtopic` VARCHAR(255) DEFAULT NULL,
        `objectives` TEXT,
        `teaching_activities` TEXT,
        `learning_activities` TEXT,
        `resources` TEXT,
        `assessment` TEXT,
        `remarks` TEXT,
        INDEX `idx_swk_scheme` (`scheme_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Created `scheme_weeks` table.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to create `scheme_weeks`: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`scheme_weeks` table already exists.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  24. Create lesson_plans table
// ──────────────────────────────────────────────────────────
$tblCheck = mysqli_query($conn, "SHOW TABLES LIKE 'lesson_plans'");
if (mysqli_num_rows($tblCheck) == 0) {
    $sql = "CREATE TABLE IF NOT EXISTS `lesson_plans` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `subject_id` INT NOT NULL,
        `teacher_id` INT DEFAULT NULL,
        `academic_year_id` INT DEFAULT NULL,
        `term_id` INT DEFAULT NULL,
        `form_level` VARCHAR(20) DEFAULT NULL,
        `scheme_week_id` INT DEFAULT NULL,
        `period_id` INT DEFAULT NULL,
        `date` DATE NOT NULL,
        `topic` VARCHAR(255) DEFAULT NULL,
        `subtopic` VARCHAR(255) DEFAULT NULL,
        `objectives` TEXT,
        `teaching_methods` TEXT,
        `learning_activities` TEXT,
        `materials` TEXT,
        `assessment` TEXT,
        `reflection` TEXT,
        `status` ENUM('draft','submitted') DEFAULT 'draft',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_lp_subject` (`subject_id`),
        INDEX `idx_lp_teacher` (`teacher_id`),
        INDEX `idx_lp_date` (`date`),
        INDEX `idx_lp_scheme_week` (`scheme_week_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Created `lesson_plans` table.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to create `lesson_plans`: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`lesson_plans` table already exists.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  25. Create subject_resources table (books & notes)
// ──────────────────────────────────────────────────────────
$tblCheck = mysqli_query($conn, "SHOW TABLES LIKE 'subject_resources'");
if (mysqli_num_rows($tblCheck) == 0) {
    $sql = "CREATE TABLE IF NOT EXISTS `subject_resources` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `subject_id` INT NOT NULL,
        `form_level` VARCHAR(20) DEFAULT NULL,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT,
        `resource_type` ENUM('book','note','reference','other') DEFAULT 'other',
        `file_path` VARCHAR(500) DEFAULT NULL,
        `link_url` VARCHAR(500) DEFAULT NULL,
        `doc_type` ENUM('file','link') DEFAULT 'file',
        `is_active` TINYINT(1) DEFAULT 1,
        `created_by` INT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_sr_subject` (`subject_id`),
        INDEX `idx_sr_type` (`resource_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Created `subject_resources` table.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to create `subject_resources`: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`subject_resources` table already exists.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  26. Add subject_id & form_level to curriculum table
// ──────────────────────────────────────────────────────────
if (!columnExists($conn, 'curriculum', 'subject_id')) {
    $sql = "ALTER TABLE `curriculum` ADD `subject_id` INT DEFAULT NULL AFTER `id`, ADD INDEX `idx_cur_subject` (`subject_id`)";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Added `subject_id` column to `curriculum`.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to add `subject_id` to curriculum: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`subject_id` already exists in `curriculum`.', 'type'=>'skip'];
}
if (!columnExists($conn, 'curriculum', 'form_level')) {
    $sql = "ALTER TABLE `curriculum` ADD `form_level` VARCHAR(20) DEFAULT NULL AFTER `subject_id`";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Added `form_level` column to `curriculum`.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to add `form_level` to curriculum: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`form_level` already exists in `curriculum`.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  Done
// ──────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Database Setup · Kitukutu</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{
    font-family:system-ui,-apple-system,'Segoe UI',sans-serif;
    background:#f4f7fc;padding:20px;color:#111827;
}
.card{
    max-width:640px;margin:20px auto;
    background:#fff;border:1px solid #e5e7eb;
    border-radius:12px;overflow:hidden;
}
.hdr{
    background:linear-gradient(135deg,#1a1a2e,#16213e);
    color:#fff;padding:14px 18px;
}
.hdr h2{margin:0;font-size:16px;font-weight:700;}
.hdr p{margin:4px 0 0;font-size:12px;opacity:.8;}
.body{padding:16px;}
.result{padding:8px 12px;margin-bottom:6px;border-radius:8px;font-size:13px;}
.result.ok{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;}
.result.err{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;}
.result.skip{background:#fef3c7;color:#92400e;border:1px solid #fde68a;}
.summary{margin-top:12px;padding-top:12px;border-top:1px solid #e5e7eb;font-size:13px;color:#6b7280;}
.btn{display:inline-block;margin-top:12px;padding:8px 18px;background:#1d4ed8;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;}
.btn:hover{background:#1e40af;}
</style>
</head>
<body>
<div class="card">
    <div class="hdr">
        <h2>⚙ Kitukutu Database Setup</h2>
        <p>Migration script — <?= date('d-m-Y H:i') ?></p>
    </div>
    <div class="body">
        <?php foreach ($results as $r): ?>
            <?php if ($r['type'] === 'ok'): ?>
                <div class="result ok">✅ <?= $r['msg'] ?></div>
            <?php elseif ($r['type'] === 'err'): ?>
                <div class="result err">❌ <?= $r['msg'] ?></div>
            <?php else: ?>
                <div class="result skip">⏩ <?= $r['msg'] ?></div>
            <?php endif; ?>
        <?php endforeach; ?>

        <div class="summary">
            <?php if ($hasError): ?>
                <strong style="color:#dc2626;">⚠ Some migrations failed.</strong>
                Check the errors above and fix any issues.
            <?php else: ?>
                <strong style="color:#059669;">✅ All migrations completed successfully.</strong>
                You may now delete this file or keep it for future use.
            <?php endif; ?>
        </div>

        <a href="admin/dashboard.php" class="btn">← Go to Dashboard</a>
    </div>
</div>
</body>
</html>
