<?php
session_start();
include "../includes/config.php";

if (!isset($_SESSION['admin_id']) || (mysqli_fetch_assoc(mysqli_query($conn,"SELECT role FROM admins WHERE id='{$_SESSION['admin_id']}'"))['role'] ?? '') !== 'admin') {
    header("Location: ../login.php"); exit();
}

$msg = $err = '';

/* ── DELETE ── */
if (isset($_GET['del_teacher'])) {
    $id = intval($_GET['del_teacher']);
    mysqli_query($conn,"DELETE FROM teacher_assignments WHERE teacher_id='$id'");
    mysqli_query($conn,"DELETE FROM teachers WHERE id='$id'");
    header("Location: manage_staff.php?ok=teacher_deleted"); exit();
}
if (isset($_GET['del_admin'])) {
    $id = intval($_GET['del_admin']);
    if ($id == $_SESSION['admin_id']) { $err = "You cannot delete your own account."; }
    else { mysqli_query($conn,"DELETE FROM admins WHERE id='$id' AND role!='admin'"); header("Location: manage_staff.php?ok=admin_deleted"); exit(); }
}

/* ── REGISTER ── */
if (isset($_POST['register'])) {
    $role  = $_POST['role'] ?? '';
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['confirm_password'] ?? '';

    if (!in_array($role, ['teacher','headmaster','academic'])) {
        $err = "Please select a valid role.";
    } elseif ($pass !== $pass2) {
        $err = "Passwords do not match.";
    } elseif (strlen($pass) < 6) {
        $err = "Password must be at least 6 characters.";
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        if ($role === 'teacher') {
            $first  = mysqli_real_escape_string($conn, trim($_POST['first_name']));
            $second = mysqli_real_escape_string($conn, trim($_POST['second_name'] ?? ''));
            $last   = mysqli_real_escape_string($conn, trim($_POST['last_name']));
            $sex    = $_POST['sex'];
            $phone  = mysqli_real_escape_string($conn, trim($_POST['phone']));

            if (!preg_match('/^255[0-9]{9}$/', $phone)) {
                $err = "Phone number must be in format 255XXXXXXXXX";
            } elseif (mysqli_num_rows(mysqli_query($conn,"SELECT id FROM teachers WHERE email='$email'")) > 0) {
                $err = "This email is already registered.";
            } elseif (mysqli_num_rows(mysqli_query($conn,"SELECT id FROM teachers WHERE phone='$phone'")) > 0) {
                $err = "This phone number is already registered.";
            } else {
                $ins = mysqli_query($conn,"INSERT INTO teachers (first_name,second_name,last_name,sex,email,phone,password) VALUES ('$first','$second','$last','$sex','$email','$phone','$hash')");
                if ($ins) {
                    $tid = mysqli_insert_id($conn);
                    if (!empty($_POST['assignments'])) {
                        foreach ($_POST['assignments'] as $sid) {
                            $sid = intval($sid);
                            $sub = mysqli_fetch_assoc(mysqli_query($conn,"SELECT stream FROM subjects WHERE id='$sid'"));
                            $stream = $sub['stream']; $cs = $stream === 'GENERAL' ? 'B' : 'A';
                            mysqli_query($conn,"INSERT IGNORE INTO teacher_assignments (teacher_id,subject_id,stream,class_stream) VALUES ('$tid','$sid','$stream','$cs')");
                        }
                    }
                    $msg = "Teacher registered successfully.";
                } else { $err = "Error: ".mysqli_error($conn); }
            }

        } else {
            $name = mysqli_real_escape_string($conn, trim($_POST['name']));
            if (empty($name)) { $err = "Full name is required."; }
            elseif (mysqli_num_rows(mysqli_query($conn,"SELECT id FROM admins WHERE email='$email'")) > 0) { $err = "This email is already registered."; }
            else {
                $ins = mysqli_query($conn,"INSERT INTO admins (name,email,password,role) VALUES ('$name','$email','$hash','$role')");
                $msg = $ins ? ucfirst($role)." registered successfully." : "Error: ".mysqli_error($conn);
            }
        }
    }
}

if (isset($_GET['ok'])) {
    $ok_map = ['teacher_deleted' => 'Teacher deleted.', 'admin_deleted' => 'User deleted.'];
    $msg = $ok_map[$_GET['ok']] ?? '';
}

