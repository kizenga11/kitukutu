<?php
error_reporting(E_ALL);
ini_set('display_errors',1);

session_start();
include '../includes/config.php';

if(!isset($_SESSION['admin_id'])){
header("Location: ../login.php");
exit();
}

/* Ensure is_published column exists */
$chk_col = mysqli_query($conn,"SHOW COLUMNS FROM exams LIKE 'is_published'");
if(mysqli_num_rows($chk_col)==0){
    mysqli_query($conn,"ALTER TABLE exams ADD COLUMN is_published TINYINT(1) DEFAULT 0");
}

/* ===== CRUD LOGIC ===== */

/* ADD EXAM */
if(isset($_POST['add_exam'])){
$exam_name=mysqli_real_escape_string($conn,$_POST['exam_name']);
$category_id=$_POST['category_id'];
$academic_year_id=$_POST['academic_year_id'];
$start_date=$_POST['start_date'];
$end_date=$_POST['end_date'];

mysqli_query($conn,"INSERT INTO exams (exam_name,category_id,academic_year_id,start_date,end_date)
VALUES ('$exam_name','$category_id','$academic_year_id','$start_date','$end_date')");

$exam_id=mysqli_insert_id($conn);

if(isset($_POST['subjects'])){
foreach($_POST['subjects'] as $subject){
mysqli_query($conn,"INSERT INTO exam_subjects (exam_id,subject_id)
VALUES ('$exam_id','$subject')");
}
}

$_SESSION['success']="Exam created successfully";
header("Location: exam_management.php");
exit();
}

/* UPDATE EXAM */
if(isset($_POST['update_exam'])){
$exam_id=$_POST['exam_id'];
$exam_name=mysqli_real_escape_string($conn,$_POST['exam_name']);
$category_id=$_POST['category_id'];
$academic_year_id=$_POST['academic_year_id'];
$start_date=$_POST['start_date'];
$end_date=$_POST['end_date'];

mysqli_query($conn,"UPDATE exams SET
exam_name='$exam_name', category_id='$category_id',
academic_year_id='$academic_year_id', start_date='$start_date', end_date='$end_date'
WHERE id='$exam_id'");

mysqli_query($conn,"DELETE FROM exam_subjects WHERE exam_id='$exam_id'");
if(isset($_POST['subjects'])){
foreach($_POST['subjects'] as $subject){
mysqli_query($conn,"INSERT INTO exam_subjects (exam_id,subject_id) VALUES ('$exam_id','$subject')");
}
}

$_SESSION['success']="Exam updated successfully";
header("Location: exam_management.php");
exit();
}

/* ADD TEST */
if(isset($_POST['add_test'])){
mysqli_query($conn,"INSERT INTO teacher_weekly_tests
(test_name,teacher_id,subject_id,class_name,class_stream,date)
VALUES
('{$_POST['test_name']}','{$_POST['teacher_id']}','{$_POST['subject_id']}','{$_POST['class_name']}','{$_POST['class_stream']}','{$_POST['date']}')");

$_SESSION['success']="Weekly test created successfully";
header("Location: exam_management.php");
exit();
}

/* ACTIVATE */
if(isset($_GET['activate'])){
$exam_id=$_GET['activate'];
mysqli_query($conn,"UPDATE exams SET is_active=0");
mysqli_query($conn,"UPDATE exams SET is_active=1 WHERE id='$exam_id'");
$_SESSION['success']="Exam activated successfully";
header("Location: exam_management.php");
exit();
}

/* DELETE */
if(isset($_POST['delete_exam'])){
$exam_id=$_POST['delete_exam'];
mysqli_query($conn,"DELETE FROM exam_subjects WHERE exam_id='$exam_id'");
mysqli_query($conn,"DELETE FROM exams WHERE id='$exam_id'");
$_SESSION['success']="Exam deleted";
header("Location: exam_management.php");
exit();
}

/* PUBLISH / UNPUBLISH */
if(isset($_GET['publish'])){
$eid = intval($_GET['publish']);
$val = intval($_GET['val'] ?? 1);
mysqli_query($conn,"UPDATE exams SET is_published=$val WHERE id='$eid'");
$_SESSION['success'] = $val ? "Exam published to public portal" : "Exam unpublished";
header("Location: exam_management.php"); exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Exam Management</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
body{background:#fff;font-family:system-ui;padding:16px;}
.content-card{border:1px solid #e5e7eb;border-radius:10px;padding:14px;margin-bottom:14px;background:#fff;}
.table-custom{width:100%;border-collapse:collapse;}
.table-custom th{background:#111827;color:white;font-size:0.75rem;padding:6px 8px;text-align:left;}
.table-custom td{padding:6px 8px;font-size:0.85rem;border-bottom:1px solid #f3f4f6;}
.btn-create{border-radius:10px !important;padding:6px 14px;font-size:0.85rem;}
@media(max-width:768px){
.table-custom thead{display:none;}
.table-custom tr{display:block;margin-bottom:10px;padding:10px;border-radius:10px;border:1px solid #e5e7eb;}
.table-custom td{display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px dashed #ddd;}
.table-custom td::before{content:attr(data-label);font-weight:bold;font-size:0.75rem;}
.table-custom td:last-child{border-bottom:none;}
}
</style>
</head>
<body>

<div class="container-fluid p-0">

<?php if(isset($_SESSION['success'])){ ?>
<div class="alert alert-success alert-dismissible fade show" style="border-radius:10px;padding:0.5rem 2rem 0.5rem 0.8rem;font-size:0.85rem;margin-bottom:10px;">
<?= $_SESSION['success']; unset($_SESSION['success']); ?>
<button type="button" class="btn-close" data-bs-dismiss="alert" style="padding:0.6rem;font-size:0.7rem;"></button>
</div>
<?php } ?>

<!-- ACTIONS -->
<div class="d-flex gap-2 flex-wrap mb-2">
<button class="btn btn-primary btn-create" data-bs-toggle="modal" data-bs-target="#examModal">+ Exam</button>
<button class="btn btn-warning btn-create" data-bs-toggle="modal" data-bs-target="#testModal">+ Weekly Test</button>
</div>

<!-- EXAMS TABLE -->
<div class="content-card">
<table class="table-custom">
<thead>
<tr>
<th>Exam</th>
<th>Category</th>
<th>Year</th>
<th>Status</th>
<th></th>
</tr>
</thead>
<tbody>
<?php
$q=mysqli_query($conn,"SELECT e.*,c.category_name,a.year_name
FROM exams e
JOIN exam_categories c ON c.id=e.category_id
JOIN academic_years a ON a.id=e.academic_year_id
ORDER BY e.id DESC");
while($row=mysqli_fetch_assoc($q)){
?>
<tr>
<td data-label="Exam"><?= $row['exam_name'] ?></td>
<td data-label="Category"><?= $row['category_name'] ?></td>
<td data-label="Year"><?= $row['year_name'] ?></td>
<td data-label="Status">
<?php if($row['is_active']){ ?>
<span style="background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:10px;font-size:0.75rem;">Active</span>
<?php } else { ?>
<span style="background:#f3f4f6;color:#6b7280;padding:2px 8px;border-radius:10px;font-size:0.75rem;">Inactive</span>
<?php } ?>
<?php if(!empty($row['is_published'])){ ?>
<span style="background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:10px;font-size:0.75rem;margin-left:3px;">Published</span>
<?php } ?>
</td>
<td data-label="">
<div class="d-flex gap-1 flex-wrap">
<a href="#" class="btn btn-sm btn-outline-success" style="border-radius:10px;font-size:0.75rem;padding:2px 8px;" onclick="event.preventDefault();showActivate(<?= $row['id'] ?>);">Activate</a>
<?php if(empty($row['is_published'])): ?>
<a href="#" class="btn btn-sm btn-outline-info" style="border-radius:10px;font-size:0.75rem;padding:2px 8px;" onclick="event.preventDefault();showPublish(<?= $row['id'] ?>,1);" title="Publish to public portal"><i class="bi bi-globe2"></i> Publish</a>
<?php else: ?>
<a href="#" class="btn btn-sm btn-info" style="border-radius:10px;font-size:0.75rem;padding:2px 8px;color:#fff;" onclick="event.preventDefault();showPublish(<?= $row['id'] ?>,0);" title="Remove from public portal"><i class="bi bi-globe2"></i> Unpublish</a>
<?php endif; ?>
<a href="#" class="btn btn-sm btn-outline-primary" style="border-radius:10px;font-size:0.75rem;padding:2px 8px;" data-bs-toggle="modal" data-bs-target="#editExamModal<?= $row['id'] ?>">Edit</a>
<a href="#" class="btn btn-sm btn-outline-danger" style="border-radius:10px;font-size:0.75rem;padding:2px 8px;" onclick="event.preventDefault();showDelete(<?= $row['id'] ?>);">Delete</a>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editExamModal<?= $row['id'] ?>" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content" style="border-radius:12px;">
<form method="POST">
<div class="modal-header border-0 pb-0">
<h5 class="modal-title" style="font-size:1rem;">Edit Exam</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<input type="hidden" name="exam_id" value="<?= $row['id'] ?>">
<input type="text" name="exam_name" class="form-control form-control-sm mb-2" value="<?= $row['exam_name'] ?>" required style="border-radius:10px;">
<select name="category_id" class="form-select form-select-sm mb-2" required style="border-radius:10px;">
<?php
$cats=mysqli_query($conn,"SELECT * FROM exam_categories");
while($c=mysqli_fetch_assoc($cats)){
$sel=$c['id']==$row['category_id']?'selected':'';
echo "<option value='{$c['id']}' $sel>{$c['category_name']}</option>";
}
?>
</select>
<select name="academic_year_id" class="form-select form-select-sm mb-2" required style="border-radius:10px;">
<?php
$ys=mysqli_query($conn,"SELECT * FROM academic_years");
while($y=mysqli_fetch_assoc($ys)){
$sel=$y['id']==$row['academic_year_id']?'selected':'';
echo "<option value='{$y['id']}' $sel>{$y['year_name']}</option>";
}
?>
</select>
<input type="date" name="start_date" class="form-control form-control-sm mb-2" value="<?= $row['start_date'] ?>" style="border-radius:10px;">
<input type="date" name="end_date" class="form-control form-control-sm mb-2" value="<?= $row['end_date'] ?>" style="border-radius:10px;">
<label class="form-label small fw-semibold mt-1">Subjects</label>
<div style="max-height:150px;overflow-y:auto;">
<?php
$subs=mysqli_query($conn,"SELECT * FROM subjects ORDER BY stream,subject_name");
$selected_subs=[];
$sel_q=mysqli_query($conn,"SELECT subject_id FROM exam_subjects WHERE exam_id='{$row['id']}'");
while($s=mysqli_fetch_assoc($sel_q)){$selected_subs[]=$s['subject_id'];}
while($sub=mysqli_fetch_assoc($subs)){
$chk=in_array($sub['id'],$selected_subs)?'checked':'';
echo "<div class='form-check'><input class='form-check-input' type='checkbox' name='subjects[]' value='{$sub['id']}' $chk><label class='form-check-label' style='font-size:0.85rem;'>{$sub['subject_name']} ({$sub['stream']})</label></div>";
}
?>
</div>
</div>
<div class="modal-footer border-0 pt-0">
<button type="submit" name="update_exam" class="btn btn-primary btn-sm" style="border-radius:10px;">Update</button>
</div>
</form>
</div>
</div>
</div>
</td>
</tr>
<?php } ?>
</tbody>
</table>
</div>

</div>

<!-- ADD EXAM MODAL -->
<div class="modal fade" id="examModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content" style="border-radius:12px;">
<form method="POST">
<div class="modal-header border-0 pb-0">
<h5 class="modal-title" style="font-size:1rem;">Create Exam</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<input type="text" name="exam_name" class="form-control form-control-sm mb-2" placeholder="Exam name" required style="border-radius:10px;">
<select name="category_id" class="form-select form-select-sm mb-2" required style="border-radius:10px;">
<option value="">Category</option>
<?php
$cats=mysqli_query($conn,"SELECT * FROM exam_categories");
while($c=mysqli_fetch_assoc($cats)){
echo "<option value='{$c['id']}'>{$c['category_name']}</option>";
}
?>
</select>
<select name="academic_year_id" class="form-select form-select-sm mb-2" required style="border-radius:10px;">
<option value="">Academic Year</option>
<?php
$ys=mysqli_query($conn,"SELECT * FROM academic_years");
while($y=mysqli_fetch_assoc($ys)){
echo "<option value='{$y['id']}'>{$y['year_name']}</option>";
}
?>
</select>
<input type="date" name="start_date" class="form-control form-control-sm mb-2" style="border-radius:10px;">
<input type="date" name="end_date" class="form-control form-control-sm mb-2" style="border-radius:10px;">
<label class="form-label small fw-semibold mt-1">Subjects</label>
<div style="max-height:150px;overflow-y:auto;">
<?php
$subs=mysqli_query($conn,"SELECT * FROM subjects ORDER BY stream,subject_name");
while($sub=mysqli_fetch_assoc($subs)){
echo "<div class='form-check'><input class='form-check-input' type='checkbox' name='subjects[]' value='{$sub['id']}'><label class='form-check-label' style='font-size:0.85rem;'>{$sub['subject_name']} ({$sub['stream']})</label></div>";
}
?>
</div>
</div>
<div class="modal-footer border-0 pt-0">
<button type="submit" name="add_exam" class="btn btn-primary btn-sm" style="border-radius:10px;">Create</button>
</div>
</form>
</div>
</div>
</div>

<!-- ADD TEST MODAL -->
<div class="modal fade" id="testModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content" style="border-radius:12px;">
<form method="POST">
<div class="modal-header border-0 pb-0">
<h5 class="modal-title" style="font-size:1rem;">Create Weekly Test</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<input type="text" name="test_name" class="form-control form-control-sm mb-2" placeholder="Test name" required style="border-radius:10px;">
<select name="teacher_id" class="form-select form-select-sm mb-2" required style="border-radius:10px;">
<option value="">Teacher</option>
<?php
$ts=mysqli_query($conn,"SELECT * FROM teachers ORDER BY first_name");
while($t=mysqli_fetch_assoc($ts)){
echo "<option value='{$t['id']}'>{$t['first_name']} {$t['last_name']}</option>";
}
?>
</select>
<select name="subject_id" class="form-select form-select-sm mb-2" required style="border-radius:10px;">
<option value="">Subject</option>
<?php
$subs=mysqli_query($conn,"SELECT * FROM subjects ORDER BY subject_name");
while($sub=mysqli_fetch_assoc($subs)){
echo "<option value='{$sub['id']}'>{$sub['subject_name']}</option>";
}
?>
</select>
<select name="class_name" class="form-select form-select-sm mb-2" required style="border-radius:10px;">
<option value="">Class</option>
<option>Form 1</option><option>Form 2</option><option>Form 3</option><option>Form 4</option>
</select>
<select name="class_stream" class="form-select form-select-sm mb-2" required style="border-radius:10px;">
<option value="">Stream</option>
<option>A</option><option>B</option><option>C</option>
</select>
<input type="date" name="date" class="form-control form-control-sm mb-2" required style="border-radius:10px;">
</div>
<div class="modal-footer border-0 pt-0">
<button type="submit" name="add_test" class="btn btn-primary btn-sm" style="border-radius:10px;">Create</button>
</div>
</form>
</div>
</div>
</div>

<!-- DELETE CONFIRM MODAL -->
<div class="modal fade" id="deleteModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered modal-sm">
<div class="modal-content" style="border-radius:12px;">
<div class="modal-body text-center py-4">
<div class="mb-3 text-danger"><i class="bi bi-exclamation-triangle" style="font-size:2rem;"></i></div>
<h6 style="font-size:0.95rem;">Delete this exam?</h6>
<p class="text-muted small mb-0">This action cannot be undone.</p>
</div>
<div class="modal-footer border-0 justify-content-center pt-0">
<form method="POST" id="deleteForm">
<input type="hidden" name="delete_exam" id="deleteId" value="">
<button type="button" class="btn btn-secondary btn-sm" style="border-radius:10px;" data-bs-dismiss="modal">Cancel</button>
<button type="submit" class="btn btn-danger btn-sm" style="border-radius:10px;">Delete</button>
</form>
</div>
</div>
</div>
</div>

<!-- ACTIVATE CONFIRM MODAL -->
<div class="modal fade" id="activateModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered modal-sm">
<div class="modal-content" style="border-radius:12px;">
<div class="modal-body text-center py-4">
<div class="mb-3 text-warning"><i class="bi bi-check-circle" style="font-size:2rem;"></i></div>
<h6 style="font-size:0.95rem;">Activate this exam?</h6>
<p class="text-muted small mb-0">Only one exam can be active at a time.</p>
</div>
<div class="modal-footer border-0 justify-content-center pt-0">
<form method="GET" id="activateForm">
<input type="hidden" name="activate" id="activateId" value="">
<button type="button" class="btn btn-secondary btn-sm" style="border-radius:10px;" data-bs-dismiss="modal">Cancel</button>
<button type="submit" class="btn btn-success btn-sm" style="border-radius:10px;">Activate</button>
</form>
</div>
</div>
</div>
</div>

<!-- PUBLISH CONFIRM MODAL -->
<div class="modal fade" id="publishModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered modal-sm">
<div class="modal-content" style="border-radius:12px;">
<div class="modal-body text-center py-4">
<div class="mb-3 text-info" id="publishIcon"><i class="bi bi-globe2" style="font-size:2rem;"></i></div>
<h6 style="font-size:0.95rem;" id="publishTitle">Publish this exam?</h6>
<p class="text-muted small mb-0" id="publishDesc">Results will be visible on the public portal.</p>
</div>
<div class="modal-footer border-0 justify-content-center pt-0">
<button type="button" class="btn btn-secondary btn-sm" style="border-radius:10px;" data-bs-dismiss="modal">Cancel</button>
<a id="publishConfirmBtn" href="#" class="btn btn-info btn-sm text-white" style="border-radius:10px;">Confirm</a>
</div>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.alert-dismissible').forEach(function(a){setTimeout(function(){a.classList.remove('show');a.style.display='none';},5000);});
function showDelete(id){
document.getElementById('deleteId').value=id;
new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
function showActivate(id){
document.getElementById('activateId').value=id;
new bootstrap.Modal(document.getElementById('activateModal')).show();
}
function showPublish(id, val){
var btn = document.getElementById('publishConfirmBtn');
btn.href = 'exam_management.php?publish='+id+'&val='+val;
var title = document.getElementById('publishTitle');
var desc  = document.getElementById('publishDesc');
if(val==1){
  title.textContent = 'Publish this exam?';
  desc.textContent  = 'Results will be visible on the public portal.';
  btn.className = 'btn btn-info btn-sm text-white';
} else {
  title.textContent = 'Unpublish this exam?';
  desc.textContent  = 'Results will be hidden from the public portal.';
  btn.className = 'btn btn-secondary btn-sm';
}
new bootstrap.Modal(document.getElementById('publishModal')).show();
}
</script>
</body>
</html>
