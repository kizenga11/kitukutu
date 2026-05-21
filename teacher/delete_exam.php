<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['teacher_id'])){
    header("Location: ../login.php");
    exit();
}

if(!isset($_GET['id'])){
    die("Exam not found.");
}

$exam_id = $_GET['id'];

/* DELETE MARKS FIRST */
mysqli_query($conn,"
DELETE FROM marks
WHERE exam_id='$exam_id'
");

/* DELETE EXAM */
mysqli_query($conn,"
DELETE FROM exams
WHERE id='$exam_id'
");

/* RETURN DASHBOARD */
header("Location: dashboard.php");
exit();
?>