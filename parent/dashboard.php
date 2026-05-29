<?php
session_start();
include "../includes/config.php";

if (!isset($_SESSION['parent_id'])) {
    header("Location: login.php");
    exit();
}

$parent_id = intval($_SESSION['parent_id']);
$parent = mysqli_fetch_assoc(mysqli_query($conn, "SELECT first_name,last_name FROM parents WHERE id='$parent_id' LIMIT 1")) ?? [];

$children = mysqli_query($conn, "
    SELECT s.id, s.first_name, s.second_name, s.last_name, s.form_level, s.stream, ps.relationship
    FROM parent_students ps
    JOIN students s ON s.id = ps.student_id AND s.is_active=1
    WHERE ps.parent_id = '$parent_id'
    ORDER BY s.first_name
");

$total_children = 0;
$child_list = [];
while ($c = mysqli_fetch_assoc($children)) {
    $child_list[] = $c;
    $total_children++;
}

// Force parent to link a student first
if ($total_children === 0) {
    header("Location: link_student.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Mzazi · Amali Kitukutu</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="../assets/css/shared.css" rel="stylesheet">
</head>
<body class="theme-parent">

<div class="overlay" id="overlay"></div>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <h4><i class="bi bi-house-heart-fill"></i> Mzazi</h4>
    <small><?= htmlspecialchars($parent['first_name'] ?? 'Mzazi') ?></small>
    <span class="role-chip" style="background:rgba(22,163,74,0.2);color:#16a34a;">Mlezi</span>
  </div>
  <ul class="sidebar-nav" style="list-style:none;padding:0;">

    <li class="nav-single">
      <a href="home.php" target="mainFrame"><i class="bi bi-speedometer2"></i> Nyumbani</a>
    </li>

    <li class="nav-group">
      <div class="nav-grp-hdr" onclick="toggleGroup(this)">
        <span class="gleft"><i class="bi bi-file-text-fill"></i> Matokeo</span>
        <i class="bi bi-chevron-down gchev"></i>
      </div>
      <ul class="nav-sub">
        <?php foreach ($child_list as $ch): ?>
        <li><a href="view_results.php?student_id=<?= $ch['id'] ?>" target="mainFrame"><i class="bi bi-person"></i> <?= htmlspecialchars($ch['first_name'].' '.$ch['last_name']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </li>

    <li class="nav-single">
      <a href="view_assignments.php" target="mainFrame"><i class="bi bi-journal-check"></i> Kazi za Nyumbani</a>
    </li>

    <li class="nav-single">
      <a href="view_materials.php" target="mainFrame"><i class="bi bi-journal-richtext"></i> Nyenzo za Masomo</a>
    </li>

    <li class="nav-single">
      <a href="view_contributions.php" target="mainFrame"><i class="bi bi-cash-stack"></i> Michango na Deni</a>
    </li>

  </ul>
</aside>

<div class="main-content">
  <div class="topbar">
    <button class="menu-toggle" id="menuBtn"><i class="bi bi-list"></i> Menyu</button>

    <?php $hour=(int)date('H'); $greeting=$hour<12?'Habari za asubuhi':($hour<17?'Habari za mchana':'Habari za jioni'); ?>
    <div class="top-welcome">
      <div class="greeting"><?=$greeting?>,</div>
      <div class="wname"><?=htmlspecialchars($parent['first_name'] ?? 'Mzazi')?></div>
      <div class="wdate"><?=date('D, d M Y')?></div>
    </div>

    <div class="topbar-actions">
      <div class="profile-wrap">
        <button class="profile-btn" id="profileBtn" type="button">
          <span class="avatar"><?=strtoupper(substr($parent['first_name'] ?? 'M',0,1))?></span>
          <span class="name-text"><?=htmlspecialchars($parent['first_name'] ?? 'Mzazi')?></span>
          <i class="bi bi-chevron-down chevron"></i>
        </button>
        <div class="profile-dropdown" id="profileDrop">
          <div class="pd-header">
            <div class="pd-name"><?=htmlspecialchars(($parent['first_name']??'').' '.($parent['last_name']??''))?></div>
            <span class="role-chip" style="background:rgba(22,163,74,0.2);color:#16a34a;">Mzazi</span>
          </div>
          <a href="#" onclick="loadParentFrame('change_password.php');closeDrop();return false;">
            <i class="bi bi-key-fill text-primary"></i> Badilisha Nenosiri
          </a>
          <a href="logout.php" class="pd-logout"><i class="bi bi-box-arrow-right"></i> Ondoka</a>
        </div>
      </div>
    </div>
  </div>

  <div class="breadcrumb-bar" id="breadcrumbBar"></div>
  <iframe title="Parent Content" class="content-frame" name="mainFrame" id="mainFrame" src="" loading="eager"></iframe>
</div>

<style>
.theme-parent .sidebar{background:#065f46;}
.theme-parent .sidebar-header h4{color:#a7f3d0;}
.theme-parent .nav-grp-hdr:hover,.theme-parent .nav-single a:hover{background:rgba(255,255,255,0.1);}
.theme-parent .nav-single a,.theme-parent .nav-sub a{color:#d1fae5;}
.theme-parent .nav-single a.active,.theme-parent .nav-sub a.active{background:rgba(255,255,255,0.15);color:#fff;}
.theme-parent .topbar{background:#fff;border-bottom:2px solid #065f46;}
</style>

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
  var groupText=group?group.textContent.trim():'Mzazi';
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
    sessionStorage.setItem('parentActivePage',this.getAttribute('href'));
    if(window.innerWidth<992)closeSidebar();
  });
});
(function(){
  var saved=sessionStorage.getItem('parentActivePage');
  var frame=document.getElementById('mainFrame');
  var target=(saved&&saved.trim())?saved:'home.php';
  frame.src=target;setActive(target);
})();
function loadParentFrame(url){
  document.getElementById('mainFrame').src=url;
  setActive(url);
  sessionStorage.setItem('parentActivePage',url);
  if(window.innerWidth<992)closeSidebar();
}
const profileBtn=document.getElementById('profileBtn'),profileDrop=document.getElementById('profileDrop');
function closeDrop(){profileDrop.classList.remove('open');profileBtn.classList.remove('open');}
profileBtn.addEventListener('click',function(e){e.stopPropagation();profileDrop.classList.contains('open')?closeDrop():(profileDrop.classList.add('open'),profileBtn.classList.add('open'));});
document.addEventListener('click',function(e){if(!profileBtn.contains(e.target)&&!profileDrop.contains(e.target))closeDrop();});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
