<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

$year_q = mysqli_query($conn,"SELECT id,year_name FROM academic_years WHERE is_active=1");
if(mysqli_num_rows($year_q)==0) die("No active academic year found!");
$year_data = mysqli_fetch_assoc($year_q);
$academic_year_id = $year_data['id'];
$academic_year_name = $year_data['year_name'];

/* ADD / EDIT PERIOD */
if(isset($_POST['save_period'])){
    $id = intval($_POST['id'] ?? 0);
    $teacher_id = intval($_POST['teacher_id']);
    $subject_id = intval($_POST['subject_id']);
    $day = mysqli_real_escape_string($conn,$_POST['day']);
    $class_name = mysqli_real_escape_string($conn,$_POST['class_name']);
    $start_time = mysqli_real_escape_string($conn,$_POST['start_time']);
    $end_time = mysqli_real_escape_string($conn,$_POST['end_time']);

    if(empty($teacher_id)||empty($subject_id)||empty($day)||empty($class_name)||empty($start_time)||empty($end_time)){
        $_SESSION['error']="Please fill all fields."; header("Location: manage_periods.php"); exit();
    }

    $get_stream = mysqli_query($conn,"SELECT class_stream FROM teacher_assignments WHERE teacher_id='$teacher_id' AND subject_id='$subject_id' LIMIT 1");
    if(mysqli_num_rows($get_stream)==0){
        $_SESSION['error']="Teacher is not assigned to this subject!"; header("Location: manage_periods.php"); exit();
    }
    $stream_data=mysqli_fetch_assoc($get_stream);
    $class_stream=$stream_data['class_stream'];

    // Clash check (exclude current period if editing)
    $exclude = $id ? "AND id!='$id'" : "";
    $class_clash=mysqli_query($conn,"SELECT id FROM periods WHERE day='$day' AND class_name='$class_name' AND class_stream='$class_stream' AND ('$start_time' < end_time AND '$end_time' > start_time) AND academic_year_id='$academic_year_id' $exclude");
    if(mysqli_num_rows($class_clash)>0){
        $_SESSION['error']="Time conflict! This class already has a period at that time."; header("Location: manage_periods.php"); exit();
    }
    $teacher_clash=mysqli_query($conn,"SELECT id FROM periods WHERE day='$day' AND teacher_id='$teacher_id' AND ('$start_time' < end_time AND '$end_time' > start_time) AND academic_year_id='$academic_year_id' $exclude");
    if(mysqli_num_rows($teacher_clash)>0){
        $_SESSION['error']="This teacher already has another period at that time."; header("Location: manage_periods.php"); exit();
    }

    if($id){
        $up = mysqli_query($conn,"UPDATE periods SET teacher_id='$teacher_id',subject_id='$subject_id',day='$day',class_name='$class_name',class_stream='$class_stream',start_time='$start_time',end_time='$end_time' WHERE id='$id'");
        $_SESSION[$up?'success':'error']=$up?"Period updated successfully!":"Error: ".mysqli_error($conn);
    } else {
        $ins = mysqli_query($conn,"INSERT INTO periods (teacher_id,subject_id,day,class_name,class_stream,start_time,end_time,academic_year_id) VALUES ('$teacher_id','$subject_id','$day','$class_name','$class_stream','$start_time','$end_time','$academic_year_id')");
        $_SESSION[$ins?'success':'error']=$ins?"Period added successfully!":"Error: ".mysqli_error($conn);
    }
    header("Location: manage_periods.php"); exit();
}

