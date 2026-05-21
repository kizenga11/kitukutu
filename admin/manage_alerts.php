<?php
session_start();
include "../includes/config.php";
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }
$admin_id = intval($_SESSION['admin_id']);

// Handle send alert
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_alert'])) {
    $title    = mysqli_real_escape_string($conn, trim($_POST['title']));
    $message  = mysqli_real_escape_string($conn, trim($_POST['message']));
    $target   = $_POST['target']; // 'all' or specific teacher id
    $sent = 0;

    if ($title && $message) {
        if ($target === 'all') {
            // Broadcast: teacher_id = NULL
            mysqli_query($conn,"INSERT INTO notifications(teacher_id,admin_id,type,title,message) VALUES(NULL,$admin_id,'admin','$title','$message')");
            $sent = 1;
        } else {
            $tid = intval($target);
            if ($tid > 0) {
                mysqli_query($conn,"INSERT INTO notifications(teacher_id,admin_id,type,title,message) VALUES($tid,$admin_id,'admin','$title','$message')");
                $sent = 1;
            }
        }
    }
    if ($sent) header("Location: manage_alerts.php?sent=1");
    else        header("Location: manage_alerts.php?error=1");
    exit();
}

// Handle delete alert
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del = intval($_GET['delete']);
    mysqli_query($conn,"DELETE FROM notification_reads WHERE notification_id=$del");
    mysqli_query($conn,"DELETE FROM notifications WHERE id=$del");
    header("Location: manage_alerts.php?deleted=1");
    exit();
}

