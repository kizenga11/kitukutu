<?php
include __DIR__ . "/../includes/config.php";
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }

/* ── Edit mode ── */
$edit_mode = false;
$edit_data = [];
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $edit_data = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM students WHERE id='".intval($_GET['edit'])."'")) ?? [];
}

/* ── Register ── */
if (isset($_POST['register_manual'])) {
    $first  = mysqli_real_escape_string($conn, trim($_POST['first_name']));
    $second = mysqli_real_escape_string($conn, trim($_POST['second_name']));
    $last   = mysqli_real_escape_string($conn, trim($_POST['last_name']));
    $sex    = $_POST['sex'];
    $stream = $_POST['stream'];
    $phone  = mysqli_real_escape_string($conn, trim($_POST['parent_phone'] ?? ''));

    if (mysqli_query($conn,"INSERT INTO students(first_name,second_name,last_name,sex,stream,parent_phone) VALUES('$first','$second','$last','$sex','$stream','$phone')")) {
        $sid = mysqli_insert_id($conn);
        $comp = mysqli_query($conn,"SELECT id FROM subjects WHERE LOWER(stream)=LOWER('$stream') AND LOWER(category)='compulsory'");
        while ($s = mysqli_fetch_assoc($comp)) mysqli_query($conn,"INSERT INTO student_subjects(student_id,subject_id) VALUES('$sid','{$s['id']}')");
        if (isset($_POST['optional_subjects'])) {
            foreach ($_POST['optional_subjects'] as $oid) mysqli_query($conn,"INSERT INTO student_subjects(student_id,subject_id) VALUES('$sid','".intval($oid)."')");
        }
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Student registered successfully.'];
        header("Location: add_student.php"); exit();
    } else {
        $error = mysqli_error($conn);
    }
}

/* ── Update ── */
if (isset($_POST['update_student'])) {
    $id     = intval($_POST['student_id']);
    $first  = mysqli_real_escape_string($conn, trim($_POST['first_name']));
    $second = mysqli_real_escape_string($conn, trim($_POST['second_name']));
    $last   = mysqli_real_escape_string($conn, trim($_POST['last_name']));
    $sex    = $_POST['sex'];
    $stream = $_POST['stream'];
    $phone  = mysqli_real_escape_string($conn, trim($_POST['parent_phone']));

    mysqli_query($conn,"UPDATE students SET first_name='$first',second_name='$second',last_name='$last',sex='$sex',stream='$stream',parent_phone='$phone' WHERE id='$id'");
    mysqli_query($conn,"DELETE FROM student_subjects WHERE student_id='$id'");
    $comp = mysqli_query($conn,"SELECT id FROM subjects WHERE LOWER(stream)=LOWER('$stream') AND LOWER(category)='compulsory'");
    while ($s = mysqli_fetch_assoc($comp)) mysqli_query($conn,"INSERT INTO student_subjects(student_id,subject_id) VALUES('$id','{$s['id']}')");
    if (isset($_POST['optional_subjects'])) {
        foreach ($_POST['optional_subjects'] as $oid) mysqli_query($conn,"INSERT INTO student_subjects(student_id,subject_id) VALUES('$id','".intval($oid)."')");
    }
    $_SESSION['flash'] = ['type'=>'success','msg'=>'Student updated successfully.'];
    header("Location: add_student.php"); exit();
}

/* ── Delete ── */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn,"DELETE FROM student_subjects WHERE student_id='$id'");
    mysqli_query($conn,"DELETE FROM students WHERE id='$id'");
    $_SESSION['flash'] = ['type'=>'success','msg'=>'Student deleted.'];
    header("Location: add_student.php"); exit();
}

