<?php
session_start();
include "../includes/config.php";
if (!isset($_SESSION['teacher_id'])) { header("Location: ../login.php"); exit(); }
$teacher_id = intval($_SESSION['teacher_id']);

$exam_id     = intval($_GET['exam']       ?? 0);
$subject_id  = intval($_GET['subject_id'] ?? 0);
$form_level  = $_GET['form_level']        ?? '';

/* ── Dropdowns ── */
$subs_q = mysqli_query($conn,"
    SELECT DISTINCT s.id, s.subject_name
    FROM teacher_assignments ta
    JOIN subjects s ON s.id = ta.subject_id
    WHERE ta.teacher_id = $teacher_id
    ORDER BY s.subject_name
");

// Get form levels for selected subject (from teacher's assignments)
$form_levels = [];
if ($subject_id) {
    $fl_q = mysqli_query($conn,"
        SELECT DISTINCT ta.form_level
        FROM teacher_assignments ta
        WHERE ta.teacher_id = $teacher_id AND ta.subject_id = $subject_id
        ORDER BY ta.form_level
    ");
    if ($fl_q) while ($fl_r = mysqli_fetch_assoc($fl_q)) $form_levels[] = $fl_r['form_level'];
}

$exams_q = mysqli_query($conn,"SELECT id, exam_name FROM exams ORDER BY start_date DESC");
$exams_all = [];
while ($e = mysqli_fetch_assoc($exams_q)) $exams_all[] = $e;

/* ── Analysis data ── */
$data = [];
$grades_dist = ['A'=>['M'=>0,'F'=>0],'B'=>['M'=>0,'F'=>0],'C'=>['M'=>0,'F'=>0],'D'=>['M'=>0,'F'=>0],'F'=>['M'=>0,'F'=>0]];
$subject_name = $exam_name = '';
$total_marks = $total_students = 0;
$highest = $lowest = null;

if ($exam_id && $subject_id) {

    $sn = mysqli_fetch_assoc(mysqli_query($conn,"SELECT subject_name FROM subjects WHERE id=$subject_id"));
    $subject_name = $sn['subject_name'] ?? '';

    foreach ($exams_all as $e) { if ($e['id'] == $exam_id) $exam_name = $e['exam_name']; }

    $fl_filter = $form_level ? "AND COALESCE(m.form_level, st.form_level)='".mysqli_real_escape_string($conn,$form_level)."'" : '';
    $stu_q = mysqli_query($conn,"
        SELECT st.id, st.first_name, st.second_name, st.last_name, st.sex,
               m.marks, m.form_level
        FROM students st
        JOIN marks m ON m.student_id = st.id
        WHERE m.exam_id = $exam_id AND m.subject_id = $subject_id $fl_filter
        ORDER BY st.first_name, st.last_name
    ");

    while ($st = mysqli_fetch_assoc($stu_q)) {
        $mark = is_numeric($st['marks']) ? (float)$st['marks'] : 0;
        $sex  = strtolower($st['sex'] ?? '') === 'female' ? 'F' : 'M';

        if ($mark >= 75)      $g = 'A';
        elseif ($mark >= 60)  $g = 'B';
        elseif ($mark >= 50)  $g = 'C';
        elseif ($mark >= 40)  $g = 'D';
        else                  $g = 'F';

        $grades_dist[$g][$sex]++;
        $total_marks += $mark;
        $total_students++;

        if ($highest === null || $mark > $highest) $highest = $mark;
        if ($lowest  === null || $mark < $lowest)  $lowest  = $mark;

        $name = trim($st['first_name'] . ' ' . ($st['second_name'] ? $st['second_name'].' ' : '') . $st['last_name']);
        $data[] = compact('name', 'mark', 'g', 'sex');
    }

    usort($data, fn($a,$b) => $b['mark'] <=> $a['mark']);
}

$fl_display = $form_level ? str_replace('Form ','F. ',$form_level) : 'All Forms';
$avg = $total_students ? round($total_marks / $total_students, 1) : 0;
$pass_count = count(array_filter($data, fn($d) => $d['mark'] >= 40));
$pass_pct   = $total_students ? round($pass_count / $total_students * 100) : 0;
$total_m = array_sum(array_column($grades_dist, 'M'));
$total_f = array_sum(array_column($grades_dist, 'F'));

/* Grade colours */
$grade_cfg = [
    'A' => ['bg'=>'#d1fae5','col'=>'#065f46','bar'=>'#10b981'],
    'B' => ['bg'=>'#dbeafe','col'=>'#1e40af','bar'=>'#3b82f6'],
    'C' => ['bg'=>'#fef3c7','col'=>'#92400e','bar'=>'#f59e0b'],
    'D' => ['bg'=>'#fed7aa','col'=>'#9a3412','bar'=>'#f97316'],
    'F' => ['bg'=>'#fee2e2','col'=>'#991b1b','bar'=>'#ef4444'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Subject Analysis</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{
  --primary:#6366f1;--primary-dark:#4f46e5;--primary-light:#ede9fe;
  --success:#10b981;--danger:#ef4444;--warn:#f59e0b;
  --bg:#f3f4f6;--card:#fff;--border:#e5e7eb;--text:#111827;--muted:#6b7280;
  --radius:14px;
}
*{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--bg);font-family:system-ui,-apple-system,sans-serif;color:var(--text);}

/* Topbar */
.topbar{
  position:sticky;top:0;z-index:100;
  background:var(--card);border-bottom:1px solid var(--border);
  padding:10px 14px;display:flex;align-items:center;gap:10px;
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
.filter-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:16px;margin-bottom:14px;}
.f-label{font-size:12px;font-weight:600;color:var(--muted);margin-bottom:4px;}
.f-select{
  width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:10px;
  font-size:13px;color:var(--text);background:var(--bg);outline:none;
  appearance:none;-webkit-appearance:none;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
  background-repeat:no-repeat;background-position:right 12px center;padding-right:32px;
}
.f-select:focus{border-color:var(--primary);}
.analyze-btn{
  width:100%;padding:11px;border:none;border-radius:10px;
  background:var(--primary-dark);color:#fff;font-size:14px;font-weight:700;
  cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;
  margin-top:14px;transition:background .15s;
}
.analyze-btn:hover{background:#4338ca;}

/* Summary grid */
.sum-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px;}
.sum-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:14px 10px;text-align:center;}
.sum-card .val{font-size:24px;font-weight:800;line-height:1.1;}
.sum-card .lbl{font-size:11px;color:var(--muted);margin-top:3px;}

/* Grade distribution */
.grade-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-bottom:14px;}
.grade-card{border-radius:12px;padding:12px 8px;text-align:center;}
.grade-card .g-letter{font-size:22px;font-weight:800;line-height:1;}
.grade-card .g-count{font-size:18px;font-weight:700;margin-top:4px;}
.grade-card .g-pct{font-size:11px;margin-top:2px;opacity:.8;}
.grade-card .g-bar{height:4px;border-radius:4px;background:rgba(0,0,0,.1);overflow:hidden;margin-top:6px;}
.grade-card .g-fill{height:100%;border-radius:4px;}
.gender-row{display:flex;justify-content:center;gap:8px;margin-top:4px;font-size:10px;font-weight:700;}

/* Section header */
.sec-hdr{font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;display:flex;align-items:center;gap:6px;}
.sec-hdr span{background:var(--primary-light);color:var(--primary-dark);border-radius:20px;padding:1px 9px;font-size:11px;text-transform:none;}

/* Student rows */
.stu-row{
  background:var(--card);border:1px solid var(--border);border-radius:12px;
  display:flex;align-items:center;gap:10px;padding:10px 12px;margin-bottom:7px;
}
.pos-num{font-size:11px;font-weight:700;color:var(--muted);min-width:22px;text-align:center;}
.stu-name{flex:1;font-size:13px;font-weight:500;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.sex-dot{font-size:10px;color:var(--muted);}
.mark-num{font-size:16px;font-weight:800;min-width:36px;text-align:right;}
.grade-pill{font-size:12px;font-weight:800;padding:3px 10px;border-radius:20px;min-width:32px;text-align:center;}
.mini-bar-wrap{width:70px;flex-shrink:0;}
.mini-bar{height:5px;border-radius:4px;background:var(--border);overflow:hidden;}
.mini-fill{height:100%;border-radius:4px;}

/* Empty */
.empty{text-align:center;padding:50px 16px;color:var(--muted);}
.empty i{font-size:3rem;display:block;margin-bottom:12px;opacity:.3;}

@media print{
  .topbar,.filter-card,.no-print{display:none!important;}
  body{background:#fff;}
  .content{padding:0;max-width:100%;}
  .stu-row{border-radius:0;border-left:none;border-right:none;border-top:none;padding:6px 0;}
  .mini-bar-wrap{display:none;}
}
@media(max-width:640px){
  .content{padding:10px;}
  .sum-grid{grid-template-columns:repeat(2,1fr);gap:8px;}
  .grade-grid{grid-template-columns:repeat(5,1fr);gap:5px;}
  .grade-card{padding:8px 5px;}
  .grade-card .g-letter{font-size:18px;}
  .grade-card .g-count{font-size:15px;}
  .mini-bar-wrap{display:none;}
  .gender-row{display:none;}
}
</style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
  <a href="javascript:history.back()" class="back-btn">&#8592;</a>
  <div class="topbar-info" style="flex:1;min-width:0;">
    <div class="title">Subject Analysis</div>
    <div class="sub"><?= $subject_name ? htmlspecialchars($subject_name).' &mdash; '.htmlspecialchars($exam_name).($form_level ? ' &mdash; '.$fl_display : '') : 'Select a subject and exam to analyse' ?></div>
  </div>
  <?php if (!empty($data)): ?>
  <button onclick="window.print()" class="no-print" style="border:1px solid var(--border);background:var(--bg);border-radius:10px;padding:7px 12px;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px;flex-shrink:0;">
    <i class="bi bi-printer"></i> Print
  </button>
  <?php endif; ?>
</div>

<div class="content">

  <!-- Filter -->
  <div class="filter-card no-print">
    <form method="GET">
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;">
        <div>
          <div class="f-label">Subject</div>
          <select name="subject_id" class="f-select" required onchange="this.form.submit()">
            <option value="">Select subject...</option>
            <?php while ($s = mysqli_fetch_assoc($subs_q)): ?>
            <option value="<?= $s['id'] ?>" <?= $subject_id == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['subject_name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div>
          <div class="f-label">Form Level</div>
          <select name="form_level" class="f-select">
            <option value="">All Forms</option>
            <?php foreach ($form_levels as $fl): ?>
            <option value="<?= $fl ?>" <?= $form_level === $fl ? 'selected' : '' ?>><?= str_replace('Form ','F. ',$fl) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <div class="f-label">Exam</div>
          <select name="exam" class="f-select" required>
            <option value="">Select exam...</option>
            <?php foreach ($exams_all as $e): ?>
            <option value="<?= $e['id'] ?>" <?= $exam_id == $e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['exam_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <button type="submit" class="analyze-btn">
        <i class="bi bi-graph-up-arrow"></i> Analyse
      </button>
    </form>
  </div>

  <?php if (!empty($data)): ?>

  <!-- Summary cards -->
  <div class="sum-grid">
    <div class="sum-card">
      <div class="val" style="color:var(--primary)"><?= $total_students ?></div>
      <div class="lbl">Students</div>
    </div>
    <div class="sum-card">
      <div class="val" style="color:var(--primary-dark)"><?= $avg ?></div>
      <div class="lbl">Class Average</div>
    </div>
    <div class="sum-card">
      <div class="val" style="color:<?= $pass_pct >= 50 ? 'var(--success)' : 'var(--danger)' ?>"><?= $pass_pct ?>%</div>
      <div class="lbl">Pass Rate (≥40)</div>
    </div>
    <div class="sum-card">
      <div class="val" style="color:var(--success)"><?= $highest ?></div>
      <div class="lbl">Highest Mark</div>
    </div>
  </div>

  <!-- Grade distribution -->
  <div class="sec-hdr no-print"><i class="bi bi-bar-chart-fill"></i> Grade Distribution <span><?= $total_students ?> students</span></div>
  <div class="grade-grid no-print">
    <?php foreach ($grade_cfg as $g => $cfg):
      $total_g  = $grades_dist[$g]['M'] + $grades_dist[$g]['F'];
      $pct_g    = $total_students ? round($total_g / $total_students * 100) : 0;
    ?>
    <div class="grade-card" style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['col'] ?>">
      <div class="g-letter"><?= $g ?></div>
      <div class="g-count"><?= $total_g ?></div>
      <div class="g-pct"><?= $pct_g ?>%</div>
      <div class="g-bar"><div class="g-fill" style="width:<?= $pct_g ?>%;background:<?= $cfg['bar'] ?>"></div></div>
      <div class="gender-row">
        <span>M:<?= $grades_dist[$g]['M'] ?></span>
        <span>F:<?= $grades_dist[$g]['F'] ?></span>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Gender summary chips -->
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;" class="no-print">
    <span style="background:#dbeafe;color:#1e40af;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700"><i class="bi bi-person-fill me-1"></i>Male: <?= $total_m ?></span>
    <span style="background:#fce7f3;color:#9d174d;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700"><i class="bi bi-person-fill me-1"></i>Female: <?= $total_f ?></span>
    <span style="background:#f3f4f6;color:#374151;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700">Lowest: <?= $lowest ?></span>
    <span style="background:var(--primary-light);color:var(--primary-dark);border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700;margin-left:auto">Pass: <?= $pass_count ?>/<?= $total_students ?></span>
  </div>

  <!-- Print header -->
  <div style="display:none" class="print-only">
    <h4 style="margin:0 0 4px"><?= htmlspecialchars($subject_name) ?> — <?= htmlspecialchars($exam_name) ?><?= $form_level ? ' — '.$fl_display : '' ?></h4>
    <p style="font-size:12px;color:#555;margin:0 0 10px">Kitukutu Secondary Technical School &bull; <?= date('d M Y') ?></p>
  </div>

  <!-- Student list -->
  <div class="sec-hdr"><i class="bi bi-people-fill"></i> Student Results <span><?= htmlspecialchars($exam_name) ?><?= $form_level ? ' · '.$fl_display : '' ?></span></div>

  <?php foreach ($data as $i => $d):
    $cfg = $grade_cfg[$d['g']];
    $col = $d['mark'] >= 75 ? 'var(--success)' : ($d['mark'] >= 50 ? 'var(--warn)' : 'var(--danger)');
  ?>
  <div class="stu-row">
    <div class="pos-num"><?= $i + 1 ?></div>
    <div class="stu-name">
      <?= htmlspecialchars($d['name']) ?>
      <div class="sex-dot"><?= $d['sex'] === 'F' ? 'Female' : 'Male' ?><?= !$form_level && isset($d['form_level']) ? ' · '.str_replace('Form ','F. ',$d['form_level']) : '' ?></div>
    </div>
    <div class="mini-bar-wrap">
      <div class="mini-bar">
        <div class="mini-fill" style="width:<?= $d['mark'] ?>%;background:<?= $col ?>"></div>
      </div>
    </div>
    <div class="mark-num" style="color:<?= $col ?>"><?= $d['mark'] ?></div>
    <div class="grade-pill" style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['col'] ?>"><?= $d['g'] ?></div>
  </div>
  <?php endforeach; ?>

  <?php elseif ($exam_id || $subject_id): ?>
  <div class="empty">
    <i class="bi bi-people"></i>
    <p style="font-size:14px;font-weight:600;margin-bottom:4px">No results found</p>
    <p style="font-size:13px">No marks recorded for this subject and exam combination.</p>
  </div>
  <?php else: ?>
  <div class="empty">
    <i class="bi bi-graph-up-arrow"></i>
    <p style="font-size:14px;font-weight:600;margin-bottom:4px">Select filters above to analyse</p>
    <p style="font-size:13px">Choose a subject and exam to view performance analysis.</p>
  </div>
  <?php endif; ?>

</div>
</body>
</html>
