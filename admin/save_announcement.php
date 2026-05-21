<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

$title = mysqli_real_escape_string($conn,$_POST['title']);
$type = $_POST['type'];
$content = mysqli_real_escape_string($conn,$_POST['content']);
$status = $_POST['status']; // NEW

$attachment="";

if(isset($_FILES['attachment']) && $_FILES['attachment']['name']!=""){

$target_dir="../uploads/announcements/";

$attachment=time()."_".$_FILES['attachment']['name'];

move_uploaded_file($_FILES['attachment']['tmp_name'],
$target_dir.$attachment);

}

mysqli_query($conn,"
INSERT INTO announcements(title,content,type,attachment,status)
VALUES('$title','$content','$type','$attachment','$status')
");

header("Location: announcements.php");
exit();
?>
