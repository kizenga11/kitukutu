<?php
require_once "includes/config.php";
$tables = ["admins","academic_years","terms","exams","marks","students","subjects","student_subjects","exam_results_summary","streams","teacher_assignments","subject_settings","exam_form_levels","grade_scales","school_events","curriculum","subject_syllabus","scheme_of_work","scheme_weeks","lesson_plans","subject_resources","password_reset_tokens","announcements","messages","admissions","stream_subjects"];
foreach ($tables as $tbl) {
    $q = mysqli_query($conn, "SHOW CREATE TABLE `$tbl`");
    if ($q && $row = mysqli_fetch_assoc($q)) {
        echo "=== $tbl ===\n";
        echo $row["Create Table"] . "\n\n";
    } else {
        echo "=== $tbl ===\nTable not found: " . mysqli_error($conn) . "\n\n";
    }
}
