<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

$id=$_POST['id'];

mysqli_query($conn,"DELETE FROM announcements WHERE id='$id'");

header("Location: announcements.php");
exit();
?>