/* DELETE PERIOD */
if(isset($_GET['delete'])){
    $delete_id=intval($_GET['delete']);
    mysqli_query($conn,"DELETE FROM periods WHERE id='$delete_id'");
    $_SESSION['success']="Period deleted successfully!";
    header("Location: manage_periods.php"); exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Periods</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
    *{margin:0;padding:0;box-sizing:border-box;}
    body{font-family:system-ui,-apple-system,'Segoe UI',Arial,sans-serif;background:#e8ecf1;padding:12px;font-size:13px;color:#1a1a2e;}
    .container{max-width:1200px;margin:auto;}
    .card{background:#fff;border-radius:8px;padding:10px 12px;margin-bottom:10px;box-shadow:0 1px 3px rgba(0,0,0,0.06);}
    .hdr{background:linear-gradient(135deg,#1a1a2e,#16213e);border-radius:8px;padding:10px 14px;margin-bottom:10px;color:#fff;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;}
    .hdr h3{margin:0;font-size:14px;font-weight:700;}
    .hdr p{margin:0;font-size:11px;opacity:.8;}
    .btn{display:inline-flex;align-items:center;gap:4px;padding:5px 10px;border:none;border-radius:5px;font-size:11px;cursor:pointer;text-decoration:none;transition:all .15s;color:#fff;}
    .btn-pr{background:#3498db;}.btn-pr:hover{background:#2980b9;}
    .btn-dr{background:#e74c3c;}.btn-dr:hover{background:#c0392b;}
    .btn-gr{background:#27ae60;}.btn-gr:hover{background:#219a52;}
    .btn-wm{background:#f39c12;}.btn-wm:hover{background:#d68910;}
    .btn-sm{padding:3px 8px;font-size:10px;}
    .btn-gap{gap:3px;display:inline-flex;}
    .badge-year{background:rgba(255,255,255,0.15);color:#fff;padding:2px 10px;border-radius:20px;font-size:10px;margin-left:6px;}
    .form-control,.form-select{border:1px solid #dde4ec;border-radius:5px;padding:5px 8px;font-size:12px;background:#fff;}
    .form-control:focus,.form-select:focus{border-color:#1a1a2e;box-shadow:0 0 0 2px rgba(26,26,46,0.08);}
    .alert-box{padding:7px 10px;border-radius:5px;font-size:12px;margin-bottom:8px;display:flex;align-items:center;gap:8px;}
    .alert-box.error{background:#fde8e8;color:#c0392b;border:1px solid #f5c6cb;}
    .alert-box.success{background:#e8f8e8;color:#27ae60;border:1px solid #c3e6cb;}
    .alert-box .close{background:none;border:none;color:inherit;cursor:pointer;font-size:14px;margin-left:auto;padding:0 4px;}
    table{width:100%;border-collapse:collapse;font-size:12px;}
    th,td{padding:6px 5px;border:1px solid #ddd;text-align:center;vertical-align:middle;}
    th{background:#1a1a2e;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;}
    tr:nth-child(even){background:#f8f9fa;}
    .name-l{text-align:left;}
    .empty{text-align:center;padding:20px;color:#95a5a6;font-size:13px;}
    .modal-content{border-radius:8px;}
    .modal-header{background:#1a1a2e;color:#fff;padding:10px 14px;}
    .modal-header .btn-close{filter:invert(1);}
    .modal-body{padding:12px;}
    .modal-footer{padding:8px 12px;}
    @media(max-width:768px){
        body{padding:8px;}
        .hdr{flex-direction:column;gap:6px;align-items:stretch;}
        table,thead,tbody,th,td,tr{display:block;}
        thead{display:none;}
        tr{margin-bottom:8px;border:1px solid #ddd;border-radius:5px;background:#fff;padding:4px 0;}
        td{display:flex;justify-content:space-between;align-items:center;padding:5px 8px;border:none;border-bottom:1px solid #eee;text-align:right;}
        td:last-child{border-bottom:none;}
        td::before{content:attr(data-label);font-weight:600;font-size:10px;color:#7f8c8d;min-width:80px;text-align:left;}
    }
    @media print{body{background:#fff;padding:0;}.hdr{background:#000!important;}.btn{display:none!important;}th{background:#000!important;}}
    </style>
</head>
<body>
<div class="container">

<div class="hdr">
    <div>
        <h3>Kitukutu Secondary Technical School</h3>
        <p>Manage Periods <span class="badge-year"><?= $academic_year_name ?></span></p>
    </div>
</div>

<div class="card">
    <?php if(isset($_SESSION['error'])): ?>
    <div class="alert-box error"><?= $_SESSION['error'] ?><button class="close" onclick="this.parentElement.remove()">&times;</button></div>
    <?php unset($_SESSION['error']); endif; ?>
    <?php if(isset($_SESSION['success'])): ?>
    <div class="alert-box success"><?= $_SESSION['success'] ?><button class="close" onclick="this.parentElement.remove()">&times;</button></div>
    <?php unset($_SESSION['success']); endif; ?>

    <form method="POST" class="row g-1">
        <input type="hidden" name="id" value="0">
        <div class="col-md-2">
            <select name="teacher_id" id="teacher_select" class="form-select" required>
                <option value="">Teacher</option>
                <?php $t=mysqli_query($conn,"SELECT * FROM teachers ORDER BY first_name"); while($teacher=mysqli_fetch_assoc($t)){ echo "<option value='{$teacher['id']}'>".$teacher['first_name']." ".$teacher['last_name']."</option>"; } ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="subject_id" id="subject_select" class="form-select" required>
                <option value="">Subject</option>
            </select>
        </div>
        <div class="col-md-1">
            <select name="day" class="form-select" required>
                <option value="">Day</option>
                <option>Monday</option><option>Tuesday</option><option>Wednesday</option><option>Thursday</option><option>Friday</option>
            </select>
        </div>
        <div class="col-md-1">
            <select name="class_name" class="form-select" required>
                <option value="">Class</option>
                <option value="I">Form I</option><option value="II">Form II</option><option value="III">Form III</option><option value="IV">Form IV</option>
            </select>
        </div>
        <div class="col-md-1">
            <input type="time" name="start_time" class="form-control" required>
        </div>
        <div class="col-md-1">
            <input type="time" name="end_time" class="form-control" required>
        </div>
        <div class="col-md-1">
            <button type="submit" name="save_period" class="btn btn-pr" style="width:100%;justify-content:center;"><i class="bi bi-plus"></i> Add</button>
        </div>
    </form>
</div>

<div class="card">
    <div style="font-size:11px;font-weight:600;color:#555;text-transform:uppercase;letter-spacing:0.3px;margin-bottom:6px;">All Periods</div>
    <div style="overflow-x:auto;">
    <table>
        <thead><tr><th>Teacher</th><th>Subject</th><th>Day</th><th>Class</th><th>Time</th><th>Action</th></tr></thead>
        <tbody>
        <?php
        $list = mysqli_query($conn,"SELECT periods.*, teachers.first_name, teachers.last_name, subjects.subject_name FROM periods JOIN teachers ON periods.teacher_id=teachers.id JOIN subjects ON periods.subject_id=subjects.id ORDER BY day, start_time");
        if(mysqli_num_rows($list)>0){
            while($row=mysqli_fetch_assoc($list)){
                $teacher=$row['first_name']." ".$row['last_name'];
                $class=$row['class_name']." ".$row['class_stream'];
                $time=$row['start_time']." - ".$row['end_time'];
                echo "<tr>
                    <td data-label='Teacher' class='name-l'>$teacher</td>
                    <td data-label='Subject'>{$row['subject_name']}</td>
                    <td data-label='Day'>{$row['day']}</td>
                    <td data-label='Class'>$class</td>
                    <td data-label='Time'>$time</td>
                    <td data-label='Action' class='btn-gap' style='justify-content:center;'>
                        <button class='btn btn-wm btn-sm' onclick='editPeriod(this)'
                            data-id='{$row['id']}'
                            data-teacher='{$row['teacher_id']}'
                            data-subject='{$row['subject_id']}'
                            data-day='{$row['day']}'
                            data-class='{$row['class_name']}'
                            data-start='{$row['start_time']}'
                            data-end='{$row['end_time']}'><i class='bi bi-pencil'></i></button>
                        <button class='btn btn-dr btn-sm' onclick='confirmDelete({$row['id']})'><i class='bi bi-trash'></i></button>
                    </td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='6' class='empty'>No periods found</td></tr>";
        }
        ?>
        </tbody>
    </table>
    </div>
</div>

</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1">
<div class="modal-dialog">
<form method="POST" class="modal-content">
<input type="hidden" name="id" id="edit_id" value="0">
<div class="modal-header"><h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Period</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="row g-2">
        <div class="col-md-6">
            <label style="font-size:11px;font-weight:600;color:#555;">Teacher</label>
            <select name="teacher_id" id="edit_teacher" class="form-select" required>
                <option value="">Select Teacher</option>
                <?php mysqli_data_seek($t,0); while($teacher=mysqli_fetch_assoc($t)){ echo "<option value='{$teacher['id']}'>".$teacher['first_name']." ".$teacher['last_name']."</option>"; } ?>
            </select>
        </div>
        <div class="col-md-6">
            <label style="font-size:11px;font-weight:600;color:#555;">Subject</label>
            <select name="subject_id" id="edit_subject" class="form-select" required>
                <option value="">Select Subject</option>
            </select>
        </div>
        <div class="col-md-4">
            <label style="font-size:11px;font-weight:600;color:#555;">Day</label>
            <select name="day" id="edit_day" class="form-select" required>
                <option>Monday</option><option>Tuesday</option><option>Wednesday</option><option>Thursday</option><option>Friday</option>
            </select>
        </div>
        <div class="col-md-4">
            <label style="font-size:11px;font-weight:600;color:#555;">Class</label>
            <select name="class_name" id="edit_class" class="form-select" required>
                <option value="I">Form I</option><option value="II">Form II</option><option value="III">Form III</option><option value="IV">Form IV</option>
            </select>
        </div>
        <div class="col-md-2">
            <label style="font-size:11px;font-weight:600;color:#555;">Start</label>
            <input type="time" name="start_time" id="edit_start" class="form-control" required>
        </div>
        <div class="col-md-2">
            <label style="font-size:11px;font-weight:600;color:#555;">End</label>
            <input type="time" name="end_time" id="edit_end" class="form-control" required>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-sm" style="background:#95a5a6;color:#fff;" data-bs-dismiss="modal">Cancel</button>
    <button type="submit" name="save_period" class="btn btn-pr btn-sm"><i class="bi bi-check"></i> Save</button>
</div>
</form>
</div>
</div>

<!-- DELETE MODAL -->
<div class="modal fade" id="deleteModal" tabindex="-1">
<div class="modal-dialog modal-sm">
<div class="modal-content">
<div class="modal-header"><h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Delete Period</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body" style="font-size:13px;">
    Are you sure you want to delete this period?
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-sm" style="background:#95a5a6;color:#fff;" data-bs-dismiss="modal">Cancel</button>
    <a href="#" id="deleteConfirm" class="btn btn-dr btn-sm"><i class="bi bi-trash"></i> Delete</a>
</div>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Dynamic subject loading for add form
$('#teacher_select').change(function(){ $('#subject_select').load('load_subjects.php?teacher_id='+$(this).val()); });

// Edit modal
function editPeriod(btn){
    $('#edit_id').val(btn.dataset.id);
    $('#edit_teacher').val(btn.dataset.teacher);
    $('#edit_subject').load('load_subjects.php?teacher_id='+btn.dataset.teacher+'&selected='+btn.dataset.subject);
    $('#edit_day').val(btn.dataset.day);
    $('#edit_class').val(btn.dataset.class);
    $('#edit_start').val(btn.dataset.start.substring(0,5));
    $('#edit_end').val(btn.dataset.end.substring(0,5));
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// Delete modal
function confirmDelete(id){
    document.getElementById('deleteConfirm').href='?delete='+id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Auto-hide alerts
document.addEventListener("DOMContentLoaded",function(){
    var a = document.querySelector(".alert-box");
    if(a) setTimeout(function(){ a.style.transition="opacity .5s"; a.style.opacity="0"; setTimeout(function(){if(a) a.remove()},500); },5000);
});
</script>

</body>
</html>