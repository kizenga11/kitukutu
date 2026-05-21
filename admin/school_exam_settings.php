<?php
if(isset($_POST['save_school_info'])){

$close = $_POST['closing_date'];
$open = $_POST['opening_date'];
$msg = mysqli_real_escape_string($conn,$_POST['school_message']);
$stream = $_POST['stream'];

mysqli_query($conn,"
UPDATE exams SET
closing_date='$close',
opening_date='$open',
school_message='$msg',
message_stream='$stream'
WHERE id=$exam_id
");

echo "Taarifa zimehifadhiwa";
}
?>

<form method="POST">
Closing Date:
<input type="date" name="closing_date"><br><br>

Opening Date:
<input type="date" name="opening_date"><br><br>

Message Stream:
<select name="stream">
<option value="">All</option>
<option>General</option>
<option>Vocational</option>
</select><br><br>

School Message:
<textarea name="school_message" style="width:100%;height:120px"></textarea>

<button name="save_school_info">SAVE</button>
</form>