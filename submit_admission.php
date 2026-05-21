<?php

include "includes/config.php";

if($_SERVER["REQUEST_METHOD"]=="POST"){

// GET DATA

$first_name = mysqli_real_escape_string($conn,$_POST['first_name']);
$middle_name = mysqli_real_escape_string($conn,$_POST['middle_name']);
$last_name = mysqli_real_escape_string($conn,$_POST['last_name']);

$gender = $_POST['gender'];
$entry_level = $_POST['entry_level'];

$psle_index_no = $_POST['psle_index_no'] ?? '';
$form2_index_no = $_POST['form2_index_no'] ?? '';

$phone = mysqli_real_escape_string($conn,$_POST['phone']);
$email = mysqli_real_escape_string($conn,$_POST['email']);


// VALIDATE PSLE FORMAT

if($entry_level=="Form One" && $psle_index_no!=""){

if(!preg_match("/^PS\d{7}-\d{1,4}\/\d{4}$/",$psle_index_no)){

die("
<h2 style='color:red;text-align:center;margin-top:100px;'>
Invalid PSLE Format<br>
Correct format example:<br>
PS1234567-0001/2025
</h2>
");

}

}



// VALIDATE FORM TWO FORMAT

    if($entry_level=="Form Three" && $form2_index_no!=""){

if(!preg_match("/^S\d{4}\/\d{1,4}\/\d{4}$/",$form2_index_no)){

die("
<h2 style='color:red;text-align:center;margin-top:100px;'>
Invalid Form Two Format<br>
Example:<br>
S1981/0001/2023
</h2>
");

}

}



$check_query = "SELECT application_no,status 
FROM admissions 
WHERE ";

if($entry_level=="Form One"){
    $check_query .= "psle_index_no='$psle_index_no'";
}

elseif($entry_level=="Form Three"){
    $check_query .= "form2_index_no='$form2_index_no'";
}

else{
    $check_query .= "phone='$phone'";
}

$check = mysqli_query($conn,$check_query);

if(mysqli_num_rows($check)>0){

$row = mysqli_fetch_assoc($check);

$app_no = $row['application_no'];
$status = $row['status'];

die("
<div style='text-align:center;margin-top:100px;font-family:Arial;'>
<h2 style='color:red;'>You already applied using this index number</h2>
<p>Application Number:</p>
<h3>$app_no</h3>
<p>Status: <b>$status</b></p>
<button onclick=\"window.location.href='track.php?application_no=$app_no'\">
Track Application
</button>
</div>
");

}





// GENERATE APPLICATION NUMBER

$year = date("Y");

$query = mysqli_query($conn,"
SELECT id FROM admissions ORDER BY id DESC LIMIT 1
");

$row = mysqli_fetch_assoc($query);

$next_id = $row ? $row['id'] + 1 : 1;

$application_no = "AKTS-$year-" . str_pad($next_id,4,"0",STR_PAD_LEFT);


// FILE UPLOAD

$document_name="";

if(isset($_FILES['document']) && $_FILES['document']['name']!=""){

$target_dir="uploads/admissions/";

$document_name=time()."_".$_FILES['document']['name'];

$target_file=$target_dir.$document_name;

move_uploaded_file(
$_FILES['document']['tmp_name'],
$target_file
);

}


// INSERT DATABASE

$sql="INSERT INTO admissions(

application_no,
first_name,
middle_name,
last_name,
gender,
entry_level,
psle_index_no,
form2_index_no,
phone,
email,
document

)

VALUES(

'$application_no',
'$first_name',
'$middle_name',
'$last_name',
'$gender',
'$entry_level',
'$psle_index_no',
'$form2_index_no',
'$phone',
'$email',
'$document_name'

)";

mysqli_query($conn,$sql);

}

?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">

<title>Application Submitted</title>

<style>

body{
font-family:Arial;
background:#f4f6f9;
text-align:center;
padding-top:80px;
}

.box{
background:white;
display:inline-block;
padding:30px;
border-radius:10px;
box-shadow:0 0 10px rgba(0,0,0,0.1);
max-width:400px;
}

.success{
color:green;
font-size:20px;
margin-bottom:10px;
}

.appno{
font-size:22px;
font-weight:bold;
color:#1e4a6d;
margin:10px 0;
}

button{
padding:10px 15px;
margin:5px;
border:none;
border-radius:5px;
background:#1e4a6d;
color:white;
cursor:pointer;
}

button:hover{
background:#f4b400;
color:black;
}

</style>

</head>

<body>

<div class="box">

<div class="success">

Application Submitted Successfully

</div>

<p>Your Application Number:</p>

<div class="appno" id="appNo">

<?php echo $application_no; ?>

</div>

<p>Save this number to track your application.</p>


<button onclick="copyNumber()">

Copy

</button>


<button onclick="window.print()">

Print

</button>


<button onclick="window.location.href='track.php?application_no=<?php echo $application_no; ?>'">

Track Application

</button>


<button onclick="window.location.href='admission.php'">

Back

</button>


</div>


<script>

function copyNumber(){

let text=document.getElementById("appNo").innerText;

navigator.clipboard.writeText(text);

alert("Application number copied");

}

</script>

</body>
</html>
