<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

if(isset($_POST['id'])){

$id = $_POST['id'];

mysqli_query($conn,"DELETE FROM admissions WHERE id='$id'");

}

header("Location: admissions.php");
exit();
?>
