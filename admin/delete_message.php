<?php
session_start();
include "../includes/config.php";

$id = $_POST['id'];

mysqli_query($conn,"
DELETE FROM messages 
WHERE id='$id'
");

header("Location: messages.php");
exit();
?>
