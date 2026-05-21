<?php

include "includes/config.php";

$result = null;

if(isset($_GET['application_no'])){

$app_no = mysqli_real_escape_string($conn,$_GET['application_no']);

$query = mysqli_query($conn,
"SELECT * FROM admissions WHERE application_no='$app_no'");

$result = mysqli_fetch_assoc($query);

}

?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Track Application</title>

<style>

body{
font-family:Arial;
background:#f4f6f9;
padding:20px;
text-align:center;
}

.container{
max-width:500px;
margin:auto;
background:white;
padding:30px;
border-radius:10px;
box-shadow:0 0 10px rgba(0,0,0,0.1);
}

.status{
font-size:22px;
font-weight:bold;
margin:15px 0;
}

.pending{
color:orange;
}

.approved{
color:green;
}

.rejected{
color:red;
}

button{
padding:10px 20px;
background:#1e4a6d;
color:white;
border:none;
border-radius:5px;
cursor:pointer;
}

button:hover{
background:#f4b400;
color:black;
}

</style>

</head>

<body>

<div class="container">

<h2>Track Application</h2>

<?php if($result){ ?>

<p><strong>Name:</strong>
<?php echo $result['first_name']." ".$result['middle_name']." ".$result['last_name']; ?>
</p>

<p><strong>Application Number:</strong>
<?php echo $result['application_no']; ?>
</p>

<p><strong>Entry Level:</strong>
<?php echo $result['entry_level']; ?>
</p>

<div class="status
<?php
if($result['status']=="Pending") echo "pending";
if($result['status']=="Approved") echo "approved";
if($result['status']=="Rejected") echo "rejected";
?>
">

Status: <?php echo $result['status']; ?>

</div>

<?php if($result['admin_message']){ ?>

<p>
<strong>Message:</strong><br>
<?php echo $result['admin_message']; ?>
</p>

<?php } ?>

<?php } else { ?>

<p style="color:red;">
Application not found
</p>

<?php } ?>

<br>

<button onclick="window.location.href='admission.php'">
Back
</button>

</div>

</body>
</html>
