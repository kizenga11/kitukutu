<?php
session_start();
include "../includes/config.php";

if (!isset($_SESSION['teacher_id'])) { header("Location: ../login.php"); exit(); }
$teacher_id = intval($_SESSION['teacher_id']);

// Active year & term
$year = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, year_name FROM academic_years WHERE is_active=1 LIMIT 1")) ?? [];
$term = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, term_name FROM terms WHERE is_active=1 LIMIT 1")) ?? [];
$year_id = intval($year['id'] ?? 0);
$term_id = intval($term['id'] ?? 0);

// ── Auto-create subject_settings from teacher_assignments ──
$asgn_q = mysqli_query($conn, "
    SELECT DISTINCT ta.subject_id, ta.form_level
    FROM teacher_assignments ta
    WHERE ta.teacher_id = $teacher_id
");
while ($a = mysqli_fetch_assoc($asgn_q)) {
    $chk = mysqli_query($conn, "
        SELECT id FROM subject_settings
        WHERE teacher_id=$teacher_id AND subject_id='{$a['subject_id']}'
          AND form_level='{$a['form_level']}' AND academic_year_id=$year_id AND term_id=$term_id
    ");
    if (mysqli_num_rows($chk) == 0) {
        mysqli_query($conn, "
            INSERT IGNORE INTO subject_settings (teacher_id, subject_id, form_level, academic_year_id, term_id, is_active)
            VALUES ($teacher_id, '{$a['subject_id']}', '{$a['form_level']}', $year_id, $term_id, 1)
        ");
    }
}

// AJAX: save topic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'save_topic') {
        $ss_id      = intval($_POST['ss_id']);
        $topic_id   = intval($_POST['topic_id'] ?? 0);
        $name       = mysqli_real_escape_string($conn, trim($_POST['topic_name']));
        $status     = in_array($_POST['status'], ['Not Taught','In Progress','Taught']) ? $_POST['status'] : 'Not Taught';
        $pct        = max(0, min(100, intval($_POST['pct'] ?? 0)));
        $notes      = mysqli_real_escape_string($conn, trim($_POST['notes'] ?? ''));
        $dstart     = $_POST['date_started'] ? "'".mysqli_real_escape_string($conn,$_POST['date_started'])."'" : 'NULL';
        $dtaught    = $_POST['date_taught']  ? "'".mysqli_real_escape_string($conn,$_POST['date_taught'])."'"  : 'NULL';
        $order      = intval($_POST['topic_order'] ?? 1);

        if ($topic_id) {
            // Verify belongs to this teacher
            $chk = mysqli_fetch_assoc(mysqli_query($conn,"SELECT t.id FROM topics t JOIN subject_settings ss ON ss.id=t.subject_setting_id WHERE t.id=$topic_id AND ss.teacher_id=$teacher_id"));
            if (!$chk) { echo json_encode(['ok'=>false,'msg'=>'Access denied']); exit(); }

            $old = mysqli_fetch_assoc(mysqli_query($conn,"SELECT teaching_status FROM topics WHERE id=$topic_id"));
            mysqli_query($conn,"UPDATE topics SET topic_name='$name',teaching_status='$status',completion_percentage=$pct,notes='$notes',date_started=$dstart,date_taught=$dtaught,topic_order=$order WHERE id=$topic_id");

            // Log action
            if ($old['teaching_status'] !== $status) {
                $action = $status === 'Taught' ? 'Completed' : ($status === 'In Progress' ? 'In Progress' : 'Started');
                mysqli_query($conn,"INSERT INTO teaching_progress_log(topic_id,teacher_id,action) VALUES($topic_id,$teacher_id,'$action')");

                // Notify teacher on completion
                if ($status === 'Taught') {
                    $tn = mysqli_real_escape_string($conn, $name);
                    mysqli_query($conn,"INSERT INTO notifications(teacher_id,type,title,message) VALUES($teacher_id,'system','Topic Completed ✓','You have marked \"$tn\" as fully taught. Well done!')");
                }
            }
        } else {
            // Insert new topic
            mysqli_query($conn,"INSERT INTO topics(subject_setting_id,topic_name,topic_order,teaching_status,completion_percentage,notes,date_started,date_taught) VALUES($ss_id,'$name',$order,'$status',$pct,'$notes',$dstart,$dtaught)");
            $topic_id = mysqli_insert_id($conn);
            if ($status !== 'Not Taught') {
                $action = $status === 'Taught' ? 'Completed' : 'In Progress';
                mysqli_query($conn,"INSERT INTO teaching_progress_log(topic_id,teacher_id,action) VALUES($topic_id,$teacher_id,'$action')");
            }
        }

        // Refresh coverage summary
        $cov = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) total, SUM(teaching_status='Taught') taught FROM topics WHERE subject_setting_id=$ss_id"));
        $pct_cov = $cov['total'] > 0 ? round($cov['taught']/$cov['total']*100) : 0;
        mysqli_query($conn,"INSERT INTO syllabus_coverage_summary(teacher_id,subject_id,subject_setting_id,academic_year_id,term_id,total_topics,taught_topics,coverage_percentage)
            SELECT $teacher_id, ss.subject_id, ss.id, ss.academic_year_id, ss.term_id,
                   COUNT(t.id), SUM(t.teaching_status='Taught'), $pct_cov
            FROM subject_settings ss LEFT JOIN topics t ON t.subject_setting_id=ss.id
            WHERE ss.id=$ss_id GROUP BY ss.id
            ON DUPLICATE KEY UPDATE
                total_topics=VALUES(total_topics), taught_topics=VALUES(taught_topics),
                coverage_percentage=VALUES(coverage_percentage), last_updated=NOW()");

        echo json_encode(['ok'=>true]);
        exit();
    }

    if ($_POST['action'] === 'delete_topic') {
        $topic_id = intval($_POST['topic_id']);
        $chk = mysqli_fetch_assoc(mysqli_query($conn,"SELECT t.id FROM topics t JOIN subject_settings ss ON ss.id=t.subject_setting_id WHERE t.id=$topic_id AND ss.teacher_id=$teacher_id"));
        if ($chk) {
            mysqli_query($conn,"DELETE FROM teaching_progress_log WHERE topic_id=$topic_id");
            mysqli_query($conn,"DELETE FROM main_competencies WHERE topic_id=$topic_id");
            mysqli_query($conn,"DELETE FROM topics WHERE id=$topic_id");
        }
        echo json_encode(['ok'=>true]);
        exit();
    }

    if ($_POST['action'] === 'save_competency') {
        $topic_id  = intval($_POST['topic_id']);
        $comp_name = mysqli_real_escape_string($conn, trim($_POST['comp_name']));
        $comp_id   = intval($_POST['comp_id'] ?? 0);
        $chk = mysqli_fetch_assoc(mysqli_query($conn,"SELECT t.id FROM topics t JOIN subject_settings ss ON ss.id=t.subject_setting_id WHERE t.id=$topic_id AND ss.teacher_id=$teacher_id"));
        if (!$chk) { echo json_encode(['ok'=>false]); exit(); }
        if ($comp_id) {
            mysqli_query($conn,"UPDATE main_competencies SET competence_name='$comp_name' WHERE id=$comp_id AND topic_id=$topic_id");
        } else {
            mysqli_query($conn,"INSERT INTO main_competencies(topic_id,competence_name) VALUES($topic_id,'$comp_name')");
        }
        echo json_encode(['ok'=>true, 'id'=>mysqli_insert_id($conn)]);
        exit();
    }

    if ($_POST['action'] === 'delete_competency') {
        $comp_id = intval($_POST['comp_id']);
        mysqli_query($conn,"DELETE FROM specific_competencies WHERE main_competence_id=$comp_id");
        mysqli_query($conn,"DELETE FROM main_competencies WHERE id=$comp_id");
        echo json_encode(['ok'=>true]);
        exit();
    }

    echo json_encode(['ok'=>false]);
    exit();
}

// GET: topics for a subject_setting
if (isset($_GET['get_topics'])) {
    $ss_id = intval($_GET['ss_id']);
    $chk = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM subject_settings WHERE id=$ss_id AND teacher_id=$teacher_id"));
    if (!$chk) { echo json_encode([]); exit(); }
    $rows = [];
    $res  = mysqli_query($conn,"SELECT t.*, (SELECT GROUP_CONCAT(mc.id,'|',mc.competence_name ORDER BY mc.competency_order SEPARATOR ';;') FROM main_competencies mc WHERE mc.topic_id=t.id) AS comps FROM topics t WHERE t.subject_setting_id=$ss_id ORDER BY t.topic_order, t.id");
    while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
    header('Content-Type: application/json');
    echo json_encode($rows);
    exit();
}

// Load teacher's subject assignments for active year/term
$subjects_query = mysqli_query($conn,"
    SELECT ss.id AS ss_id, ss.subject_id, ss.form_level, sub.subject_name, sub.stream,
           (SELECT COUNT(*) FROM topics t WHERE t.subject_setting_id=ss.id) AS total_topics,
           (SELECT COUNT(*) FROM topics t WHERE t.subject_setting_id=ss.id AND t.teaching_status='Taught') AS taught_topics,
           (SELECT COUNT(*) FROM topics t WHERE t.subject_setting_id=ss.id AND t.teaching_status='In Progress') AS inprog_topics
    FROM subject_settings ss
    JOIN subjects sub ON sub.id=ss.subject_id
    WHERE ss.teacher_id=$teacher_id AND ss.academic_year_id=$year_id AND ss.term_id=$term_id AND ss.is_active=1
    GROUP BY ss.id
    ORDER BY sub.subject_name, ss.form_level
");
$subjects = [];
while ($r = mysqli_fetch_assoc($subjects_query)) $subjects[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Teaching Progress</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body{background:#f3f4f6;font-family:system-ui;padding:12px;}
.sub-card{border:1px solid #e5e7eb;border-radius:14px;background:#fff;padding:12px 14px;cursor:pointer;transition:.15s;margin-bottom:8px;}
.sub-card:hover{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.1);}
.sub-card.active-card{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.15);}
.prog-bar{height:7px;border-radius:6px;background:#e5e7eb;overflow:hidden;margin-top:6px;}
.prog-fill{height:100%;border-radius:6px;transition:width .4s;}
.badge-status{font-size:10px;padding:2px 8px;border-radius:20px;font-weight:600;}
.status-taught{background:#d1fae5;color:#065f46;}
.status-inprog{background:#fef3c7;color:#92400e;}
.status-nottaught{background:#fee2e2;color:#991b1b;}
.topic-row{border:1px solid #e5e7eb;border-radius:10px;padding:10px 12px;margin-bottom:7px;background:#fff;cursor:pointer;}
.topic-row:active{background:#f3f4f6;}
.comp-chip{background:#ede9fe;color:#5b21b6;border-radius:20px;font-size:11px;padding:2px 10px;display:inline-block;margin:2px;}
.section-hdr{font-size:13px;font-weight:700;color:#374151;margin:12px 0 8px;}
.pct-pill{font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;background:#ede9fe;color:#5b21b6;}
@media(max-width:767px){
  body{padding:8px;}
  .row.g-2{--bs-gutter-x:0;}
  /* Panel switching on mobile */
  .panel-hidden{display:none!important;}
  #topicsPanel{margin-top:0;}
  .back-subjects{display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;
    color:#6366f1;background:none;border:none;padding:0 0 10px;cursor:pointer;}
  .back-subjects:hover{color:#4338ca;}
  /* Modal full-screen on phone */
  .modal-dialog{margin:0;max-width:100%;height:100%;border-radius:0;}
  .modal-content{border-radius:0;min-height:100vh;}
  .modal-body{padding:12px;overflow-y:auto;max-height:calc(100vh - 130px);}
}
@media(min-width:768px){
  .back-subjects{display:none!important;}
}
</style>
</head>
<body>

<div class="d-flex align-items-center justify-content-between mb-3" style="flex-wrap:wrap;gap:6px;">
  <h5 class="mb-0 fw-bold" style="font-size:15px"><i class="bi bi-journal-check text-primary me-2"></i>Teaching Progress</h5>
  <span class="badge bg-secondary" style="font-size:10px"><?= htmlspecialchars($year['year_name'] ?? '') ?> &bull; <?= htmlspecialchars($term['term_name'] ?? '') ?></span>
</div>

<?php if (empty($subjects)): ?>
<div class="alert alert-warning">No active subject assignments found for this term.</div>
<?php else: ?>

<div class="row g-2">
  <!-- Subject list -->
  <div class="col-md-4" id="subjectList">
    <div class="section-hdr"><i class="bi bi-book me-1"></i>Your Subjects (<?= count($subjects) ?>)</div>
    <?php foreach ($subjects as $s):
      $total  = intval($s['total_topics']);
      $taught = intval($s['taught_topics']);
      $pct    = $total > 0 ? round($taught/$total*100) : 0;
      $col    = $pct >= 80 ? '#10b981' : ($pct >= 40 ? '#f59e0b' : '#ef4444');
      $fl     = str_replace('Form ','F. ',$s['form_level']??'F.1');
    ?>
    <div class="sub-card" data-ssid="<?= $s['ss_id'] ?>" data-name="<?= htmlspecialchars($s['subject_name'].' ('.$fl.')') ?>" onclick="loadTopics(this)">
      <div class="d-flex justify-content-between align-items-start">
        <div class="fw-semibold" style="font-size:13px"><?= htmlspecialchars($s['subject_name']) ?> <span style="font-size:10px;color:#6b7280;font-weight:400;"><?=$fl?></span></div>
        <span class="pct-pill"><?= $pct ?>%</span>
      </div>
      <div class="text-muted" style="font-size:11px"><?= $s['stream'] ?></div>
      <div class="prog-bar mt-2">
        <div class="prog-fill" style="width:<?= $pct ?>%;background:<?= $col ?>;"></div>
      </div>
      <div class="mt-1" style="font-size:11px;color:#6b7280"><?= $taught ?>/<?= $total ?> topics taught &bull; <?= $s['inprog_topics'] ?> in progress</div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Topics panel -->
  <div class="col-md-8" id="topicsPanel">
    <button class="back-subjects" onclick="showSubjects()">
      <i class="bi bi-chevron-left"></i> Back to Subjects
    </button>
    <div class="text-muted text-center mt-5 pt-4" id="selectMsg">
      <i class="bi bi-arrow-down-circle d-md-none" style="font-size:2rem;color:#d1d5db;"></i>
      <i class="bi bi-arrow-left-circle d-none d-md-block" style="font-size:2rem;color:#d1d5db;margin:0 auto;width:fit-content;"></i><br>
      <span style="font-size:13px">Select a subject to view and manage topics</span>
    </div>
    <div id="topicsContent" class="d-none">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <div class="section-hdr mb-0" id="topicsTitle"></div>
        <button class="btn btn-sm btn-primary" onclick="openTopicModal(0)"><i class="bi bi-plus-lg me-1"></i>Add Topic</button>
      </div>
      <div id="topicsList"></div>
    </div>
  </div>
</div>

<?php endif; ?>

<!-- Delete Confirm Modal -->
<div class="modal fade" id="delConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center pt-1 px-4">
        <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size:2.4rem;"></i>
        <h6 class="fw-bold mt-2 mb-1">Confirm Delete</h6>
        <p class="text-muted small mb-0" id="delConfirmMsg">This action cannot be undone.</p>
      </div>
      <div class="modal-footer border-0 justify-content-center gap-2 pt-2">
        <button type="button" class="btn btn-light btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger btn-sm px-4" id="delConfirmBtn">
          <i class="bi bi-trash me-1"></i>Delete
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Topic Modal -->
<div class="modal fade" id="topicModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-bold" id="topicModalTitle">Topic</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="mTopicId">
        <input type="hidden" id="mSsId">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label fw-semibold">Topic Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="mTopicName" placeholder="e.g. Introduction to Algebra">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Status</label>
            <select class="form-select" id="mStatus" onchange="autoFillPct()">
              <option value="Not Taught">Not Taught</option>
              <option value="In Progress">In Progress</option>
              <option value="Taught">Taught</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Completion %</label>
            <input type="number" class="form-control" id="mPct" min="0" max="100" value="0">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Order</label>
            <input type="number" class="form-control" id="mOrder" min="1" value="1">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Date Started</label>
            <input type="date" class="form-control" id="mDateStart">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Date Completed</label>
            <input type="date" class="form-control" id="mDateTaught">
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">Notes</label>
            <textarea class="form-control" id="mNotes" rows="2" placeholder="Any additional notes..."></textarea>
          </div>
        </div>

        <!-- Competencies -->
        <hr class="my-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div class="fw-bold" style="font-size:13px"><i class="bi bi-list-check me-1"></i>Competencies</div>
          <button class="btn btn-sm btn-outline-primary" id="addCompBtn" onclick="addCompRow()" style="display:none"><i class="bi bi-plus-lg me-1"></i>Add</button>
        </div>
        <div id="compList" class="mb-2"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-sm btn-outline-danger me-auto" id="deleteTopicBtn" style="display:none" onclick="deleteTopic()">
          <i class="bi bi-trash me-1"></i>Delete Topic
        </button>
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary btn-sm" onclick="saveTopic()"><i class="bi bi-save me-1"></i>Save Topic</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let activeSsId = 0, activeName = '', topicModal;

// ── Delete confirm modal utility ──
let _delConfirmCb = null;
function showDelConfirm(msg, callback) {
  document.getElementById('delConfirmMsg').textContent = msg;
  _delConfirmCb = callback;
  new bootstrap.Modal(document.getElementById('delConfirmModal')).show();
}
document.getElementById('delConfirmBtn').addEventListener('click', function () {
  bootstrap.Modal.getInstance(document.getElementById('delConfirmModal')).hide();
  if (_delConfirmCb) { _delConfirmCb(); _delConfirmCb = null; }
});
const isMobile = () => window.innerWidth < 768;

document.addEventListener('DOMContentLoaded', () => {
  topicModal = new bootstrap.Modal(document.getElementById('topicModal'));
});

function showSubjects() {
  document.getElementById('subjectList').classList.remove('panel-hidden');
  document.getElementById('topicsPanel').classList.add('panel-hidden');
}

function loadTopics(el) {
  document.querySelectorAll('.sub-card').forEach(c => c.classList.remove('active-card'));
  el.classList.add('active-card');
  activeSsId = el.dataset.ssid;
  activeName = el.dataset.name;
  document.getElementById('topicsTitle').textContent = activeName;
  document.getElementById('selectMsg').classList.add('d-none');
  document.getElementById('topicsContent').classList.remove('d-none');
  document.getElementById('topicsList').innerHTML = '<div class="text-muted small">Loading...</div>';

  // On mobile: hide subjects, show topics panel, scroll to top
  if (isMobile()) {
    document.getElementById('subjectList').classList.add('panel-hidden');
    document.getElementById('topicsPanel').classList.remove('panel-hidden');
    window.scrollTo({top: 0, behavior: 'smooth'});
  }

  fetch(`?get_topics=1&ss_id=${activeSsId}`)
    .then(r => r.json())
    .then(renderTopics);
}

function renderTopics(topics) {
  const c = document.getElementById('topicsList');
  if (!topics.length) {
    c.innerHTML = '<div class="text-muted text-center py-4" style="font-size:13px"><i class="bi bi-inbox" style="font-size:2rem;color:#d1d5db;display:block;margin-bottom:6px"></i>No topics yet. Click "Add Topic" to start.</div>';
    return;
  }
  c.innerHTML = topics.map(t => {
    const sClass = t.teaching_status === 'Taught' ? 'status-taught' : t.teaching_status === 'In Progress' ? 'status-inprog' : 'status-nottaught';
    const comps = t.comps ? t.comps.split(';;').map(c => {
      const [,name] = c.split('|'); return `<span class="comp-chip">${name}</span>`;
    }).join('') : '';
    const pct = parseInt(t.completion_percentage || 0);
    const col = pct >= 80 ? '#10b981' : pct >= 40 ? '#f59e0b' : '#ef4444';
    return `<div class="topic-row" onclick='openTopicModal(${t.id}, ${JSON.stringify(t)})'>
      <div class="d-flex align-items-start justify-content-between">
        <div class="fw-semibold" style="font-size:13px">#${t.topic_order} ${t.topic_name}</div>
        <span class="badge-status ${sClass}">${t.teaching_status}</span>
      </div>
      <div class="prog-bar mt-1 mb-1" style="height:6px">
        <div class="prog-fill" style="width:${pct}%;background:${col}"></div>
      </div>
      <div style="font-size:11px;color:#6b7280">${pct}% complete ${t.date_started ? '· Started: '+t.date_started : ''} ${t.date_taught ? '· Done: '+t.date_taught : ''}</div>
      ${comps ? `<div class="mt-1">${comps}</div>` : ''}
      ${t.notes ? `<div class="mt-1 text-muted" style="font-size:11px">${t.notes}</div>` : ''}
    </div>`;
  }).join('');
}

function openTopicModal(id, data = null) {
  document.getElementById('mTopicId').value = id;
  document.getElementById('mSsId').value = activeSsId;
  document.getElementById('mTopicName').value = data ? data.topic_name : '';
  document.getElementById('mStatus').value = data ? data.teaching_status : 'Not Taught';
  document.getElementById('mPct').value = data ? data.completion_percentage : 0;
  document.getElementById('mOrder').value = data ? data.topic_order : (document.querySelectorAll('.topic-row').length + 1);
  document.getElementById('mDateStart').value = data ? (data.date_started || '') : '';
  document.getElementById('mDateTaught').value = data ? (data.date_taught || '') : '';
  document.getElementById('mNotes').value = data ? (data.notes || '') : '';
  document.getElementById('topicModalTitle').textContent = id ? 'Edit Topic' : 'Add New Topic';
  document.getElementById('deleteTopicBtn').style.display = id ? 'inline-flex' : 'none';
  document.getElementById('addCompBtn').style.display = id ? 'inline-flex' : 'none';

  // Load competencies
  const compList = document.getElementById('compList');
  if (id && data && data.comps) {
    compList.innerHTML = data.comps.split(';;').map(c => {
      const [cid, cname] = c.split('|');
      return compRow(cid, cname);
    }).join('');
  } else {
    compList.innerHTML = id ? '' : '<div class="text-muted small">Save topic first to add competencies.</div>';
  }

  topicModal.show();
}

function compRow(cid, cname) {
  return `<div class="d-flex gap-2 mb-2 align-items-center comp-entry" data-cid="${cid}">
    <input type="text" class="form-control form-control-sm comp-name" value="${cname}" placeholder="Competency name">
    <button class="btn btn-sm btn-outline-success" onclick="saveComp(this)" title="Save"><i class="bi bi-check-lg"></i></button>
    <button class="btn btn-sm btn-outline-danger" onclick="deleteComp(this)" title="Delete"><i class="bi bi-trash"></i></button>
  </div>`;
}

function addCompRow() {
  document.getElementById('compList').insertAdjacentHTML('beforeend', compRow(0, ''));
}

function saveComp(btn) {
  const row = btn.closest('.comp-entry');
  const cid = row.dataset.cid;
  const name = row.querySelector('.comp-name').value.trim();
  if (!name) return;
  const tid = document.getElementById('mTopicId').value;
  fetch('', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: `action=save_competency&topic_id=${tid}&comp_id=${cid}&comp_name=${encodeURIComponent(name)}`})
    .then(r => r.json()).then(d => { if (d.ok && !cid) row.dataset.cid = d.id; });
}

function deleteComp(btn) {
  const row = btn.closest('.comp-entry');
  const cid = row.dataset.cid;
  if (!cid || cid === '0') { row.remove(); return; }
  showDelConfirm('Delete this competency? This cannot be undone.', function () {
    fetch('', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: `action=delete_competency&comp_id=${cid}`})
      .then(r => r.json()).then(d => { if (d.ok) row.remove(); });
  });
}

function autoFillPct() {
  const s = document.getElementById('mStatus').value;
  if (s === 'Taught') document.getElementById('mPct').value = 100;
  else if (s === 'Not Taught') document.getElementById('mPct').value = 0;
  if (s === 'Taught' && !document.getElementById('mDateTaught').value)
    document.getElementById('mDateTaught').value = new Date().toISOString().slice(0,10);
  if ((s === 'In Progress' || s === 'Taught') && !document.getElementById('mDateStart').value)
    document.getElementById('mDateStart').value = new Date().toISOString().slice(0,10);
}

function saveTopic() {
  const name = document.getElementById('mTopicName').value.trim();
  if (!name) { alert('Topic name is required.'); return; }
  const body = new URLSearchParams({
    action: 'save_topic',
    ss_id:  document.getElementById('mSsId').value,
    topic_id: document.getElementById('mTopicId').value,
    topic_name: name,
    status: document.getElementById('mStatus').value,
    pct:    document.getElementById('mPct').value,
    topic_order: document.getElementById('mOrder').value,
    date_started: document.getElementById('mDateStart').value,
    date_taught: document.getElementById('mDateTaught').value,
    notes: document.getElementById('mNotes').value
  });
  fetch('', {method:'POST', body}).then(r => r.json()).then(d => {
    if (d.ok) {
      topicModal.hide();
      // Reload topics and subject list
      const el = document.querySelector(`.sub-card[data-ssid="${activeSsId}"]`);
      if (el) loadTopics(el);
      location.reload(); // refresh sidebar stats
    }
  });
}

function deleteTopic() {
  showDelConfirm('Delete this topic and all its competencies? This cannot be undone.', function () {
    const tid = document.getElementById('mTopicId').value;
    fetch('', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: `action=delete_topic&topic_id=${tid}`})
      .then(r => r.json()).then(d => {
        if (d.ok) { topicModal.hide(); const el = document.querySelector(`.sub-card[data-ssid="${activeSsId}"]`); if(el) loadTopics(el); location.reload(); }
      });
  });
}
</script>
</body>
</html>
