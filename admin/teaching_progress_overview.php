<?php
session_start();
include "../includes/config.php";
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }

$year  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id,year_name FROM academic_years WHERE is_active=1 LIMIT 1")) ?? [];
$term  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id,term_name FROM terms WHERE is_active=1 LIMIT 1")) ?? [];
$year_id = intval($year['id'] ?? 0);
$term_id = intval($term['id'] ?? 0);

$view = $_GET['view'] ?? 'teacher'; // teacher | subject | stream
$filter_teacher  = intval($_GET['tid'] ?? 0);
$filter_subject  = intval($_GET['sid'] ?? 0);
$filter_stream   = mysqli_real_escape_string($conn, $_GET['stream'] ?? '');

// AJAX: get topic details for a subject_setting
if (isset($_GET['topics_json'])) {
    $ss_id = intval($_GET['ss_id']);
    $rows  = [];
    $res   = mysqli_query($conn,"SELECT t.*, (SELECT COUNT(*) FROM main_competencies mc WHERE mc.topic_id=t.id) AS comp_count FROM topics t WHERE t.subject_setting_id=$ss_id ORDER BY t.topic_order,t.id");
    while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
    header('Content-Type: application/json');
    echo json_encode($rows);
    exit();
}

// Fetch overview by teacher+subject
$where = "ss.academic_year_id=$year_id AND ss.term_id=$term_id AND ss.is_active=1";
if ($filter_teacher) $where .= " AND ss.teacher_id=$filter_teacher";
if ($filter_subject) $where .= " AND ss.subject_id=$filter_subject";
if ($filter_stream)  $where .= " AND sub.stream='$filter_stream'";