/* ── DATA – arrays to avoid mysqli_data_seek ── */
$teachers_arr = [];
$tq = mysqli_query($conn,"SELECT id,first_name,second_name,last_name,email,phone,sex FROM teachers ORDER BY first_name");
if ($tq) while ($r = mysqli_fetch_assoc($tq)) $teachers_arr[] = $r;

$admins_arr = [];
$aq = mysqli_query($conn,"SELECT id, COALESCE(name,'') AS name, email, role FROM admins WHERE role IN ('headmaster','academic') ORDER BY role, email");
if (!$aq) $aq = mysqli_query($conn,"SELECT id, '' AS name, email, role FROM admins WHERE role IN ('headmaster','academic') ORDER BY role, email");
if ($aq) while ($r = mysqli_fetch_assoc($aq)) $admins_arr[] = $r;

$subjects_arr = [];
$sq = mysqli_query($conn,"SELECT id,subject_name,stream FROM subjects ORDER BY stream,subject_name");
if ($sq) while ($r = mysqli_fetch_assoc($sq)) $subjects_arr[] = $r;

$counts = [
    'teacher'    => count($teachers_arr),
    'headmaster' => count(array_filter($admins_arr, fn($a) => $a['role']==='headmaster')),
    'academic'   => count(array_filter($admins_arr, fn($a) => $a['role']==='academic')),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Staff · Kitukutu</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{background:#f4f7fc;font-family:system-ui,-apple-system,'Segoe UI',sans-serif;padding:16px;color:#111827;}

.stat-row{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px;}
.stat-box{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px 12px;text-align:center;border-top:3px solid var(--c);}
.stat-box .n{font-size:24px;font-weight:800;color:#111827;}
.stat-box .l{font-size:11px;color:#6b7280;margin-top:2px;}

.card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:16px;}
.card-hdr{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid #e5e7eb;background:#f9fafb;}
.card-hdr .title{font-size:13px;font-weight:700;color:#111827;display:flex;align-items:center;gap:6px;}
.card-body{padding:16px;}

.btn-add{background:#059669;color:#fff;border:none;border-radius:8px;padding:6px 12px;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px;}
.btn-add:hover{background:#047857;}

.f-label{font-size:11px;font-weight:600;color:#374151;margin-bottom:3px;display:block;}
.f-input{width:100%;padding:7px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;color:#111827;background:#fff;}
.f-input:focus{outline:none;border-color:#6366f1;box-shadow:0 0 0 2px rgba(99,102,241,.15);}
.f-select{appearance:none;}
.f-grid{display:grid;gap:10px;}
.f-grid.cols2{grid-template-columns:1fr 1fr;}
.f-grid.cols3{grid-template-columns:1fr 1fr 1fr;}
.btn-submit{background:#1d4ed8;color:#fff;border:none;border-radius:8px;padding:9px 18px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;}
.btn-submit:hover{background:#1e40af;}

.role-tabs{display:flex;gap:4px;margin-bottom:14px;background:#f3f4f6;padding:4px;border-radius:10px;}
.role-tab{flex:1;padding:7px;border:none;background:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;color:#6b7280;transition:all .15s;}
.role-tab.active{background:#fff;color:#111827;box-shadow:0 1px 4px rgba(0,0,0,.1);}

.rbadge{display:inline-block;padding:2px 8px;border-radius:6px;font-size:10px;font-weight:700;}
.rb-teacher{background:#dbeafe;color:#1e40af;}
.rb-headmaster{background:#d1fae5;color:#065f46;}
.rb-academic{background:#ede9fe;color:#5b21b6;}

.user-table{width:100%;border-collapse:collapse;}
.user-table th{background:#f9fafb;border-bottom:1px solid #e5e7eb;padding:8px 10px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.3px;text-align:left;}
.user-table td{padding:9px 10px;border-bottom:1px solid #f3f4f6;font-size:13px;vertical-align:middle;}
.user-table tr:last-child td{border-bottom:none;}
.user-table tr:hover td{background:#f9fafb;}
.uname{font-weight:600;color:#111827;}
.uemail{font-size:11px;color:#6b7280;}
.btn-del{background:none;border:1px solid #fca5a5;color:#dc2626;border-radius:6px;padding:3px 8px;font-size:11px;font-weight:600;cursor:pointer;}
.btn-del:hover{background:#fee2e2;}

.subj-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:6px;max-height:200px;overflow-y:auto;padding:2px;}
.subj-item{display:flex;align-items:center;gap:6px;padding:5px 8px;border:1px solid #e5e7eb;border-radius:6px;cursor:pointer;font-size:12px;}
.subj-item:hover{border-color:#6366f1;background:#f0f0ff;}
.subj-item input{width:14px;height:14px;cursor:pointer;}
.subj-stream{font-size:10px;font-weight:700;padding:1px 5px;border-radius:4px;margin-left:auto;flex-shrink:0;}
.s-gen{background:#dbeafe;color:#1e40af;}
.s-voc{background:#fce7f3;color:#9d174d;}

.alert{border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.alert-ok{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;}
.alert-err{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;}

.reg-panel{display:none;}
.reg-panel.open{display:block;}

.empty-row td{text-align:center;color:#9ca3af;padding:24px!important;font-size:13px;}

.search-box{padding:6px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;width:200px;}
.search-box:focus{outline:none;border-color:#6366f1;}

@media(max-width:600px){
  .f-grid.cols3{grid-template-columns:1fr 1fr;}
  .f-grid.cols2{grid-template-columns:1fr;}
  .subj-grid{grid-template-columns:repeat(2,1fr);}
  .user-table thead{display:none;}
  .user-table td{display:flex;justify-content:space-between;align-items:center;padding:6px 10px;}
  .user-table td::before{content:attr(data-label);font-size:10px;font-weight:700;color:#9ca3af;flex-shrink:0;margin-right:8px;}
}
</style>
</head>
<body>

<?php if($msg): ?>
<div class="alert alert-ok"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>
<?php if($err): ?>
<div class="alert alert-err"><i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($err) ?></div>
<?php endif; ?>

<!-- Stats -->
<div class="stat-row">
  <div class="stat-box" style="--c:#3b82f6"><div class="n"><?=$counts['teacher']?></div><div class="l">Teachers</div></div>
  <div class="stat-box" style="--c:#10b981"><div class="n"><?=$counts['headmaster']?></div><div class="l">Headmaster</div></div>
  <div class="stat-box" style="--c:#8b5cf6"><div class="n"><?=$counts['academic']?></div><div class="l">Academic Officer</div></div>
</div>

<!-- Register Card -->
<div class="card">
  <div class="card-hdr">
    <span class="title"><i class="bi bi-person-plus-fill"></i> Register New User</span>
    <button class="btn-add" onclick="togglePanel()"><i class="bi bi-plus-lg"></i><span id="toggleLabel">Open Form</span></button>
  </div>
  <div class="card-body">
    <div class="reg-panel" id="regPanel">

      <div class="role-tabs">
        <button class="role-tab" id="tab-teacher"    onclick="switchRole('teacher')"><i class="bi bi-person-badge"></i> Teacher</button>
        <button class="role-tab" id="tab-headmaster" onclick="switchRole('headmaster')"><i class="bi bi-mortarboard-fill"></i> Headmaster</button>
        <button class="role-tab" id="tab-academic"   onclick="switchRole('academic')"><i class="bi bi-book-fill"></i> Academic Officer</button>
      </div>

      <form method="POST" id="regForm">
        <input type="hidden" name="register" value="1">
        <input type="hidden" name="role" id="roleInput" value="teacher">

        <!-- TEACHER fields -->
        <div id="fields-teacher">
          <div class="f-grid cols3" style="margin-bottom:10px;">
            <div><label class="f-label">First Name *</label><input name="first_name" class="f-input" placeholder="First name"></div>
            <div><label class="f-label">Middle Name</label><input name="second_name" class="f-input" placeholder="Middle name"></div>
            <div><label class="f-label">Last Name *</label><input name="last_name" class="f-input" placeholder="Last name"></div>
          </div>
          <div class="f-grid cols3" style="margin-bottom:10px;">
            <div>
              <label class="f-label">Gender *</label>
              <select name="sex" class="f-input f-select">
                <option value="">Select</option>
                <option value="M">Male</option>
                <option value="F">Female</option>
              </select>
            </div>
            <div><label class="f-label">Email *</label><input name="email" type="email" class="f-input" placeholder="teacher@school.tz"></div>
            <div><label class="f-label">Phone (255XXXXXXXXX) *</label><input name="phone" class="f-input" placeholder="255712345678"></div>
          </div>
          <div class="f-grid cols2" style="margin-bottom:14px;">
            <div>
              <label class="f-label">Password *</label>
              <div style="position:relative;">
                <input name="password" type="password" id="pw1" class="f-input" placeholder="Min. 6 characters" style="padding-right:36px;">
                <button type="button" onclick="togglePw('pw1',this)" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;color:#9ca3af;cursor:pointer;font-size:15px;"><i class="bi bi-eye"></i></button>
              </div>
            </div>
            <div>
              <label class="f-label">Confirm Password *</label>
              <div style="position:relative;">
                <input name="confirm_password" type="password" id="pw2" class="f-input" placeholder="Repeat password" style="padding-right:36px;">
                <button type="button" onclick="togglePw('pw2',this)" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;color:#9ca3af;cursor:pointer;font-size:15px;"><i class="bi bi-eye"></i></button>
              </div>
            </div>
          </div>

          <div style="margin-bottom:14px;">
            <label class="f-label" style="margin-bottom:6px;">Teaching Assignments</label>
            <div class="subj-grid">
              <?php foreach($subjects_arr as $s):
                $sc = $s['stream']==='GENERAL' ? 's-gen' : 's-voc';
                $sl = $s['stream']==='GENERAL' ? 'General' : 'Voc';
              ?>
              <label class="subj-item">
                <input type="checkbox" name="assignments[]" value="<?=$s['id']?>">
                <span><?=htmlspecialchars($s['subject_name'])?></span>
                <span class="subj-stream <?=$sc?>"><?=$sl?></span>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- HEADMASTER / ACADEMIC fields -->
        <div id="fields-admin" style="display:none;">
          <div class="f-grid cols2" style="margin-bottom:10px;">
            <div><label class="f-label">Full Name *</label><input name="name" class="f-input" placeholder="Full name"></div>
            <div><label class="f-label">Email *</label><input name="email" type="email" class="f-input" placeholder="user@school.tz"></div>
          </div>
          <div class="f-grid cols2" style="margin-bottom:14px;">
            <div>
              <label class="f-label">Password *</label>
              <div style="position:relative;">
                <input name="password" type="password" id="pw3" class="f-input" placeholder="Min. 6 characters" style="padding-right:36px;">
                <button type="button" onclick="togglePw('pw3',this)" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;color:#9ca3af;cursor:pointer;font-size:15px;"><i class="bi bi-eye"></i></button>
              </div>
            </div>
            <div>
              <label class="f-label">Confirm Password *</label>
              <div style="position:relative;">
                <input name="confirm_password" type="password" id="pw4" class="f-input" placeholder="Repeat password" style="padding-right:36px;">
                <button type="button" onclick="togglePw('pw4',this)" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;color:#9ca3af;cursor:pointer;font-size:15px;"><i class="bi bi-eye"></i></button>
              </div>
            </div>
          </div>
        </div>

        <button type="submit" class="btn-submit"><i class="bi bi-person-check-fill"></i> Register</button>
      </form>
    </div>
  </div>
</div>

<!-- Staff List -->
<div class="card">
  <div class="card-hdr">
    <span class="title"><i class="bi bi-people-fill"></i> All Staff</span>
    <input type="text" class="search-box" id="searchBox" placeholder="Search name / email..." onkeyup="applyFilters()">
  </div>
  <div class="card-body" style="padding:0;overflow-x:auto;">

    <div style="display:flex;gap:6px;padding:12px 14px;border-bottom:1px solid #f3f4f6;">
      <button class="role-tab active" id="ftab-all"        onclick="filterRole('all')"        style="flex:none;padding:5px 14px;">All</button>
      <button class="role-tab"        id="ftab-teacher"    onclick="filterRole('teacher')"    style="flex:none;padding:5px 14px;">Teachers</button>
      <button class="role-tab"        id="ftab-headmaster" onclick="filterRole('headmaster')" style="flex:none;padding:5px 14px;">Headmaster</button>
      <button class="role-tab"        id="ftab-academic"   onclick="filterRole('academic')"   style="flex:none;padding:5px 14px;">Academic</button>
    </div>

    <table class="user-table" id="userTable">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Role</th>
          <th>Subjects</th>
          <th style="width:100px;"></th>
        </tr>
      </thead>
      <tbody>

        <!-- TEACHERS -->
        <?php if(empty($teachers_arr)): ?>
        <tr class="empty-row"><td colspan="5">No teachers registered yet.</td></tr>
        <?php else: foreach($teachers_arr as $t):
          $tname = htmlspecialchars(trim($t['first_name'].' '.$t['second_name'].' '.$t['last_name']));
          $asgn = [];
          $ar = mysqli_query($conn,"SELECT s.subject_name FROM teacher_assignments ta JOIN subjects s ON s.id=ta.subject_id WHERE ta.teacher_id='{$t['id']}'");
          if ($ar) while($row=mysqli_fetch_assoc($ar)) $asgn[] = $row['subject_name'];
        ?>
        <tr data-role="teacher" data-search="<?= strtolower($tname.' '.$t['email']) ?>">
          <td data-label="Name"><div class="uname"><?=$tname?></div></td>
          <td data-label="Email"><span class="uemail"><?=htmlspecialchars($t['email'])?></span></td>
          <td data-label="Role"><span class="rbadge rb-teacher">Teacher</span></td>
          <td data-label="Subjects"><span class="uemail"><?= count($asgn)>0 ? htmlspecialchars(implode(', ',$asgn)) : '—' ?></span></td>
          <td data-label="">
            <a href="edit_teacher.php?id=<?=$t['id']?>" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:6px;padding:3px 8px;font-size:11px;font-weight:600;text-decoration:none;margin-right:4px;" target="mainFrame">Edit</a>
            <button class="btn-del" onclick="confirmDel('teacher',<?=$t['id']?>,'<?=addslashes($tname)?>')">Delete</button>
          </td>
        </tr>
        <?php endforeach; endif; ?>

        <!-- HEADMASTER / ACADEMIC -->
        <?php foreach($admins_arr as $a):
          $rclass  = $a['role']==='headmaster' ? 'rb-headmaster' : 'rb-academic';
          $rlabel  = $a['role']==='headmaster' ? 'Headmaster'    : 'Academic Officer';
          $aname   = $a['name'] ?: $a['email'];
        ?>
        <tr data-role="<?=$a['role']?>" data-search="<?= strtolower($aname.' '.$a['email']) ?>">
          <td data-label="Name"><div class="uname"><?=htmlspecialchars($aname)?></div></td>
          <td data-label="Email"><span class="uemail"><?=htmlspecialchars($a['email'])?></span></td>
          <td data-label="Role"><span class="rbadge <?=$rclass?>"><?=$rlabel?></span></td>
          <td data-label="Subjects"><span class="uemail">—</span></td>
          <td data-label="">
            <button class="btn-del" onclick="confirmDel('admin',<?=$a['id']?>,'<?=addslashes(htmlspecialchars($aname))?>')">Delete</button>
          </td>
        </tr>
        <?php endforeach; ?>

      </tbody>
    </table>
  </div>
</div>

<script>
var panelOpen = false;
function togglePanel(){
  panelOpen = !panelOpen;
  document.getElementById('regPanel').classList.toggle('open', panelOpen);
  document.getElementById('toggleLabel').textContent = panelOpen ? 'Close Form' : 'Open Form';
  if(panelOpen) switchRole('teacher');
}

function switchRole(role){
  document.getElementById('roleInput').value = role;
  ['teacher','headmaster','academic'].forEach(function(r){
    document.getElementById('tab-'+r).classList.toggle('active', r===role);
  });
  document.getElementById('fields-teacher').style.display = role==='teacher' ? '' : 'none';
  document.getElementById('fields-admin').style.display   = role!=='teacher' ? '' : 'none';
}

document.addEventListener('DOMContentLoaded', function(){ switchRole('teacher'); });

function togglePw(id, btn){
  var inp = document.getElementById(id);
  var icon = btn.querySelector('i');
  if(inp.type==='password'){ inp.type='text'; icon.className='bi bi-eye-slash'; }
  else { inp.type='password'; icon.className='bi bi-eye'; }
}

function confirmDel(type, id, name){
  if(!confirm('Delete "'+name+'"? This cannot be undone.')) return;
  window.location.href = 'manage_staff.php?del_'+type+'='+id;
}

var activeRole = 'all';
function filterRole(role){
  activeRole = role;
  ['all','teacher','headmaster','academic'].forEach(function(r){
    document.getElementById('ftab-'+r).classList.toggle('active', r===role);
  });
  applyFilters();
}

function applyFilters(){
  var q = document.getElementById('searchBox').value.toLowerCase();
  document.querySelectorAll('#userTable tbody tr:not(.empty-row)').forEach(function(tr){
    var show = (activeRole==='all' || tr.dataset.role===activeRole) &&
               (!q || (tr.dataset.search||'').includes(q));
    tr.style.display = show ? '' : 'none';
  });
}

document.querySelectorAll('.alert').forEach(function(a){
  setTimeout(function(){ a.style.transition='opacity .5s'; a.style.opacity='0'; setTimeout(function(){a.style.display='none';},500); }, 4000);
});
</script>
</body>
</html>
