<?php
include "includes/config.php";

$exam_id = intval($_GET['exam_id'] ?? 0);
if(!$exam_id) { header("Location: index.php"); exit(); }

$exam = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT e.*, ec.category_name, ay.year_name
     FROM exams e
     LEFT JOIN exam_categories ec ON ec.id=e.category_id
     LEFT JOIN academic_years ay ON ay.id=e.academic_year_id
     WHERE e.id='$exam_id' AND e.is_published=1 LIMIT 1"
));
if(!$exam){ header("Location: index.php"); exit(); }

/* Build per-student results */
$marks_q = mysqli_query($conn,"
    SELECT s.id AS student_id,
           s.registration_no,
           s.sex, s.stream,
           AVG(CASE WHEN m.marks REGEXP '^[0-9]+$' THEN CAST(m.marks AS DECIMAL(5,2)) ELSE NULL END) AS avg_mark,
           COUNT(m.id) AS subject_count,
           ers.division
    FROM students s
    JOIN marks m ON m.student_id=s.id AND m.exam_id='$exam_id'
    LEFT JOIN exam_results_summary ers ON ers.student_id=s.id AND ers.exam_id='$exam_id'
    GROUP BY s.id, ers.division
    HAVING subject_count > 0
    ORDER BY avg_mark DESC
");

$results = [];
while($r = mysqli_fetch_assoc($marks_q)) $results[] = $r;
$total_students = count($results);

/* Grade helper */
function gradeFromAvg($avg){
    if($avg >= 75) return ['A','#059669','#d1fae5'];
    if($avg >= 60) return ['B','#2563eb','#dbeafe'];
    if($avg >= 45) return ['C','#d97706','#fef3c7'];
    if($avg >= 30) return ['D','#ea580c','#ffedd5'];
    return ['F','#dc2626','#fee2e2'];
}

/* Stats */
$class_avg = $total_students > 0 ? array_sum(array_column($results,'avg_mark')) / $total_students : 0;
$pass_count = count(array_filter($results, fn($r) => $r['avg_mark'] >= 40));
$pass_rate  = $total_students > 0 ? round($pass_count / $total_students * 100) : 0;

/* Date string */
$date_str = '';
if($exam['start_date']) $date_str = date('d M Y', strtotime($exam['start_date']));
if($exam['end_date'] && $exam['end_date'] != $exam['start_date']) $date_str .= ' – '.date('d M Y', strtotime($exam['end_date']));
?>
<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($exam['exam_name']) ?> – Matokeo | Amali Kitukutu</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<style>
:root{
  --primary:#0f2b4b;--primary-light:#1e4a6d;--accent:#f4b400;
  --bg:#f8fafc;--card:#fff;--border:#e5e7eb;--text:#1e293b;--muted:#6b7280;
  --radius:14px;--shadow:0 4px 20px rgba(0,0,0,0.06);
}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}