$data = mysqli_query($conn,"
    SELECT ss.id AS ss_id,
           t.id AS teacher_id,
           CONCAT(t.first_name,' ',t.last_name) AS teacher_name,
           sub.subject_name, sub.stream, ss.form_level,
           COUNT(tp.id) AS total_topics,
           SUM(tp.teaching_status='Taught') AS taught,
           SUM(tp.teaching_status='In Progress') AS inprog,
           SUM(tp.teaching_status='Not Taught') AS not_taught,
           AVG(tp.completion_percentage) AS avg_pct,
           MAX(tpl.log_date) AS last_update
    FROM subject_settings ss
    JOIN teachers t ON t.id=ss.teacher_id
    JOIN subjects sub ON sub.id=ss.subject_id
    LEFT JOIN topics tp ON tp.subject_setting_id=ss.id
    LEFT JOIN teaching_progress_log tpl ON tpl.topic_id=tp.id
    WHERE $where
    GROUP BY ss.id
    ORDER BY teacher_name, sub.subject_name, ss.form_level
");

$rows = [];
while ($r = mysqli_fetch_assoc($data)) $rows[] = $r;

// Teachers list for filter
$teachers_list = mysqli_query($conn,"SELECT id, CONCAT(first_name,' ',last_name) AS name FROM teachers ORDER BY first_name");
$subjects_list = mysqli_query($conn,"SELECT id, subject_name FROM subjects ORDER BY subject_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Teaching Progress Overview</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body{background:#f4f7fc;font-family:system-ui;padding:14px;}
.pbar{height:8px;border-radius:6px;background:#e5e7eb;overflow:hidden;}
.pbar-fill{height:100%;border-radius:6px;}
.badge-s{font-size:10px;padding:2px 8px;border-radius:20px;font-weight:600;}
.s-taught{background:#d1fae5;color:#065f46;}
.s-inprog{background:#fef3c7;color:#92400e;}
.s-nottaught{background:#fee2e2;color:#991b1b;}
.warn-row{background:#fff7ed!important;}
.detail-panel{background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:10px;margin-top:6px;display:none;}
.card-hdr{font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;}
@media print{
  @page{size:A4 landscape;margin:10mm;}
  .no-print{display:none!important;}
  body{background:#fff;padding:0;}
  .detail-panel{display:block!important;}
}
@media(max-width:767px){
  body{padding:10px;}
  .table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;}
  .table{font-size:11px;min-width:620px;}
  .table th,.table td{padding:6px 8px!important;}
  .filters-row .col-auto{width:100%;}
  .filters-row .col-auto select,.filters-row .col-auto .btn{width:100%;}
  h5{font-size:14px;}
}
</style>
</head>
<body>

<div class="d-flex align-items-center justify-content-between mb-3 no-print">
  <h5 class="mb-0 fw-bold"><i class="bi bi-graph-up text-primary me-2"></i>Teaching Progress Overview</h5>
  <span class="badge bg-secondary"><?= htmlspecialchars($year['year_name'] ?? '') ?> &bull; <?= htmlspecialchars($term['term_name'] ?? '') ?></span>
</div>

<!-- Filters -->
<form method="GET" class="row g-2 mb-3 no-print filters-row">
  <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
  <div class="col-auto">
    <select name="tid" class="form-select form-select-sm">
      <option value="">All Teachers</option>
      <?php while ($t = mysqli_fetch_assoc($teachers_list)): ?>
      <option value="<?= $t['id'] ?>" <?= $filter_teacher == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
      <?php endwhile; ?>
    </select>
  </div>
  <div class="col-auto">
    <select name="sid" class="form-select form-select-sm">
      <option value="">All Subjects</option>
      <?php while ($s = mysqli_fetch_assoc($subjects_list)): ?>
      <option value="<?= $s['id'] ?>" <?= $filter_subject == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['subject_name']) ?></option>
      <?php endwhile; ?>
    </select>
  </div>
  <div class="col-auto">
    <select name="stream" class="form-select form-select-sm">
      <option value="">All Streams</option>
      <option value="GENERAL" <?= $filter_stream==='GENERAL' ? 'selected':'' ?>>General</option>
      <option value="VOCATIONAL" <?= $filter_stream==='VOCATIONAL' ? 'selected':'' ?>>Vocational</option>
    </select>
  </div>
  <div class="col-auto">
    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
    <a href="teaching_progress_overview.php" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
  </div>
  <div class="col-auto ms-auto">
    <button type="button" class="btn btn-sm btn-outline-dark" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
  </div>
</form>

<!-- Summary cards -->
<?php
$total_rows = count($rows);
$no_topics  = array_filter($rows, fn($r) => $r['total_topics'] == 0);
$low_cov    = array_filter($rows, fn($r) => $r['total_topics'] > 0 && $r['avg_pct'] < 40);
$good_cov   = array_filter($rows, fn($r) => $r['avg_pct'] >= 80);
?>
<div class="row g-2 mb-3 no-print">
  <div class="col-6 col-md-3">
    <div class="bg-white rounded-3 border p-3 text-center">
      <div style="font-size:22px;font-weight:800;color:#6366f1"><?= $total_rows ?></div>
      <div style="font-size:11px;color:#6b7280">Subject Assignments</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="bg-white rounded-3 border p-3 text-center">
      <div style="font-size:22px;font-weight:800;color:#ef4444"><?= count($no_topics) ?></div>
      <div style="font-size:11px;color:#6b7280">No Topics Added</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="bg-white rounded-3 border p-3 text-center">
      <div style="font-size:22px;font-weight:800;color:#f59e0b"><?= count($low_cov) ?></div>
      <div style="font-size:11px;color:#6b7280">Low Coverage (&lt;40%)</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="bg-white rounded-3 border p-3 text-center">
      <div style="font-size:22px;font-weight:800;color:#10b981"><?= count($good_cov) ?></div>
      <div style="font-size:11px;color:#6b7280">Good Coverage (≥80%)</div>
    </div>
  </div>
</div>

<!-- Print header (hidden on screen) -->
<div style="display:none" class="print-only">
  <h4><?= htmlspecialchars($year['year_name']??'') ?> · <?= htmlspecialchars($term['term_name']??'') ?> — Teaching Progress Overview</h4>
  <p>Kitukutu Secondary Technical School &bull; Printed: <?= date('d M Y H:i') ?></p><hr>
</div>

<!-- Main table -->
<div class="bg-white rounded-3 border overflow-hidden">
<div class="table-wrap">
<table class="table table-sm table-hover mb-0" style="font-size:12px">
  <thead class="table-dark">
    <tr>
      <th>#</th>
      <th>Teacher</th>
      <th>Subject</th>
      <th>Form</th>
      <th>Stream</th>
      <th class="text-center">Topics</th>
      <th class="text-center">Taught</th>
      <th class="text-center">In Prog</th>
      <th>Coverage</th>
      <th class="text-center">Last Update</th>
      <th class="no-print"></th>
    </tr>
  </thead>
  <tbody>
  <?php if (empty($rows)): ?>
  <tr><td colspan="10" class="text-center text-muted py-4">No data found for selected filters.</td></tr>
  <?php endif; ?>
  <?php foreach ($rows as $i => $r):
    $total  = intval($r['total_topics']);
    $taught = intval($r['taught']);
    $inprog = intval($r['inprog']);
    $pct    = $r['avg_pct'] !== null ? round((float)$r['avg_pct']) : 0;
    $col    = $pct >= 80 ? '#10b981' : ($pct >= 40 ? '#f59e0b' : '#ef4444');
    $warn   = $total == 0 || $pct < 30;
    $last   = $r['last_update'] ? date('d M Y', strtotime($r['last_update'])) : '—';
  ?>
  <tr class="<?= $warn ? 'warn-row' : '' ?>">
    <td class="text-muted"><?= $i+1 ?></td>
    <td class="fw-semibold"><?= htmlspecialchars($r['teacher_name']) ?></td>
    <td><?= htmlspecialchars($r['subject_name']) ?></td>
    <td><?= str_replace('Form ','F. ',$r['form_level']??'F.1') ?></td>
    <td><span class="badge-s <?= $r['stream']==='GENERAL' ? 's-taught' : 's-inprog' ?>"><?= $r['stream'] ?></span></td>
    <td class="text-center"><?= $total ?></td>
    <td class="text-center"><span class="badge-s s-taught"><?= $taught ?></span></td>
    <td class="text-center"><span class="badge-s s-inprog"><?= $inprog ?></span></td>
    <td style="min-width:130px">
      <div class="d-flex align-items-center gap-2">
        <div class="pbar flex-grow-1"><div class="pbar-fill" style="width:<?= $pct ?>%;background:<?= $col ?>"></div></div>
        <span style="font-size:11px;font-weight:700;color:<?= $col ?>"><?= $pct ?>%</span>
      </div>
    </td>
    <td class="text-center text-muted"><?= $last ?></td>
    <td class="no-print">
      <button class="btn btn-xs btn-sm btn-outline-primary" style="padding:1px 8px;font-size:11px" onclick="toggleDetail(<?= $r['ss_id'] ?>, this)">
        <i class="bi bi-chevron-down"></i>
      </button>
    </td>
  </tr>
  <tr class="detail-tr" id="dtr-<?= $r['ss_id'] ?>" style="display:none">
    <td colspan="10" style="padding:0 12px 10px">
      <div class="detail-panel" id="dp-<?= $r['ss_id'] ?>">
        <div class="card-hdr">Topics in <?= htmlspecialchars($r['subject_name']) ?></div>
        <div class="topics-body">Loading...</div>
      </div>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</div>

<script>
function toggleDetail(ssId, btn) {
  const tr = document.getElementById('dtr-'+ssId);
  const dp = document.getElementById('dp-'+ssId);
  const visible = tr.style.display !== 'none';
  tr.style.display = visible ? 'none' : '';
  dp.style.display = visible ? 'none' : 'block';
  btn.innerHTML = visible ? '<i class="bi bi-chevron-down"></i>' : '<i class="bi bi-chevron-up"></i>';

  if (!visible && dp.dataset.loaded !== '1') {
    dp.dataset.loaded = '1';
    fetch(`?topics_json=1&ss_id=${ssId}`)
      .then(r => r.json())
      .then(topics => {
        if (!topics.length) { dp.querySelector('.topics-body').innerHTML = '<em class="text-muted">No topics added yet.</em>'; return; }
        dp.querySelector('.topics-body').innerHTML = topics.map(t => {
          const sClass = t.teaching_status === 'Taught' ? 's-taught' : t.teaching_status === 'In Progress' ? 's-inprog' : 's-nottaught';
          return `<div class="d-flex gap-2 align-items-center py-1 border-bottom">
            <span class="text-muted" style="font-size:11px;min-width:20px">${t.topic_order}.</span>
            <span style="font-size:12px;flex:1">${t.topic_name}</span>
            <span class="badge-s ${sClass}">${t.teaching_status}</span>
            <span style="font-size:11px;color:#6b7280">${t.completion_percentage}%</span>
            <span style="font-size:11px;color:#9ca3af">${t.comp_count} comp.</span>
          </div>`;
        }).join('');
      });
  }
}
</script>
</body>
</html>
