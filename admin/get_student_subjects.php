<?php
session_start();
include "../includes/config.php";

if (!isset($_SESSION['admin_id'])) {
    header("HTTP/1.0 401 Unauthorized");
    exit();
}

$student_id = intval($_GET['student_id'] ?? 0);
if (!$student_id) {
    echo json_encode(['subjects' => []]);
    exit();
}

$result = mysqli_query($conn, "SELECT subject_id FROM student_subjects WHERE student_id='$student_id'");
$ids = [];
while ($r = mysqli_fetch_assoc($result)) {
    $ids[] = intval($r['subject_id']);
}

header('Content-Type: application/json');
echo json_encode(['subjects' => $ids]);
