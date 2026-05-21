<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

$exam_id = $_GET['exam_id'] ?? '';

if(!$exam_id){
    die("Exam not selected.");
}

$exam = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT * FROM exams WHERE id='$exam_id'
"));

/* ===============================
   GET OVERALL RESULTS
=================================*/
$results = mysqli_query($conn,"
SELECT ers.*, 
st.stream,
CONCAT(st.first_name,' ',st.second_name,' ',st.last_name) AS full_name
FROM exam_results_summary ers
JOIN students st ON ers.student_id=st.id
WHERE ers.exam_id='$exam_id'
ORDER BY ers.total_points ASC, ers.average_marks DESC
");

$total_students=0;
$total_avg=0;
$division_count=['I'=>0,'II'=>0,'III'=>0,'IV'=>0,'0'=>0];
$grade_count=['A'=>0,'B'=>0,'C'=>0,'D'=>0,'F'=>0];

function schoolGrade($avg){
    if($avg >= 75) return 'A';
    if($avg >= 65) return 'B';
    if($avg >= 45) return 'C';
    if($avg >= 30) return 'D';
    return 'F';
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Overall School Merit</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#f4f6f9;}
.school-header{text-align:center;}
.school-header h4{color:#c1121f;font-weight:bold;}
@media print{
.no-print{display:none!important;}
}
</style>
</head>
<body>

<div class="container py-4">

<div class="card shadow">
<div class="card-body">

<div class="school-header mb-3">
<h4>KITUKUTU SECONDARY TECHNICAL SCHOOL</h4>
<p><strong><?= $exam['exam_name'] ?></strong></p>
<p>OVERALL SCHOOL MERIT LIST</p>
<p>Date Printed: <?= date("d M Y") ?></p>
<hr>
</div>

<div class="table-responsive">
<table class="table table-bordered table-sm text-center align-middle">

<tr class="table-dark">
<th>Pos</th>
<th>Name</th>
<th>Stream</th>
<th>Total</th>
<th>Average</th>
<th>Grade</th>
<th>Points</th>
<th>Division</th>
</tr>

<?php
$position=1;

while($row=mysqli_fetch_assoc($results)){

$total_students++;
$total_avg += $row['average_marks'];
$division_count[$row['division']]++;

$grade_count[ schoolGrade($row['average_marks']) ]++;
?>

<tr>
<td><?= $position++ ?></td>
<td><?= $row['full_name'] ?></td>
<td><?= $row['stream'] ?></td>
<td><?= $row['total_marks'] ?></td>
<td><?= $row['average_marks'] ?></td>
<td><?= $row['grade'] ?></td>
<td><?= $row['total_points'] ?></td>
<td><?= $row['division'] ?></td>
</tr>

<?php } ?>

</table>
</div>

<?php
$school_avg = $total_students ? round($total_avg/$total_students,2) : 0;
$school_grade = schoolGrade($school_avg);
?>

<hr>

<h5>Overall School Summary</h5>

<div class="row text-center">

<?php foreach($division_count as $div=>$count){ ?>
<div class="col-md-2 col-6 mb-2">
<div class="card bg-dark text-white">
<div class="card-body">
Div <?= $div ?><br><?= $count ?>
</div>
</div>
</div>
<?php } ?>

<div class="col-md-2 col-12 mb-2">
<div class="card bg-secondary text-white">
<div class="card-body">
Total<br><?= $total_students ?>
</div>
</div>
</div>

</div>

<hr>

<div class="row text-center">

<div class="col-md-4 mb-2">
<div class="card bg-primary text-white">
<div class="card-body">
School Avg<br><?= $school_avg ?>
</div>
</div>
</div>

<div class="col-md-4 mb-2">
<div class="card bg-success text-white">
<div class="card-body">
School Grade<br><?= $school_grade ?>
</div>
</div>
</div>

</div>

<hr>

<h5>Average Grade Distribution</h5>

<div class="row text-center">

<?php foreach($grade_count as $grade=>$count){ ?>
<div class="col-md-2 col-6 mb-2">
<div class="card bg-info text-white">
<div class="card-body">
<?= $grade ?><br><?= $count ?>
</div>
</div>
</div>
<?php } ?>

</div>

<hr>

<div class="mt-3 text-end no-print">
<button onclick="window.print()" class="btn btn-success">
Print
</button>

<a href="view_exam_results.php?exam_id=<?= $exam_id ?>" class="btn btn-secondary">
Back to Streams
</a>
</div>

</div>
</div>

</div>
</body>
</html>