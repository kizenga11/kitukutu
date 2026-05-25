<?php
session_start();
include "../includes/config.php";

if (!isset($_SESSION['teacher_id'])) {
    header("Location: ../login.php");
    exit();
}

$teacher_id = intval($_SESSION['teacher_id']);
$teacher  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT first_name,last_name FROM teachers WHERE id='$teacher_id' LIMIT 1")) ?? [];
$fullname = trim(($teacher['first_name'] ?? '') . ' ' . ($teacher['last_name'] ?? ''));
$initials = strtoupper(substr($teacher['first_name'] ?? 'T', 0, 1) . substr($teacher['last_name'] ?? '', 0, 1));

$year = mysqli_fetch_assoc(mysqli_query($conn, "SELECT year_name FROM academic_years WHERE is_active=1 LIMIT 1")) ?? [];
$active_year_name = $year['year_name'] ?? 'Not Set';

// Unread notifications count
$unread_notif = intval(mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT COUNT(*) AS c FROM notifications n
    WHERE (n.teacher_id=$teacher_id OR n.teacher_id IS NULL)
      AND NOT EXISTS (SELECT 1 FROM notification_reads nr WHERE nr.notification_id=n.id AND nr.teacher_id=$teacher_id)
"))['c'] ?? 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Teacher Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../assets/css/shared.css" rel="stylesheet">
</head>
<body class="theme-teacher">

<div class="overlay" id="overlay"></div>

<div class="layout">
  <aside class="sidebar" id="sidebar">
    <div class="mb-2" style="padding:4px 2px 12px;border-bottom:1px solid rgba(255,255,255,.1)">
      <div class="brand" style="font-size:15px;letter-spacing:.3px">🏫 Teacher Panel</div>
      <div class="sub"><?= htmlspecialchars($active_year_name) ?> · <?= htmlspecialchars($teacher['first_name'] ?? 'Teacher') ?></div>
    </div>

    <nav class="nav flex-column mt-2">
      <a class="active" href="home.php" target="mainFrame">Home</a>
      <a href="enter_marks_hub.php" target="mainFrame">✏️ Enter Marks</a>
      <a href="teaching_progress.php" target="mainFrame">📚 Teaching Progress</a>
      <a href="teaching_docs.php" target="mainFrame">📖 Teaching Docs</a>
      <a href="notifications.php" target="mainFrame">🔔 Notifications<?php if ($unread_notif > 0): ?> <span style="background:#ef4444;color:#fff;border-radius:20px;font-size:10px;padding:1px 7px;margin-left:auto;font-weight:700"><?= $unread_notif ?></span><?php endif; ?></a>
      <a href="teacher_subject_analysis.php" target="mainFrame">📊 Analysis</a>
      <a href="teacher_exam_comparison.php" target="mainFrame">📈 Comparison</a>
      <a href="view_exam_results.php" target="mainFrame">📋 View Results</a>
      <a href="academic_calendar.php" target="mainFrame">📅 Calendar</a>
    </nav>
  </aside>

  <main class="main">
    <div class="top">
      <button class="menu-btn" id="menuBtn" type="button">☰ Menu</button>

      <!-- Welcome -->
      <?php
        $hour = (int)date('H');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
      ?>
      <div class="top-welcome">
        <div class="greeting"><?= $greeting ?>,</div>
        <div class="wname"><?= htmlspecialchars($fullname) ?></div>
        <div class="wdate"><?= date('D, d M Y') ?></div>
      </div>

      <div class="topbar-actions">
        <!-- Notification bell -->
        <a class="notif-btn" href="#" title="Notifications" onclick="loadTeacherFrame('notifications.php');return false;">
          <i class="bi bi-bell-fill"></i>
          <?php if ($unread_notif > 0): ?>
          <span class="notif-badge"><?= $unread_notif > 9 ? '9+' : $unread_notif ?></span>
          <?php endif; ?>
        </a>

        <!-- Profile dropdown -->
        <div class="profile-wrap">
          <button class="profile-btn" id="profileBtn" type="button">
            <span class="avatar"><?= $initials ?></span>
            <span class="name-text"><?= htmlspecialchars($teacher['first_name'] ?? 'Teacher') ?></span>
            <i class="bi bi-chevron-down chevron"></i>
          </button>
          <div class="profile-dropdown" id="profileDrop">
            <div class="pd-header">
              <div class="pd-name"><?= htmlspecialchars($fullname) ?></div>
              Teacher
            </div>
            <a href="#" onclick="loadTeacherFrame('change_password.php');closeDrop();return false;">
              <i class="bi bi-key-fill text-primary"></i> Change Password
            </a>
            <a href="../logout.php" class="pd-logout">
              <i class="bi bi-box-arrow-right"></i> Logout
            </a>
          </div>
        </div>
      </div>
    </div>

    <div class="breadcrumb-bar" id="breadcrumbBar"></div>
    <iframe title="Teacher Content" class="frame" name="mainFrame" id="mainFrame" src="" loading="eager"></iframe>
  </main>
</div>

<script>
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('overlay');
  const menuBtn = document.getElementById('menuBtn');
  const links = sidebar.querySelectorAll('a[target="mainFrame"]');

  function openNav(){ sidebar.classList.add('active'); overlay.classList.add('active'); }
  function closeNav(){ sidebar.classList.remove('active'); overlay.classList.remove('active'); }

  menuBtn?.addEventListener('click', () => {
    if (sidebar.classList.contains('active')) closeNav();
    else openNav();
  });
  overlay.addEventListener('click', closeNav);

  function updateBreadcrumb(href) {
    var bar = document.getElementById('breadcrumbBar');
    if (!bar) return;
    var link = Array.from(links).find(l => l.getAttribute('href') === href);
    if (!link) { bar.innerHTML = ''; return; }
    var pageText = link.textContent.trim().replace(/\s+/g, ' ');
    bar.innerHTML = '<span>Teacher</span><span class="bc-sep">›</span><span class="bc-page">' + pageText + '</span>';
  }

  links.forEach(a => {
    a.addEventListener('click', () => {
      links.forEach(x => x.classList.remove('active'));
      a.classList.add('active');
      updateBreadcrumb(a.getAttribute('href'));
      if (window.innerWidth < 992) closeNav();
      sessionStorage.setItem('teacherActivePage', a.getAttribute('href'));
    });
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth >= 992) closeNav();
  });

  (function(){
    var saved = sessionStorage.getItem('teacherActivePage');
    var frame = document.getElementById('mainFrame');
    var target = (saved && saved.trim() !== '') ? saved : 'home.php';
    frame.src = target;
    links.forEach(function(l){ l.classList.remove('active'); });
    links.forEach(function(l){ if(l.getAttribute('href') === target) l.classList.add('active'); });
    updateBreadcrumb(target);
  })();

  // ── Profile dropdown ──
  const profileBtn  = document.getElementById('profileBtn');
  const profileDrop = document.getElementById('profileDrop');

  function closeDrop() {
    profileDrop.classList.remove('open');
    profileBtn.classList.remove('open');
  }

  profileBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    const isOpen = profileDrop.classList.contains('open');
    isOpen ? closeDrop() : (profileDrop.classList.add('open'), profileBtn.classList.add('open'));
  });

  document.addEventListener('click', function(e) {
    if (!profileBtn.contains(e.target) && !profileDrop.contains(e.target)) closeDrop();
  });

  function loadTeacherFrame(url) {
    document.getElementById('mainFrame').src = url;
    links.forEach(l => l.classList.remove('active'));
    links.forEach(l => { if (l.getAttribute('href') === url) l.classList.add('active'); });
    updateBreadcrumb(url);
    sessionStorage.setItem('teacherActivePage', url);
    if (window.innerWidth < 992) closeNav();
  }
</script>


</body>
</html>