/* Topbar */
.topbar{background:var(--primary);color:#fff;padding:10px 20px;display:flex;align-items:center;gap:12px;}
.topbar a{color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;display:flex;align-items:center;gap:5px;}
.topbar a:hover{color:#fff;}
.topbar .divider{color:rgba(255,255,255,.3);margin:0 4px;}

/* Hero */
.hero{background:linear-gradient(135deg,var(--primary) 0%,var(--primary-light) 100%);color:#fff;padding:40px 24px 36px;text-align:center;}
.hero .school{font-family:'Poppins',sans-serif;font-size:13px;text-transform:uppercase;letter-spacing:1px;opacity:.7;margin-bottom:8px;}
.hero h1{font-family:'Poppins',sans-serif;font-size:1.9rem;font-weight:700;margin-bottom:6px;line-height:1.2;}
.hero .meta{font-size:13px;opacity:.7;display:flex;align-items:center;justify-content:center;gap:10px;flex-wrap:wrap;margin-top:8px;}
.hero .meta span{background:rgba(255,255,255,.1);padding:3px 12px;border-radius:20px;}

/* Stats bar */
.stats-bar{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;max-width:860px;margin:-24px auto 0;padding:0 20px 0;}
.stat-card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);padding:16px 12px;text-align:center;border-top:3px solid var(--accent);}
.stat-val{font-size:1.8rem;font-weight:800;color:var(--primary);line-height:1;}
.stat-lbl{font-size:11px;color:var(--muted);margin-top:3px;text-transform:uppercase;letter-spacing:.4px;}

/* Container */
.container{max-width:960px;margin:0 auto;padding:32px 20px;}

/* Filter bar */
.filter-row{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;align-items:center;}
.filter-row input{flex:1;min-width:180px;padding:8px 12px;border:1px solid var(--border);border-radius:10px;font-size:13px;background:#fff;}
.filter-row select{padding:8px 12px;border:1px solid var(--border);border-radius:10px;font-size:13px;background:#fff;}

/* Table */
.results-table{width:100%;border-collapse:collapse;background:var(--card);border-radius:var(--radius);overflow:hidden;box-shadow:var(--shadow);}
.results-table thead th{background:var(--primary);color:#fff;padding:10px 12px;font-size:12px;text-align:left;font-weight:600;text-transform:uppercase;letter-spacing:.4px;}
.results-table thead th:first-child{border-radius:0;}
.results-table tbody tr{border-bottom:1px solid var(--border);}
.results-table tbody tr:last-child{border-bottom:none;}
.results-table tbody tr:hover{background:#f8fafc;}
.results-table td{padding:10px 12px;font-size:13px;vertical-align:middle;}
.pos-num{font-weight:800;color:var(--muted);font-size:12px;min-width:30px;}
.stu-name{font-weight:600;color:var(--text);}
.stu-meta{font-size:11px;color:var(--muted);margin-top:1px;}
.stream-pill{display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;}
.stream-gen{background:#dbeafe;color:#1e40af;}
.stream-voc{background:#fce7f3;color:#9d174d;}
.sex-badge{display:inline-block;width:20px;height:20px;border-radius:50%;font-size:10px;font-weight:700;text-align:center;line-height:20px;}
.sex-M{background:#dbeafe;color:#1e40af;}
.sex-F{background:#fce7f3;color:#9d174d;}

/* Mini bar */
.bar-wrap{display:flex;align-items:center;gap:8px;}
.bar-bg{flex:1;height:6px;background:#f3f4f6;border-radius:3px;overflow:hidden;min-width:60px;}
.bar-fill{height:100%;border-radius:3px;}
.mark-num{font-weight:700;font-size:13px;min-width:38px;text-align:right;}

/* Grade pill */
.grade-pill{display:inline-block;padding:2px 10px;border-radius:10px;font-size:11px;font-weight:700;}

/* Division pill */
.div-pill{display:inline-block;padding:3px 10px;border-radius:10px;font-size:12px;font-weight:800;letter-spacing:.3px;}
.div-I  {background:#d1fae5;color:#065f46;}
.div-II {background:#dbeafe;color:#1e40af;}
.div-III{background:#fef3c7;color:#92400e;}
.div-IV {background:#ffedd5;color:#9a3412;}
.div-0  {background:#fee2e2;color:#991b1b;}
.div-na {background:#f3f4f6;color:#6b7280;}

/* Empty */
.empty{text-align:center;padding:60px 20px;color:var(--muted);}
.empty svg{display:block;margin:0 auto 12px;opacity:.3;}

/* Print */
@media print{
  .topbar,.filter-row{display:none!important;}
  .hero{background:#0f2b4b!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
  .stats-bar{margin-top:0;}
  body{background:#fff;}
  .results-table{box-shadow:none;}
}

@media(max-width:600px){
  .stats-bar{grid-template-columns:repeat(2,1fr);margin-top:-16px;}
  .stat-val{font-size:1.4rem;}
  .hero h1{font-size:1.4rem;}
  .results-table thead{display:none;}
  .results-table tbody tr{display:block;margin-bottom:10px;border-radius:12px;border:1px solid var(--border)!important;padding:10px 12px;}
  .results-table td{display:flex;justify-content:space-between;align-items:center;padding:5px 0;border-bottom:1px dashed var(--border);}
  .results-table td:last-child{border-bottom:none;}
  .results-table td::before{content:attr(data-label);font-size:11px;font-weight:600;color:var(--muted);flex-shrink:0;margin-right:8px;}
}
</style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
  <a href="index.php">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    Nyumbani
  </a>
  <span class="divider">/</span>
  <span style="font-size:13px;opacity:.7"><?= htmlspecialchars($exam['exam_name']) ?></span>
  <span style="margin-left:auto;">
    <a href="#" onclick="window.print();return false;" style="color:rgba(255,255,255,.7);">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      Chapisha
    </a>
  </span>
</div>

<!-- Hero -->
<div class="hero">
  <div class="school">Amali Kitukutu – Kitukutu Technical Secondary School</div>
  <h1><?= htmlspecialchars($exam['exam_name']) ?></h1>
  <div class="meta">
    <?php if($exam['category_name']): ?><span><?= htmlspecialchars($exam['category_name']) ?></span><?php endif; ?>
    <?php if($exam['year_name']): ?><span><?= htmlspecialchars($exam['year_name']) ?></span><?php endif; ?>
    <?php if($date_str): ?><span><?= $date_str ?></span><?php endif; ?>
  </div>
</div>

<!-- Stats -->
<div class="stats-bar">
  <div class="stat-card">
    <div class="stat-val"><?= $total_students ?></div>
    <div class="stat-lbl">Wanafunzi</div>
  </div>
  <div class="stat-card">
    <div class="stat-val"><?= $total_students > 0 ? round($class_avg, 1) : '–' ?></div>
    <div class="stat-lbl">Wastani wa Darasa</div>
  </div>
  <div class="stat-card">
    <div class="stat-val"><?= $pass_rate ?>%</div>
    <div class="stat-lbl">Waliofaulu (≥40)</div>
  </div>
  <div class="stat-card">
    <div class="stat-val"><?= $pass_count ?></div>
    <div class="stat-lbl">Waliofaulu</div>
  </div>
</div>

<div class="container">

  <!-- Filter -->
  <div class="filter-row">
    <input type="text" id="searchInput" placeholder="Tafuta namba ya usajili..." onkeyup="filterRows()">
    <select id="streamFilter" onchange="filterRows()">
      <option value="">Mkondo wote</option>
      <option value="General">General</option>
      <option value="Vocational">Vocational</option>
    </select>
    <select id="sexFilter" onchange="filterRows()">
      <option value="">Jinsia yote</option>
      <option value="M">Wanaume</option>
      <option value="F">Wanawake</option>
    </select>
    <select id="divFilter" onchange="filterRows()">
      <option value="">Division yote</option>
      <option value="I">Division I</option>
      <option value="II">Division II</option>
      <option value="III">Division III</option>
      <option value="IV">Division IV</option>
      <option value="0">Division 0</option>
    </select>
  </div>

  <?php if(empty($results)): ?>
  <div class="empty">
    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    <p style="font-size:15px;font-weight:600;color:#374151;">Hakuna matokeo yaliyoingizwa bado</p>
    <p style="font-size:13px;margin-top:4px;">Matokeo yataonekana hapa baada ya kuingizwa na walimu.</p>
  </div>
  <?php else: ?>
  <table class="results-table" id="resultsTable">
    <thead>
      <tr>
        <th>#</th>
        <th>Namba ya Usajili</th>
        <th>Jinsia</th>
        <th>Mkondo</th>
        <th>Wastani</th>
        <th>Daraja</th>
        <th>Division</th>
      </tr>
    </thead>
    <tbody>
    <?php $pos = 1; foreach($results as $r):
      $avg = round(floatval($r['avg_mark']), 1);
      [$grade, $gcol, $gbg] = gradeFromAvg($avg);
      $bar_pct = min(100, $avg);
      $stream_label = strtolower($r['stream'] ?? '') === 'vocational' ? 'Vocational' : 'General';
      $stream_class = $stream_label === 'Vocational' ? 'stream-voc' : 'stream-gen';
      $div_raw = $r['division'] ?? '';
      $div_display = $div_raw !== '' ? $div_raw : '–';
      $div_css = match($div_raw) {
          'I'   => 'div-I',
          'II'  => 'div-II',
          'III' => 'div-III',
          'IV'  => 'div-IV',
          '0'   => 'div-0',
          default => 'div-na',
      };
    ?>
      <tr data-reg="<?= strtolower($r['registration_no'] ?? '') ?>" data-stream="<?= $stream_label ?>" data-sex="<?= $r['sex'] ?>" data-div="<?= htmlspecialchars($div_raw) ?>">
        <td data-label="#"><span class="pos-num"><?= $pos++ ?></span></td>
        <td data-label="Namba">
          <div class="stu-name"><?= htmlspecialchars($r['registration_no'] ?? '') ?></div>
          <div class="stu-meta"><?= $r['subject_count'] ?> masomo</div>
        </td>
        <td data-label="Jinsia"><span class="sex-badge sex-<?= $r['sex'] ?>"><?= $r['sex'] ?></span></td>
        <td data-label="Mkondo"><span class="stream-pill <?= $stream_class ?>"><?= $stream_label ?></span></td>
        <td data-label="Wastani">
          <div class="bar-wrap">
            <div class="bar-bg"><div class="bar-fill" style="width:<?= $bar_pct ?>%;background:<?= $gcol ?>;"></div></div>
            <span class="mark-num" style="color:<?= $gcol ?>"><?= $avg ?></span>
          </div>
        </td>
        <td data-label="Daraja"><span class="grade-pill" style="background:<?= $gbg ?>;color:<?= $gcol ?>"><?= $grade ?></span></td>
        <td data-label="Division"><span class="div-pill <?= $div_css ?>"><?= htmlspecialchars($div_display) ?></span></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

</div>

<script>
function filterRows(){
  var search = document.getElementById('searchInput').value.toLowerCase();
  var stream = document.getElementById('streamFilter').value;
  var sex    = document.getElementById('sexFilter').value;
  var div    = document.getElementById('divFilter').value;
  document.querySelectorAll('#resultsTable tbody tr').forEach(function(tr){
    var reg  = tr.dataset.reg    || '';
    var s    = tr.dataset.stream || '';
    var g    = tr.dataset.sex    || '';
    var d    = tr.dataset.div    || '';
    var show = reg.includes(search) && (!stream || s===stream) && (!sex || g===sex) && (!div || d===div);
    tr.style.display = show ? '' : 'none';
  });
}
</script>

</body>
</html>
