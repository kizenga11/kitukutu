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
//  27. Create parents table
// ──────────────────────────────────────────────────────────
$tblCheck = mysqli_query($conn, "SHOW TABLES LIKE 'parents'");
if (mysqli_num_rows($tblCheck) == 0) {
    $sql = "CREATE TABLE IF NOT EXISTS `parents` (
        `id`          INT AUTO_INCREMENT PRIMARY KEY,
        `first_name`  VARCHAR(100) NOT NULL,
        `last_name`   VARCHAR(100) NOT NULL,
        `email`       VARCHAR(100) NOT NULL UNIQUE,
        `password`    VARCHAR(255) NOT NULL,
        `phone`       VARCHAR(20) DEFAULT '',
        `created_at`  DATETIME DEFAULT NOW(),
        INDEX `idx_email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Created `parents` table.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to create `parents`: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`parents` table already exists.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  28. Create parent_students table
// ──────────────────────────────────────────────────────────
$tblCheck = mysqli_query($conn, "SHOW TABLES LIKE 'parent_students'");
if (mysqli_num_rows($tblCheck) == 0) {
    $sql = "CREATE TABLE IF NOT EXISTS `parent_students` (
        `id`          INT AUTO_INCREMENT PRIMARY KEY,
        `parent_id`   INT NOT NULL,
        `student_id`  INT NOT NULL,
        `relationship` VARCHAR(50) DEFAULT '',
        UNIQUE KEY `uq_parent_student` (`parent_id`, `student_id`),
        FOREIGN KEY (`parent_id`)  REFERENCES `parents`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Created `parent_students` junction table.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to create `parent_students`: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`parent_students` table already exists.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  29. Create assignments table
// ──────────────────────────────────────────────────────────
$tblCheck = mysqli_query($conn, "SHOW TABLES LIKE 'assignments'");
if (mysqli_num_rows($tblCheck) == 0) {
    $sql = "CREATE TABLE IF NOT EXISTS `assignments` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Created `assignments` table.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to create `assignments`: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`assignments` table already exists.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  30. Create contributions table
// ──────────────────────────────────────────────────────────
$tblCheck = mysqli_query($conn, "SHOW TABLES LIKE 'contributions'");
if (mysqli_num_rows($tblCheck) == 0) {
    $sql = "CREATE TABLE IF NOT EXISTS `contributions` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Created `contributions` table.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to create `contributions`: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`contributions` table already exists.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  31. Update password_reset_tokens user_type to include parent
// ──────────────────────────────────────────────────────────
$enumCheck = mysqli_query($conn, "SHOW COLUMNS FROM `password_reset_tokens` LIKE 'user_type'");
if ($enumCheck && mysqli_num_rows($enumCheck) > 0) {
    $enumRow = mysqli_fetch_assoc($enumCheck);
    if (strpos($enumRow['Type'], 'parent') === false) {
        $sql = "ALTER TABLE `password_reset_tokens` MODIFY COLUMN `user_type` ENUM('admin','teacher','parent') NOT NULL";
        if (mysqli_query($conn, $sql)) {
            $results[] = ['msg'=>'Updated `password_reset_tokens.user_type` to include parent.', 'type'=>'ok'];
        } else {
            $results[] = ['msg'=>'Failed to update password_reset_tokens enum: ' . mysqli_error($conn), 'type'=>'err'];
            $hasError = true;
        }
    } else {
        $results[] = ['msg'=>'`password_reset_tokens.user_type` already includes parent.', 'type'=>'skip'];
    }
} else {
    $results[] = ['msg'=>'`password_reset_tokens` table not found, skipping.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  32. Add registration_no column to students
// ──────────────────────────────────────────────────────────
if (!columnExists($conn, 'students', 'registration_no')) {
    $sql = "ALTER TABLE `students` ADD `registration_no` VARCHAR(50) DEFAULT NULL AFTER `id`, ADD UNIQUE KEY `uq_reg_no` (`registration_no`)";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Added `registration_no` column to `students`.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to add `registration_no`: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`registration_no` already exists in `students`.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  33. Generate registration_no for existing students
// ──────────────────────────────────────────────────────────
$nullReg = mysqli_query($conn, "SELECT id FROM students WHERE registration_no IS NULL OR registration_no = ''");
$countNull = mysqli_num_rows($nullReg);
if ($countNull > 0) {
    $updated = 0;
    $year = date('Y');
    $counter = 1;
    $prefix = 'S.8486/' . $year . '/';
    $seqQ = mysqli_query($conn,"SELECT MAX(CAST(SUBSTRING(registration_no, LENGTH('$prefix') + 1) AS UNSIGNED)) as max_seq FROM students WHERE registration_no LIKE '$prefix%'");
    $seqR = mysqli_fetch_assoc($seqQ);
    $counter = ($seqR['max_seq'] ?? 0) + 1;
    while ($r = mysqli_fetch_assoc($nullReg)) {
        $sid = (int) $r['id'];
        $regNo = $prefix . str_pad($counter, 4, '0', STR_PAD_LEFT);
        $regNoEsc = mysqli_real_escape_string($conn, $regNo);
        mysqli_query($conn, "UPDATE students SET registration_no='$regNoEsc' WHERE id=$sid AND (registration_no IS NULL OR registration_no = '')");
        $updated++;
        $counter++;
    }
    $results[] = ['msg'=>"Generated registration_no for $updated existing students.", 'type'=>'ok'];
} else {
    $results[] = ['msg'=>'All students already have registration_no.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  34. Change parent_students unique key — one parent per student
// ──────────────────────────────────────────────────────────
// First, drop FK constraints so we can alter indexes
$fkDropped = false;
$fkResult = mysqli_query($conn, "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='parent_students' AND REFERENCED_TABLE_NAME IS NOT NULL");
if ($fkResult) {
    while ($fk = mysqli_fetch_assoc($fkResult)) {
        $cn = $fk['CONSTRAINT_NAME'];
        mysqli_query($conn, "ALTER TABLE `parent_students` DROP FOREIGN KEY `$cn`");
    }
    $fkDropped = true;
}

// Remove duplicate student entries first (keep only the first link)
$dupes = mysqli_query($conn, "
    SELECT student_id FROM parent_students
    GROUP BY student_id HAVING COUNT(*) > 1
");
$removed = 0;
while ($d = mysqli_fetch_assoc($dupes)) {
    $sId = (int) $d['student_id'];
    $keep = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MIN(id) AS min_id FROM parent_students WHERE student_id=$sId"));
    $keepId = (int) $keep['min_id'];
    mysqli_query($conn, "DELETE FROM parent_students WHERE student_id=$sId AND id != $keepId");
    $removed++;
}
if ($removed > 0) {
    $results[] = ['msg'=>"Removed $removed duplicate parent-student link(s).", 'type'=>'ok'];
}

// Drop old composite unique key if it exists
$uqCheck = mysqli_query($conn, "SHOW INDEX FROM `parent_students` WHERE Key_name = 'uq_parent_student'");
if (mysqli_num_rows($uqCheck) > 0) {
    mysqli_query($conn, "ALTER TABLE `parent_students` DROP INDEX `uq_parent_student`");
}

// Add new unique key on student_id only (if not already exists)
$newIdx = mysqli_query($conn, "SHOW INDEX FROM `parent_students` WHERE Key_name = 'uq_student'");
$uqAdded = false;
if (mysqli_num_rows($newIdx) == 0) {
    try {
        if (mysqli_query($conn, "ALTER TABLE `parent_students` ADD UNIQUE KEY `uq_student` (`student_id`)")) {
            $results[] = ['msg'=>'Added UNIQUE KEY on `student_id` — one parent per student.', 'type'=>'ok'];
            $uqAdded = true;
        }
    } catch (\Throwable $e) {
        $results[] = ['msg'=>'Failed to add unique key: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`parent_students` already has the correct unique constraint.', 'type'=>'skip'];
}

// Re-add FK constraints
mysqli_query($conn, "ALTER TABLE `parent_students` ADD FOREIGN KEY (`parent_id`) REFERENCES `parents`(`id`) ON DELETE CASCADE");
mysqli_query($conn, "ALTER TABLE `parent_students` ADD FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE");

// ──────────────────────────────────────────────────────────
//  35. Create lesson_plan_syllabus table (TIE 2023 Curriculum)
// ──────────────────────────────────────────────────────────
$tblCheck = mysqli_query($conn, "SHOW TABLES LIKE 'lesson_plan_syllabus'");
if (mysqli_num_rows($tblCheck) == 0) {
    $sql = "CREATE TABLE IF NOT EXISTS `lesson_plan_syllabus` (
        `id` int NOT NULL AUTO_INCREMENT,
        `subject_id` int NOT NULL,
        `form_level` enum('Form One','Form Two','Form Three','Form Four') NOT NULL,
        `topic_name` varchar(255) NOT NULL,
        `main_competence` text NOT NULL,
        `specific_competence` text NOT NULL,
        `main_activity` text NOT NULL,
        `action_word` varchar(50) NOT NULL,
        `learning_activities` text DEFAULT NULL,
        `suggested_resources` text DEFAULT NULL,
        `no_of_periods` int DEFAULT NULL,
        `reference` text,
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_lps_subject` (`subject_id`),
        KEY `idx_lps_form` (`form_level`),
        CONSTRAINT `fk_lps_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Created `lesson_plan_syllabus` table.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to create `lesson_plan_syllabus`: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`lesson_plan_syllabus` table already exists.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  36. Add learning_activities & suggested_resources to lesson_plan_syllabus
// ──────────────────────────────────────────────────────────
if (!columnExists($conn, 'lesson_plan_syllabus', 'learning_activities')) {
    $sql = "ALTER TABLE `lesson_plan_syllabus` ADD COLUMN `learning_activities` text DEFAULT NULL AFTER `action_word`";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Added `learning_activities` column to `lesson_plan_syllabus`.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to add `learning_activities`: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`learning_activities` already exists in `lesson_plan_syllabus`.', 'type'=>'skip'];
}
if (!columnExists($conn, 'lesson_plan_syllabus', 'suggested_resources')) {
    $sql = "ALTER TABLE `lesson_plan_syllabus` ADD COLUMN `suggested_resources` text DEFAULT NULL AFTER `learning_activities`";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Added `suggested_resources` column to `lesson_plan_syllabus`.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to add `suggested_resources`: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`suggested_resources` already exists in `lesson_plan_syllabus`.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  37. Add syllabus_id column to topics table
// ──────────────────────────────────────────────────────────
if (!columnExists($conn, 'topics', 'syllabus_id')) {
    $sql = "ALTER TABLE `topics` ADD COLUMN `syllabus_id` int DEFAULT NULL AFTER `topic_name`, ADD KEY `idx_topics_syllabus` (`syllabus_id`)";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Added `syllabus_id` column to `topics`.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to add `syllabus_id` to topics: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`syllabus_id` already exists in `topics`.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  38. Seed Mathematics & Chemistry syllabus from mathchemistry_seed.sql
// ──────────────────────────────────────────────────────────
$seedFile = __DIR__ . DIRECTORY_SEPARATOR . 'mathchemistry_seed.sql';
if (file_exists($seedFile)) {
    $seedSql = file_get_contents($seedFile);
    // Remove comment lines
    $seedLines = explode("\n", $seedSql);
    $seedClean = [];
    foreach ($seedLines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
            continue;
        }
        $seedClean[] = $line;
    }
    $seedSql = implode("\n", $seedClean);

    // Split by semicolons (handle quoted strings)
    $statements = [];
    $buffer = '';
    $inString = false;
    $quoteChar = null;
    $len = strlen($seedSql);
    for ($i = 0; $i < $len; $i++) {
        $ch = $seedSql[$i];
        $next = $i + 1 < $len ? $seedSql[$i + 1] : '';
        if (!$inString) {
            if ($ch === "'" || $ch === '"') {
                $inString = true;
                $quoteChar = $ch;
            } elseif ($ch === ';') {
                $trimmed = trim($buffer);
                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }
                $buffer = '';
                continue;
            }
        } else {
            if ($ch === '\\' && $next === $quoteChar) {
                $buffer .= $ch . $next;
                $i++;
                continue;
            }
            if ($ch === $quoteChar) {
                $inString = false;
                $quoteChar = null;
            }
        }
        $buffer .= $ch;
    }
    $trimmed = trim($buffer);
    if ($trimmed !== '') {
        $statements[] = $trimmed;
    }

    $seedSuccess = 0;
    $seedErrors = 0;
    foreach ($statements as $stmt) {
        if (stripos($stmt, 'SELECT') === 0) continue;
        if (mysqli_query($conn, $stmt)) {
            $seedSuccess++;
        } else {
            $seedErrors++;
            $results[] = ['msg' => 'Seed statement failed: ' . mysqli_error($conn), 'type' => 'err'];
            $hasError = true;
        }
    }
    if ($seedSuccess > 0) {
        $affected = mysqli_affected_rows($conn);
        $results[] = ['msg' => "Seeded Mathematics & Chemistry syllabus from mathchemistry_seed.sql ($affected rows affected).", 'type' => 'ok'];
    }
} else {
    $results[] = ['msg' => 'mathchemistry_seed.sql not found — skipping syllabus seed.', 'type' => 'skip'];
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
