<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

/* ===============================
   HANDLE PUBLISH
=================================*/
if(isset($_GET['publish'])){
    $id = intval($_GET['publish']);
    mysqli_query($conn,"UPDATE exams SET is_published=1 WHERE id='$id'");
    header("Location: view_exam_results.php?exam_id=".$id);
    exit();
}

if(isset($_GET['unpublish'])){
    $id = intval($_GET['unpublish']);
    mysqli_query($conn,"UPDATE exams SET is_published=0 WHERE id='$id'");
    header("Location: view_exam_results.php?exam_id=".$id);
    exit();
}

/* ===============================
   GET EXAMS
=================================*/
$exams = mysqli_query($conn,"SELECT * FROM exams ORDER BY id DESC");

$exam_id = $_GET['exam_id'] ?? null;
?>

<!DOCTYPE html>
<html>
<head>
<title>Exam Results</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{ background:#f4f6f9; }
.school-header{text-align:center;}
.school-header h4{color:#d90429;font-weight:bold;}
@media print{
    .no-print{display:none!important;}
}
</style>
</head>
<body>

<div class="container py-4">

<div class="card shadow mb-4 no-print">
<div class="card-body">

<form method="GET" class="row g-2 align-items-center">

<div class="col-md-8">
<select name="exam_id" class="form-select" required onchange="this.form.submit()">
<option value="">Select Exam</option>
<?php while($row=mysqli_fetch_assoc($exams)){ ?>
<option value="<?= $row['id'] ?>" <?= ($exam_id==$row['id'])?'selected':'' ?>>
<?= $row['exam_name'] ?>
</option>
<?php } ?>
</select>
</div>

<div class="col-md-4 text-end">
<a href="dashboard.php" class="btn btn-secondary">Back</a>
</div>

</form>

</div>
</div>

<?php
if($exam_id){

$exam = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM exams WHERE id='$exam_id'"));

$results = mysqli_query($conn,"
SELECT ers.*, 
CONCAT(st.first_name,' ',st.second_name,' ',st.last_name) AS full_name
FROM exam_results_summary ers
JOIN students st ON ers.student_id=st.id
WHERE ers.exam_id='$exam_id'
ORDER BY ers.position ASC
");

$subjects = mysqli_query($conn,"SELECT * FROM subjects ORDER BY stream,subject_name");
?>

<div class="card shadow">

<div class="card-body">

<div class="school-header mb-3">
<h4>KITUKUTU SECONDARY TECHNICAL SCHOOL</h4>
<p><strong><?= $exam['exam_name'] ?></strong></p>
<p>Date Printed: <?= date("d M Y") ?></p>
<hr>
</div>

<div class="table-responsive">
<table class="table table-bordered table-sm text-center align-middle">

<tr class="table-dark">
<th>Pos</th>
<th>Name</th>

<?php 
$subject_array=[];
while($sub=mysqli_fetch_assoc($subjects)){
$subject_array[]=$sub;
echo "<th>".$sub['subject_name']."</th>";
}
?>

<th>Total</th>
<th>Avg</th>
<th>Grade</th>
<th>Points</th>
<th>Division</th>
</tr>

<?php
$total_students=0;
$total_average=0;
$division_count=['I'=>0,'II'=>0,'III'=>0,'IV'=>0,'0'=>0];

while($row=mysqli_fetch_assoc($results)){
$total_students++;
$total_average+=$row['average_marks'];
$division_count[$row['division']]++;
?>

<tr>
<td><?= $row['position'] ?></td>
<td><?= $row['full_name'] ?></td>

<?php
foreach($subject_array as $sub){
$m=mysqli_fetch_assoc(mysqli_query($conn,"
SELECT marks FROM marks
WHERE student_id='{$row['student_id']}'
AND subject_id='{$sub['id']}'
AND exam_id='$exam_id'
"));
echo "<td>".($m['marks'] ?? '-')."</td>";
}
?>

<td><?= $row['total_marks'] ?></td>
<td><?= $row['average_marks'] ?></td>
<td><?= $row['grade'] ?></td>
<td><?= $row['total_points'] ?></td>
<td><?= $row['division'] ?></td>
</tr>

<?php } ?>

</table>
</div>

<hr>

<h5>Summary</h5>

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
<div class="card bg-primary text-white">
<div class="card-body">
School Avg<br><?= $total_students?round($total_average/$total_students,2):0 ?>
</div>
</div>
</div>

</div>

<div class="mt-3 d-flex justify-content-between no-print">

<button onclick="window.print()" class="btn btn-success">
Print
</button>

<?php if($exam['is_published']){ ?>
<a href="?unpublish=<?= $exam_id ?>" class="btn btn-warning">
Unpublish
</a>
<?php } else { ?>
<a href="?publish=<?= $exam_id ?>" class="btn btn-primary">
Publish Public
</a>
<?php } ?>

<a href="export_excel.php?exam_id=<?= $exam_id ?>" class="btn btn-dark">
Export Excel
</a>

</div>

</div>
</div>

<?php } ?>

</div>
</body>
</html>