/* ── CSV Upload ── */
if (isset($_POST['upload_csv']) && !empty($_FILES['csv_file']['tmp_name'])) {
    $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
    $count = 0;
    while (($row = fgetcsv($file, 1000, ',')) !== false) {
        if (count($row) < 5) continue;
        $f = mysqli_real_escape_string($conn, trim($row[0]));
        $s = mysqli_real_escape_string($conn, trim($row[1]));
        $l = mysqli_real_escape_string($conn, trim($row[2]));
        $sx = trim($row[3]);
        $st = trim($row[4]);
        if ($f && $l) { mysqli_query($conn,"INSERT INTO students(first_name,second_name,last_name,sex,stream) VALUES('$f','$s','$l','$sx','$st')"); $count++; }
    }
    fclose($file);
    $_SESSION['flash'] = ['type'=>'success','msg'=>"CSV uploaded: $count students added."];
    header("Location: add_student.php"); exit();
}

/* ── Students list ── */
$students_q = mysqli_query($conn,"SELECT * FROM students ORDER BY first_name, last_name");
$students = [];
while ($r = mysqli_fetch_assoc($students_q)) $students[] = $r;
$total = count($students);

$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Students</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{
  --primary:#6366f1;--primary-dark:#4f46e5;--primary-light:#ede9fe;
  --success:#10b981;--danger:#ef4444;--warn:#f59e0b;
  --navy:#0f2744;
  --bg:#f3f4f6;--card:#fff;--border:#e5e7eb;--text:#111827;--muted:#6b7280;
  --radius:14px;
}
*{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--bg);font-family:system-ui,-apple-system,sans-serif;color:var(--text);padding:14px;}

