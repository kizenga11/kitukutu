<?php
include "../includes/config.php";

$exam_id = $_GET['exam_id'] ?? null;

$exams = mysqli_query($conn,"
SELECT * FROM exams 
WHERE is_published=1
ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>School Results</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#e9ecef;
}
.result-container{
    background:white;
    padding:30px;
    border-radius:8px;
    box-shadow:0 0 20px rgba(0,0,0,0.1);
}
.school-header{
    text-align:center;
}
.school-header h3{
    color:#c1121f;
    font-weight:bold;
}
@media print{
    body{background:white;}
    .no-print{display:none!important;}
    .result-container{box-shadow:none;border:none;}
}
</style>
</head>
<body>

<div class="container py-4">

<div class="result-container">

<?php if(!$exam_id){ ?>

<h4 class="text-center mb-4">Published Exam Results</h4>

<ul class="list-group">
<?php while($row=mysqli_fetch_assoc($exams)){ ?>
<li class="list-group-item d-flex justify-content-between align-items-center">
<?= $row['exam_name'] ?>
<a href="?exam_id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">
View
</a>
</li>
<?php } ?>
</ul>

<?php } else {

$exam=mysqli_fetch_assoc(mysqli_query($conn,"
SELECT * FROM exams 
WHERE id='$exam_id' 
AND is_published=1
"));

if(!$exam){
    echo "<div class='alert alert-danger'>Results not available.</div>";
    exit();
}

$results=mysqli_query($conn,"
SELECT ers.*, 
st.registration_no
FROM exam_results_summary ers
JOIN students st ON ers.student_id=st.id AND st.is_active=1
WHERE ers.exam_id='$exam_id'
ORDER BY ers.position ASC
");

$subjects=mysqli_query($conn,"SELECT * FROM subjects ORDER BY stream,subject_name");
?>

<div class="school-header mb-3">
<h3>KITUKUTU SECONDARY TECHNICAL SCHOOL</h3>
<p><strong><?= $exam['exam_name'] ?></strong></p>
<p>Date: <?= date("d M Y") ?></p>
<hr>
</div>

<div class="table-responsive">
<table class="table table-bordered table-sm text-center align-middle">

<tr class="table-dark">
<th>Pos</th>
<th>Reg No</th>

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
while($row=mysqli_fetch_assoc($results)){
?>

<tr>
<td><?= $row['position'] ?></td>
<td><?= htmlspecialchars($row['registration_no']) ?></td>

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

<div class="mt-3 text-end no-print">
<button onclick="window.print()" class="btn btn-success">
Print PDF
</button>
<a href="results.php" class="btn btn-secondary">
Back
</a>
</div>

<?php } ?>

</div>
</div>

</body>
</html>