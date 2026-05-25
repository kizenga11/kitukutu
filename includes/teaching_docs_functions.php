<?php
function getSubjects($conn) {
    $q = mysqli_query($conn, "SELECT * FROM subjects ORDER BY subject_name ASC");
    $r = [];
    if ($q) while ($row = mysqli_fetch_assoc($q)) $r[] = $row;
    return $r;
}

function getTeacherAssignments($conn, $teacher_id) {
    $q = mysqli_query($conn, "
        SELECT ta.*, s.subject_name, s.stream
        FROM teacher_assignments ta
        JOIN subjects s ON s.id = ta.subject_id
        WHERE ta.teacher_id = $teacher_id
        ORDER BY s.subject_name
    ");
    $r = [];
    if ($q) while ($row = mysqli_fetch_assoc($q)) $r[] = $row;
    return $r;
}

function getForms() {
    return ['Form One', 'Form Two', 'Form Three', 'Form Four'];
}

function generateSchemeWeeks($conn, $scheme_id, $term_id) {
    $t = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM terms WHERE id = $term_id LIMIT 1"));
    if (!$t) return 0;
    $teaching_days = intval($t['teaching_days'] ?? 0);
    $weeks = $teaching_days > 0 ? ceil($teaching_days / 5) : 12;
    $start = $t['opening_date'];
    $mid_start = $t['midterm_break_start'];
    $mid_end = $t['midterm_break_end'];
    $week_num = 1;
    $current = $start;
    $created = 0;
    for ($i = 0; $i < $weeks; $i++) {
        $week_start = $current;
        $week_end = date('Y-m-d', strtotime($week_start . ' +4 days'));
        if ($mid_start && $mid_end && $week_start >= $mid_start && $week_start <= $mid_end) {
            $current = date('Y-m-d', strtotime($mid_end . ' +1 day'));
            continue;
        }
        $ws = mysqli_real_escape_string($conn, $week_start);
        $we = mysqli_real_escape_string($conn, $week_end);
        $exists = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM scheme_weeks WHERE scheme_id=$scheme_id AND week_number=$week_num"));
        if ($exists && $exists['c'] == 0) {
            mysqli_query($conn, "INSERT INTO scheme_weeks (scheme_id, week_number, start_date, end_date) VALUES ($scheme_id, $week_num, '$ws', '$we')");
            $created++;
        }
        $week_num++;
        $current = date('Y-m-d', strtotime($week_end . ' +3 days'));
    }
    mysqli_query($conn, "UPDATE scheme_of_work SET total_weeks = $week_num - 1 WHERE id = $scheme_id");
    return $created;
}

function getDocIcon($type) {
    $map = ['file' => 'bi-file-earmark-pdf', 'link' => 'bi-link-45deg'];
    return $map[$type] ?? 'bi-file-earmark';
}
