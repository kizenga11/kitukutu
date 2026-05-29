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

$child_subject_ids = [];
$child_map = [];
while ($c = mysqli_fetch_assoc($children)) {
    $child_map[] = $c;
    $subs = mysqli_query($conn, "SELECT subject_id FROM student_subjects WHERE student_id='{$c['id']}'");
    while ($s = mysqli_fetch_assoc($subs)) {
        $child_subject_ids[(int)$s['subject_id']] = true;
    }
}

$materials = [];
if (!empty($child_subject_ids)) {
    $ids = implode(",", array_keys($child_subject_ids));
    $materials = mysqli_query($conn, "
        SELECT sr.*, sub.subject_name, sub.short_name
        FROM subject_resources sr
        JOIN subjects sub ON sub.id = sr.subject_id
        WHERE sr.subject_id IN ($ids) AND sr.is_active = 1
        ORDER BY sub.subject_name, sr.resource_type, sr.created_at DESC
    ");
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nyenzo za Masomo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{--primary:#065f46;--bg:#f0fdf4;}
body{background:var(--bg);font-family:system-ui,-apple-system,sans-serif;padding:16px;}
h2{color:var(--primary);font-weight:700;font-size:1.2rem;}
.subj-group{margin-bottom:16px;}
.subj-hdr{background:#065f46;color:#fff;padding:8px 14px;border-radius:10px 10px 0 0;font-weight:700;font-size:0.9rem;}
.resource-card{background:#fff;border:1px solid #e5e7eb;border-top:none;padding:10px 14px;}
.resource-card:last-child{border-radius:0 0 10px 10px;}
.resource-card+.resource-card{border-top:1px solid #e5e7eb;}
.resource-title{font-weight:600;color:#111827;}
.resource-desc{font-size:0.85rem;color:#6b7280;margin-top:2px;}
.resource-meta{font-size:0.78rem;color:#9ca3af;margin-top:4px;}
.resource-link{font-size:0.82rem;}
.empty{text-align:center;padding:40px;color:#9ca3af;}
</style>
</head>
<body>

<h2><i class="bi bi-journal-richtext"></i> Nyenzo za Masomo</h2>
<p style="color:#6b7280;font-size:0.9rem;margin-bottom:16px;">Vitabu, maelezo, na nyenzo mbalimbali za kusaidia masomo ya mtoto wako.</p>

<?php if (empty($child_map)): ?>
<div class="alert alert-warning">Huna mtoto yeyote aliyehusishwa na akaunti yako.</div>
<?php elseif (!$materials || mysqli_num_rows($materials) == 0): ?>
<div class="empty">
  <i class="bi bi-inbox" style="font-size:2.5rem;display:block;margin-bottom:8px;"></i>
  <p>Hakuna nyenzo za masomo kwa sasa.</p>
</div>
<?php else:
  $current_subj = null;
  while ($r = mysqli_fetch_assoc($materials)):
    if ($current_subj !== $r['subject_name']):
      if ($current_subj !== null) echo "</div>";
      $current_subj = $r['subject_name'];
?>
<div class="subj-group">
  <div class="subj-hdr"><i class="bi bi-book"></i> <?= htmlspecialchars($r['subject_name']) ?> (<?= htmlspecialchars($r['short_name']) ?>)</div>
<?php endif; ?>
  <div class="resource-card">
    <div class="d-flex justify-content-between align-items-start">
      <div class="resource-title">
        <?php
          $type_icon = $r['resource_type'] == 'book' ? 'bi-book-fill' : ($r['resource_type'] == 'note' ? 'bi-file-text-fill' : ($r['resource_type'] == 'reference' ? 'bi-pencil-square' : 'bi-folder'));
          $type_color = $r['resource_type'] == 'book' ? '#065f46' : ($r['resource_type'] == 'note' ? '#2563eb' : ($r['resource_type'] == 'reference' ? '#ca8a04' : '#6b7280'));
        ?>
        <i class="bi <?= $type_icon ?>" style="color:<?= $type_color ?>"></i>
        <?= htmlspecialchars($r['title']) ?>
        <span class="badge bg-secondary" style="font-size:0.65rem;vertical-align:middle;"><?= htmlspecialchars(ucfirst($r['resource_type'])) ?></span>
      </div>
      <?php if ($r['doc_type'] == 'file' && $r['file_path']): ?>
        <a href="../uploads/resources/<?= htmlspecialchars($r['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-success resource-link"><i class="bi bi-download"></i> Fungua</a>
      <?php elseif ($r['doc_type'] == 'link' && $r['link_url']): ?>
        <a href="<?= htmlspecialchars($r['link_url']) ?>" target="_blank" class="btn btn-sm btn-outline-primary resource-link"><i class="bi bi-box-arrow-up-right"></i> Tembelea</a>
      <?php endif; ?>
    </div>
    <?php if ($r['description']): ?>
    <div class="resource-desc"><?= nl2br(htmlspecialchars($r['description'])) ?></div>
    <?php endif; ?>
    <?php if ($r['form_level']): ?>
    <div class="resource-meta"><i class="bi bi-layers"></i> <?= htmlspecialchars($r['form_level']) ?></div>
    <?php endif; ?>
  </div>
<?php endwhile; ?>
  </div>
<?php endif; ?>

</body>
</html>
