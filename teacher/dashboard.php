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
  <style>
    *{box-sizing:border-box}
    body{background:#f3f4f6;font-family:system-ui;margin:0;}

    .layout{display:flex;min-height:100vh;}

    /* Sidebar (minimal, mobile-first) */
    .sidebar{
      position:fixed;inset:0 auto 0 0;width:260px;background:#0f2744;border-right:none;
      padding:12px;transform:translateX(-100%);transition:transform .2s ease;z-index:1050;overflow:auto;
    }
    .sidebar.active{transform:translateX(0)}
    .brand{font-weight:800;color:#fff;}
    .sub{color:#93aec8;font-size:12px;}
    .nav a{display:flex;gap:10px;align-items:center;padding:10px 10px;border-radius:10px;color:#cbd5e1;text-decoration:none;justify-content:space-between;}
    .nav a:hover{background:rgba(255,255,255,.1);color:#fff;}
    .nav a.active{background:rgba(255,255,255,.15);color:#fff;font-weight:700;}

    .overlay{position:fixed;inset:0;background:rgba(0,0,0,.35);display:none;z-index:1040;}
    .overlay.active{display:block;}

    /* Topbar */
    .main{flex:1;padding:12px;}
    .top{
      background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:8px 12px;
      display:flex;align-items:center;justify-content:space-between;gap:10px;
    }
    .menu-btn{border:1px solid #e5e7eb;background:#fff;border-radius:10px;padding:6px 10px;flex-shrink:0;}

    /* Welcome section */
    .top-welcome{flex:1;min-width:0;padding:0 4px;}
    .top-welcome .greeting{font-size:11px;color:#6b7280;}
    .top-welcome .wname{font-size:14px;font-weight:800;color:#0f2744;line-height:1.1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
    .top-welcome .wdate{font-size:11px;color:#6b7280;margin-top:1px;}
    @media(max-width:480px){
      .top-welcome .wdate{display:none;}
    }

    .frame{width:100%;border:0;border-radius:12px;background:#fff;min-height:calc(100vh - 88px);margin-top:10px;}

    @media (min-width: 992px){
      .sidebar{position:sticky;transform:none;}
      .overlay{display:none !important;}
      .main{margin-left:0;}
      .menu-btn{display:none;}
      .layout{align-items:stretch;}
    }

    /* ── Topbar right actions ── */
    .topbar-actions{display:flex;align-items:center;gap:6px;}

    .notif-btn{
      position:relative;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:50%;
      width:36px;height:36px;display:flex;align-items:center;justify-content:center;
      cursor:pointer;color:#111827;font-size:16px;text-decoration:none;transition:background .15s;
    }
    .notif-btn:hover{background:#e5e7eb;color:#111827;}
    .notif-badge{
      position:absolute;top:-4px;right:-4px;background:#ef4444;color:#fff;
      border-radius:50%;width:17px;height:17px;font-size:10px;font-weight:700;
      display:flex;align-items:center;justify-content:center;border:2px solid #fff;
    }

    .profile-wrap{position:relative;}
    .profile-btn{
      display:flex;align-items:center;gap:6px;background:#f3f4f6;border:1px solid #e5e7eb;
      border-radius:40px;padding:3px 10px 3px 3px;cursor:pointer;transition:background .15s;
    }
    .profile-btn:hover{background:#e5e7eb;}
    .avatar{
      width:30px;height:30px;border-radius:50%;
      background:linear-gradient(135deg,#6366f1,#4338ca);
      color:#fff;font-weight:700;font-size:12px;
      display:flex;align-items:center;justify-content:center;
    }
    .name-text{font-size:12px;font-weight:600;color:#111827;max-width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
    .chevron{font-size:10px;color:#6b7280;transition:transform .2s;}
    .profile-btn.open .chevron{transform:rotate(180deg);}

    .profile-dropdown{
      display:none;position:absolute;top:calc(100% + 8px);right:0;
      background:#fff;border:1px solid #e5e7eb;border-radius:14px;
      box-shadow:0 8px 30px rgba(0,0,0,0.12);min-width:200px;z-index:2000;overflow:hidden;
    }
    .profile-dropdown.open{display:block;}
    .pd-header{padding:12px 14px 10px;border-bottom:1px solid #f3f4f6;font-size:11px;color:#6b7280;}
    .pd-header .pd-name{font-weight:700;font-size:13px;color:#111827;margin-bottom:2px;}
    .profile-dropdown a{
      display:flex;align-items:center;gap:10px;padding:10px 14px;
      font-size:13px;color:#111827;text-decoration:none;transition:background .12s;
    }
    .profile-dropdown a:hover{background:#f3f4f6;}
    .profile-dropdown a i{font-size:16px;width:20px;}
    .pd-logout{color:#ef4444!important;border-top:1px solid #f3f4f6;}
    .pd-logout i{color:#ef4444;}
  </style>
</head>
<body>

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
      <a href="notifications.php" target="mainFrame">🔔 Notifications<?php if ($unread_notif > 0): ?> <span style="background:#ef4444;color:#fff;border-radius:20px;font-size:10px;padding:1px 7px;margin-left:auto;font-weight:700"><?= $unread_notif ?></span><?php endif; ?></a>
      <a href="teacher_subject_analysis.php" target="mainFrame">📊 Analysis</a>
      <a href="teacher_exam_comparison.php" target="mainFrame">📈 Comparison</a>
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

  links.forEach(a => {
    a.addEventListener('click', () => {
      links.forEach(x => x.classList.remove('active'));
      a.classList.add('active');
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
    if(saved && saved.trim() !== ''){
      frame.src = saved;
      links.forEach(function(l){
        if(l.getAttribute('href') === saved) l.classList.add('active');
      });
    } else {
      frame.src = 'home.php';
      links.forEach(function(l){
        if(l.getAttribute('href') === 'home.php') l.classList.add('active');
      });
    }
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
    sessionStorage.setItem('teacherActivePage', url);
    if (window.innerWidth < 992) closeNav();
  }
</script>


</body>
</html>
