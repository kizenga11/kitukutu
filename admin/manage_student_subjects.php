<?php
session_start();
include "../includes/config.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$check = mysqli_query($conn, "SELECT role FROM admins WHERE id='$admin_id'");
$data = mysqli_fetch_assoc($check);
if (($data['role'] ?? '') != 'admin') {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

/* ── Handle add/update subjects (optional only) ── */
if (isset($_POST['save_subjects'])) {
    $sid = intval($_POST['student_id']);
    // Get student stream
    $stu = mysqli_fetch_assoc(mysqli_query($conn, "SELECT stream FROM students WHERE id='$sid'"));
    $stream = $stu ? mysqli_real_escape_string($conn, $stu['stream']) : 'General';

    // Force-compulsory: always keep all compulsory subjects for this stream
    $comp = mysqli_query($conn, "SELECT id FROM subjects WHERE LOWER(stream)=LOWER('$stream') AND LOWER(category)='compulsory'");
    $comp_ids = [0]; // 0 won't match any real subject_id
    while ($c = mysqli_fetch_assoc($comp)) $comp_ids[] = intval($c['id']);

    $new_ids = $_POST['subjects'] ?? [];
    $new_ids = array_map('intval', $new_ids);
    $new_ids = array_unique(array_merge($new_ids, $comp_ids));

    // Get current subject IDs for this student
    $cur = mysqli_query($conn, "SELECT subject_id FROM student_subjects WHERE student_id='$sid'");
    $cur_ids = [0];
    while ($r = mysqli_fetch_assoc($cur)) $cur_ids[] = intval($r['subject_id']);

    // Remove optional subjects that were unchecked
    $to_remove = array_diff($cur_ids, $new_ids);
    foreach ($to_remove as $subj_id) {
        if ($subj_id > 0) {
            mysqli_query($conn, "DELETE FROM student_subjects WHERE student_id='$sid' AND subject_id='$subj_id'");
        }
    }

    // Add optional subjects that were newly checked
    $to_add = array_diff($new_ids, $cur_ids);
    foreach ($to_add as $subj_id) {
        if ($subj_id > 0) {
            mysqli_query($conn, "INSERT IGNORE INTO student_subjects(student_id,subject_id) VALUES('$sid','$subj_id')");
        }
    }

    $count_removed = max(0, count($to_remove) - 1); // -1 for the dummy 0
    $count_added = max(0, count($to_add) - 1);
    $msg = "Subjects updated.";
    if ($count_added) $msg .= " $count_added added.";
    if ($count_removed) $msg .= " $count_removed removed.";
    $_SESSION['flash'] = ['type'=>'success','msg'=>$msg];
    header("Location: manage_student_subjects.php"); exit();
}

/* ── Handle remove single subject via AJAX (optional only) ── */
if (isset($_POST['remove_single_subject'])) {
    header('Content-Type: application/json');
    $sid = intval($_POST['student_id']);
    $subj_id = intval($_POST['subject_id']);
    // Check if subject is compulsory
    $chk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT category FROM subjects WHERE id='$subj_id'"));
    if ($chk && strtolower($chk['category']) === 'compulsory') {
        echo json_encode(['success' => false, 'error' => 'Compulsory subjects cannot be removed.']);
        exit();
    }
    if ($sid && $subj_id) {
        mysqli_query($conn, "DELETE FROM student_subjects WHERE student_id='$sid' AND subject_id='$subj_id'");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid IDs']);
    }
    exit();
}

$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

/* ── All students ── */
$students_q = mysqli_query($conn, "SELECT s.*,
    (SELECT GROUP_CONCAT(sub.subject_name SEPARATOR ', ') FROM student_subjects ss JOIN subjects sub ON sub.id=ss.subject_id WHERE ss.student_id=s.id ORDER BY sub.subject_name) AS subjects_list,
    (SELECT COUNT(*) FROM student_subjects ss WHERE ss.student_id=s.id) AS subj_count
    FROM students s ORDER BY s.first_name, s.last_name");
$students = [];
while ($r = mysqli_fetch_assoc($students_q)) $students[] = $r;

/* ── All subjects grouped by stream for modals ── */
$subjects_gen = mysqli_query($conn, "SELECT * FROM subjects WHERE stream='General' ORDER BY category, subject_name");
$subjects_voc = mysqli_query($conn, "SELECT * FROM subjects WHERE stream='Vocational' ORDER BY category, subject_name");
$all_subjects = ['General'=>[], 'Vocational'=>[]];
while ($r = mysqli_fetch_assoc($subjects_gen)) $all_subjects['General'][] = $r;
while ($r = mysqli_fetch_assoc($subjects_voc)) $all_subjects['Vocational'][] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Manage Student Subjects</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="../assets/css/loader.css" rel="stylesheet">
<style>
:root{
  --primary:#4f46e5;--primary-light:#ede9fe;--primary-dark:#4338ca;
  --success:#10b981;--danger:#ef4444;--warn:#f59e0b;
  --bg:#f3f4f6;--card:#fff;--border:#e5e7eb;--text:#111827;--muted:#6b7280;
  --radius:12px;
}
*{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--bg);font-family:system-ui,-apple-system,sans-serif;color:var(--text);padding:16px;}

/* Toast */
.toast-wrap{position:fixed;top:14px;left:50%;transform:translateX(-50%);z-index:9999;}
.toast-msg{background:var(--success);color:#fff;padding:9px 22px;border-radius:30px;font-size:13px;font-weight:600;box-shadow:0 4px 16px rgba(16,185,129,.3);white-space:nowrap;animation:fadeUp .3s ease;}
.toast-msg.err{background:var(--danger);}
@keyframes fadeUp{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

/* Section header */
.sec-hdr{font-size:13px;font-weight:700;color:var(--text);margin-bottom:12px;display:flex;align-items:center;gap:7px;}
.sec-hdr i{font-size:16px;color:var(--primary);}

/* Panel */
.panel{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:16px;margin-bottom:14px;}

/* Search */
.search-wrap{position:relative;margin-bottom:12px;}
.search-wrap i{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:15px;}
.search-input{width:100%;padding:9px 12px 9px 34px;border:1.5px solid var(--border);border-radius:10px;font-size:13px;background:#fafafa;outline:none;color:var(--text);transition:border-color .15s;}
.search-input:focus{border-color:var(--primary);background:#fff;}

/* Student row */
.stu-row{
  display:flex;align-items:center;gap:10px;
  padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius);
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
.stu-body{flex:1;min-width:0;}
.stu-body .sn{font-size:13px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.stu-body .sm{font-size:11px;color:var(--muted);}
.pill{font-size:10px;font-weight:700;padding:2px 9px;border-radius:20px;flex-shrink:0;}
.pill-gen{background:#dbeafe;color:#1e40af;}
.pill-voc{background:#fce7f3;color:#9d174d;}
.pill-form{background:#ede9fe;color:#5b21b6;}

/* Subject tags */
.subj-tag{
  display:inline-flex;align-items:center;gap:4px;
  font-size:11px;padding:3px 8px;border-radius:6px;
  margin:2px 3px 2px 0;
}
.subj-tag.comp{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;}
.subj-tag.opt{background:#fef3c7;color:#92400e;border:1px solid #fde68a;}

/* Modal subject list */
.subj-item{
  display:flex;align-items:center;gap:8px;
  padding:7px 10px;border:1px solid var(--border);border-radius:8px;
  margin-bottom:5px;cursor:pointer;font-size:13px;
  transition:border-color .12s,background .12s;
}
.subj-item:hover{border-color:var(--primary);background:var(--primary-light);}
.subj-item.disabled{opacity:.6;cursor:not-allowed;}
.subj-item.disabled:hover{border-color:var(--border);background:transparent;}
.subj-item input{accent-color:var(--primary);width:16px;height:16px;}
.subj-item .badge{font-size:9px;padding:2px 6px;border-radius:4px;}
.subj-item .badge-comp{background:#d1fae5;color:#065f46;}
.subj-item .badge-opt{background:#fef3c7;color:#92400e;}
.btn-remove-subj{
  width:22px;height:22px;border:none;border-radius:6px;background:#fee2e2;color:#dc2626;
  font-size:13px;cursor:pointer;display:flex;align-items:center;justify-content:center;
  flex-shrink:0;transition:background .12s;
}
.btn-remove-subj:hover{background:#fecaca;}
.btn-remove-subj:disabled{opacity:.5;cursor:not-allowed;}

.btn-sm-subj{
  padding:4px 10px;border:none;border-radius:6px;font-size:11px;font-weight:600;
  cursor:pointer;transition:background .12s;
}
.btn-remove{background:#fee2e2;color:#dc2626;}
.btn-remove:hover{background:#fecaca;}
.btn-manage{background:var(--primary-light);color:var(--primary-dark);}
.btn-manage:hover{background:#ddd6fe;}

.empty-state{text-align:center;padding:40px 16px;color:var(--muted);}
.empty-state i{font-size:2.5rem;display:block;margin-bottom:8px;opacity:.3;}

/* Stats bar */
.stats{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:14px;}
.stat-item{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:8px 14px;display:flex;align-items:center;gap:8px;}
.stat-item .n{font-weight:700;font-size:15px;}
.stat-item .l{font-size:11px;color:var(--muted);}

@media(max-width:600px){
  body{padding:10px;}
  .panel{padding:12px;}
  .subj-tag{font-size:10px;padding:2px 6px;}
}
</style>
</head>
<body>

<?php if ($flash): ?>
<div class="toast-wrap" id="toast">
  <div class="toast-msg <?= $flash['type']==='err'?'err':'' ?>">
    <?= $flash['type']==='success' ? '✓' : '✕' ?> <?= htmlspecialchars($flash['msg']) ?>
  </div>
</div>
<script>setTimeout(()=>{const t=document.getElementById('toast');if(t)t.style.display='none'},3000)</script>
<?php endif; ?>

<!-- Header -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
  <div style="display:flex;align-items:center;gap:10px;">
    <div style="width:36px;height:36px;border-radius:10px;background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;flex-shrink:0">
      <i class="bi bi-journal-bookmark-fill"></i>
    </div>
    <div>
      <div style="font-weight:800;font-size:15px">Manage Student Subjects</div>
      <div style="font-size:11px;color:var(--muted)">Assign and manage subjects per student</div>
    </div>
  </div>
</div>

<!-- Stats -->
<div class="stats">
  <div class="stat-item"><span class="n"><?= count($students) ?></span><span class="l">Total Students</span></div>
  <div class="stat-item">
    <span style="width:8px;height:8px;border-radius:50%;background:var(--success);flex-shrink:0;"></span>
    <span class="l">Compulsory</span>
  </div>
  <div class="stat-item">
    <span style="width:8px;height:8px;border-radius:50%;background:var(--warn);flex-shrink:0;"></span>
    <span class="l">Optional</span>
  </div>
</div>

<div class="panel">
  <div class="sec-hdr"><i class="bi bi-people-fill"></i> Students</div>

  <!-- Search -->
  <div class="search-wrap">
    <i class="bi bi-search"></i>
    <input type="text" class="search-input" id="searchInput" placeholder="Search by name, form or stream...">
  </div>

  <!-- Student list -->
  <div id="studentList">
    <?php if (empty($students)): ?>
    <div class="empty-state"><i class="bi bi-people"></i><p>No students registered.</p></div>
    <?php else: ?>
    <?php foreach ($students as $row):
      $name = trim($row['first_name'].' '.($row['second_name'] ? $row['second_name'].' ' : '').$row['last_name']);
      $init = strtoupper(substr($row['first_name'],0,1).substr($row['last_name'],0,1));
      $is_voc = strtolower($row['stream']) === 'vocational';
      $av_bg  = $is_voc ? '#9d174d' : '#1e40af';
      $form_label = str_replace('Form ', 'F. ', $row['form_level'] ?? '');
      $subj_text = $row['subjects_list'] ?: '—';
    ?>
    <div class="stu-row" data-search="<?= strtolower(htmlspecialchars($name.' '.$row['sex'].' '.$row['stream'].' '.($row['form_level']??''))) ?>">
      <div class="stu-avatar" style="background:<?= $av_bg ?>"><?= $init ?></div>
      <div class="stu-body">
        <div class="sn"><?= htmlspecialchars($name) ?></div>
        <div class="sm" style="margin-bottom:4px;">
          <?= htmlspecialchars($row['sex']) ?> &bull;
          <span class="pill pill-form"><?= $form_label ?></span>
          <span class="pill <?= $is_voc ? 'pill-voc' : 'pill-gen' ?>"><?= htmlspecialchars($row['stream']) ?></span>
          &bull; <strong><?= $row['subj_count'] ?></strong> subject(s)
        </div>
        <div>
          <?php if ($row['subjects_list']): ?>
            <?php
            $subj_parts = explode(', ', $row['subjects_list']);
            foreach ($subj_parts as $subj_name):
              // Determine if compulsory or optional
              $stream_esc = mysqli_real_escape_string($conn, $row['stream']);
              $sn_esc = mysqli_real_escape_string($conn, trim($subj_name));
              $cat_q = mysqli_query($conn, "SELECT category FROM subjects WHERE subject_name='$sn_esc' AND stream='$stream_esc' LIMIT 1");
              $cat_r = mysqli_fetch_assoc($cat_q);
              $is_comp = $cat_r && strtolower($cat_r['category']) === 'compulsory';
            ?>
            <span class="subj-tag <?= $is_comp ? 'comp' : 'opt' ?>">
              <?= htmlspecialchars(trim($subj_name)) ?>
            </span>
            <?php endforeach; ?>
          <?php else: ?>
          <span style="font-size:11px;color:var(--muted);font-style:italic;">No subjects assigned</span>
          <?php endif; ?>
        </div>
      </div>
      <button class="btn-sm-subj btn-manage" onclick="openModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($name)) ?>', '<?= $row['stream'] ?>')">
        <i class="bi bi-gear"></i> Manage
      </button>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Manage Subjects Modal -->
<div class="modal-backdrop-custom" id="subjectModal">
  <div class="modal-inner">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
      <div>
        <div style="font-weight:700;font-size:15px;" id="modalTitle">Manage Subjects</div>
        <div style="font-size:11px;color:var(--muted);" id="modalStream"></div>
      </div>
      <button onclick="closeModal()" style="width:32px;height:32px;border-radius:8px;border:1px solid var(--border);background:var(--card);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;">
        <i class="bi bi-x"></i>
      </button>
    </div>

    <form method="POST" id="subjectForm">
      <input type="hidden" name="student_id" id="modalStudentId">
      <input type="hidden" name="save_subjects" value="1">

      <!-- Compulsory subjects -->
      <div style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:6px;display:flex;align-items:center;gap:6px;">
        <span style="width:8px;height:8px;border-radius:50%;background:var(--success);flex-shrink:0;"></span>
        Compulsory Subjects
      </div>
      <div id="compulsorySubjects" style="margin-bottom:12px;"></div>

      <!-- Optional subjects -->
      <div style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:6px;display:flex;align-items:center;gap:6px;">
        <span style="width:8px;height:8px;border-radius:50%;background:var(--warn);flex-shrink:0;"></span>
        Optional Subjects
      </div>
      <div id="optionalSubjects" style="margin-bottom:16px;"></div>

      <div style="display:flex;gap:8px;">
        <button type="button" onclick="closeModal()" style="flex:1;padding:10px;border:1.5px solid var(--border);border-radius:10px;background:var(--card);font-size:13px;font-weight:600;cursor:pointer;">Cancel</button>
        <button type="submit" style="flex:2;padding:10px;border:none;border-radius:10px;background:var(--primary);color:#fff;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;">
          <i class="bi bi-check-lg"></i> Save Changes
        </button>
      </div>
    </form>
  </div>
</div>

<style>
.modal-backdrop-custom{
  position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1050;
  display:none;align-items:center;justify-content:center;padding:16px;
}
.modal-backdrop-custom.show{display:flex;}
.modal-inner{background:var(--card);border-radius:var(--radius);padding:20px;max-width:480px;width:100%;max-height:90vh;overflow-y:auto;}
</style>

<script>
/* ── Search ── */
document.getElementById('searchInput').addEventListener('input', function(){
  const q = this.value.toLowerCase();
  document.querySelectorAll('.stu-row').forEach(row => {
    row.style.display = row.dataset.search.includes(q) ? '' : 'none';
  });
});

/* ── Modal ── */
const subjectsData = {
  General: <?= json_encode($all_subjects['General']) ?>,
  Vocational: <?= json_encode($all_subjects['Vocational']) ?>
};

function openModal(studentId, studentName, stream) {
  document.getElementById('modalStudentId').value = studentId;
  document.getElementById('modalTitle').textContent = studentName;
  document.getElementById('modalStream').textContent = stream + ' Stream';

  // Show loading placeholders
  document.getElementById('compulsorySubjects').innerHTML = '<div style="padding:10px;text-align:center;color:var(--muted);font-size:12px;"><span class="loader-inline"></span> Loading...</div>';
  document.getElementById('optionalSubjects').innerHTML = '';

  // Fetch current subjects for this student
  fetch('get_student_subjects.php?student_id=' + studentId)
    .then(r => r.json())
    .then(data => {
      const currentIds = data.subjects || [];
      const subjects = subjectsData[stream] || [];

      const compDiv = document.getElementById('compulsorySubjects');
      const optDiv = document.getElementById('optionalSubjects');
      compDiv.innerHTML = '';
      optDiv.innerHTML = '';

      subjects.forEach(sub => {
        const isComp = sub.category.toLowerCase() === 'compulsory';
        const checked = currentIds.includes(sub.id);
        const div = document.createElement('div');
        div.className = 'subj-item' + (isComp ? ' disabled' : '');

        const cb = document.createElement('input');
        cb.type = 'checkbox';
        cb.name = 'subjects[]';
        cb.value = sub.id;
        cb.checked = checked || isComp;
        if (isComp) cb.disabled = true;

        const label = document.createElement('span');
        label.style.flex = '1';
        label.textContent = sub.subject_name;

        const badge = document.createElement('span');
        badge.className = 'badge ' + (isComp ? 'badge-comp' : 'badge-opt');
        badge.textContent = isComp ? 'Compulsory' : 'Optional';

        div.appendChild(cb);
        div.appendChild(label);
        div.appendChild(badge);

        // Add remove button for already-assigned optional subjects only
        if (checked && !isComp) {
          const rmBtn = document.createElement('button');
          rmBtn.type = 'button';
          rmBtn.className = 'btn-remove-subj';
          rmBtn.innerHTML = '<i class="bi bi-x"></i>';
          rmBtn.title = 'Remove this subject';
          rmBtn.setAttribute('onclick', 'removeSingleSubject(' + studentId + ', ' + sub.id + ', this)');
          div.appendChild(rmBtn);
        }

        if (isComp) {
          compDiv.appendChild(div);
        } else {
          optDiv.appendChild(div);
        }
      });
    });

  document.getElementById('subjectModal').classList.add('show');
}

function closeModal() {
  document.getElementById('subjectModal').classList.remove('show');
}

function removeSingleSubject(studentId, subjectId, btn) {
  if (!confirm('Remove this subject from the student?')) return;

  // Disable button & show loading
  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-hourglass"></i>';

  fetch('manage_student_subjects.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'remove_single_subject=1&student_id=' + studentId + '&subject_id=' + subjectId
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      // Uncheck the checkbox
      const item = btn.closest('.subj-item');
      const cb = item.querySelector('input[type="checkbox"]');
      if (cb) cb.checked = false;
      // Remove the button itself
      btn.remove();
    } else {
      alert('Failed to remove subject.');
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-x"></i>';
    }
  })
  .catch(() => {
    alert('Network error.');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-x"></i>';
  });
}

document.getElementById('subjectModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
</script>

<script src="../assets/js/loader.js"></script>
</body>
</html>
