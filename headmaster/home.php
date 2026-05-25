<?php
session_start();
include "../includes/config.php";
if(!isset($_SESSION['admin_id'])||($_SESSION['user_role']??'')!=='headmaster'){header("Location: ../login.php");exit();}

$year  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT year_name FROM academic_years WHERE is_active=1 LIMIT 1")) ?? [];
$term  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT term_name FROM terms WHERE is_active=1 LIMIT 1")) ?? [];
$year_id = intval(mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM academic_years WHERE is_active=1 LIMIT 1"))['id'] ?? 0);
$term_id = intval(mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM terms WHERE is_active=1 LIMIT 1"))['id'] ?? 0);

$stats = [
  'students'    => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM students"))['c'] ?? 0,
  'teachers'    => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM teachers"))['c'] ?? 0,
  'exams'       => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM exams WHERE is_active=1"))['c'] ?? 0,
  'pending'     => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM admissions WHERE status='Pending'"))['c'] ?? 0,
  'general'     => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM students WHERE stream='General'"))['c'] ?? 0,
  'vocational'  => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM students WHERE stream='Vocational'"))['c'] ?? 0,
];

$form_counts = [];
$fc_q = mysqli_query($conn, "SELECT form_level, COUNT(*) as total FROM students GROUP BY form_level ORDER BY form_level");
if ($fc_q) while ($fc_r = mysqli_fetch_assoc($fc_q)) $form_counts[$fc_r['form_level']] = $fc_r['total'];

$tp = mysqli_fetch_assoc(mysqli_query($conn,"
  SELECT COUNT(t.id) total, SUM(t.teaching_status='Taught') taught
  FROM subject_settings ss JOIN topics t ON t.subject_setting_id=ss.id
  WHERE ss.academic_year_id=$year_id AND ss.term_id=$term_id AND ss.is_active=1
")) ?? ['total'=>0,'taught'=>0];
$taught_pct = $tp['total']>0?round($tp['taught']/$tp['total']*100):0;

$recent_exams = mysqli_query($conn,"SELECT exam_name,start_date,is_active FROM exams ORDER BY id DESC LIMIT 5");
$recent_adm   = mysqli_query($conn,"SELECT first_name,last_name,entry_level,status,created_at FROM admissions ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{--green:#10b981;--navy:#064e3b;--bg:#f0fdf4;--card:#fff;--border:#d1fae5;--muted:#6b7280;--radius:14px;}
*{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--bg);font-family:system-ui,sans-serif;padding:14px;color:#111827;}
.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px;}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:14px 10px;text-align:center;border-top:3px solid var(--green);}
.stat-val{font-size:26px;font-weight:800;color:var(--navy);}
.stat-lbl{font-size:11px;color:var(--muted);margin-top:3px;}
.stat-sub{font-size:10px;color:var(--muted);margin-top:2px;}
.panel{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:14px;margin-bottom:12px;}
.sec-hdr{font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;display:flex;align-items:center;gap:6px;}
.prog-bar{background:#d1fae5;border-radius:4px;height:8px;overflow:hidden;margin-top:6px;}
.prog-fill{height:100%;background:var(--green);border-radius:4px;}
.stream-row{display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid var(--border);font-size:13px;}
.stream-row:last-child{border-bottom:none;}
.badge-status{display:inline-block;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700;}
.s-pending{background:#fef3c7;color:#92400e;}
.s-approved{background:#d1fae5;color:#065f46;}
.s-rejected{background:#fee2e2;color:#991b1b;}
.exam-row{display:flex;align-items:center;gap:8px;padding:7px 0;border-bottom:1px solid var(--border);font-size:13px;}
.exam-row:last-child{border-bottom:none;}
.exam-dot{width:8px;height:8px;border-radius:50%;background:var(--green);flex-shrink:0;}
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
    <div class="stat-val" style="color:<?=$taught_pct>=70?'#10b981':($taught_pct>=30?'#f59e0b':'#ef4444')?>"><?=$taught_pct?>%</div>
    <div class="stat-lbl">Mada Zilizofunzwa</div>
    <div class="stat-sub"><?=intval($tp['taught'])?>/<?=intval($tp['total'])?></div>
  </div>
  <div class="stat-card">
    <div class="stat-val" style="color:<?=$stats['pending']>0?'#ef4444':'#10b981'?>"><?=$stats['pending']?></div>
    <div class="stat-lbl">Maombi Pending</div>
  </div>
</div>

<?php if (!empty($form_counts)): ?>
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;">
  <?php
  $form_colors = ['Form One'=>'#3b82f6','Form Two'=>'#10b981','Form Three'=>'#f59e0b','Form Four'=>'#ef4444'];
  foreach ($form_counts as $fl => $cnt):
    $color = $form_colors[$fl] ?? '#6b7280';
  ?>
  <div style="display:flex;align-items:center;gap:6px;background:#fff;border:1px solid #d1fae5;border-radius:10px;padding:5px 10px;">
    <span style="width:10px;height:10px;border-radius:50%;background:<?= $color ?>;flex-shrink:0;"></span>
    <span style="font-weight:700;font-size:14px;"><?= (int)$cnt ?></span>
    <span style="font-size:11px;color:#6b7280;"><?= str_replace('Form ','F. ',$fl) ?></span>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-md-7">
    <!-- Teaching progress -->
    <div class="panel">
      <div class="sec-hdr"><i class="bi bi-graph-up-arrow"></i> Maendeleo ya Ufundishaji</div>
      <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
        <span><?=$year['year_name']??'—'?> · <?=$term['term_name']??'—'?></span>
        <strong style="color:var(--navy)"><?=$taught_pct?>%</strong>
      </div>
      <div class="prog-bar"><div class="prog-fill" style="width:<?=$taught_pct?>%"></div></div>
      <div style="font-size:11px;color:var(--muted);margin-top:5px;"><?=intval($tp['taught'])?> kati ya <?=intval($tp['total'])?> mada zimefunzwa</div>
    </div>

    <!-- Streams breakdown -->
    <div class="panel">
      <div class="sec-hdr"><i class="bi bi-people-fill"></i> Mgawanyo wa Mikondo</div>
      <div class="stream-row"><span>General Education</span><strong><?=$stats['general']?></strong></div>
      <div class="stream-row"><span>Technical / Vocational</span><strong><?=$stats['vocational']?></strong></div>
      <div class="stream-row"><span>Jumla</span><strong><?=$stats['students']?></strong></div>
    </div>

    <!-- Recent Admissions -->
    <div class="panel">
      <div class="sec-hdr"><i class="bi bi-journal-check"></i> Maombi ya Hivi Karibuni</div>
      <?php while($a=mysqli_fetch_assoc($recent_adm)):
        $sc=$a['status']==='Pending'?'s-pending':($a['status']==='Approved'?'s-approved':'s-rejected');?>
      <div class="exam-row">
        <div class="exam-dot" style="background:<?=$a['status']==='Pending'?'#f59e0b':($a['status']==='Approved'?'#10b981':'#ef4444')?>"></div>
        <div style="flex:1"><?=htmlspecialchars($a['first_name'].' '.$a['last_name'])?> <span style="font-size:11px;color:var(--muted)">(<?=$a['entry_level']?>)</span></div>
        <span class="badge-status <?=$sc?>"><?=$a['status']?></span>
      </div>
      <?php endwhile;?>
    </div>
  </div>

  <div class="col-md-5">
    <!-- Active Exams -->
    <div class="panel">
      <div class="sec-hdr"><i class="bi bi-clipboard-check"></i> Mitihani</div>
      <?php while($e=mysqli_fetch_assoc($recent_exams)):?>
      <div class="exam-row">
        <div class="exam-dot" style="background:<?=$e['is_active']?'#10b981':'#d1d5db'?>"></div>
        <div style="flex:1;font-size:13px"><?=htmlspecialchars($e['exam_name'])?></div>
        <?php if($e['is_active']):?><span class="badge-status" style="background:#d1fae5;color:#065f46">Active</span><?php endif;?>
      </div>
      <?php endwhile;?>
    </div>

    <!-- Quick Links -->
    <div class="panel">
      <div class="sec-hdr"><i class="bi bi-lightning-fill"></i> Viungo vya Haraka</div>
      <div style="display:flex;flex-direction:column;gap:7px;">
        <a href="#" onclick="parent.loadFrame('../admin/view_exam_results.php');return false;" style="display:flex;align-items:center;gap:8px;padding:9px 12px;background:#f0fdf4;border-radius:10px;text-decoration:none;color:#064e3b;font-size:13px;font-weight:600;"><i class="bi bi-bar-chart-steps"></i> Angalia Matokeo</a>
        <a href="#" onclick="parent.loadFrame('../admin/admissions.php');return false;" style="display:flex;align-items:center;gap:8px;padding:9px 12px;background:#f0fdf4;border-radius:10px;text-decoration:none;color:#064e3b;font-size:13px;font-weight:600;"><i class="bi bi-journal-check"></i> Maombi ya Kujiunga</a>
        <a href="#" onclick="parent.loadFrame('../admin/teaching_progress_overview.php');return false;" style="display:flex;align-items:center;gap:8px;padding:9px 12px;background:#f0fdf4;border-radius:10px;text-decoration:none;color:#064e3b;font-size:13px;font-weight:600;"><i class="bi bi-graph-up-arrow"></i> Maendeleo ya Ufundishaji</a>
        <a href="#" onclick="parent.loadFrame('../admin/admin_student_report.php');return false;" style="display:flex;align-items:center;gap:8px;padding:9px 12px;background:#f0fdf4;border-radius:10px;text-decoration:none;color:#064e3b;font-size:13px;font-weight:600;"><i class="bi bi-file-text"></i> Ripoti za Wanafunzi</a>
      </div>
    </div>
  </div>
</div>

</body>
</html>
