<?php
include "includes/config.php";

$name = mysqli_real_escape_string($conn,$_POST['name']);
$email = mysqli_real_escape_string($conn,$_POST['email']);
$message = mysqli_real_escape_string($conn,$_POST['message']);

mysqli_query($conn,"
INSERT INTO messages(name,email,message,status)
VALUES('$name','$email','$message','unread')
");


header("Location: index.php?msg=1");
exit();
?>
