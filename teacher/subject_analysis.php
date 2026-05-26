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
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Subject Analysis</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body{background:#f4f6f9;font-family:system-ui,-apple-system,sans-serif;}
.school-header{text-align:center;margin-bottom:16px;}
.school-header h4{color:#d90429;font-weight:bold;font-size:15px;margin:0 0 4px;}
.school-header p{margin:0;font-size:12px;color:#555;}
.summary-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin:12px 0;}
.sum-card{background:#fff;border-radius:10px;padding:12px 8px;text-align:center;border:1px solid #e5e7eb;}
.sum-card .val{font-size:20px;font-weight:800;line-height:1.1;}
.sum-card .lbl{font-size:10px;color:#6b7280;margin-top:3px;text-transform:uppercase;letter-spacing:.3px;}
.grade-dist{display:flex;flex-wrap:wrap;gap:6px;margin:12px 0;}
.grade-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:700;}
.grade-badge .g-letter{width:22px;height:22px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;color:#fff;}
table{font-size:12px;}
.table th{background:#1a1a2e;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;}
.table td{vertical-align:middle;}
.btn-back{background:#6c757d;color:#fff;padding:6px 14px;border-radius:6px;text-decoration:none;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:6px;}
.btn-back:hover{background:#5a6268;color:#fff;}
@media(max-width:600px){
  .summary-grid{grid-template-columns:repeat(2,1fr);}
  .school-header h4{font-size:13px;}
}
@media print {
  body{background:white!important;}
  .btn,.no-print{display:none!important;}
  .card{box-shadow:none!important;border:none!important;}
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

<h5 class="mt-3 mb-2" style="font-size:13px;font-weight:700;">Summary</h5>

<div class="grade-dist">
<span class="grade-badge" style="background:#d1fae5;color:#065f46;"><span class="g-letter" style="background:#10b981;">A</span><?= $grade_count['A'] ?></span>
<span class="grade-badge" style="background:#dbeafe;color:#1e40af;"><span class="g-letter" style="background:#3b82f6;">B</span><?= $grade_count['B'] ?></span>
<span class="grade-badge" style="background:#fef3c7;color:#92400e;"><span class="g-letter" style="background:#f59e0b;">C</span><?= $grade_count['C'] ?></span>
<span class="grade-badge" style="background:#fed7aa;color:#9a3412;"><span class="g-letter" style="background:#f97316;">D</span><?= $grade_count['D'] ?></span>
<span class="grade-badge" style="background:#fee2e2;color:#991b1b;"><span class="g-letter" style="background:#ef4444;">F</span><?= $grade_count['F'] ?></span>
<span class="grade-badge" style="background:#1a1a2e;color:#fff;"><span class="g-letter" style="background:#374151;">AVG</span><?= $average ?></span>
</div>

    
<div class="mt-3 d-flex justify-content-between no-print">

<button onclick="window.print()" class="btn btn-primary btn-sm">
<i class="bi bi-printer"></i> Print
</button>

<a href="dashboard.php" class="btn btn-secondary btn-sm">
Back
</a>

</div>

</div>
</div>

</div>

</body>
</html>