// Auto-generate system alerts for teachers with no topics
$year_id = intval(mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM academic_years WHERE is_active=1 LIMIT 1"))['id'] ?? 0);
$term_id = intval(mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM terms WHERE is_active=1 LIMIT 1"))['id'] ?? 0);

if (isset($_GET['auto_check'])) {
    // Find teachers assigned subjects but have 0 topics
    $at_risk = mysqli_query($conn,"
        SELECT DISTINCT ss.teacher_id, CONCAT(t.first_name,' ',t.last_name) AS teacher_name
        FROM subject_settings ss
        JOIN teachers t ON t.id=ss.teacher_id
        WHERE ss.academic_year_id=$year_id AND ss.term_id=$term_id AND ss.is_active=1
          AND ss.teacher_id NOT IN (
            SELECT DISTINCT ss2.teacher_id FROM subject_settings ss2
            JOIN topics tp ON tp.subject_setting_id=ss2.id
            WHERE ss2.academic_year_id=$year_id AND ss2.term_id=$term_id
          )
    ");
    $count = 0;
    while ($r = mysqli_fetch_assoc($at_risk)) {
        $tid = $r['teacher_id'];
        // Check if we already sent this alert recently (within 7 days)
        $existing = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM notifications WHERE teacher_id=$tid AND type='system' AND title LIKE '%topics%' AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) LIMIT 1"));
        if (!$existing) {
            $name = mysqli_real_escape_string($conn, $r['teacher_name']);
            mysqli_query($conn,"INSERT INTO notifications(teacher_id,type,title,message) VALUES($tid,'system','Action Required: Topics Not Added','Dear teacher, you have been assigned subjects for the current term but have not added any topics yet. Please log in and update your teaching progress to help track syllabus coverage.')");
            $count++;
        }
    }
    header("Location: manage_alerts.php?auto_sent=$count");
    exit();
}

// Fetch all notifications
$alerts = mysqli_query($conn,"
    SELECT n.*,
           a.email AS admin_email,
           IF(n.teacher_id IS NULL, 'All Teachers', CONCAT(t.first_name,' ',t.last_name)) AS target_name,
           (SELECT COUNT(*) FROM notification_reads nr WHERE nr.notification_id=n.id) AS read_count
    FROM notifications n
    LEFT JOIN admins a ON a.id=n.admin_id
    LEFT JOIN teachers t ON t.id=n.teacher_id
    ORDER BY n.created_at DESC
    LIMIT 200
");
if (!$alerts) die("Query error: ".mysqli_error($conn));

// Teachers for dropdown
$teachers = mysqli_query($conn,"SELECT id, CONCAT(first_name,' ',last_name) AS name FROM teachers ORDER BY first_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Manage Alerts</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body{background:#f4f7fc;font-family:system-ui;padding:14px;}
.alert-card{border:1px solid #e5e7eb;border-radius:12px;background:#fff;padding:12px 14px;margin-bottom:8px;}
.ic-system{background:#ede9fe;color:#6366f1;}
.ic-admin{background:#dbeafe;color:#1d4ed8;}
.notif-icon{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.meta{font-size:11px;color:#9ca3af;}
@media(max-width:600px){
  .page-hdr{flex-direction:column;align-items:flex-start!important;gap:8px;}
  .page-hdr .d-flex{width:100%;}
  .page-hdr .btn{flex:1;font-size:11px;padding:6px 8px;}
  .alert-card .d-flex.gap-3{gap:10px!important;}
  .notif-icon{width:32px;height:32px;font-size:15px;}
}
</style>
</head>
<body>

<div class="d-flex align-items-center justify-content-between mb-3 page-hdr">
  <h5 class="mb-0 fw-bold"><i class="bi bi-megaphone text-warning me-2"></i>Manage Alerts & Notifications</h5>
  <div class="d-flex gap-2 flex-wrap">
    <a href="?auto_check=1" class="btn btn-sm btn-outline-warning" onclick="return confirm('Run auto-check for teachers with no topics?')">
      <i class="bi bi-robot me-1"></i>Auto-Check Teachers
    </a>
    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newAlertModal">
      <i class="bi bi-plus-lg me-1"></i>New Alert
    </button>
  </div>
</div>

<?php if (isset($_GET['sent'])): ?>
<div class="alert alert-success alert-dismissible py-2"><i class="bi bi-check-circle me-1"></i>Alert sent successfully. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php elseif (isset($_GET['auto_sent'])): ?>
<div class="alert alert-info alert-dismissible py-2"><i class="bi bi-robot me-1"></i><?= intval($_GET['auto_sent']) ?> system alert(s) sent to teachers with no topics. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php elseif (isset($_GET['deleted'])): ?>
<div class="alert alert-secondary alert-dismissible py-2">Alert deleted. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php elseif (isset($_GET['error'])): ?>
<div class="alert alert-danger alert-dismissible py-2">Failed to send alert. Check title and message. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="bg-info bg-opacity-10 border border-info-subtle rounded-3 p-2 mb-3" style="font-size:12px">
  <i class="bi bi-info-circle text-info me-1"></i>
  <b>Auto-Check</b> scans teachers assigned to subjects but with no topics added, and sends them a system reminder alert automatically.
</div>

<!-- Alert list -->
<?php
$count = 0;
while ($n = mysqli_fetch_assoc($alerts)):
  $count++;
  $icon = $n['admin_id'] ? 'bi-person-badge' : 'bi-robot';
  $icCls = $n['admin_id'] ? 'ic-admin' : 'ic-system';
  $from = $n['admin_id'] ? htmlspecialchars($n['admin_email'] ?? 'Admin') : 'System';
  $time = date('d M Y, H:i', strtotime($n['created_at']));
?>
<div class="alert-card">
  <div class="d-flex gap-3 align-items-start">
    <div class="notif-icon <?= $icCls ?>"><i class="bi <?= $icon ?>"></i></div>
    <div class="flex-grow-1">
      <div class="d-flex align-items-center gap-2">
        <span class="fw-semibold" style="font-size:13px"><?= htmlspecialchars($n['title']) ?></span>
        <span class="badge <?= $n['teacher_id'] ? 'bg-primary' : 'bg-warning text-dark' ?>" style="font-size:10px">
          → <?= htmlspecialchars($n['target_name']) ?>
        </span>
      </div>
      <div class="text-muted mt-1" style="font-size:12px"><?= nl2br(htmlspecialchars($n['message'])) ?></div>
      <div class="meta mt-1">
        <i class="bi bi-person me-1"></i>From: <?= $from ?>
        &bull; <i class="bi bi-clock me-1"></i><?= $time ?>
        &bull; <i class="bi bi-eye me-1"></i><?= $n['read_count'] ?> read
      </div>
    </div>
    <a href="?delete=<?= $n['id'] ?>" class="btn btn-sm btn-outline-danger" style="padding:2px 8px;font-size:11px"
       onclick="return confirm('Delete this alert?')" title="Delete">
      <i class="bi bi-trash"></i>
    </a>
  </div>
</div>
<?php endwhile; ?>
<?php if (!$count): ?>
<div class="text-center text-muted py-5">
  <i class="bi bi-megaphone" style="font-size:2.5rem;display:block;margin-bottom:8px;color:#d1d5db"></i>
  No alerts sent yet.
</div>
<?php endif; ?>

<!-- New Alert Modal -->
<div class="modal fade" id="newAlertModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h6 class="modal-title fw-bold"><i class="bi bi-megaphone me-1"></i>Send New Alert</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Send To <span class="text-danger">*</span></label>
            <select class="form-select" name="target" required>
              <option value="all">📢 All Teachers (Broadcast)</option>
              <?php
              $teachers_again = mysqli_query($conn,"SELECT id, CONCAT(first_name,' ',last_name) AS name FROM teachers ORDER BY first_name");
              while ($t = mysqli_fetch_assoc($teachers_again)):
              ?>
              <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Alert Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="title" placeholder="e.g. Reminder: Update Your Topics" required maxlength="255">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
            <textarea class="form-control" name="message" rows="4" placeholder="Write your message here..." required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="send_alert" class="btn btn-primary btn-sm"><i class="bi bi-send me-1"></i>Send Alert</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
