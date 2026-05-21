<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

$id = $_POST['id'];
$status = $_POST['status'];

mysqli_query($conn,"
UPDATE announcements 
SET status='$status' 
WHERE id='$id'
");

header("Location: announcements.php?success=1");
exit();
?>
