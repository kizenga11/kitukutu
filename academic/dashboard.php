<?php
session_start();
include "../includes/config.php";

if (!isset($_SESSION['admin_id']) || ($_SESSION['user_role'] ?? '') !== 'academic') {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$admin    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT email, name FROM admins WHERE id='$admin_id' LIMIT 1")) ?? [];
$admin_name  = !empty($admin['name']) ? $admin['name'] : 'Academic Officer';
$admin_email = $admin['email'] ?? '';
$active_year = mysqli_fetch_assoc(mysqli_query($conn, "SELECT year_name FROM academic_years WHERE is_active=1 LIMIT 1"));
$active_year_name = $active_year['year_name'] ?? 'Not Set';
$unread_messages  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM messages WHERE status='unread'"))['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Academic Dashboard · Kitukutu</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="../assets/css/shared.css" rel="stylesheet">
</head>
<body class="theme-academic">

<div class="overlay" id="overlay"></div>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <h4><i class="bi bi-mortarboard-fill"></i> Academic</h4>
    <small><?= htmlspecialchars($active_year_name) ?></small>
    <span class="role-chip">Mkuu wa Masomo</span>
  </div>
  <ul class="sidebar-nav" style="list-style:none;padding:0;">

    <li class="nav-single">
      <a href="home.php" target="mainFrame"><i class="bi bi-speedometer2"></i> Dashboard</a>
    </li>

    <!-- Wanafunzi & Madarasa -->
    <li class="nav-group">
      <div class="nav-grp-hdr" onclick="toggleGroup(this)">
        <span class="gleft"><i class="bi bi-people-fill"></i> Wanafunzi</span>
        <i class="bi bi-chevron-down gchev"></i>
      </div>
      <ul class="nav-sub">
        <li><a href="../admin/add_student.php" target="mainFrame"><i class="bi bi-people"></i> Orodha ya Wanafunzi</a></li>
        <li><a href="../admin/admissions.php" target="mainFrame"><i class="bi bi-journal-check"></i> Maombi ya Kujiunga</a></li>
      </ul>
    </li>

    <!-- Mitihani & Matokeo -->
    <li class="nav-group">
      <div class="nav-grp-hdr" onclick="toggleGroup(this)">
        <span class="gleft"><i class="bi bi-clipboard2-check-fill"></i> Mitihani & Matokeo</span>
        <i class="bi bi-chevron-down gchev"></i>
      </div>
      <ul class="nav-sub">
        <li><a href="../admin/exam_management.php" target="mainFrame"><i class="bi bi-pencil-square"></i> Mitihani</a></li>
        <li><a href="../admin/view_exam_results.php" target="mainFrame"><i class="bi bi-bar-chart-steps"></i> Matokeo</a></li>
        <li><a href="../admin/admin_exam_comparison.php" target="mainFrame"><i class="bi bi-graph-up"></i> Ulinganisho</a></li>
        <li><a href="../admin/admin_student_report.php" target="mainFrame"><i class="bi bi-file-text"></i> Ripoti za Wanafunzi</a></li>
        <li><a href="../admin/unprocessed_results.php" target="mainFrame"><i class="bi bi-hourglass-split"></i> Matokeo Yanayosubiri</a></li>
      </ul>
    </li>

    <!-- Masomo -->
    <li class="nav-group">
      <div class="nav-grp-hdr" onclick="toggleGroup(this)">
        <span class="gleft"><i class="bi bi-book-fill"></i> Masomo</span>
        <i class="bi bi-chevron-down gchev"></i>
      </div>
      <ul class="nav-sub">
        <li><a href="../admin/teaching_progress_overview.php" target="mainFrame"><i class="bi bi-graph-up-arrow"></i> Maendeleo ya Ufundishaji</a></li>
        <li><a href="../admin/manage_periods.php" target="mainFrame"><i class="bi bi-calendar-week"></i> Ratiba (Periods)</a></li>
      </ul>
    </li>

    <!-- Nyaraka za Kufundishia -->
    <li class="nav-group">
      <div class="nav-grp-hdr" onclick="toggleGroup(this)">
        <span class="gleft"><i class="bi bi-book-fill"></i> Nyaraka za Kufundishia</span>
        <i class="bi bi-chevron-down gchev"></i>
      </div>
      <ul class="nav-sub">
        <li><a href="../admin/manage_curriculum.php" target="mainFrame"><i class="bi bi-book"></i> Mtaala</a></li>
        <li><a href="../admin/manage_syllabus.php" target="mainFrame"><i class="bi bi-journal-text"></i> Silabasi</a></li>
        <li><a href="../admin/manage_schemes.php" target="mainFrame"><i class="bi bi-calendar-week"></i> Mpangilio wa Mada</a></li>
        <li><a href="../admin/manage_lesson_plans.php" target="mainFrame"><i class="bi bi-file-earmark-text"></i> Mipango ya Somo</a></li>
        <li><a href="../admin/manage_subject_resources.php" target="mainFrame"><i class="bi bi-journal-richtext"></i> Vitabu & Maelezo</a></li>
      </ul>
    </li>

    <!-- Calendar -->
    <li class="nav-group">
      <div class="nav-grp-hdr" onclick="toggleGroup(this)">
        <span class="gleft"><i class="bi bi-calendar3"></i> Calendar</span>
        <i class="bi bi-chevron-down gchev"></i>
      </div>
      <ul class="nav-sub">
        <li><a href="../admin/academic_years.php" target="mainFrame"><i class="bi bi-calendar-range"></i> Academic Years</a></li>
        <li><a href="../admin/academic_terms.php" target="mainFrame"><i class="bi bi-layers"></i> Terms</a></li>
        <li><a href="../admin/manage_events.php" target="mainFrame"><i class="bi bi-calendar-event"></i> Events</a></li>
        <li><a href="../admin/academic_calendar.php" target="mainFrame"><i class="bi bi-calendar3"></i> Calendar View</a></li>
      </ul>
    </li>

    <!-- Communication -->
    <li class="nav-group">
      <div class="nav-grp-hdr" onclick="toggleGroup(this)">
        <span class="gleft"><i class="bi bi-megaphone-fill"></i> Communication</span>
        <i class="bi bi-chevron-down gchev"></i>
      </div>
      <ul class="nav-sub">
        <li><a href="../admin/announcements.php" target="mainFrame"><i class="bi bi-megaphone"></i> Announcements</a></li>
        <li><a href="../admin/messages.php" target="mainFrame"><i class="bi bi-envelope"></i> Messages
          <?php if($unread_messages>0): ?><span style="background:#ef4444;color:#fff;border-radius:20px;font-size:10px;padding:1px 7px;margin-left:auto;font-weight:700"><?=$unread_messages?></span><?php endif;?>
        </a></li>
      </ul>
    </li>

  </ul>
</aside>

<div class="main-content">
  <div class="topbar">
    <button class="menu-toggle" id="menuBtn">☰ Menu</button>
    <?php $hour=(int)date('H'); $greeting=$hour<12?'Good morning':($hour<17?'Good afternoon':'Good evening'); ?>
    <div class="top-welcome">
      <div class="greeting"><?=$greeting?>,</div>
      <div class="wname"><?=htmlspecialchars($admin_name)?></div>
      <div class="wdate"><?=date('D, d M Y')?> &bull; <?=htmlspecialchars($active_year_name)?></div>
    </div>
    <div class="topbar-actions">
      <a class="notif-btn" href="#" title="Ujumbe" onclick="loadFrame('../admin/messages.php');return false;">
        <i class="bi bi-envelope-fill"></i>
        <?php if($unread_messages>0): ?><span class="notif-badge"><?=$unread_messages>9?'9+':$unread_messages?></span><?php endif;?>
      </a>
      <div class="profile-wrap">
        <button class="profile-btn" id="profileBtn" type="button">
          <span class="avatar"><?=strtoupper(substr($admin_name,0,1))?></span>
          <span class="name-text"><?=htmlspecialchars($admin_name)?></span>
          <i class="bi bi-chevron-down chevron"></i>
        </button>
        <div class="profile-dropdown" id="profileDrop">
          <div class="pd-header">
            <div class="pd-name"><?=htmlspecialchars($admin_name)?></div>
            <span class="role-chip">Academic Officer</span>
          </div>
          <a href="../logout.php" class="pd-logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
      </div>
    </div>
  </div>
  <div class="breadcrumb-bar" id="breadcrumbBar"></div>
  <iframe title="Academic Content" class="content-frame" name="mainFrame" id="mainFrame" src="" loading="eager"></iframe>
</div>

<script>
const sidebar=document.getElementById('sidebar'),overlay=document.getElementById('overlay'),menuBtn=document.getElementById('menuBtn');
function closeSidebar(){sidebar.classList.remove('active');overlay.classList.remove('active');}
function openSidebar(){sidebar.classList.add('active');overlay.classList.add('active');}
menuBtn.addEventListener('click',()=>sidebar.classList.contains('active')?closeSidebar():openSidebar());
overlay.addEventListener('click',closeSidebar);
window.addEventListener('resize',()=>{if(window.innerWidth>=992)closeSidebar();});

function toggleGroup(hdr){
  const sub=hdr.nextElementSibling,isOpen=sub.classList.contains('open');
  document.querySelectorAll('.nav-grp-hdr').forEach(h=>{h.classList.remove('open');h.nextElementSibling.classList.remove('open');});
  if(!isOpen){hdr.classList.add('open');sub.classList.add('open');}
}
function openGroupForLink(link){
  const sub=link.closest('.nav-sub');
  if(sub){sub.classList.add('open');const hdr=sub.previousElementSibling;if(hdr)hdr.classList.add('open');}
}
const allLinks=document.querySelectorAll('.sidebar-nav a[target="mainFrame"]');
function updateBreadcrumb(href){
  var bar=document.getElementById('breadcrumbBar');
  if(!bar)return;
  var link=Array.from(allLinks).find(l=>l.getAttribute('href')===href);
  if(!link){bar.innerHTML='';return;}
  var pageText=link.textContent.trim().replace(/\s+/g,' ');
  var sub=link.closest('.nav-sub');
  var group=sub?sub.previousElementSibling.querySelector('.gleft'):null;
  var groupText=group?group.textContent.trim():'Academic';
  bar.innerHTML='<span>'+groupText+'</span><span class="bc-sep">›</span><span class="bc-page">'+pageText+'</span>';
}
function setActive(href){
  allLinks.forEach(l=>l.classList.remove('active'));
  allLinks.forEach(l=>{if(l.getAttribute('href')===href){l.classList.add('active');openGroupForLink(l);}});
  updateBreadcrumb(href);
}
allLinks.forEach(link=>{
  link.addEventListener('click',function(){
    setActive(this.getAttribute('href'));
    sessionStorage.setItem('acActivePage',this.getAttribute('href'));
    if(window.innerWidth<992)closeSidebar();
  });
});
(function(){
  var saved=sessionStorage.getItem('acActivePage');
  var frame=document.getElementById('mainFrame');
  var target=(saved&&saved.trim())?saved:'home.php';
  frame.src=target;setActive(target);
})();
const profileBtn=document.getElementById('profileBtn'),profileDrop=document.getElementById('profileDrop');
function closeDrop(){profileDrop.classList.remove('open');profileBtn.classList.remove('open');}
profileBtn.addEventListener('click',function(e){e.stopPropagation();profileDrop.classList.contains('open')?closeDrop():(profileDrop.classList.add('open'),profileBtn.classList.add('open'));});
document.addEventListener('click',function(e){if(!profileBtn.contains(e.target)&&!profileDrop.contains(e.target))closeDrop();});
function loadFrame(url){document.getElementById('mainFrame').src=url;setActive(url);sessionStorage.setItem('acActivePage',url);if(window.innerWidth<992)closeSidebar();}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
