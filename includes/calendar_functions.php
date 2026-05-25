<?php
function getActiveAcademicYear($conn) {
    $q = mysqli_query($conn, "SELECT * FROM academic_years WHERE is_active = 1 LIMIT 1");
    return $q ? mysqli_fetch_assoc($q) : null;
}

function getCurrentTerm($conn, $academic_year_id) {
    $q = mysqli_query($conn, "
        SELECT * FROM terms
        WHERE academic_year_id = $academic_year_id
          AND opening_date <= CURDATE()
          AND closing_date >= CURDATE()
        LIMIT 1
    ");
    return $q ? mysqli_fetch_assoc($q) : null;
}

function getTermsByYear($conn, $academic_year_id) {
    $q = mysqli_query($conn, "
        SELECT * FROM terms
        WHERE academic_year_id = $academic_year_id
        ORDER BY opening_date ASC
    ");
    $r = [];
    if ($q) while ($row = mysqli_fetch_assoc($q)) $r[] = $row;
    return $r;
}

function getOngoingEvents($conn, $academic_year_id, $limit = 10) {
    $q = mysqli_query($conn, "
        SELECT * FROM school_events
        WHERE academic_year_id = $academic_year_id
          AND start_date <= CURDATE()
          AND (end_date >= CURDATE() OR end_date IS NULL)
        ORDER BY start_date ASC
        LIMIT $limit
    ");
    $r = [];
    if ($q) while ($row = mysqli_fetch_assoc($q)) $r[] = $row;
    return $r;
}

function getUpcomingEvents($conn, $academic_year_id, $limit = 5) {
    $q = mysqli_query($conn, "
        SELECT * FROM school_events
        WHERE academic_year_id = $academic_year_id
          AND start_date >= CURDATE()
        ORDER BY start_date ASC
        LIMIT $limit
    ");
    $r = [];
    if ($q) while ($row = mysqli_fetch_assoc($q)) $r[] = $row;
    return $r;
}

function getAllEventsByYear($conn, $academic_year_id) {
    $q = mysqli_query($conn, "
        SELECT * FROM school_events
        WHERE academic_year_id = $academic_year_id
        ORDER BY start_date ASC
    ");
    $r = [];
    if ($q) while ($row = mysqli_fetch_assoc($q)) $r[] = $row;
    return $r;
}

function getEventById($conn, $id) {
    $id = intval($id);
    $q = mysqli_query($conn, "SELECT * FROM school_events WHERE id = $id LIMIT 1");
    return $q ? mysqli_fetch_assoc($q) : null;
}

function ensureSingleActiveYear($conn, $active_id) {
    $active_id = intval($active_id);
    mysqli_query($conn, "UPDATE academic_years SET is_active = 0 WHERE is_active = 1 AND id != $active_id");
}

function getEventTypeBadge($type) {
    $map = [
        'exam' => ['bg' => '#dbeafe', 'color' => '#1d4ed8', 'label' => 'Exam'],
        'mock' => ['bg' => '#fce7f3', 'color' => '#9d174d', 'label' => 'Mock'],
        'meeting' => ['bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'Meeting'],
        'sports' => ['bg' => '#d1fae5', 'color' => '#065f46', 'label' => 'Sports'],
        'academic' => ['bg' => '#ede9fe', 'color' => '#5b21b6', 'label' => 'Academic'],
        'holiday' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => 'Holiday'],
        'trip' => ['bg' => '#e0f2fe', 'color' => '#0369a1', 'label' => 'Trip'],
        'terminal' => ['bg' => '#ffedd5', 'color' => '#c2410c', 'label' => 'Terminal'],
        'annual' => ['bg' => '#f0fdf4', 'color' => '#15803d', 'label' => 'Annual'],
        'national' => ['bg' => '#fef2f2', 'color' => '#b91c1c', 'label' => 'National'],
        'other' => ['bg' => '#f3f4f6', 'color' => '#374151', 'label' => 'Other'],
    ];
    return $map[$type] ?? $map['other'];
}
