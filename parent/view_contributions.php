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
    JOIN students s ON s.id = ps.student_id AND s.is_active=1
    WHERE ps.parent_id = '$parent_id'
");

$form_levels = [];
$streams = [];
$child_map = [];
while ($c = mysqli_fetch_assoc($children)) {
    $form_levels[$c['form_level']] = true;
    $streams[$c['stream']] = true;
    $child_map[] = $c;
}

$contributions = [];
if (!empty($form_levels) && !empty($streams)) {
    $where_parts = [];
    foreach ($form_levels as $fl => $_) {
        $fl_e = mysqli_real_escape_string($conn, $fl);
        foreach ($streams as $st => $_) {
            $st_e = mysqli_real_escape_string($conn, $st);
            $where_parts[] = "(c.form_level='$fl_e' AND c.stream='$st_e')";
        }
    }
    $where_sql = implode(" OR ", $where_parts);
    $contributions = mysqli_query($conn, "
        SELECT c.*
        FROM contributions c
        WHERE ($where_sql) OR (c.form_level IS NULL AND c.stream IS NULL)
        ORDER BY c.created_at DESC
    ");
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Michango na Deni</title>
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
.amount{font-size:1.2rem;font-weight:800;color:#059669;}
.empty{text-align:center;padding:40px;color:#9ca3af;}
.type-badge{font-size:0.72rem;}
</style>
</head>
<body>

<h2><i class="bi bi-cash-stack"></i> Michango na Deni</h2>
<p style="color:#6b7280;font-size:0.9rem;margin-bottom:16px;">Taarifa za michango, deni, na vifaa vinavyotakiwa na shule.</p>

<?php if (empty($child_map)): ?>
<div class="alert alert-warning">Huna mtoto yeyote aliyehusishwa na akaunti yako.</div>
<?php elseif (!$contributions || mysqli_num_rows($contributions) == 0): ?>
<div class="empty">
  <i class="bi bi-inbox" style="font-size:2.5rem;display:block;margin-bottom:8px;"></i>
  <p>Hakuna taarifa za michango kwa sasa.</p>
</div>
<?php else: ?>
<?php while ($c = mysqli_fetch_assoc($contributions)):
  $due = $c['due_date'] ? date('d/m/Y', strtotime($c['due_date'])) : 'Hakuna';
  $is_overdue = $c['due_date'] && strtotime($c['due_date']) < strtotime(date('Y-m-d'));
  $type_label = $c['type'] == 'contribution' ? 'Mchango' : ($c['type'] == 'debt' ? 'Deni' : 'Kifaa');
  $type_color = $c['type'] == 'contribution' ? 'bg-success' : ($c['type'] == 'debt' ? 'bg-danger' : 'bg-info');
?>
<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start">
      <div>
        <div class="card-title"><?= htmlspecialchars($c['item_name']) ?></div>
        <div class="card-sub">
          <span class="badge type-badge <?= $type_color ?> me-1"><?= $type_label ?></span>
          <?php if ($c['form_level']): ?><span class="badge bg-secondary me-1"><?= htmlspecialchars($c['form_level']) ?></span><?php endif; ?>
          <?php if ($c['stream']): ?><span class="badge bg-info"><?= htmlspecialchars($c['stream']) ?></span><?php endif; ?>
        </div>
      </div>
      <div class="text-end">
        <?php if ($c['amount'] !== null): ?>
        <div class="amount">TSh <?= number_format($c['amount'], 0) ?></div>
        <?php endif; ?>
        <span class="badge <?= $is_overdue ? 'bg-danger' : 'bg-secondary' ?>" style="font-size:0.72rem;">
          <i class="bi bi-calendar3"></i> <?= $due ?>
        </span>
      </div>
    </div>
    <?php if ($c['description']): ?>
    <div class="card-text mt-2"><?= nl2br(htmlspecialchars($c['description'])) ?></div>
    <?php endif; ?>
    <div class="mt-2" style="font-size:0.78rem;color:#9ca3af;">
      Imechapishwa: <?= date('d/m/Y H:i', strtotime($c['created_at'])) ?>
    </div>
  </div>
</div>
<?php endwhile; ?>
<?php endif; ?>

</body>
</html>
