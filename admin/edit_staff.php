<?php
session_start();
include "../includes/config.php";

if (!isset($_SESSION['admin_id']) || (mysqli_fetch_assoc(mysqli_query($conn,"SELECT role FROM admins WHERE id='{$_SESSION['admin_id']}'"))['role'] ?? '') !== 'admin') {
    header("Location: ../login.php"); exit();
}

$type = $_GET['type'] ?? 'teacher';
$id   = intval($_GET['id'] ?? 0);
$error = $msg = '';

if ($type === 'admin') {
    $row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM admins WHERE id='$id' AND role IN ('headmaster','academic')"));
    if (!$row) { $error = "User not found."; }
} else {
    $row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM teachers WHERE id='$id'"));
    if (!$row) { $error = "Teacher not found."; }
}

// UPDATE
if (isset($_POST['update']) && !$error) {
    $first  = mysqli_real_escape_string($conn, trim($_POST['first_name']));
    $second = mysqli_real_escape_string($conn, trim($_POST['second_name'] ?? ''));
    $last   = mysqli_real_escape_string($conn, trim($_POST['last_name']));
    $sex    = $_POST['sex'] ?? 'M';
    $email  = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone  = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $role   = $_POST['role'] ?? '';

    if (!preg_match('/^255[0-9]{9}$/', $phone)) {
        $error = "Phone number must be in format 255XXXXXXXXX";
    } else {
        if ($type === 'teacher') {
            $check = mysqli_query($conn,"SELECT id FROM teachers WHERE email='$email' AND id!='$id'");
            if (mysqli_num_rows($check) > 0) {
                $error = "This email is already registered.";
            } else {
                $check_phone = mysqli_query($conn,"SELECT id FROM teachers WHERE phone='$phone' AND id!='$id'");
                if (mysqli_num_rows($check_phone) > 0) {
                    $error = "This phone number is already registered.";
                } else {
                    mysqli_query($conn,"UPDATE teachers SET first_name='$first', second_name='$second', last_name='$last', sex='$sex', email='$email', phone='$phone' WHERE id='$id'");
                    saveAssignmentsEdit($conn, $id);
                    $msg = "Teacher updated successfully.";
                }
            }
        } else {
            $check = mysqli_query($conn,"SELECT id FROM admins WHERE email='$email' AND id!='$id'");
            if (mysqli_num_rows($check) > 0) {
                $error = "This email is already registered.";
            } elseif (!in_array($role, ['headmaster','academic'])) {
                $error = "Invalid role selected.";
            } else {
                mysqli_query($conn,"UPDATE admins SET first_name='$first', second_name='$second', last_name='$last', sex='$sex', phone='$phone', email='$email', role='$role' WHERE id='$id'");
                saveAssignmentsEdit($conn, $id);
                $msg = "Staff updated successfully.";
            }
        }
    }
}

function saveAssignmentsEdit($conn, $uid) {
    mysqli_query($conn,"DELETE FROM teacher_assignments WHERE teacher_id='$uid'");
    if (!empty($_POST['assignments'])) {
        foreach ($_POST['assignments'] as $sid) {
            $sid = intval($sid);
            $sub = mysqli_fetch_assoc(mysqli_query($conn,"SELECT stream FROM subjects WHERE id='$sid'"));
            $stream = $sub['stream']; $cs = $stream === 'GENERAL' ? 'B' : 'A';
            mysqli_query($conn,"INSERT IGNORE INTO teacher_assignments (teacher_id,subject_id,stream,class_stream) VALUES ('$uid','$sid','$stream','$cs')");
        }
    }
}

$subjects_list = [];
$sq = mysqli_query($conn,"SELECT id,subject_name,stream FROM subjects ORDER BY stream,subject_name");
if ($sq) while ($r = mysqli_fetch_assoc($sq)) $subjects_list[] = $r;

$current_assignments = [];
if ($row) {
    $ar = mysqli_query($conn,"SELECT subject_id FROM teacher_assignments WHERE teacher_id='{$row['id']}'");
    if ($ar) while ($r = mysqli_fetch_assoc($ar)) $current_assignments[] = $r['subject_id'];
}

