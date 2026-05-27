<?php
session_start();
include "../includes/config.php";

if (!isset($_SESSION['parent_id'])) {
    header("Location: login.php");
    exit();
}

$parent_id = intval($_SESSION['parent_id']);

$children = mysqli_query($conn, "
    SELECT s.id, s.first_name, s.last_name, s.form_level, s.stream
    FROM parent_students ps
    JOIN students s ON s.id = ps.student_id
    WHERE ps.parent_id = '$parent_id'
");

$child_subject_ids = [];
$form_levels = [];
$streams = [];
$child_map = [];
while ($c = mysqli_fetch_assoc($children)) {
    $child_map[] = $c;
    $form_levels[$c['form_level']] = true;
    $streams[$c['stream']] = true;
    $subs = mysqli_query($conn, "SELECT subject_id FROM student_subjects WHERE student_id='{$c['id']}'");
    while ($s = mysqli_fetch_assoc($subs)) {
        $child_subject_ids[(int)$s['subject_id']] = true;
    }
}

$assignments = [];
if (!empty($child_map)) {
    $where_parts = [];
    foreach ($form_levels as $fl => $_) {
        $fl_e = mysqli_real_escape_string($conn, $fl);
        foreach ($streams as $st => $_) {
            $st_e = mysqli_real_escape_string($conn, $st);
            $where_parts[] = "(a.form_level='$fl_e' AND a.stream='$st_e')";
        }
    }
    $where_sql = implode(" OR ", $where_parts);

    // Build subject filter: only subjects this child is enrolled in
    $subj_filter = "";
    if (!empty($child_subject_ids)) {
        $ids = implode(",", array_keys($child_subject_ids));
        // Allow null subject_id (assignments for all subjects) OR subjects the child takes
        $subj_filter = "AND (a.subject_id IS NULL OR a.subject_id IN ($ids))";
    }

    $assignments = mysqli_query($conn, "
        SELECT a.*, s.short_name AS subject_name
        FROM assignments a
        LEFT JOIN subjects s ON s.id = a.subject_id
        WHERE ($where_sql) OR (a.form_level IS NULL AND a.stream IS NULL)
        $subj_filter
        ORDER BY a.created_at DESC
    ");
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kazi za Nyumbani</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{--primary:#065f46;--bg:#f0fdf4;}
body{background:var(--bg);font-family:system-ui,-apple-system,sans-serif;padding:16px;}
h2{color:var(--primary);font-weight:700;font-size:1.2rem;}
.card{border-radius:12px;border:1px solid #e5e7eb;margin-bottom:12px;}
.card-body{padding:14px 16px;}
.card-title{font-weight:700;font-size:1rem;color:#111827;}
.card-sub{font-size:0.8rem;color:#6b7280;margin-bottom:6px;}
.card-text{font-size:0.9rem;color:#374151;}
.badge-due{font-size:0.75rem;}
.empty{text-align:center;padding:40px;color:#9ca3af;}
</style>
</head>
<body>

<h2><i class="bi bi-journal-check"></i> Kazi za Nyumbani</h2>
<p style="color:#6b7280;font-size:0.9rem;margin-bottom:16px;">Kazi mbalimbali zilizotumwa na waalimu kwa ajili ya wanafunzi.</p>

<?php if (empty($child_map)): ?>
<div class="alert alert-warning">Huna mtoto yeyote aliyehusishwa na akaunti yako.</div>
<?php elseif (!$assignments || mysqli_num_rows($assignments) == 0): ?>
<div class="empty">
  <i class="bi bi-inbox" style="font-size:2.5rem;display:block;margin-bottom:8px;"></i>
  <p>Hakuna kazi za nyumbani kwa sasa.</p>
</div>
<?php else: ?>
<?php while ($a = mysqli_fetch_assoc($assignments)):
  $due = $a['due_date'] ? date('d/m/Y', strtotime($a['due_date'])) : 'Hakuna';
  $is_overdue = $a['due_date'] && strtotime($a['due_date']) < strtotime(date('Y-m-d'));
?>
<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start">
      <div>
        <div class="card-title"><?= htmlspecialchars($a['title']) ?></div>
        <div class="card-sub">
          <?php if ($a['subject_name']): ?><span class="badge bg-primary me-1"><?= htmlspecialchars($a['subject_name']) ?></span><?php endif; ?>
          <?php if ($a['form_level']): ?><span class="badge bg-secondary me-1"><?= htmlspecialchars($a['form_level']) ?></span><?php endif; ?>
          <?php if ($a['stream']): ?><span class="badge bg-info"><?= htmlspecialchars($a['stream']) ?></span><?php endif; ?>
        </div>
      </div>
      <span class="badge badge-due <?= $is_overdue ? 'bg-danger' : 'bg-success' ?>">
        <i class="bi bi-calendar3"></i> <?= $due ?>
        <?php if ($is_overdue): ?><br><small>Imechelewa</small><?php endif; ?>
      </span>
    </div>
    <?php if ($a['description']): ?>
    <div class="card-text mt-2"><?= nl2br(htmlspecialchars($a['description'])) ?></div>
    <?php endif; ?>
    <div class="mt-2" style="font-size:0.78rem;color:#9ca3af;">
      Imechapishwa: <?= date('d/m/Y H:i', strtotime($a['created_at'])) ?>
    </div>
  </div>
</div>
<?php endwhile; ?>
<?php endif; ?>

</body>
</html>
