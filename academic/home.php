<?php
session_start();
include "../includes/config.php";
if(!isset($_SESSION['admin_id']) || ($_SESSION['user_role']??'') !== 'academic'){
    header("Location: ../login.php"); exit();
}

$year  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT year_name FROM academic_years WHERE is_active=1 LIMIT 1")) ?? [];
$term  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT term_name FROM terms WHERE is_active=1 LIMIT 1")) ?? [];
$year_id = intval(mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM academic_years WHERE is_active=1 LIMIT 1"))['id'] ?? 0);
$term_id = intval(mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM terms WHERE is_active=1 LIMIT 1"))['id'] ?? 0);

$stats = [
  'students'   => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM students"))['c'] ?? 0,
  'teachers'   => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM teachers"))['c'] ?? 0,
  'exams'      => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM exams WHERE is_active=1"))['c'] ?? 0,
  'general'    => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM students WHERE stream='General'"))['c'] ?? 0,
  'vocational' => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM students WHERE stream='Vocational'"))['c'] ?? 0,
];

$form_counts = [];
$fc_q = mysqli_query($conn, "SELECT form_level, COUNT(*) as total FROM students GROUP BY form_level ORDER BY form_level");
if ($fc_q) while ($fc_r = mysqli_fetch_assoc($fc_q)) $form_counts[$fc_r['form_level']] = $fc_r['total'];

$tp = mysqli_fetch_assoc(mysqli_query($conn,"
  SELECT COUNT(t.id) total, SUM(t.teaching_status='Taught') taught
  FROM subject_settings ss JOIN topics t ON t.subject_setting_id=ss.id
  WHERE ss.academic_year_id=$year_id AND ss.term_id=$term_id AND ss.is_active=1
")) ?? ['total'=>0,'taught'=>0];
$taught_pct = $tp['total']>0 ? round($tp['taught']/$tp['total']*100) : 0;

$subject_progress = mysqli_query($conn,"
  SELECT ss.subject_name,
         COUNT(t.id) total,
         SUM(t.teaching_status='Taught') taught
  FROM subject_settings ss
  LEFT JOIN topics t ON t.subject_setting_id=ss.id
  WHERE ss.academic_year_id=$year_id AND ss.term_id=$term_id AND ss.is_active=1
  GROUP BY ss.id, ss.subject_name
  ORDER BY (SUM(t.teaching_status='Taught')/COUNT(t.id)) ASC
  LIMIT 6
");

$recent_exams = mysqli_query($conn,"SELECT exam_name, start_date, is_active FROM exams ORDER BY id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Academic Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{--blue:#3b82f6;--navy:#1e3a5f;--bg:#eff6ff;--card:#fff;--border:#dbeafe;--muted:#6b7280;--radius:14px;}
*{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--bg);font-family:system-ui,sans-serif;padding:14px;color:#111827;}
.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px;}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:14px 10px;text-align:center;border-top:3px solid var(--blue);}
.stat-val{font-size:26px;font-weight:800;color:var(--navy);}
.stat-lbl{font-size:11px;color:var(--muted);margin-top:3px;}
.stat-sub{font-size:10px;color:var(--muted);margin-top:2px;}
.panel{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:14px;margin-bottom:12px;}
.sec-hdr{font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;display:flex;align-items:center;gap:6px;}
.prog-bar{background:#dbeafe;border-radius:4px;height:8px;overflow:hidden;margin-top:4px;}
.prog-fill{height:100%;background:var(--blue);border-radius:4px;}
.subj-row{padding:7px 0;border-bottom:1px solid var(--border);font-size:12px;}
.subj-row:last-child{border-bottom:none;}
.subj-name{font-weight:600;color:#111827;margin-bottom:4px;display:flex;justify-content:space-between;}
.exam-row{display:flex;align-items:center;gap:8px;padding:7px 0;border-bottom:1px solid var(--border);font-size:13px;}
.exam-row:last-child{border-bottom:none;}
.exam-dot{width:8px;height:8px;border-radius:50%;background:var(--blue);flex-shrink:0;}
.stream-row{display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid var(--border);font-size:13px;}
.stream-row:last-child{border-bottom:none;}
.quick-link{display:flex;align-items:center;gap:8px;padding:9px 12px;background:#eff6ff;border-radius:10px;text-decoration:none;color:var(--navy);font-size:13px;font-weight:600;}
.quick-link:hover{background:#dbeafe;}
@media(max-width:600px){.stat-grid{grid-template-columns:repeat(2,1fr);}.stat-val{font-size:20px;}}
</style>
</head>
<body>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-val"><?=$stats['students']?></div>
    <div class="stat-lbl">Wanafunzi</div>
    <div class="stat-sub">G:<?=$stats['general']?> V:<?=$stats['vocational']?></div>
  </div>
  <div class="stat-card">
    <div class="stat-val"><?=$stats['teachers']?></div>
    <div class="stat-lbl">Walimu</div>
  </div>
  <div class="stat-card">
    <div class="stat-val" style="color:<?=$taught_pct>=70?'#3b82f6':($taught_pct>=30?'#f59e0b':'#ef4444')?>"><?=$taught_pct?>%</div>
    <div class="stat-lbl">Mada Zilizofunzwa</div>
    <div class="stat-sub"><?=intval($tp['taught'])?>/<?=intval($tp['total'])?></div>
  </div>
  <div class="stat-card">
    <div class="stat-val"><?=$stats['exams']?></div>
    <div class="stat-lbl">Mitihani</div>
  </div>
</div>

<?php if (!empty($form_counts)): ?>
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;">
  <?php
  $form_colors = ['Form One'=>'#3b82f6','Form Two'=>'#10b981','Form Three'=>'#f59e0b','Form Four'=>'#ef4444'];
  foreach ($form_counts as $fl => $cnt):
    $color = $form_colors[$fl] ?? '#6b7280';
  ?>
  <div style="display:flex;align-items:center;gap:6px;background:#fff;border:1px solid #dbeafe;border-radius:10px;padding:5px 10px;">
    <span style="width:10px;height:10px;border-radius:50%;background:<?= $color ?>;flex-shrink:0;"></span>
    <span style="font-weight:700;font-size:14px;"><?= (int)$cnt ?></span>
    <span style="font-size:11px;color:#6b7280;"><?= str_replace('Form ','F. ',$fl) ?></span>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<div class="row g-3">
  <div class="col-md-7">

    <!-- Overall teaching progress -->
    <div class="panel">
      <div class="sec-hdr"><i class="bi bi-graph-up-arrow"></i> Maendeleo ya Ufundishaji – <?=$year['year_name']??'—'?> &bull; <?=$term['term_name']??'—'?></div>
      <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
        <span>Jumla ya Mada</span>
        <strong style="color:var(--navy)"><?=$taught_pct?>%</strong>
      </div>
      <div class="prog-bar"><div class="prog-fill" style="width:<?=$taught_pct?>%"></div></div>
      <div style="font-size:11px;color:var(--muted);margin-top:5px;"><?=intval($tp['taught'])?> kati ya <?=intval($tp['total'])?> mada zimefunzwa</div>
    </div>

    <!-- Per-subject progress -->
    <div class="panel">
      <div class="sec-hdr"><i class="bi bi-book-half"></i> Maendeleo kwa Somo (chini kabisa)</div>
      <?php while($s=mysqli_fetch_assoc($subject_progress)):
        $pct = $s['total']>0 ? round($s['taught']/$s['total']*100) : 0;
        $col = $pct>=70?'#3b82f6':($pct>=30?'#f59e0b':'#ef4444');
      ?>
      <div class="subj-row">
        <div class="subj-name"><span><?=htmlspecialchars($s['subject_name'])?></span><span style="color:<?=$col?>;font-weight:800"><?=$pct?>%</span></div>
        <div class="prog-bar"><div class="prog-fill" style="width:<?=$pct?>%;background:<?=$col?>"></div></div>
        <div style="font-size:10px;color:var(--muted);margin-top:3px;"><?=intval($s['taught'])?>/<?=intval($s['total'])?> mada</div>
      </div>
      <?php endwhile;?>
    </div>

    <!-- Stream breakdown -->
    <div class="panel">
      <div class="sec-hdr"><i class="bi bi-people-fill"></i> Mgawanyo wa Mikondo</div>
      <div class="stream-row"><span>General Education</span><strong><?=$stats['general']?></strong></div>
      <div class="stream-row"><span>Technical / Vocational</span><strong><?=$stats['vocational']?></strong></div>
      <div class="stream-row"><span>Jumla</span><strong><?=$stats['students']?></strong></div>
    </div>

  </div>

  <div class="col-md-5">

    <!-- Exams -->
    <div class="panel">
      <div class="sec-hdr"><i class="bi bi-clipboard-check"></i> Mitihani</div>
      <?php while($e=mysqli_fetch_assoc($recent_exams)):?>
      <div class="exam-row">
        <div class="exam-dot" style="background:<?=$e['is_active']?'#3b82f6':'#d1d5db'?>"></div>
        <div style="flex:1;font-size:13px"><?=htmlspecialchars($e['exam_name'])?></div>
        <?php if($e['is_active']):?><span style="background:#dbeafe;color:#1e40af;border-radius:20px;font-size:10px;padding:1px 8px;font-weight:700">Hai</span><?php endif;?>
      </div>
      <?php endwhile;?>
    </div>

    <!-- Quick Links -->
    <div class="panel">
      <div class="sec-hdr"><i class="bi bi-lightning-fill"></i> Viungo vya Haraka</div>
      <div style="display:flex;flex-direction:column;gap:7px;">
        <a href="#" onclick="parent.loadFrame('../admin/teaching_progress_overview.php');return false;" class="quick-link"><i class="bi bi-graph-up-arrow"></i> Maendeleo ya Ufundishaji</a>
        <a href="#" onclick="parent.loadFrame('../admin/view_exam_results.php');return false;" class="quick-link"><i class="bi bi-bar-chart-steps"></i> Angalia Matokeo</a>
        <a href="#" onclick="parent.loadFrame('../admin/manage_periods.php');return false;" class="quick-link"><i class="bi bi-calendar-week"></i> Ratiba ya Masomo</a>
        <a href="#" onclick="parent.loadFrame('../admin/admin_student_report.php');return false;" class="quick-link"><i class="bi bi-file-text"></i> Ripoti za Wanafunzi</a>
        <a href="#" onclick="parent.loadFrame('../admin/exam_management.php');return false;" class="quick-link"><i class="bi bi-pencil-square"></i> Simamia Mitihani</a>
      </div>
    </div>

  </div>
</div>

</body>
</html>
