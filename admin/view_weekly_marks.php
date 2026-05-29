<?php
session_start();
include "../includes/config.php";

$test_id=$_GET['test_id'];

$query=mysqli_query($conn,"
SELECT m.marks,
st.first_name,
st.second_name,
st.last_name,
s.subject_name
FROM marks m
JOIN students st ON st.id=m.student_id AND st.is_active=1
JOIN subjects s ON s.id=m.subject_id
WHERE m.exam_id='$test_id'
");

?>

<!DOCTYPE html>
<html>
<head>

<title>Weekly Test Marks</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container mt-4">

<h4>Weekly Test Results</h4>

<a href="exam_management.php" class="btn btn-secondary mb-3">
Back
</a>

<table class="table table-bordered">

<tr>
<th>#</th>
<th>Student</th>
<th>Marks</th>
</tr>

<?php
$i = 1;
if (mysqli_num_rows($query) === 0): ?>
<tr>
  <td colspan="3" class="text-center py-5 text-muted">
    <i class="bi bi-inbox" style="font-size:2.2rem;display:block;margin-bottom:8px;opacity:.25;"></i>
    No marks found for this test.
  </td>
</tr>
<?php else: while($row = mysqli_fetch_assoc($query)): ?>

<tr>
<td><?= $i++ ?></td>
<td><?= $row['first_name']." ".$row['second_name']." ".$row['last_name'] ?></td>
<td><?= $row['marks'] ?></td>
</tr>

<?php endwhile; endif; ?>

</table>

</div>

</body>
</html>