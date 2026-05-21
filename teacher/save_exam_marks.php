<?php
include '../includes/config.php';

session_start();

$exam = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT id, academic_year_id FROM exams WHERE is_active=1 LIMIT 1"));

$exam_id = $exam['id'];
$year_id = $exam['academic_year_id'];

$subject_id = $_POST['subject_id'];
$students = $_POST['student_id'];
$marks = $_POST['marks'];

for($i=0;$i<count($students);$i++){

$student_id = $students[$i];
$mark = $marks[$i];

if($mark === "" || $mark === null) continue;

mysqli_query($conn,"
REPLACE INTO marks(student_id,subject_id,academic_year_id,marks,exam_id)
VALUES('$student_id','$subject_id','$year_id','$mark','$exam_id')
");

}

echo "✔ Marks Saved Successfully";
?>