<?php
require_once "../includes/config.php";

$exam_id = (int)$_GET['exam_id'];

$exam = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT * FROM exams WHERE id=$exam_id
"));

$students = mysqli_query($conn,"
SELECT s.*, r.*
FROM exam_results_summary r
JOIN students s ON s.id=r.student_id AND s.is_active=1
WHERE r.exam_id=$exam_id
ORDER BY r.position ASC
");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Bulk Reports</title>

<style>
body{font-family:Arial}

.page{
width:210mm;
min-height:297mm;
padding:15mm;
border:2px solid black;
margin:auto;
page-break-after:always;
position:relative;
}

.watermark{
position:absolute;
top:40%;
left:30%;
opacity:0.07;
}

table{
width:100%;
border-collapse:collapse;
margin-top:10px;
}

th,td{
border:1px solid black;
padding:5px;
text-align:center;
font-size:13px;
}

.header{text-align:center}

@media print{
@page{size:A4 portrait;margin:10mm;}
button{display:none!important;}
body{margin:0;padding:0;}
.page{
width:100%;
min-height:0;
padding:0;
margin:0;
border:none;
box-shadow:none;
}
}

</style>
</head>
<body>

<button onclick="window.print()">DOWNLOAD / PRINT ALL</button>

<?php
while($stu=mysqli_fetch_assoc($students)){

$subs = mysqli_query($conn,"
SELECT sub.subject_name,m.marks
FROM marks m
JOIN subjects sub ON sub.id=m.subject_id
JOIN student_subjects ss ON ss.student_id=m.student_id AND ss.subject_id=m.subject_id
WHERE m.exam_id=$exam_id
AND m.student_id={$stu['student_id']}
");
?>

<div class="page">

<img src="../assets/logo.png" width="250" class="watermark">

<div class="header">
<h2>JINA LA SHULE SEKONDARI</h2>
<h3>RIPOTI YA <?= $exam['exam_name'] ?></h3>
</div>

<b>Jina:</b>
<?= $stu['first_name']." ".$stu['second_name']." ".$stu['last_name'] ?><br>

<b>Mkondo:</b> <?= $stu['stream'] ?><br>
<b>Wastani:</b> <?= number_format($stu['average_marks'],2) ?><br>
<b>Division:</b> <?= $stu['division'] ?><br>
<b>Nafasi:</b> <?= $stu['position'] ?><br>

<table>
<tr><th>Somo</th><th>Alama</th></tr>

<?php
while($s=mysqli_fetch_assoc($subs)){
echo "<tr>
<td>{$s['subject_name']}</td>
<td>{$s['marks']}</td>
</tr>";
}
?>
</table>

<br>

<b>Shule itafungwa:</b> <?= $exam['closing_date'] ?><br>
<b>Shule itafunguliwa:</b> <?= $exam['opening_date'] ?><br>

<br>

<b>Ujumbe wa Shule:</b><br>

<?php
if($exam['message_stream']=="" || $exam['message_stream']==$stu['stream']){
echo nl2br($exam['school_message']);
}
?>

<br><br>

_______________________<br>
MKUU WA SHULE

</div>

<?php } ?>

</body>
</html>