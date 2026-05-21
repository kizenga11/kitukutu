<?php
session_start();
include "../includes/config.php";

if (!isset($_SESSION['admin_id']) || ($_SESSION['user_role'] ?? '') !== 'headmaster') {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$admin    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT email, name FROM admins WHERE id='$admin_id' LIMIT 1")) ?? [];
$admin_name  = !empty($admin['name']) ? $admin['name'] : 'Headmaster';
$admin_email = $admin['email'] ?? '';
$active_year = mysqli_fetch_assoc(mysqli_query($conn, "SELECT year_name FROM academic_years WHERE is_active=1 LIMIT 1"));
$active_year_name = $active_year['year_name'] ?? 'Not Set';
$unread_messages  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM messages WHERE status='unread'"))['c'] ?? 0;
$pending_admissions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM admissions WHERE status='Pending'"))['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Headmaster Dashboard · Kitukutu</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{background:#f4f7fc;font-family:system-ui,-apple-system,'Segoe UI',sans-serif;overflow-x:hidden;}

/* Sidebar */
.sidebar{
  position:fixed;top:0;left:-280px;width:280px;height:100%;
  background:linear-gradient(180deg,#064e3b 0%,#022c22 100%);
  z-index:1050;transition:left .3s cubic-bezier(.4,0,.2,1);
  padding:1.2rem .8rem;overflow-y:auto;box-shadow:4px 0 20px rgba(0,0,0,.15);
}
.sidebar.active{left:0;}
.sidebar-header{padding-bottom:1rem;border-bottom:1px solid rgba(255,255,255,.1);margin-bottom:1rem;}
.sidebar-header h4{color:#fff;font-weight:800;font-size:1.1rem;display:flex;align-items:center;gap:8px;}
.sidebar-header small{color:#6ee7b7;font-size:.7rem;display:block;margin-top:4px;}
.role-chip{display:inline-block;background:rgba(16,185,129,.2);color:#6ee7b7;border-radius:20px;padding:2px 10px;font-size:.68rem;font-weight:700;margin-top:4px;}

/* Nav same pattern as admin but green accent */
.nav-single{margin-bottom:3px;}
.nav-single>a,.nav-single>a:visited{
  display:flex;align-items:center;gap:12px;
  padding:10px 12px;border-radius:12px;
  color:#d1fae5;text-decoration:none;font-size:.88rem;font-weight:500;transition:all .2s;
}
.nav-single>a i{font-size:1.1rem;width:22px;}
.nav-single>a:hover{background:rgba(16,185,129,.15);color:#fff;transform:translateX(3px);}
.nav-single>a.active{background:#10b981;color:#fff;font-weight:700;transform:none;}

.nav-group{margin-bottom:3px;}
.nav-grp-hdr{
  display:flex;align-items:center;justify-content:space-between;
  padding:8px 12px;border-radius:10px;
  color:#6ee7b7;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;
  cursor:pointer;user-select:none;transition:all .2s;
}
.nav-grp-hdr:hover{background:rgba(255,255,255,.06);color:#a7f3d0;}
.nav-grp-hdr.open{color:#34d399;}
.nav-grp-hdr .gleft{display:flex;align-items:center;gap:9px;}
.nav-grp-hdr .gleft i{font-size:1rem;}
.nav-grp-hdr .gchev{font-size:10px;transition:transform .2s;flex-shrink:0;}
.nav-grp-hdr.open .gchev{transform:rotate(180deg);}
.nav-sub{display:none;padding-left:8px;margin-top:2px;}
.nav-sub.open{display:block;}
.nav-sub li{margin-bottom:1px;list-style:none;}
.nav-sub a,.nav-sub a:visited{
  display:flex;align-items:center;gap:10px;
  padding:8px 12px;border-radius:9px;
  color:#a7f3d0;text-decoration:none;font-size:.875rem;font-weight:500;transition:all .18s;
}
.nav-sub a i{font-size:1rem;width:20px;flex-shrink:0;}
.nav-sub a:hover{background:rgba(16,185,129,.15);color:#fff;transform:translateX(3px);}
.nav-sub a.active{background:#10b981;color:#fff;font-weight:600;transform:none;}

.overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1040;display:none;}
.overlay.active{display:block;}

.main-content{margin-left:0;padding:.8rem;transition:all .3s;}
.topbar{
  background:#fff;border-radius:18px;padding:.6rem 1rem;
  margin-bottom:1rem;box-shadow:0 2px 8px rgba(0,0,0,.04);
  border:1px solid #d1fae5;display:flex;align-items:center;gap:10px;
}
.menu-toggle{background:#10b981;border:none;padding:7px 12px;border-radius:10px;color:#fff;font-weight:700;display:flex;align-items:center;gap:6px;cursor:pointer;}
.content-frame{width:100%;border:0;border-radius:18px;background:#fff;min-height:calc(100vh - 108px);box-shadow:0 2px 8px rgba(0,0,0,.03);}

.top-welcome{flex:1;min-width:0;padding:0 4px;}
.top-welcome .greeting{font-size:11px;color:#6b7280;}
.top-welcome .wname{font-size:14px;font-weight:800;color:#064e3b;line-height:1.1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.top-welcome .wdate{font-size:11px;color:#6b7280;margin-top:1px;}

.topbar-actions{display:flex;align-items:center;gap:6px;}
.notif-btn{
  position:relative;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:50%;
  width:36px;height:36px;display:flex;align-items:center;justify-content:center;
  cursor:pointer;color:#064e3b;font-size:16px;text-decoration:none;transition:background .15s;
}
.notif-btn:hover{background:#dcfce7;color:#064e3b;}
.notif-badge{position:absolute;top:-4px;right:-4px;background:#ef4444;color:#fff;border-radius:50%;width:17px;height:17px;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;border:2px solid #fff;}

.profile-wrap{position:relative;}
.profile-btn{display:flex;align-items:center;gap:6px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:40px;padding:3px 10px 3px 3px;cursor:pointer;transition:background .15s;}
.profile-btn:hover{background:#dcfce7;}
.avatar{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#10b981,#059669);color:#fff;font-weight:700;font-size:12px;display:flex;align-items:center;justify-content:center;}
.name-text{font-size:12px;font-weight:600;color:#111827;max-width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.chevron{font-size:10px;color:#6b7280;transition:transform .2s;}
.profile-btn.open .chevron{transform:rotate(180deg);}
.profile-dropdown{display:none;position:absolute;top:calc(100% + 8px);right:0;background:#fff;border:1px solid #d1fae5;border-radius:14px;box-shadow:0 8px 30px rgba(0,0,0,.12);min-width:200px;z-index:2000;overflow:hidden;}
.profile-dropdown.open{display:block;}
.pd-header{padding:12px 14px 10px;border-bottom:1px solid #f0fdf4;font-size:11px;color:#6b7280;}
.pd-header .pd-name{font-weight:700;font-size:13px;color:#111827;margin-bottom:2px;}
.profile-dropdown a{display:flex;align-items:center;gap:10px;padding:10px 14px;font-size:13px;color:#111827;text-decoration:none;transition:background .12s;}
.profile-dropdown a:hover{background:#f0fdf4;}
.profile-dropdown a i{font-size:16px;width:20px;}
.pd-logout{color:#ef4444!important;border-top:1px solid #f0fdf4;}
.pd-logout i{color:#ef4444;}

@media(min-width:992px){
  .sidebar{position:sticky;left:0;top:0;height:100vh;transform:none;}
  .overlay{display:none!important;}
  .main-content{margin-left:0;padding:1rem 1.5rem;}
  .menu-toggle{display:none;}
  body{display:flex;align-items:stretch;}
  .sidebar{position:fixed;left:0;}
  .main-content{margin-left:280px;}
}
@media(max-width:480px){.top-welcome .wdate{display:none;}}
</style>
</head>
<body>

<div class="overlay" id="overlay"></div>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <h4><i class="bi bi-mortarboard-fill"></i> Headmaster</h4>
    <small><?= htmlspecialchars($active_year_name) ?></small>
    <span class="role-chip">Mkuu wa Shule</span>
  </div>
  <ul class="sidebar-nav" style="list-style:none;padding:0;">
    <li class="nav-single"><a href="home.php" target="mainFrame"><i class="bi bi-speedometer2"></i> Dashboard</a></li>

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

    <li class="nav-group">
      <div class="nav-grp-hdr" onclick="toggleGroup(this)">
        <span class="gleft"><i class="bi bi-clipboard2-check-fill"></i> Mitihani & Matokeo</span>
        <i class="bi bi-chevron-down gchev"></i>
      </div>
      <ul class="nav-sub">
        <li><a href="../admin/view_exam_results.php" target="mainFrame"><i class="bi bi-bar-chart-steps"></i> Matokeo</a></li>
        <li><a href="../admin/admin_exam_comparison.php" target="mainFrame"><i class="bi bi-graph-up"></i> Ulinganisho</a></li>
        <li><a href="../admin/admin_student_report.php" target="mainFrame"><i class="bi bi-file-text"></i> Ripoti za Wanafunzi</a></li>
      </ul>
    </li>

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

    <li class="nav-group">
      <div class="nav-grp-hdr" onclick="toggleGroup(this)">
        <span class="gleft"><i class="bi bi-megaphone-fill"></i> Mawasiliano</span>
        <i class="bi bi-chevron-down gchev"></i>
      </div>
      <ul class="nav-sub">
        <li><a href="../admin/announcements.php" target="mainFrame"><i class="bi bi-megaphone"></i> Matangazo</a></li>
        <li><a href="../admin/messages.php" target="mainFrame"><i class="bi bi-envelope"></i> Ujumbe <?php if($unread_messages>0): ?><span style="background:#ef4444;color:#fff;border-radius:20px;font-size:10px;padding:1px 7px;margin-left:auto;font-weight:700"><?=$unread_messages?></span><?php endif;?></a></li>
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
            <span class="role-chip">Headmaster</span>
          </div>
          <a href="../logout.php" class="pd-logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
      </div>
    </div>
  </div>
  <iframe title="Headmaster Content" class="content-frame" name="mainFrame" id="mainFrame" src="" loading="eager"></iframe>
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
function setActive(href){
  allLinks.forEach(l=>l.classList.remove('active'));
  allLinks.forEach(l=>{if(l.getAttribute('href')===href){l.classList.add('active');openGroupForLink(l);}});
}
allLinks.forEach(link=>{
  link.addEventListener('click',function(){
    setActive(this.getAttribute('href'));
    sessionStorage.setItem('hmActivePage',this.getAttribute('href'));
    if(window.innerWidth<992)closeSidebar();
  });
});
(function(){
  var saved=sessionStorage.getItem('hmActivePage');
  var frame=document.getElementById('mainFrame');
  var target=(saved&&saved.trim())?saved:'home.php';
  frame.src=target;setActive(target);
})();
const profileBtn=document.getElementById('profileBtn'),profileDrop=document.getElementById('profileDrop');
function closeDrop(){profileDrop.classList.remove('open');profileBtn.classList.remove('open');}
profileBtn.addEventListener('click',function(e){e.stopPropagation();profileDrop.classList.contains('open')?closeDrop():(profileDrop.classList.add('open'),profileBtn.classList.add('open'));});
document.addEventListener('click',function(e){if(!profileBtn.contains(e.target)&&!profileDrop.contains(e.target))closeDrop();});
function loadFrame(url){document.getElementById('mainFrame').src=url;setActive(url);sessionStorage.setItem('hmActivePage',url);if(window.innerWidth<992)closeSidebar();}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