$back_link = 'manage_staff.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Edit Staff · Kitukutu</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{background:#fff;font-family:system-ui;padding:16px;color:#111827;}
.card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:16px;}
.card-hdr{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid #e5e7eb;background:#f9fafb;}
.card-hdr .title{font-size:13px;font-weight:700;color:#111827;display:flex;align-items:center;gap:6px;}
.card-body{padding:16px;}
.f-label{font-size:11px;font-weight:600;color:#374151;margin-bottom:3px;display:block;}
.f-input{width:100%;padding:7px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;color:#111827;background:#fff;}
.f-input:focus{outline:none;border-color:#6366f1;box-shadow:0 0 0 2px rgba(99,102,241,.15);}
.f-select{appearance:none;}
.f-grid{display:grid;gap:10px;}
.f-grid.cols2{grid-template-columns:1fr 1fr;}
.f-grid.cols3{grid-template-columns:1fr 1fr 1fr;}
.btn-save{background:#1d4ed8;color:#fff;border:none;border-radius:8px;padding:9px 18px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;}
.btn-save:hover{background:#1e40af;}
.btn-back{background:#fff;color:#374151;border:1px solid #d1d5db;border-radius:8px;padding:9px 18px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:6px;}
.btn-back:hover{background:#f3f4f6;}
.alert{border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.alert-ok{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;}
.alert-err{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;}
.subj-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:6px;max-height:200px;overflow-y:auto;padding:2px;}
.subj-item{display:flex;align-items:center;gap:6px;padding:5px 8px;border:1px solid #e5e7eb;border-radius:6px;cursor:pointer;font-size:12px;}
.subj-item:hover{border-color:#6366f1;background:#f0f0ff;}
.subj-item input{width:14px;height:14px;cursor:pointer;}
.subj-stream{font-size:10px;font-weight:700;padding:1px 5px;border-radius:4px;margin-left:auto;flex-shrink:0;}
.s-gen{background:#dbeafe;color:#1e40af;}
.s-voc{background:#fce7f3;color:#9d174d;}
.action-row{display:flex;gap:8px;margin-top:14px;}
@media(max-width:600px){
  .f-grid.cols3{grid-template-columns:1fr 1fr;}
  .f-grid.cols2{grid-template-columns:1fr;}
  .subj-grid{grid-template-columns:repeat(2,1fr);}
}
</style>
</head>
<body>

<?php if ($msg): ?>
<div class="alert alert-ok"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($msg) ?></div>
<script>setTimeout(function(){ window.location.href='<?= $back_link ?>'; },1500);</script>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-err"><i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($row && !$msg): ?>
<div class="card">
  <div class="card-hdr">
    <span class="title"><i class="bi bi-pencil-square"></i> Edit <?= $type === 'admin' ? 'Staff' : 'Teacher' ?></span>
  </div>
  <div class="card-body">
    <form method="POST">
      <?php if ($type === 'admin'): ?>
      <div class="f-grid cols2" style="margin-bottom:10px;">
        <div>
          <label class="f-label">Role</label>
          <select name="role" class="f-input f-select">
            <option value="headmaster" <?= $row['role']==='headmaster'?'selected':'' ?>>Headmaster</option>
            <option value="academic" <?= $row['role']==='academic'?'selected':'' ?>>Academic Officer</option>
          </select>
        </div>
        <div></div>
      </div>
      <?php endif; ?>

      <div class="f-grid cols3" style="margin-bottom:10px;">
        <div><label class="f-label">First Name *</label><input name="first_name" class="f-input" value="<?= htmlspecialchars($row['first_name']??'') ?>" placeholder="First name"></div>
        <div><label class="f-label">Middle Name</label><input name="second_name" class="f-input" value="<?= htmlspecialchars($row['second_name']??'') ?>" placeholder="Middle name"></div>
        <div><label class="f-label">Last Name *</label><input name="last_name" class="f-input" value="<?= htmlspecialchars($row['last_name']??'') ?>" placeholder="Last name"></div>
      </div>
      <div class="f-grid cols3" style="margin-bottom:10px;">
        <div>
          <label class="f-label">Gender *</label>
          <select name="sex" class="f-input f-select">
            <option value="M" <?= ($row['sex']??'')==='M'?'selected':'' ?>>Male</option>
            <option value="F" <?= ($row['sex']??'')==='F'?'selected':'' ?>>Female</option>
          </select>
        </div>
        <div><label class="f-label">Email *</label><input name="email" type="email" class="f-input" value="<?= htmlspecialchars($row['email']??'') ?>" placeholder="user@school.tz"></div>
        <div><label class="f-label">Phone (255XXXXXXXXX) *</label><input name="phone" class="f-input" value="<?= htmlspecialchars($row['phone']??'') ?>" placeholder="255712345678"></div>
      </div>

      <div style="margin-bottom:14px;">
        <label class="f-label" style="margin-bottom:6px;">Subject Assignments</label>
        <div class="subj-grid">
          <?php foreach($subjects_list as $s):
            $checked = in_array($s['id'], $current_assignments) ? 'checked' : '';
            $sc = $s['stream']==='GENERAL' ? 's-gen' : 's-voc';
            $sl = $s['stream']==='GENERAL' ? 'General' : 'Voc';
          ?>
          <label class="subj-item">
            <input type="checkbox" name="assignments[]" value="<?=$s['id']?>" <?=$checked?>>
            <span><?=htmlspecialchars($s['subject_name'])?></span>
            <span class="subj-stream <?=$sc?>"><?=$sl?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="action-row">
        <button type="submit" name="update" class="btn-save"><i class="bi bi-check-circle-fill"></i> Save Changes</button>
        <a href="<?= $back_link ?>" class="btn-back"><i class="bi bi-arrow-left"></i> Back</a>
      </div>
    </form>
  </div>
</div>
<?php elseif (!$row && !$msg): ?>
<div class="alert alert-err"><i class="bi bi-exclamation-triangle-fill"></i> Staff member not found. <a href="<?= $back_link ?>" style="color:#991b1b;font-weight:700;">Go back</a></div>
<?php endif; ?>

</body>
</html>
