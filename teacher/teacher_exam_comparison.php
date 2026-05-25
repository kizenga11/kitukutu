<?php
session_start();
include "../includes/config.php";
if (!isset($_SESSION['teacher_id'])) { header("Location: ../login.php"); exit(); }
$teacher_id = intval($_SESSION['teacher_id']);

/* ── Dropdowns ── */
$subjects_q = mysqli_query($conn,"
    SELECT s.id, s.subject_name
    FROM teacher_assignments ta
    JOIN subjects s ON s.id = ta.subject_id
    WHERE ta.teacher_id = $teacher_id
    ORDER BY s.subject_name
");

$exams_q = mysqli_query($conn,"SELECT id, exam_name FROM exams ORDER BY start_date DESC");
$exams_all = [];
while ($e = mysqli_fetch_assoc($exams_q)) $exams_all[] = $e;

/* ── Inputs ── */
$subject_id   = intval($_GET['subject']      ?? 0);
$prev_exam_id = intval($_GET['prev_exam']    ?? 0);
$curr_exam_id = intval($_GET['current_exam'] ?? 0);
$form_level   = $_GET['form_level']          ?? '';

// Form levels for selected subject
$comp_form_levels = [];
if ($subject_id) {
    $fl_q = mysqli_query($conn,"SELECT DISTINCT ta.form_level FROM teacher_assignments ta WHERE ta.teacher_id=$teacher_id AND ta.subject_id=$subject_id ORDER BY ta.form_level");
    if ($fl_q) while ($fl_r = mysqli_fetch_assoc($fl_q)) $comp_form_levels[] = $fl_r['form_level'];
}

$data = [];
$avg_prev = $avg_curr = $subject_percent = 0;
$subject_trend = '';
$subject_name  = '';
$prev_name = $curr_name = '';

if ($subject_id && $prev_exam_id && $curr_exam_id) {

    /* Subject name */
    $sn = mysqli_fetch_assoc(mysqli_query($conn,"SELECT subject_name FROM subjects WHERE id=$subject_id"));
    $subject_name = $sn['subject_name'] ?? '';

    /* Exam names */
    foreach ($exams_all as $e) {
        if ($e['id'] == $prev_exam_id) $prev_name = $e['exam_name'];
        if ($e['id'] == $curr_exam_id) $curr_name = $e['exam_name'];
    }

    /* Build filter for form_level */
    $fl_where = '';
    $fl_where_sub = '';
    $fl_join  = '';
    if ($form_level) {
        $fl        = mysqli_real_escape_string($conn,$form_level);
        $fl_where  = "AND COALESCE(m.form_level, s.form_level)='$fl'";
        $fl_where_sub = "AND COALESCE(form_level,'$fl')='$fl'";
    }

    /* Students in both exams */
    $stu_q = mysqli_query($conn,"
        SELECT DISTINCT s.id, s.first_name, s.second_name, s.last_name, s.sex
        FROM students s
        JOIN marks m ON m.student_id = s.id
        WHERE m.subject_id = $subject_id AND m.exam_id IN ($prev_exam_id, $curr_exam_id) $fl_where
        ORDER BY s.first_name, s.last_name
    ");

    while ($st = mysqli_fetch_assoc($stu_q)) {
        $sid  = $st['id'];
        $name = trim($st['first_name'] . ' ' . ($st['second_name'] ? $st['second_name'].' ' : '') . $st['last_name']);

        $r1 = mysqli_fetch_assoc(mysqli_query($conn,"SELECT marks FROM marks WHERE student_id=$sid AND subject_id=$subject_id AND exam_id=$prev_exam_id $fl_where_sub LIMIT 1"));
        $r2 = mysqli_fetch_assoc(mysqli_query($conn,"SELECT marks FROM marks WHERE student_id=$sid AND subject_id=$subject_id AND exam_id=$curr_exam_id $fl_where_sub LIMIT 1"));

        $m1 = isset($r1['marks']) && is_numeric($r1['marks']) ? (float)$r1['marks'] : null;
        $m2 = isset($r2['marks']) && is_numeric($r2['marks']) ? (float)$r2['marks'] : null;

        $diff = ($m1 !== null && $m2 !== null) ? $m2 - $m1 : null;
        $pct  = ($m1 && $m1 > 0 && $diff !== null) ? round(($diff / $m1) * 100, 1) : null;

        $trend = '';
        if ($diff !== null) {
            if ($diff > 0) $trend = 'Improved';
            elseif ($diff < 0) $trend = 'Dropped';
            else $trend = 'Same';
        }

        $data[] = compact('name', 'm1', 'm2', 'diff', 'pct', 'trend');
    }

    /* Class averages */
    $count = count($data);
    $sum1 = array_sum(array_column(array_filter($data, fn($d) => $d['m1'] !== null), 'm1'));
    $sum2 = array_sum(array_column(array_filter($data, fn($d) => $d['m2'] !== null), 'm2'));
    $c1 = count(array_filter($data, fn($d) => $d['m1'] !== null));
    $c2 = count(array_filter($data, fn($d) => $d['m2'] !== null));
    $avg_prev = $c1 ? round($sum1 / $c1, 1) : 0;
    $avg_curr = $c2 ? round($sum2 / $c2, 1) : 0;
    $subject_percent = $avg_prev > 0 ? round(($avg_curr - $avg_prev) / $avg_prev * 100, 1) : 0;
    if ($avg_curr > $avg_prev) $subject_trend = 'Improved';
    elseif ($avg_curr < $avg_prev) $subject_trend = 'Dropped';
    else $subject_trend = 'Same';

    /* Sort: improved first */
    usort($data, fn($a,$b) => ($b['diff'] ?? -999) <=> ($a['diff'] ?? -999));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Exam Comparison</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{
  --primary:#6366f1; --primary-dark:#4f46e5; --primary-light:#ede9fe;
  --success:#10b981; --danger:#ef4444; --warn:#f59e0b;
  --bg:#f3f4f6; --card:#fff; --border:#e5e7eb; --text:#111827; --muted:#6b7280;
  --radius:14px;
}
*{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--bg);font-family:system-ui,-apple-system,sans-serif;color:var(--text);}

/* Topbar */
.topbar{
  position:sticky;top:0;z-index:100;
  background:var(--card);border-bottom:1px solid var(--border);
  padding:10px 14px;
  display:flex;align-items:center;gap:10px;
}
.back-btn{
  display:flex;align-items:center;justify-content:center;
  width:34px;height:34px;border-radius:10px;
  background:var(--bg);border:1px solid var(--border);
  color:var(--text);text-decoration:none;font-size:16px;flex-shrink:0;
}
.topbar-info .title{font-weight:800;font-size:15px;}
.topbar-info .sub{font-size:11px;color:var(--muted);}

/* Content */
.content{padding:14px;max-width:860px;margin:0 auto;}

/* Filter card */
.filter-card{
  background:var(--card);border:1px solid var(--border);
  border-radius:var(--radius);padding:16px;margin-bottom:14px;
}
.filter-card .label{font-size:12px;font-weight:600;color:var(--muted);margin-bottom:4px;}
.form-select{
  width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:10px;
  font-size:13px;color:var(--text);background:var(--bg);outline:none;
  appearance:none;-webkit-appearance:none;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
  background-repeat:no-repeat;background-position:right 12px center;padding-right:32px;
}
.form-select:focus{border-color:var(--primary);}
.compare-btn{
  width:100%;padding:11px;border:none;border-radius:10px;
  background:var(--primary-dark);color:#fff;font-size:14px;font-weight:700;
  cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;
  margin-top:14px;transition:background .15s;
}
.compare-btn:hover{background:#4338ca;}

/* Summary cards */
.summary-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px;}
.sum-card{
  background:var(--card);border:1px solid var(--border);border-radius:var(--radius);
  padding:14px 12px;text-align:center;
}
.sum-card .val{font-size:26px;font-weight:800;line-height:1.1;}
.sum-card .lbl{font-size:11px;color:var(--muted);margin-top:4px;}
.sum-card .sublbl{font-size:10px;color:var(--muted);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}

/* Section header */
.sec-hdr{
  font-size:12px;font-weight:700;color:var(--muted);
  text-transform:uppercase;letter-spacing:.5px;
  margin-bottom:10px;display:flex;align-items:center;gap:6px;
}
.sec-hdr span{background:var(--primary-light);color:var(--primary-dark);border-radius:20px;padding:1px 9px;font-size:11px;text-transform:none;}

/* Student rows */
.student-card{
  background:var(--card);border:1px solid var(--border);
  border-radius:var(--radius);padding:12px 14px;margin-bottom:8px;
}
.student-card:last-child{margin-bottom:0;}
.stu-top{display:flex;align-items:center;gap:10px;margin-bottom:8px;}
.stu-rank{
  width:26px;height:26px;border-radius:8px;background:var(--bg);
  display:flex;align-items:center;justify-content:center;
  font-size:11px;font-weight:700;color:var(--muted);flex-shrink:0;
}
.stu-name{font-weight:600;font-size:13px;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.trend-badge{
  font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;flex-shrink:0;
  display:flex;align-items:center;gap:4px;
}
.trend-improved{background:#d1fae5;color:#065f46;}
.trend-dropped{background:#fee2e2;color:#991b1b;}
.trend-same{background:#f3f4f6;color:#6b7280;}

.marks-row{display:grid;grid-template-columns:1fr 1fr;gap:8px;}
.mark-block .mark-lbl{font-size:10px;color:var(--muted);margin-bottom:3px;}
.mark-block .mark-val{font-size:16px;font-weight:800;margin-bottom:4px;}
.mini-bar{height:5px;border-radius:4px;background:var(--border);overflow:hidden;}
.mini-fill{height:100%;border-radius:4px;transition:width .4s;}

.diff-pill{
  display:inline-flex;align-items:center;gap:3px;
  font-size:12px;font-weight:700;padding:2px 9px;border-radius:20px;
}

/* Empty state */
.empty{text-align:center;padding:50px 16px;color:var(--muted);}
.empty i{font-size:3rem;display:block;margin-bottom:12px;opacity:.3;}

@media(max-width:600px){
  .content{padding:10px;}
  .summary-grid{grid-template-columns:repeat(3,1fr);gap:7px;}
  .sum-card{padding:10px 8px;}
  .sum-card .val{font-size:20px;}
  .filter-card{padding:12px;}
}
</style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
  <a href="javascript:history.back()" class="back-btn">&#8592;</a>
  <div class="topbar-info">
    <div class="title">Exam Comparison</div>
    <div class="sub"><?= $subject_name ? htmlspecialchars($subject_name) . ' &mdash; ' . htmlspecialchars($prev_name) . ' vs ' . htmlspecialchars($curr_name) . ($form_level ? ' &mdash; '.str_replace('Form ','F. ',$form_level) : '') : 'Compare student performance across two exams' ?></div>
  </div>
</div>

<div class="content">

  <!-- Filter -->
  <div class="filter-card">
    <form method="GET">
      <div style="display:grid;gap:10px;">
        <div>
          <div class="label">Subject</div>
          <select name="subject" class="form-select" required onchange="this.form.submit()">
            <option value="">Select subject...</option>
            <?php while ($s = mysqli_fetch_assoc($subjects_q)): ?>
            <option value="<?= $s['id'] ?>" <?= $subject_id == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['subject_name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div>
          <div class="label">Form Level</div>
          <select name="form_level" class="form-select">
            <option value="">All Forms</option>
            <?php foreach ($comp_form_levels as $fl): ?>
            <option value="<?= $fl ?>" <?= $form_level === $fl ? 'selected' : '' ?>><?= str_replace('Form ','F. ',$fl) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
          <div>
            <div class="label">Previous Exam</div>
            <select name="prev_exam" class="form-select" required>
              <option value="">Select...</option>
              <?php foreach ($exams_all as $e): ?>
              <option value="<?= $e['id'] ?>" <?= $prev_exam_id == $e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['exam_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <div class="label">Current Exam</div>
            <select name="current_exam" class="form-select" required>
              <option value="">Select...</option>
              <?php foreach ($exams_all as $e): ?>
              <option value="<?= $e['id'] ?>" <?= $curr_exam_id == $e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['exam_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <button type="submit" class="compare-btn">
        <i class="bi bi-bar-chart-line"></i> Compare
      </button>
    </form>
  </div>

  <?php if (!empty($data)): ?>

  <!-- Summary cards -->
  <?php
    $trend_col = $subject_trend === 'Improved' ? 'var(--success)' : ($subject_trend === 'Dropped' ? 'var(--danger)' : 'var(--muted)');
    $trend_icon = $subject_trend === 'Improved' ? '&#8593;' : ($subject_trend === 'Dropped' ? '&#8595;' : '&#8212;');
    $improved_count = count(array_filter($data, fn($d) => $d['trend'] === 'Improved'));
    $dropped_count  = count(array_filter($data, fn($d) => $d['trend'] === 'Dropped'));
  ?>
  <div class="summary-grid">
    <div class="sum-card">
      <div class="val" style="color:var(--muted)"><?= $avg_prev ?></div>
      <div class="lbl">Previous Avg</div>
      <div class="sublbl"><?= htmlspecialchars($prev_name) ?></div>
    </div>
    <div class="sum-card">
      <div class="val" style="color:var(--primary)"><?= $avg_curr ?></div>
      <div class="lbl">Current Avg</div>
      <div class="sublbl"><?= htmlspecialchars($curr_name) ?></div>
    </div>
    <div class="sum-card">
      <div class="val" style="color:<?= $trend_col ?>"><?= $subject_percent > 0 ? '+' : '' ?><?= $subject_percent ?>%</div>
      <div class="lbl">Class Change</div>
      <div class="sublbl" style="color:<?= $trend_col ?>;font-weight:700"><?= $trend_icon ?> <?= $subject_trend ?></div>
    </div>
  </div>

  <!-- Breakdown chips -->
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;">
    <span style="background:#d1fae5;color:#065f46;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700">
      <i class="bi bi-arrow-up-short"></i> <?= $improved_count ?> Improved
    </span>
    <span style="background:#fee2e2;color:#991b1b;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700">
      <i class="bi bi-arrow-down-short"></i> <?= $dropped_count ?> Dropped
    </span>
    <span style="background:#f3f4f6;color:#6b7280;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700">
      <?= count($data) - $improved_count - $dropped_count ?> Same
    </span>
    <span style="background:var(--primary-light);color:var(--primary-dark);border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700;margin-left:auto">
      <?= count($data) ?> students
    </span>
  </div>

  <!-- Student list -->
  <div class="sec-hdr">
    <i class="bi bi-people-fill"></i> Students
    <span><?= htmlspecialchars($prev_name) ?> vs <?= htmlspecialchars($curr_name) ?></span>
  </div>

  <?php foreach ($data as $i => $d):
    $tc  = $d['trend'] === 'Improved' ? 'trend-improved' : ($d['trend'] === 'Dropped' ? 'trend-dropped' : 'trend-same');
    $ico = $d['trend'] === 'Improved' ? 'bi-arrow-up-short' : ($d['trend'] === 'Dropped' ? 'bi-arrow-down-short' : 'bi-dash');
    $c1  = $d['m1'] !== null ? ($d['m1'] >= 80 ? 'var(--success)' : ($d['m1'] >= 50 ? 'var(--warn)' : 'var(--danger)')) : 'var(--muted)';
    $c2  = $d['m2'] !== null ? ($d['m2'] >= 80 ? 'var(--success)' : ($d['m2'] >= 50 ? 'var(--warn)' : 'var(--danger)')) : 'var(--muted)';
    $diffStr = $d['diff'] !== null ? ($d['diff'] > 0 ? '+'.number_format($d['diff'],1) : number_format($d['diff'],1)) : '—';
    $diffBg  = $d['trend'] === 'Improved' ? '#d1fae5' : ($d['trend'] === 'Dropped' ? '#fee2e2' : '#f3f4f6');
    $diffCol = $d['trend'] === 'Improved' ? '#065f46' : ($d['trend'] === 'Dropped' ? '#991b1b' : '#6b7280');
  ?>
  <div class="student-card">
    <div class="stu-top">
      <div class="stu-rank"><?= $i + 1 ?></div>
      <div class="stu-name"><?= htmlspecialchars($d['name']) ?></div>
      <?php if ($d['diff'] !== null): ?>
      <span class="diff-pill" style="background:<?= $diffBg ?>;color:<?= $diffCol ?>"><?= $diffStr ?></span>
      <?php endif; ?>
      <span class="trend-badge <?= $tc ?>"><i class="bi <?= $ico ?>"></i><?= $d['trend'] ?></span>
    </div>
    <div class="marks-row">
      <div class="mark-block">
        <div class="mark-lbl"><?= htmlspecialchars($prev_name) ?></div>
        <div class="mark-val" style="color:<?= $c1 ?>"><?= $d['m1'] !== null ? $d['m1'] : '—' ?></div>
        <div class="mini-bar">
          <div class="mini-fill" style="width:<?= $d['m1'] !== null ? $d['m1'] : 0 ?>%;background:<?= $c1 ?>"></div>
        </div>
      </div>
      <div class="mark-block">
        <div class="mark-lbl"><?= htmlspecialchars($curr_name) ?></div>
        <div class="mark-val" style="color:<?= $c2 ?>"><?= $d['m2'] !== null ? $d['m2'] : '—' ?></div>
        <div class="mini-bar">
          <div class="mini-fill" style="width:<?= $d['m2'] !== null ? $d['m2'] : 0 ?>%;background:<?= $c2 ?>"></div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <?php elseif ($subject_id || $prev_exam_id || $curr_exam_id): ?>
  <div class="empty">
    <i class="bi bi-people"></i>
    <p style="font-size:14px;font-weight:600;margin-bottom:4px">No matching data found</p>
    <p style="font-size:13px">No students have marks in both selected exams for this subject.</p>
  </div>
  <?php else: ?>
  <div class="empty">
    <i class="bi bi-bar-chart-line"></i>
    <p style="font-size:14px;font-weight:600;margin-bottom:4px">Select filters above to compare</p>
    <p style="font-size:13px">Choose a subject and two exams to see how students performed.</p>
  </div>
  <?php endif; ?>

</div>
</body>
</html>
