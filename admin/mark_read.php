<?php
session_start();
include "../includes/config.php";

$id = $_POST['id'];

mysqli_query($conn,"
UPDATE messages 
SET status='read' 
WHERE id='$id'
");

header("Location: messages.php");
exit();
?>
