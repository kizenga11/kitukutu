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

$summary_json = isset($exam['summary_json']) && $exam['summary_json'] ? json_decode($exam['summary_json'],true) : null;

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
ORDER BY ers.position ASC
");

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
@page{size:A4 landscape;margin:10mm;}
.no-print{display:none!important;}
body{background:#fff!important;font-size:11px;}
.container{max-width:100%!important;padding:0!important;}
.card{box-shadow:none!important;border:1px solid #ccc!important;}
.card-body{padding:8px!important;}
.table{font-size:10px;}
.table th,.table td{padding:4px 5px!important;border:1px solid #000!important;}
.table-dark th{background:#444!important;color:#fff!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
.bg-dark,.bg-secondary,.bg-primary,.bg-success,.bg-info{-webkit-print-color-adjust:exact;print-color-adjust:exact;}
.school-header h4{color:#000!important;}
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
while($row=mysqli_fetch_assoc($results)):
$div_display = $row['division'] ?: '-';
?>

<tr>
<td><?= (int)$row['position'] ?></td>
<td><?= $row['full_name'] ?></td>
<td><?= $row['stream'] ?></td>
<td><?= (int)$row['total_marks'] ?></td>
<td><?= number_format((float)$row['average_marks'],2) ?></td>
<td><?= $row['grade'] ?></td>
<td><?= (int)$row['total_points'] ?></td>
<td><?= $div_display ?></td>
</tr>

<?php endwhile; ?>

</table>
</div>

<?php
if($summary_json && isset($summary_json['school'])){
    $sc = $summary_json['school'];
    $school_avg = $sc['school_avg'];
    $school_grade = $sc['school_grade'];
    $total_students = (int)($sc['total_students']??0);
    $divisions = $summary_json['divisions'] ?? [];
} else {
    // fallback: compute on the fly
    $divQ = mysqli_query($conn,"SELECT division,COUNT(*) as c FROM exam_results_summary WHERE exam_id='$exam_id' GROUP BY division");
    $divisions = [];
    while($d=mysqli_fetch_assoc($divQ)) $divisions[$d['division']] = ['total'=>(int)$d['c']];

    $sumQ = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total,AVG(average_marks) as avg FROM exam_results_summary WHERE exam_id='$exam_id'"));
    $total_students = (int)$sumQ['total'];
    $school_avg = $sumQ['avg'] ? round((float)$sumQ['avg'],2) : 0;
    $school_grade = schoolGrade($school_avg);
}

// Grade distribution from stored averages
$gradQ = mysqli_query($conn,"SELECT average_marks FROM exam_results_summary WHERE exam_id='$exam_id'");
$gradeCounts = ['A'=>0,'B'=>0,'C'=>0,'D'=>0,'F'=>0];
while($gr=mysqli_fetch_assoc($gradQ)){
    $g = schoolGrade($gr['average_marks']);
    $gradeCounts[$g]++;
}
?>

<hr>

<h5>Overall School Summary</h5>

<div class="row text-center">

<?php foreach(['I','II','III','IV','0','N/A'] as $div):
$count = isset($divisions[$div]) ? (int)($divisions[$div]['total']??$divisions[$div]??0) : 0;
if(!$count) continue;
?>
<div class="col-md-2 col-6 mb-2">
<div class="card bg-dark text-white">
<div class="card-body">
Div <?= $div ?><br><?= $count ?>
</div>
</div>
</div>
<?php endforeach; ?>

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

<?php foreach(['A','B','C','D','F'] as $g):
$c = $gradeCounts[$g] ?? 0;
if(!$c) continue;
?>
<div class="col-md-2 col-6 mb-2">
<div class="card bg-info text-white">
<div class="card-body">
<?= $g ?><br><?= $c ?>
</div>
</div>
</div>
<?php endforeach; ?>

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