/* Toast */
.toast-wrap{position:fixed;top:14px;left:50%;transform:translateX(-50%);z-index:9999;}
.toast-msg{background:var(--success);color:#fff;padding:9px 22px;border-radius:30px;font-size:13px;font-weight:600;box-shadow:0 4px 16px rgba(16,185,129,.3);white-space:nowrap;animation:fadeUp .3s ease;}
@keyframes fadeUp{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

/* Section header */
.sec-hdr{font-size:13px;font-weight:700;color:var(--text);margin-bottom:12px;display:flex;align-items:center;gap:7px;}
.sec-hdr i{font-size:16px;color:var(--primary);}

/* Panel */
.panel{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:16px;margin-bottom:14px;}

/* Form inputs */
.f-label{font-size:12px;font-weight:600;color:var(--muted);margin-bottom:4px;}
.f-input{
  width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:10px;
  font-size:13px;color:var(--text);background:#fafafa;outline:none;
  transition:border-color .15s,background .15s;
}
.f-input:focus{border-color:var(--primary);background:#fff;}
.f-group{margin-bottom:10px;}

/* Buttons */
.btn-primary-solid{
  width:100%;padding:11px;border:none;border-radius:10px;
  background:var(--primary-dark);color:#fff;font-size:14px;font-weight:700;
  cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;
  transition:background .15s;
}
.btn-primary-solid:hover{background:#4338ca;}
.btn-warn-solid{background:var(--warn);color:#fff;}
.btn-warn-solid:hover{background:#d97706;}
.btn-success-solid{background:var(--success);color:#fff;}
.btn-success-solid:hover{background:#059669;}

/* Search bar */
.search-wrap{position:relative;margin-bottom:12px;}
.search-wrap i{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:15px;}
.search-input{
  width:100%;padding:9px 12px 9px 34px;border:1.5px solid var(--border);border-radius:10px;
  font-size:13px;background:#fafafa;outline:none;color:var(--text);
  transition:border-color .15s;
}
.search-input:focus{border-color:var(--primary);background:#fff;}

/* Student rows */
.stu-row{
  display:flex;align-items:center;gap:10px;
  padding:9px 10px;border:1px solid var(--border);border-radius:10px;
  margin-bottom:6px;background:#fafafa;
  transition:border-color .15s;
}
.stu-row:last-child{margin-bottom:0;}
.stu-row:hover{border-color:#c4b5fd;background:#fff;}
.stu-avatar{
  width:34px;height:34px;border-radius:50%;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
  font-size:13px;font-weight:700;color:#fff;
}
.stu-name{flex:1;min-width:0;}
.stu-name .sn{font-size:13px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.stu-name .sm{font-size:11px;color:var(--muted);}
.stream-pill{font-size:10px;font-weight:700;padding:2px 9px;border-radius:20px;flex-shrink:0;}
.stream-gen{background:#dbeafe;color:#1e40af;}
.stream-voc{background:#fce7f3;color:#9d174d;}
.act-btn{
  width:30px;height:30px;border-radius:8px;border:1px solid var(--border);
  background:var(--card);display:flex;align-items:center;justify-content:center;
  font-size:14px;text-decoration:none;color:var(--text);flex-shrink:0;
  transition:background .12s,border-color .12s;
}
.act-btn:hover{background:var(--bg);}
.act-btn.del:hover{background:#fee2e2;border-color:#fca5a5;color:var(--danger);}
.act-btn.edt:hover{background:#fef3c7;border-color:#fcd34d;color:#92400e;}

/* CSV info */
.csv-hint{font-size:11px;color:var(--muted);margin-top:6px;padding:8px 10px;background:var(--bg);border-radius:8px;}

/* Empty state */
.empty{text-align:center;padding:30px 16px;color:var(--muted);}
.empty i{font-size:2.5rem;display:block;margin-bottom:8px;opacity:.3;}

/* Delete confirm modal */
.modal-inner{background:var(--card);border-radius:var(--radius);padding:24px;max-width:340px;width:calc(100% - 32px);margin:auto;}
.modal-backdrop-custom{
  position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1050;
  display:none;align-items:center;justify-content:center;
}
.modal-backdrop-custom.show{display:flex;}

/* Optional subjects */
.opt-subject-label{
  display:flex;align-items:center;gap:8px;
  padding:7px 10px;border:1px solid var(--border);border-radius:8px;
  margin-bottom:5px;cursor:pointer;font-size:13px;
  transition:border-color .12s,background .12s;
}
.opt-subject-label:hover{border-color:var(--primary);background:var(--primary-light);}
.opt-subject-label input{accent-color:var(--primary);}

@media(max-width:600px){
  body{padding:10px;}
  .panel{padding:12px;}
}
</style>
</head>
<body>

<!-- Toast -->
<?php if ($flash): ?>
<div class="toast-wrap" id="toast">
  <div class="toast-msg" style="background:<?= $flash['type']==='success' ? 'var(--success)' : 'var(--danger)' ?>">
    <?= $flash['type']==='success' ? '✓' : '✕' ?> <?= htmlspecialchars($flash['msg']) ?>
  </div>
</div>
<script>setTimeout(()=>{const t=document.getElementById('toast');if(t)t.style.display='none'},3000)</script>
<?php endif; ?>
<?php if (isset($error)): ?>
<div class="toast-wrap"><div class="toast-msg" style="background:var(--danger)">✕ <?= htmlspecialchars($error) ?></div></div>
<?php endif; ?>

<!-- Page title -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
  <div style="display:flex;align-items:center;gap:10px;">
    <div style="width:36px;height:36px;border-radius:10px;background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;flex-shrink:0">
      <i class="bi bi-people-fill"></i>
    </div>
    <div>
      <div style="font-weight:800;font-size:15px">Students</div>
      <div style="font-size:11px;color:var(--muted)"><?= $total ?> registered</div>
    </div>
  </div>
  <?php if ($edit_mode): ?>
  <a href="add_student.php" style="font-size:12px;font-weight:600;color:var(--primary);text-decoration:none;display:flex;align-items:center;gap:4px;">
    <i class="bi bi-x-circle"></i> Cancel edit
  </a>
  <?php endif; ?>
</div>

<div class="row g-3">

  <!-- Left: Registration form -->
  <div class="col-md-5">

    <!-- Registration / Edit -->
    <div class="panel">
      <div class="sec-hdr">
        <i class="bi bi-<?= $edit_mode ? 'pencil-square' : 'person-plus' ?>"></i>
        <?= $edit_mode ? 'Edit Student' : 'Register Student' ?>
      </div>
      <form method="POST">
        <?php if ($edit_mode): ?>
        <input type="hidden" name="student_id" value="<?= intval($edit_data['id']) ?>">
        <?php endif; ?>
        <div class="row g-2 mb-0">
          <div class="col-12">
            <div class="f-group">
              <div class="f-label">First Name <span style="color:var(--danger)">*</span></div>
              <input type="text" name="first_name" class="f-input" placeholder="First name" required value="<?= htmlspecialchars($edit_data['first_name'] ?? '') ?>">
            </div>
          </div>
          <div class="col-12">
            <div class="f-group">
              <div class="f-label">Second Name</div>
              <input type="text" name="second_name" class="f-input" placeholder="Second name" value="<?= htmlspecialchars($edit_data['second_name'] ?? '') ?>">
            </div>
          </div>
          <div class="col-12">
            <div class="f-group">
              <div class="f-label">Last Name <span style="color:var(--danger)">*</span></div>
              <input type="text" name="last_name" class="f-input" placeholder="Last name" required value="<?= htmlspecialchars($edit_data['last_name'] ?? '') ?>">
            </div>
          </div>
          <div class="col-6">
            <div class="f-group">
              <div class="f-label">Sex <span style="color:var(--danger)">*</span></div>
              <select name="sex" class="f-input" required>
                <option value="">Select...</option>
                <option value="Male"   <?= ($edit_data['sex']??'')==='Male'   ? 'selected':'' ?>>Male</option>
                <option value="Female" <?= ($edit_data['sex']??'')==='Female' ? 'selected':'' ?>>Female</option>
              </select>
            </div>
          </div>
          <div class="col-6">
            <div class="f-group">
              <div class="f-label">Stream <span style="color:var(--danger)">*</span></div>
              <select name="stream" id="streamSelect" class="f-input" required>
                <option value="">Select...</option>
                <option value="General"    <?= ($edit_data['stream']??'')==='General'    ? 'selected':'' ?>>General</option>
                <option value="Vocational" <?= ($edit_data['stream']??'')==='Vocational' ? 'selected':'' ?>>Vocational</option>
              </select>
            </div>
          </div>
          <div class="col-12">
            <div class="f-group">
              <div class="f-label">Parent Phone</div>
              <input type="text" name="parent_phone" class="f-input" placeholder="e.g. 0712 345 678" value="<?= htmlspecialchars($edit_data['parent_phone'] ?? '') ?>">
            </div>
          </div>
        </div>

        <!-- Optional subjects -->
        <div id="optionalSubjects"></div>

        <button type="submit" name="<?= $edit_mode ? 'update_student' : 'register_manual' ?>" class="btn-primary-solid <?= $edit_mode ? 'btn-warn-solid' : '' ?>" style="margin-top:4px;">
          <i class="bi bi-<?= $edit_mode ? 'save' : 'person-plus' ?>"></i>
          <?= $edit_mode ? 'Update Student' : 'Register Student' ?>
        </button>
      </form>
    </div>

    <!-- CSV Upload -->
    <div class="panel">
      <div class="sec-hdr"><i class="bi bi-upload"></i> CSV Upload</div>
      <form method="POST" enctype="multipart/form-data">
        <div class="f-group">
          <div class="f-label">CSV File</div>
          <input type="file" name="csv_file" class="f-input" accept=".csv" required style="padding:7px 12px;">
        </div>
        <div class="csv-hint">
          <i class="bi bi-info-circle me-1"></i>
          Column order: <b>FirstName, SecondName, LastName, Sex, Stream</b>
        </div>
        <button type="submit" name="upload_csv" class="btn-primary-solid btn-success-solid" style="margin-top:10px;">
          <i class="bi bi-cloud-upload"></i> Upload CSV
        </button>
      </form>
    </div>

  </div>

  <!-- Right: Student list -->
  <div class="col-md-7">
    <div class="panel" style="height:100%;">
      <div class="sec-hdr">
        <i class="bi bi-people-fill"></i> Registered Students
        <span style="background:var(--primary-light);color:var(--primary-dark);border-radius:20px;padding:1px 9px;font-size:11px;font-weight:700;margin-left:auto"><?= $total ?></span>
      </div>

      <!-- Search -->
      <div class="search-wrap">
        <i class="bi bi-search"></i>
        <input type="text" class="search-input" id="searchInput" placeholder="Search by name, sex or stream...">
      </div>

      <!-- List -->
      <div id="studentList" style="max-height:65vh;overflow-y:auto;padding-right:2px;">
        <?php if (empty($students)): ?>
        <div class="empty">
          <i class="bi bi-people"></i>
          <p>No students registered yet.</p>
        </div>
        <?php else: ?>
        <?php foreach ($students as $i => $row):
          $name = trim($row['first_name'].' '.($row['second_name'] ? $row['second_name'].' ' : '').$row['last_name']);
          $init = strtoupper(substr($row['first_name'],0,1).substr($row['last_name'],0,1));
          $is_voc = strtolower($row['stream']) === 'vocational';
          $av_bg  = $is_voc ? '#9d174d' : '#1e40af';
        ?>
        <div class="stu-row" data-search="<?= strtolower(htmlspecialchars($name.' '.$row['sex'].' '.$row['stream'])) ?>">
          <div class="stu-avatar" style="background:<?= $av_bg ?>"><?= $init ?></div>
          <div class="stu-name">
            <div class="sn"><?= htmlspecialchars($name) ?></div>
            <div class="sm"><?= htmlspecialchars($row['sex']) ?> &bull; <?= htmlspecialchars($row['parent_phone'] ?? '') ?></div>
          </div>
          <span class="stream-pill <?= $is_voc ? 'stream-voc' : 'stream-gen' ?>"><?= htmlspecialchars($row['stream']) ?></span>
          <a href="?edit=<?= $row['id'] ?>" class="act-btn edt" title="Edit"><i class="bi bi-pencil"></i></a>
          <button class="act-btn del" title="Delete" onclick="confirmDel(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($name)) ?>')"><i class="bi bi-trash"></i></button>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>

    </div>
  </div>

</div>

<!-- Delete confirm modal -->
<div class="modal-backdrop-custom" id="delModal">
  <div class="modal-inner">
    <div style="text-align:center;margin-bottom:16px;">
      <div style="width:52px;height:52px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:22px;color:var(--danger)">
        <i class="bi bi-trash3"></i>
      </div>
      <div style="font-weight:700;font-size:15px;margin-bottom:4px">Delete Student?</div>
      <div style="font-size:13px;color:var(--muted)" id="delName">This action cannot be undone.</div>
    </div>
    <div style="display:flex;gap:8px;">
      <button onclick="document.getElementById('delModal').classList.remove('show')" style="flex:1;padding:10px;border:1.5px solid var(--border);border-radius:10px;background:var(--card);font-size:13px;font-weight:600;cursor:pointer;">Cancel</button>
      <a href="#" id="delLink" style="flex:1;padding:10px;border:none;border-radius:10px;background:var(--danger);color:#fff;font-size:13px;font-weight:700;text-align:center;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:6px;">
        <i class="bi bi-trash"></i> Delete
      </a>
    </div>
  </div>
</div>

<script>
/* Search */
document.getElementById('searchInput').addEventListener('input', function(){
  const q = this.value.toLowerCase();
  document.querySelectorAll('.stu-row').forEach(row => {
    row.style.display = row.dataset.search.includes(q) ? '' : 'none';
  });
});

/* Delete modal */
function confirmDel(id, name) {
  document.getElementById('delName').textContent = 'Delete "' + name + '"? This cannot be undone.';
  document.getElementById('delLink').href = '?delete=' + id;
  document.getElementById('delModal').classList.add('show');
}
document.getElementById('delModal').addEventListener('click', function(e){
  if (e.target === this) this.classList.remove('show');
});

/* Load optional subjects */
document.getElementById('streamSelect').addEventListener('change', function(){
  fetch('get_optional_subjects.php?stream=' + encodeURIComponent(this.value))
    .then(r => r.text())
    .then(html => { document.getElementById('optionalSubjects').innerHTML = html; });
});

<?php if ($edit_mode && !empty($edit_data['stream'])): ?>
document.addEventListener('DOMContentLoaded', function(){
  fetch('get_optional_subjects.php?stream=<?= urlencode($edit_data['stream']) ?>')
    .then(r => r.text())
    .then(html => { document.getElementById('optionalSubjects').innerHTML = html; });
});
<?php endif; ?>
</script>

</body>
</html>
