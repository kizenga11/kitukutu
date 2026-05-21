<?php
session_start();
include "db.php";

$teacher_id = $_SESSION['teacher_id'];

$current = $_GET['current'];
$compare = $_GET['compare'];

$sql = mysqli_query($conn,"
SELECT 
st.first_name,
st.second_name,
st.last_name,

m1.marks AS current_marks,
m2.marks AS compare_marks,

(m1.marks - m2.marks) AS diff

FROM marks m1

LEFT JOIN marks m2 
ON m2.student_id = m1.student_id
AND m2.subject_id = m1.subject_id
AND m2.exam_id = '$compare'

JOIN students st ON st.id = m1.student_id

WHERE m1.exam_id = '$current'
AND m1.subject_id IN (
    SELECT subject_id FROM teacher_assignments 
    WHERE teacher_id='$teacher_id'
)

ORDER BY m1.marks DESC
");

echo "<table border='1' cellpadding='5'>";
echo "<tr>
<th>Student</th>
<th>Current</th>
<th>Previous</th>
<th>Diff</th>
</tr>";

while($r=mysqli_fetch_assoc($sql)){
echo "<tr>
<td>".$r['first_name']." ".$r['second_name']." ".$r['last_name']."</td>
<td>".$r['current_marks']."</td>
<td>".$r['compare_marks']."</td>
<td>".$r['diff']."</td>
</tr>";
}

echo "</table>";
?>