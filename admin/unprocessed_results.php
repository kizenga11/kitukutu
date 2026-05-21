<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Unprocessed Results</title>

<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
body{
    font-family: Arial, sans-serif;
    background:#f4f6fb;
    margin:0;
    padding:20px;
}

.container{
    max-width:1200px;
    margin:auto;
    background:white;
    padding:20px;
    border-radius:10px;
    box-shadow:0 0 10px rgba(0,0,0,0.08);
}

h2{
    margin-top:0;
    color:#1a2b4c;
}

.form-box{
    margin-bottom:20px;
}

select,button{
    padding:8px 10px;
    margin:5px;
}

button{
    background:#1a2b4c;
    color:white;
    border:none;
    cursor:pointer;
    border-radius:4px;
}

.back-btn{
    background:#777;
    color:white;
    text-decoration:none;
    padding:7px 12px;
    border-radius:4px;
}

.table-box{
    overflow-x:auto;
}

table{
    border-collapse:collapse;
    width:100%;
    min-width:700px;
}

th{
    background:#1a2b4c;
    color:white;
    padding:8px;
    font-size:13px;
}

td{
    padding:7px;
    text-align:center;
    border-bottom:1px solid #ddd;
    font-size:13px;
}

.student{
    text-align:left;
    font-weight:bold;
}

.missing{
    color:red;
    font-weight:bold;
}

.print-area{
    margin-bottom:10px;
}

@media print{
    body{
        background:white;
        padding:0;
    }
    .form-box, .print-area{
        display:none;
    }
    .container{
        box-shadow:none;
        border:none;
    }
}
</style>
</head>

<body>

<div class="container">

<h2>Unprocessed Examination Results</h2>

<div class="form-box">
<form method="GET">

Stream:
<select name="stream" required>
<option value="">Select Stream</option>
<option value="General">General</option>
<option value="Vocational">Vocational</option>
</select>

Exam:
<select name="exam_id" required>
<?php
$ex = mysqli_query($conn,"SELECT id, exam_name FROM exams WHERE is_published=1");
while($e = mysqli_fetch_assoc($ex)){
echo "<option value='".$e['id']."'>".$e['exam_name']."</option>";
}
?>
</select>

<button type="submit">Load Results</button>

</form>
</div>

<?php
if(isset($_GET['stream']) && isset($_GET['exam_id'])){

$stream = mysqli_real_escape_string($conn,$_GET['stream']);
$exam_id = (int)$_GET['exam_id'];

$students = mysqli_query($conn,"
SELECT id, first_name, second_name, last_name
FROM students
WHERE stream='$stream'
ORDER BY first_name
");

$subjects = mysqli_query($conn,"
SELECT s.id, s.short_name
FROM exam_subjects es
JOIN subjects s ON s.id = es.subject_id
WHERE es.exam_id='$exam_id'
AND s.stream='$stream'
ORDER BY s.short_name
");
echo "<div class='print-area'>
<a href='dashboard.php' class='back-btn'>← Back Dashboard</a>
<button onclick='window.print()'>Print</button>
</div>";

echo "<div class='table-box'>";
echo "<table>";

echo "<tr><th>Student Name</th>";

$subject_array = [];
while($sub = mysqli_fetch_assoc($subjects)){
$subject_array[] = $sub;
echo "<th>".$sub['short_name']."</th>";
}

echo "</tr>";

while($stu = mysqli_fetch_assoc($students)){

echo "<tr>";
echo "<td class='student'>".$stu['first_name']." ".$stu['second_name']." ".$stu['last_name']."</td>";

foreach($subject_array as $sub){

$hasSubjectQ = mysqli_query($conn,"
SELECT id FROM student_subjects
WHERE student_id='".$stu['id']."'
AND subject_id='".$sub['id']."'
");

$hasSubject = mysqli_fetch_assoc($hasSubjectQ);

if(!$hasSubject){

echo "<td style='background:#e5e5e5'></td>";

}else{

$markQ = mysqli_query($conn,"
SELECT marks FROM marks
WHERE student_id='".$stu['id']."'
AND subject_id='".$sub['id']."'
AND exam_id='$exam_id'
");

$mark = mysqli_fetch_assoc($markQ);

if($mark){
echo "<td>".$mark['marks']."</td>";
}else{
echo "<td class='missing'>-</td>";
}

}

}

echo "</tr>";
}

echo "</table>";
echo "</div>";

}
?>

</div>

</body>
</html>