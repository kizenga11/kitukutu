<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

$id = $_POST['id'];
$status = $_POST['action']; // kutoka button approve/reject
$message = $_POST['admin_message'];

mysqli_query($conn,"
UPDATE admissions 
SET status='$status',
    admin_message='$message'
WHERE id='$id'
");

header("Location: admissions.php?success=1");
exit();
?>