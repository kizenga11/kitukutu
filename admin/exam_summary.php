<?php
session_start();
include "../includes/config.php";

/* SECURITY */
if(!isset($_SESSION['admin_id'])){
header("Location: ../login.php");
exit();
}

$exam_id = $_GET['exam_id'] ?? 0;
if(!$exam_id) die("No exam selected");

/* EXAM */
$exam = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT exam_name FROM exams WHERE id='$exam_id'
"));

/* SUBJECTS */
$subjects = mysqli_query($conn,"
SELECT DISTINCT subject_name 
FROM subjects
ORDER BY subject_name
");

$data=[];

/* SCHOOL CALC */
$school_points = 0;
$school_subjects = 0;

while($sub=mysqli_fetch_assoc($subjects)){

$subject_name = $sub['subject_name'];

$q=mysqli_query($conn,"
SELECT 
COUNT(*) as reg,
SUM(CASE WHEN m.marks!='A' THEN 1 ELSE 0 END) as sat,
SUM(CASE WHEN m.marks='A' THEN 1 ELSE 0 END) as abs,
SUM(CASE WHEN m.marks >=30 THEN 1 ELSE 0 END) as pass,

AVG(
CASE 
WHEN m.marks >=75 THEN 1
WHEN m.marks >=65 THEN 2
WHEN m.marks >=45 THEN 3
WHEN m.marks >=30 THEN 4
ELSE 5
END
) as gpa

FROM marks m
JOIN student_subjects ss ON ss.student_id = m.student_id AND ss.subject_id = m.subject_id
WHERE m.subject_id IN (
SELECT id FROM subjects WHERE subject_name='$subject_name'
)
AND m.exam_id='$exam_id'
");

$r=mysqli_fetch_assoc($q);

$reg=$r['reg'] ?? 0;
$sat=$r['sat'] ?? 0;
$abs=$r['abs'] ?? 0;
$pass=$r['pass'] ?? 0;

/* HANDLE EMPTY SUBJECT */
if($r['gpa'] === NULL){
$gpa='-';
$level='-';
}else{
$gpa=round($r['gpa'],4);

/* COMPETENCY */
if($gpa <= 1.5) $level="A (Excellent)";
elseif($gpa <= 2.5) $level="B (Very Good)";
elseif($gpa <= 3.5) $level="C (Good)";
elseif($gpa <= 4.5) $level="D (Satisfactory)";
else $level="F (Fail)";

/* SCHOOL GPA */
$school_points += $gpa;
$school_subjects++;
}

/* GENDER */
$gender=mysqli_query($conn,"
SELECT s.sex,

SUM(CASE WHEN m.marks >=75 THEN 1 ELSE 0 END) A,
SUM(CASE WHEN m.marks >=65 AND m.marks <75 THEN 1 ELSE 0 END) B,
SUM(CASE WHEN m.marks >=45 AND m.marks <65 THEN 1 ELSE 0 END) C,
SUM(CASE WHEN m.marks >=30 AND m.marks <45 THEN 1 ELSE 0 END) D,
SUM(CASE WHEN m.marks <30 AND m.marks!='A' THEN 1 ELSE 0 END) F,
SUM(CASE WHEN m.marks='A' THEN 1 ELSE 0 END) ABS

FROM marks m
JOIN students s ON s.id=m.student_id AND s.is_active=1
JOIN student_subjects ss ON ss.student_id = m.student_id AND ss.subject_id = m.subject_id

WHERE m.subject_id IN (
SELECT id FROM subjects WHERE subject_name='$subject_name'
)
AND m.exam_id='$exam_id'

GROUP BY s.sex
");

$male=[]; $female=[];
while($g=mysqli_fetch_assoc($gender)){
if($g['sex']=='Male') $male=$g;
if($g['sex']=='Female') $female=$g;
}

$data[]=[
"name"=>$subject_name,
"reg"=>$reg,
"sat"=>$sat,
"abs"=>$abs,
"pass"=>$pass,
"gpa"=>$gpa,
"level"=>$level,
"male"=>$male,
"female"=>$female
];
}

/* SCHOOL SUMMARY */
$school_gpa = ($school_subjects>0) ? round($school_points/$school_subjects,4) : '-';

if($school_gpa==='-'){
$school_grade='-';
}else{
if($school_gpa <= 1.5) $school_grade="A";
elseif($school_gpa <= 2.5) $school_grade="B";
elseif($school_gpa <= 3.5) $school_grade="C";
elseif($school_gpa <= 4.5) $school_grade="D";
else $school_grade="F";
}

/* SORT */
usort($data,function($a,$b){
return ($a['gpa']=='-'?999:$a['gpa']) <=> ($b['gpa']=='-'?999:$b['gpa']);
});
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>NECTA Dashboard</title>

<style>
body{font-family:Arial;background:#f4f6f9;padding:10px;}
.container{max-width:1100px;margin:auto;}
.card{background:#fff;padding:12px;border-radius:10px;margin-bottom:10px;}

table{width:100%;border-collapse:collapse;margin-top:10px;}
th,td{padding:6px;border:1px solid #ddd;text-align:center;font-size:12px;}
th{background:#333;color:#fff;}

.btn{padding:6px 10px;border-radius:6px;text-decoration:none;font-size:12px;}
.back{background:#555;color:white;}
.print{background:#27ae60;color:white;}

@media print{.btn{display:none;}}
</style>
</head>

<body>

<div class="container">

<div class="card">
<button onclick="window.print()" class="btn print">🖨 Print</button>

<h3 style="text-align:center;">KITUKUTU TECHNICAL SECONDARY SCHOOL</h3>
<h4 style="text-align:center;"><?= $exam['exam_name'] ?> - PERFORMANCE SUMMARY</h4>
</div>

<!-- SCHOOL SUMMARY -->
<div class="card">
<h4>🏫 School Summary</h4>

<table>
<tr>
<th>School GPA</th>
<th>School Grade</th>
</tr>
<tr>
<td><?= $school_gpa ?></td>
<td><?= $school_grade ?></td>
</tr>
</table>
</div>

<!-- SUBJECT TABLE -->
<div class="card">
<table>
<tr>
<th>Rank</th>
<th>Subject</th>
<th>REG</th>
<th>SAT</th>
<th>ABS</th>
<th>PASS</th>
<th>GPA</th>
<th>LEVEL</th>
</tr>

<?php $r=1; foreach($data as $d){ ?>
<tr>
<td><?= $r++ ?></td>
<td><?= $d['name'] ?></td>
<td><?= $d['reg'] ?></td>
<td><?= $d['sat'] ?></td>
<td><?= $d['abs'] ?></td>
<td><?= $d['pass'] ?></td>
<td><?= $d['gpa'] ?></td>
<td><?= $d['level'] ?></td>
</tr>
<?php } ?>

</table>
</div>

<!-- GENDER -->
<div class="card">
<h4>👨‍🎓 Gender Distribution</h4>

<table>
<tr>
<th>Subject</th>
<th colspan="6">Boys</th>
<th colspan="6">Girls</th>
</tr>

<tr>
<th></th>
<th>A</th><th>B</th><th>C</th><th>D</th><th>F</th><th>ABS</th>
<th>A</th><th>B</th><th>C</th><th>D</th><th>F</th><th>ABS</th>
</tr>

<?php foreach($data as $d){ ?>
<tr>
<td><?= $d['name'] ?></td>

<td><?= $d['male']['A'] ?? 0 ?></td>
<td><?= $d['male']['B'] ?? 0 ?></td>
<td><?= $d['male']['C'] ?? 0 ?></td>
<td><?= $d['male']['D'] ?? 0 ?></td>
<td><?= $d['male']['F'] ?? 0 ?></td>
<td><?= $d['male']['ABS'] ?? 0 ?></td>

<td><?= $d['female']['A'] ?? 0 ?></td>
<td><?= $d['female']['B'] ?? 0 ?></td>
<td><?= $d['female']['C'] ?? 0 ?></td>
<td><?= $d['female']['D'] ?? 0 ?></td>
<td><?= $d['female']['F'] ?? 0 ?></td>
<td><?= $d['female']['ABS'] ?? 0 ?></td>

</tr>
<?php } ?>

</table>
</div>

</div>

</body>
</html>