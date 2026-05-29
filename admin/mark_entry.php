<?php
session_start();
include '../includes/config.php';

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

// SAVE MARK
if(isset($_POST['save_mark'])){

    $student_id = (int) $_POST['student_id'];
    $subject_id = (int) $_POST['subject_id'];
    $exam_id = (int) $_POST['exam_id'];
    $marks = (int) $_POST['marks'];

    // INSERT OR UPDATE
    $check = mysqli_query($conn,"
        SELECT * FROM marks 
        WHERE student_id=$student_id 
        AND subject_id=$subject_id 
        AND exam_id=$exam_id
    ");

    if(mysqli_num_rows($check) > 0){

        // Get student's form level
        $fl_r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT form_level FROM students WHERE id=$student_id"));
        $fl = $fl_r ? $fl_r['form_level'] : 'Form One';
        mysqli_query($conn,"
            UPDATE marks 
            SET marks=$marks, form_level='$fl'
            WHERE student_id=$student_id 
            AND subject_id=$subject_id 
            AND exam_id=$exam_id
        ");

    } else {

        $fl_r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT form_level FROM students WHERE id=$student_id"));
        $fl = $fl_r ? $fl_r['form_level'] : 'Form One';
        mysqli_query($conn,"
            INSERT INTO marks(student_id,subject_id,exam_id,form_level,marks)
            VALUES($student_id,$subject_id,$exam_id,'$fl',$marks)
        ");
    }

    $success = "Marks saved successfully.";
}
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mark Entry</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container mt-4">

<h4>Mark Entry</h4>

<?php if(isset($success)){ ?>
<div class="alert alert-success alert-dismissible fade show" style="border-radius:10px;padding:0.5rem 2rem 0.5rem 0.8rem;font-size:0.85rem;"><?= $success ?>
<button type="button" class="btn-close" data-bs-dismiss="alert" style="padding:0.6rem;font-size:0.7rem;"></button>
</div>
<?php } ?>

<form method="POST">

<div class="row mb-3">

<div class="col-md-4">
<label>Exam</label>
<select name="exam_id" class="form-control" required>
<option value="">Select Exam</option>
<?php
$exam = mysqli_query($conn,"SELECT * FROM exams ORDER BY id DESC");
while($e=mysqli_fetch_assoc($exam)){
echo "<option value='{$e['id']}'>{$e['exam_name']}</option>";
}
?>
</select>
</div>

<div class="col-md-4">
<label>Student</label>
<select name="student_id" class="form-control" required>
<option value="">Select Student</option>
<?php
$stu = mysqli_query($conn,"SELECT * FROM students WHERE is_active=1 ORDER BY first_name ASC");
while($s=mysqli_fetch_assoc($stu)){
echo "<option value='{$s['id']}'>{$s['first_name']} {$s['last_name']}</option>";
}
?>
</select>
</div>

<div class="col-md-4">
<label>Subject</label>
<select name="subject_id" class="form-control" required>
<option value="">Select Subject</option>
<?php
$sub = mysqli_query($conn,"SELECT * FROM subjects ORDER BY subject_name ASC");
while($sb=mysqli_fetch_assoc($sub)){
echo "<option value='{$sb['id']}'>{$sb['subject_name']}</option>";
}
?>
</select>
</div>

</div>

<div class="mb-3">
<label>Marks</label>
<input type="number" name="marks" min="0" max="100" class="form-control" required>
</div>

<button type="submit" name="save_mark" class="btn btn-primary" data-loading-text="Saving...">
Save Marks
</button>

</form>

</div>
<script>
document.querySelectorAll('.alert-dismissible').forEach(function(a){setTimeout(function(){a.classList.remove('show');a.style.display='none';},5000);});
</script>
<script src="../assets/js/forms.js"></script>
<script src="../assets/js/loader.js"></script>
</body>
</html>
