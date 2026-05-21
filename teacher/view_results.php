<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['teacher_id'])){
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];

/* Get streams teacher is assigned to */
$assignments = mysqli_query($conn,"
    SELECT DISTINCT stream 
    FROM teacher_assignments 
    WHERE teacher_id='$teacher_id'
");

$streams = [];
while($row = mysqli_fetch_assoc($assignments)){
    $streams[] = $row['stream'];
}

if(empty($streams)){
    die("No stream assigned.");
}

/* Convert array to string for SQL */
$stream_list = "'" . implode("','", $streams) . "'";

/* Fetch results */
$query = mysqli_query($conn,"
SELECT students.id AS sid,
       students.first_name,
       students.second_name,
       students.last_name,
       students.sex,
       students.stream,
       results.total,
       results.average,
       results.grade
FROM students
LEFT JOIN results ON students.id = results.student_id
WHERE students.stream IN ($stream_list)
ORDER BY students.stream ASC, students.first_name ASC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Stream Results</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-4">

<div class="card shadow mb-4">
<div class="card-body text-center">

<img src="../assets/logo.png" width="80" class="mb-2">

<h5 class="fw-bold">
KITUKUTU SECONDARY TECHNICAL SCHOOL
</h5>

<p class="text-muted">
Where Skills Become Careers
</p>

<hr>
<h6>Students Results (Your Streams)</h6>

</div>
</div>

<div class="table-responsive">

<table class="table table-bordered table-striped text-center align-middle">

<tr class="table-dark">
<th>Name</th>
<th>Sex</th>
<th>Stream</th>
<th>Science</th>
<th>Mathematics</th>
<th>English</th>
<th>Social Science</th>
<th>Total</th>
<th>Average</th>
<th>Grade</th>
</tr>

<?php

function getMark($conn,$student_id,$subject){
    $q = mysqli_query($conn,"
        SELECT marks FROM marks 
        WHERE student_id='$student_id' 
        AND subject='$subject'
    ");
    $data = mysqli_fetch_assoc($q);
    return $data ? $data['marks'] : 0;
}

while($row = mysqli_fetch_assoc($query)){

    $sid = $row['sid'];

    $science = getMark($conn,$sid,'Science');
    $math = getMark($conn,$sid,'Mathematics');
    $english = getMark($conn,$sid,'English');
    $social = getMark($conn,$sid,'Social Science');

    $sex = ($row['sex'] == "Male") ? "M" : "F";
?>

<tr>
<td><?php echo $row['first_name']." ".$row['second_name']." ".$row['last_name']; ?></td>
<td><?php echo $sex; ?></td>
<td><?php echo $row['stream']; ?></td>
<td><?php echo $science; ?></td>
<td><?php echo $math; ?></td>
<td><?php echo $english; ?></td>
<td><?php echo $social; ?></td>
<td><?php echo $row['total'] ? $row['total'] : "-"; ?></td>
<td><?php echo $row['average'] ? number_format($row['average'],2) : "-"; ?></td>
<td><?php echo $row['grade'] ? $row['grade'] : "-"; ?></td>
</tr>

<?php } ?>

</table>

</div>

<a href="dashboard.php" class="btn btn-secondary mt-3">
Back
</a>

</div>

</body>
</html>
