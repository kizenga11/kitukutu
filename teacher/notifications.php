<?php
session_start();
include "../includes/config.php";
if (!isset($_SESSION['teacher_id'])) { header("Location: ../login.php"); exit(); }
$teacher_id = intval($_SESSION['teacher_id']);

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    // Personal notifications
    mysqli_query($conn,"UPDATE notifications SET id=id WHERE teacher_id=$teacher_id"); // just trigger
    // Insert reads for broadcast notifications not yet read
    $bcast = mysqli_query($conn,"SELECT id FROM notifications WHERE teacher_id IS NULL");
    while ($n = mysqli_fetch_assoc($bcast)) {
        mysqli_query($conn,"INSERT IGNORE INTO notification_reads(notification_id,teacher_id) VALUES({$n['id']},$teacher_id)");
    }
    // For personal notifications, mark as read via notification_reads
    $personal = mysqli_query($conn,"SELECT id FROM notifications WHERE teacher_id=$teacher_id");
    while ($n = mysqli_fetch_assoc($personal)) {
        mysqli_query($conn,"INSERT IGNORE INTO notification_reads(notification_id,teacher_id) VALUES({$n['id']},$teacher_id)");
    }
    header("Location: notifications.php");
    exit();
}

// Mark single as read
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $nid = intval($_GET['read']);
    mysqli_query($conn,"INSERT IGNORE INTO notification_reads(notification_id,teacher_id) VALUES($nid,$teacher_id)");
}

// Fetch notifications (personal + broadcast, newest first)
$notifs = mysqli_query($conn,"
    SELECT n.*, a.email AS admin_email,
           IF(nr.teacher_id IS NOT NULL, 1, 0) AS is_read
    FROM notifications n
    LEFT JOIN admins a ON a.id=n.admin_id
    LEFT JOIN notification_reads nr ON nr.notification_id=n.id AND nr.teacher_id=$teacher_id
    WHERE n.teacher_id=$teacher_id OR n.teacher_id IS NULL
    ORDER BY n.created_at DESC
    LIMIT 100
");

$unread = 0;
$all = [];
while ($r = mysqli_fetch_assoc($notifs)) { $all[] = $r; if (!$r['is_read']) $unread++; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Notifications</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body{background:#f3f4f6;font-family:system-ui;padding:14px;}
.notif-card{border:1px solid #e5e7eb;border-radius:12px;background:#fff;padding:12px 14px;margin-bottom:8px;transition:.15s;cursor:pointer;}
.notif-card.unread{border-left:4px solid #6366f1;background:#fefefe;}
.notif-card:hover{box-shadow:0 2px 8px rgba(0,0,0,0.07);}
.notif-icon{width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;}
.ic-system{background:#ede9fe;color:#6366f1;}
.ic-admin{background:#dbeafe;color:#1d4ed8;}
.meta{font-size:11px;color:#9ca3af;}
@media(max-width:480px){
  body{padding:10px;}
  .notif-card{padding:10px 12px;}
  .notif-icon{width:34px;height:34px;font-size:16px;}
  .page-top{flex-wrap:wrap;gap:6px!important;}
  .page-top h5{font-size:15px;}
}
</style>
</head>
<body>

<div class="d-flex align-items-center justify-content-between mb-3 page-top">
  <h5 class="mb-0 fw-bold"><i class="bi bi-bell text-primary me-2"></i>Notifications
    <?php if ($unread): ?><span class="badge bg-danger ms-1" style="font-size:11px"><?= $unread ?></span><?php endif; ?>
  </h5>
  <?php if ($unread): ?>
  <a href="?mark_all_read=1" class="btn btn-sm btn-outline-secondary">Mark all read</a>
  <?php endif; ?>
</div>

<?php if (empty($all)): ?>
<div class="text-center text-muted py-5">
  <i class="bi bi-bell-slash" style="font-size:2.5rem;display:block;margin-bottom:8px;color:#d1d5db"></i>
  No notifications yet.
</div>
<?php else: ?>
<?php foreach ($all as $n):
  $isRead = $n['is_read'];
  $icon   = $n['type'] === 'admin' ? 'bi-person-badge' : 'bi-robot';
  $icCls  = $n['type'] === 'admin' ? 'ic-admin' : 'ic-system';
  $from   = $n['type'] === 'admin' ? htmlspecialchars($n['admin_email'] ?? 'Admin') : 'System';
  $time   = date('d M Y, H:i', strtotime($n['created_at']));
?>
<div class="notif-card <?= $isRead ? '' : 'unread' ?>" onclick="markRead(<?= $n['id'] ?>, this)">
  <div class="d-flex gap-3 align-items-start">
    <div class="notif-icon <?= $icCls ?>"><i class="bi <?= $icon ?>"></i></div>
    <div class="flex-grow-1">
      <div class="fw-semibold" style="font-size:13px"><?= htmlspecialchars($n['title']) ?>
        <?php if (!$isRead): ?><span class="badge bg-primary ms-1" style="font-size:10px">New</span><?php endif; ?>
      </div>
      <div class="text-muted mt-1" style="font-size:12px;line-height:1.5"><?= nl2br(htmlspecialchars($n['message'])) ?></div>
      <div class="meta mt-1"><i class="bi bi-person me-1"></i><?= $from ?> &bull; <i class="bi bi-clock me-1"></i><?= $time ?></div>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<script>
function markRead(id, el) {
  if (el.classList.contains('unread')) {
    fetch('notifications.php?read=' + id);
    el.classList.remove('unread');
    el.querySelector('.badge.bg-primary')?.remove();
    const badge = document.querySelector('h5 .badge.bg-danger');
    if (badge) {
      let n = parseInt(badge.textContent) - 1;
      if (n <= 0) badge.remove();
      else badge.textContent = n;
    }
  }
}
</script>
</body>
</html>
