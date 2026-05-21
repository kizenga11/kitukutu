<?php
session_start();
include "../includes/config.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$check = mysqli_query($conn, "SELECT role FROM admins WHERE id='$admin_id'");
$data = mysqli_fetch_assoc($check);
if (($data['role'] ?? '') != 'admin') {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM students"))['total'] ?? 0;
$total_teachers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM teachers"))['total'] ?? 0;
$total_exams = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM exams"))['total'] ?? 0;
$total_announcements = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM announcements WHERE status='published'"))['total'] ?? 0;
$pending_admissions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM admissions WHERE status='Pending'"))['total'] ?? 0;
$unread_messages = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM messages WHERE status='unread'"))['total'] ?? 0;

$active_year = mysqli_fetch_assoc(mysqli_query($conn, "SELECT year_name FROM academic_years WHERE is_active=1 LIMIT 1"));
$active_year_name = $active_year ? $active_year['year_name'] : 'Not Set';

$recent_admissions = mysqli_query($conn, "SELECT application_no, first_name, last_name, entry_level, status, created_at FROM admissions ORDER BY created_at DESC LIMIT 5");
$recent_announcements = mysqli_query($conn, "SELECT title, type, created_at FROM announcements WHERE status='published' ORDER BY created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Home</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{background:#f4f7fc;font-family:system-ui;padding:16px;}
    .cardx{background:#fff;border:1px solid #e2edf2;border-radius:16px;box-shadow:0 2px 8px rgba(0,0,0,.03)}
    .stat{padding:14px 16px}
    .stat .n{font-weight:800;font-size:1.5rem;color:#0b2b3f}
    .stat .l{color:#64748b;font-size:.8rem}
    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px}
    .title{font-weight:800;color:#0b2b3f}
  </style>
</head>
<body>

<!-- Header removed: dashboard shell provides navigation -->

<div class="grid mb-3">
  <div class="cardx stat"><div class="n"><?= (int)$total_students ?></div><div class="l">Students</div></div>
  <div class="cardx stat"><div class="n"><?= (int)$total_teachers ?></div><div class="l">Teachers</div></div>
  <div class="cardx stat"><div class="n"><?= (int)$total_exams ?></div><div class="l">Exams</div></div>
  <div class="cardx stat"><div class="n"><?= (int)$total_announcements ?></div><div class="l">Announcements</div></div>
  <div class="cardx stat"><div class="n"><?= (int)$pending_admissions ?></div><div class="l">Pending Admissions</div></div>
  <div class="cardx stat"><div class="n"><?= (int)$unread_messages ?></div><div class="l">Unread Messages</div></div>
</div>

<div class="row g-3">
  <div class="col-12 col-lg-6">
    <div class="cardx p-3">
      <div class="fw-bold mb-2">Recent Admissions</div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>Name</th><th>Level</th><th>Status</th><th>Date</th></tr></thead>
          <tbody>
          <?php if($recent_admissions && mysqli_num_rows($recent_admissions) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($recent_admissions)): ?>
              <tr>
                <td><?= htmlspecialchars(($row['first_name'] ?? '').' '.($row['last_name'] ?? '')) ?></td>
                <td><?= htmlspecialchars($row['entry_level'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['status'] ?? '') ?></td>
                <td><?= !empty($row['created_at']) ? date('d/m', strtotime($row['created_at'])) : '' ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="4" class="text-muted">No admissions found</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="mt-2 text-end"><a href="admissions.php" class="small text-decoration-none">View all</a></div>
    </div>
  </div>

  <div class="col-12 col-lg-6">
    <div class="cardx p-3">
      <div class="fw-bold mb-2">Latest Announcements</div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>Title</th><th>Type</th><th>Date</th></tr></thead>
          <tbody>
          <?php if($recent_announcements && mysqli_num_rows($recent_announcements) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($recent_announcements)): ?>
              <tr>
                <td><?= htmlspecialchars(substr((string)($row['title'] ?? ''), 0, 40)) ?></td>
                <td><?= htmlspecialchars($row['type'] ?? '') ?></td>
                <td><?= !empty($row['created_at']) ? date('d M', strtotime($row['created_at'])) : '' ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="3" class="text-muted">No announcements</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="mt-2 text-end"><a href="announcements.php" class="small text-decoration-none">View all</a></div>
    </div>
  </div>
</div>

</body>
</html>
