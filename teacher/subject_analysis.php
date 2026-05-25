<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['teacher_id'])){
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];

if(!isset($_GET['subject_id'])){
    die("Subject not selected.");
}

$subject_id = $_GET['subject_id'];

/* ===============================
   GET ACTIVE EXAM
=================================*/
$active_exam = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT * FROM exams WHERE is_active=1 LIMIT 1
"));

if(!$active_exam){
    die("No active exam set.");
}

$exam_id   = $active_exam['id'];
$exam_name = $active_exam['exam_name'];

/* ===============================
   GET SUBJECT INFO
=================================*/
$assignment = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT ta.*, s.subject_name 
    FROM teacher_assignments ta
    JOIN subjects s ON ta.subject_id=s.id
    WHERE ta.teacher_id='$teacher_id'
    AND ta.subject_id='$subject_id'
"));

if(!$assignment){
    die("Unauthorized.");
}

$subject_name = $assignment['subject_name'];
$stream       = $assignment['stream'];

/* ===============================
   GET RESULTS
=================================*/
$results = mysqli_query($conn,"
    SELECT st.id,
           CONCAT(st.first_name,' ',st.second_name,' ',st.last_name) AS full_name,
           m.marks
    FROM students st
    JOIN marks m ON st.id=m.student_id
    WHERE m.subject_id='$subject_id'
    AND m.exam_id='$exam_id'
    AND st.stream='$stream'
    ORDER BY m.marks DESC
");

/* ===============================
   SUMMARY VARIABLES
=================================*/
$total_students = 0;
$total_marks = 0;

$grade_count = ['A'=>0,'B'=>0,'C'=>0,'D'=>0,'F'=>0];

function getGrade($mark){
    if($mark >= 75) return 'A';
    if($mark >= 65) return 'B';
    if($mark >= 45) return 'C';
    if($mark >= 30) return 'D';
    return 'F';
}
?>

<!DOCTYPE html>
<html>
<head
      
      
      >
<title>Subject Analysis</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body{ background:#f4f6f9; }
.school-header{
    text-align:center;
    margin-bottom:20px;
}
.school-header h4{
    color:#d90429;
    font-weight:bold;
}
@media(max-width:768px){
    table{ font-size:13px; }
}
   
@media print {

    body {
        background:white !important;
    }

    .btn,
    .no-print {
        display:none !important;
    }

    .card {
        box-shadow:none !important;
        border:none !important;
    }

    hr {
        border:1px solid black;
    }
}
</style>
</head>

<body>

<div class="container py-4">

<div class="school-header">
<h4>KITUKUTU SECONDARY TECHNICAL SCHOOL</h4>
<p><strong><?= $subject_name ?> (<?= $stream ?>)</strong></p>
<p>Exam: <?= $exam_name ?></p>
<hr>
</div>

<div class="card shadow">

<div class="card-body">

<div class="table-responsive">
<table class="table table-bordered text-center align-middle">

<tr class="table-dark">
<th>Position</th>
<th>Student Name</th>
<th>Marks</th>
<th>Grade</th>
</tr>

<?php
$position = 1;
if (mysqli_num_rows($results) === 0): ?>
<tr>
  <td colspan="4" class="text-center py-5 text-muted">
    <i class="bi bi-inbox" style="font-size:2.2rem;display:block;margin-bottom:8px;opacity:.25;"></i>
    No marks entered for this exam yet.
  </td>
</tr>
<?php else: while($row = mysqli_fetch_assoc($results)):
    $grade = getGrade($row['marks']);
    $total_students++;
    $total_marks += $row['marks'];
    $grade_count[$grade]++;
?>

<tr>
<td><?= $position++ ?></td>
<td><?= $row['full_name'] ?></td>
<td><?= $row['marks'] ?></td>
<td><strong><?= $grade ?></strong></td>
</tr>

<?php endwhile; endif; ?>

</table>
</div>

<?php
$average = $total_students > 0 ? round($total_marks/$total_students,2) : 0;
?>

<hr>

<h5>Summary</h5>

<div class="row text-center">

<div class="col-md-2 col-6 mb-2">
<div class="card bg-success text-white">
<div class="card-body">
A<br><?= $grade_count['A'] ?>
</div>
</div>
</div>

<div class="col-md-2 col-6 mb-2">
<div class="card bg-primary text-white">
<div class="card-body">
B<br><?= $grade_count['B'] ?>
</div>
</div>
</div>

<div class="col-md-2 col-6 mb-2">
<div class="card bg-warning text-dark">
<div class="card-body">
C<br><?= $grade_count['C'] ?>
</div>
</div>
</div>

<div class="col-md-2 col-6 mb-2">
<div class="card bg-secondary text-white">
<div class="card-body">
D<br><?= $grade_count['D'] ?>
</div>
</div>
</div>

<div class="col-md-2 col-6 mb-2">
<div class="card bg-danger text-white">
<div class="card-body">
F<br><?= $grade_count['F'] ?>
</div>
</div>
</div>

<div class="col-md-2 col-12 mb-2">
<div class="card bg-dark text-white">
<div class="card-body">
Average<br><?= $average ?>
</div>
</div>
</div>

</div>

    
<div class="mt-3 d-flex justify-content-between">

<button onclick="window.print()" class="btn btn-primary btn-sm">
<i class="bi bi-printer"></i> Print
</button>

<a href="dashboard.php" class="btn btn-secondary btn-sm no-print">
Back
</a>

</div>

</div>
</div>

</div>

</body>
</html>