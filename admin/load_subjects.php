<?php
include "../includes/config.php";

if(isset($_GET['teacher_id'])){

$teacher_id = intval($_GET['teacher_id']);
$selected_id = isset($_GET['selected']) ? intval($_GET['selected']) : 0;

$query = mysqli_query($conn,"
SELECT 
s.id,
s.subject_name,
s.stream,
ta.form_level
FROM teacher_assignments ta
JOIN subjects s ON ta.subject_id = s.id
WHERE ta.teacher_id='$teacher_id'
ORDER BY s.subject_name, ta.form_level
");

if(mysqli_num_rows($query) > 0){

echo "<option value=''>Select Subject</option>";

while($row = mysqli_fetch_assoc($query)){

$subject = $row['subject_name'];
$stream = $row['stream'];
$fl = str_replace('Form ','F. ',$row['form_level']??'F.1');
$sel = $row['id'] == $selected_id ? ' selected' : '';

echo "<option value='{$row['id']}'$sel>
$subject ($fl - $stream)
</option>";

}

}else{

echo "<option value=''>No subjects assigned</option>";

}

}
?>