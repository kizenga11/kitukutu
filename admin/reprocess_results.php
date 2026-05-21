<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

$exam_id = intval($_GET['exam_id'] ?? 0);

mysqli_query($conn,"DELETE FROM exam_results_summary WHERE exam_id='$exam_id'");
mysqli_query($conn,"UPDATE exams SET summary_json=NULL WHERE id='$exam_id'");

header("Location: process_results.php?exam_id=$exam_id");
exit();
?>