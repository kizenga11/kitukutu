<?php
session_start();
include "../includes/config.php";

if (!isset($_SESSION['parent_id'])) {
    header("Location: login.php");
    exit();
}

$parent_id = intval($_SESSION['parent_id']);

$children = mysqli_query($conn, "
    SELECT s.id, s.first_name, s.second_name, s.last_name, s.form_level, s.stream
    FROM parent_students ps
    JOIN students s ON s.id = ps.student_id
    WHERE ps.parent_id = '$parent_id'
    ORDER BY s.first_name
");

$child_list = [];
while ($c = mysqli_fetch_assoc($children)) {
    $child_list[] = $c;
}

// Upcoming assignments count
$total_assignments = 0;
if (!empty($child_list)) {
    $form_levels = array_unique(array_column($child_list, 'form_level'));
    $streams = array_unique(array_column($child_list, 'stream'));
    $where = [];
    foreach ($form_levels as $f) {
        foreach ($streams as $s) {
            $f_esc = mysqli_real_escape_string($conn, $f);
            $s_esc = mysqli_real_escape_string($conn, $s);
            $where[] = "(form_level='$f_esc' AND stream='$s_esc')";
        }
    }
    if (!empty($where)) {
        $where_sql = implode(" OR ", $where);
        $total_assignments = intval(mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT COUNT(*) c FROM assignments WHERE ($where_sql) AND (due_date >= CURDATE() OR due_date IS NULL)
        "))['c'] ?? 0);
    }
}

$total_exams = 0;
if (!empty($child_list)) {
    $ids = array_column($child_list, 'id');
    $ids_str = implode(",", $ids);
    $total_exams = intval(mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(DISTINCT exam_id) c FROM exam_results_summary WHERE student_id IN ($ids_str)
    "))['c'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nyumbani · Mzazi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{--primary:#065f46;--primary-light:#d1fae5;--bg:#f0fdf4;}
body{background:var(--bg);font-family:system-ui,-apple-system,sans-serif;padding:20px;}
h2{color:var(--primary);font-weight:700;font-size:1.3rem;}
.card{border-radius:12px;border:1px solid #e5e7eb;transition:box-shadow .2s;}
.card:hover{box-shadow:0 4px 12px rgba(0,0,0,0.06);}
.stat-card{text-align:center;padding:20px;}
.stat-card .icon{font-size:2rem;color:var(--primary);}
.stat-card .num{font-size:1.8rem;font-weight:800;color:#111827;}
.stat-card .lbl{font-size:0.85rem;color:#6b7280;font-weight:600;}
.child-card{padding:16px;display:flex;align-items:center;gap:14px;}
.child-card .avatar{width:44px;height:44px;border-radius:50%;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1rem;flex-shrink:0;}
.child-card .info{flex:1;}
.child-card .name{font-weight:700;color:#111827;font-size:1rem;}
.child-card .detail{font-size:0.82rem;color:#6b7280;}
.child-card .btn{font-size:0.82rem;border-radius:8px;}
</style>
</head>
<body>

<h2><i class="bi bi-house-heart-fill"></i> Nyumbani</h2>
<p style="color:#6b7280;font-size:0.9rem;margin-bottom:20px;">Karibu kwenye mfumo wa wazazi wa Amali Kitukutu. Hapa unaweza kuona matokeo, kazi za nyumbani, na taarifa mbalimbali za mtoto wako.</p>

<?php if (empty($child_list)): ?>
<div class="alert alert-warning">Huna mtoto yeyote aliyehusishwa na akaunti yako. Tafadhali wasiliana na shule.</div>
<?php else: ?>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card stat-card">
      <div class="icon"><i class="bi bi-people-fill"></i></div>
      <div class="num"><?= count($child_list) ?></div>
      <div class="lbl">Watoto</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card stat-card">
      <div class="icon"><i class="bi bi-file-text"></i></div>
      <div class="num"><?= $total_exams ?></div>
      <div class="lbl">Matokeo ya Mitihani</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card stat-card">
      <div class="icon"><i class="bi bi-journal-check"></i></div>
      <div class="num"><?= $total_assignments ?></div>
      <div class="lbl">Kazi za Nyumbani</div>
    </div>
  </div>
</div>

<h5 style="color:var(--primary);font-weight:700;margin-bottom:12px;"><i class="bi bi-people"></i> Watoto Wako</h5>
<div class="row g-3">
  <?php foreach ($child_list as $ch): ?>
  <div class="col-md-6">
    <div class="card child-card">
      <div class="avatar"><?= strtoupper(substr($ch['first_name'],0,1).substr($ch['last_name']??$ch['first_name'],0,1)) ?></div>
      <div class="info">
        <div class="name"><?= htmlspecialchars($ch['first_name'].' '.($ch['second_name']?$ch['second_name'].' ':'').$ch['last_name']) ?></div>
        <div class="detail"><?= htmlspecialchars($ch['form_level']) ?> &bull; <?= htmlspecialchars($ch['stream']) ?></div>
      </div>
      <a href="view_results.php?student_id=<?= $ch['id'] ?>" class="btn btn-sm" style="background:var(--primary);color:#fff;"><i class="bi bi-eye"></i> Matokeo</a>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php endif; ?>

</body>
</html>
