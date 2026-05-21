<?php
session_start();
include "../includes/config.php";

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Verify admin role
$admin_id = $_SESSION['admin_id'];
$check = mysqli_query($conn, "SELECT role, email FROM admins WHERE id='$admin_id'");
$data = mysqli_fetch_assoc($check);

if ($data['role'] != 'admin') {
    session_destroy();
    header("Location: ../login.php");
    exit();
}
$admin_email = $data['email'] ?? 'Admin';

// Fetch stats
$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM students"))['total'];
$total_teachers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM teachers"))['total'];
$total_exams = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM exams"))['total'];
$total_announcements = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM announcements WHERE status='published'"))['total'];
$pending_admissions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM admissions WHERE status='Pending'"))['total'];
$unread_messages = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM messages WHERE status='unread'"))['total'];
$total_notifications = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM notifications WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"))['total'] ?? 0;

// Active year
$active_year = mysqli_fetch_assoc(mysqli_query($conn, "SELECT year_name FROM academic_years WHERE is_active=1 LIMIT 1"));
$active_year_name = $active_year ? $active_year['year_name'] : 'Not Set';

// Recent data
$recent_admissions = mysqli_query($conn, "SELECT application_no, first_name, last_name, entry_level, status, created_at FROM admissions ORDER BY created_at DESC LIMIT 5");
$recent_announcements = mysqli_query($conn, "SELECT title, content, type, created_at FROM announcements WHERE status='published' ORDER BY created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, viewport-fit=cover">
<title>Admin Dashboard · Kitukutu Secondary</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: #f4f7fc;
    font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
    overflow-x: hidden;
}

/* SIDEBAR - mobile first */
.sidebar {
    position: fixed;
    top: 0;
    left: -280px;
    width: 280px;
    height: 100%;
    background: linear-gradient(180deg, #0b2b3f 0%, #071a24 100%);
    z-index: 1050;
    transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    padding: 1.2rem 0.8rem;
    overflow-y: auto;
    box-shadow: 4px 0 20px rgba(0,0,0,0.15);
}

.sidebar.active {
    left: 0;
}

.sidebar-header {
    padding-bottom: 1.2rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    margin-bottom: 1.2rem;
}

.sidebar-header h4 {
    color: white;
    font-weight: 700;
    font-size: 1.3rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.sidebar-header small {
    color: #a0c4e0;
    font-size: 0.7rem;
    display: block;
    margin-top: 5px;
}

.sidebar-nav {
    list-style: none;
    padding: 0;
}

.sidebar-nav li {
    margin-bottom: 6px;
}

.sidebar-nav a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    color: #cbdbe0;
    text-decoration: none;
    border-radius: 12px;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.2s;
}

.sidebar-nav a i {
    font-size: 1.2rem;
    width: 24px;
}

.sidebar-nav a:hover {
    background: rgba(244, 180, 0, 0.12);
    color: white;
    transform: translateX(4px);
}

.sidebar-nav a.active {
    background: #f4b400;
    color: #0b2b3f;
    font-weight: 600;
}

.badge-msg {
    background: #f4b400;
    color: #0b2b3f;
    border-radius: 30px;
    padding: 2px 8px;
    font-size: 0.7rem;
    font-weight: bold;
    margin-left: auto;
}

/* overlay for mobile */
.overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1040;
    display: none;
}

.overlay.active {
    display: block;
}

/* main content */
.main-content {
    margin-left: 0;
    padding: 0.8rem;
    transition: all 0.3s;
}

.content-frame {
    width: 100%;
    border: 0;
    border-radius: 20px;
    background: white;
    min-height: calc(100vh - 110px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
}

/* topbar */
.topbar {
    background: white;
    border-radius: 20px;
    padding: 0.6rem 1rem;
    margin-bottom: 1.2rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    border: 1px solid #e2edf2;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.menu-toggle {
    background: #f4b400;
    border: none;
    padding: 8px 12px;
    border-radius: 12px;
    color: #0b2b3f;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 6px;
}

.year-badge {
    background: #eef3fa;
    padding: 5px 12px;
    border-radius: 40px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* stat cards */
.stats-wrapper {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px;
    margin-bottom: 1.5rem;
}

.stat-item {
    background: white;
    border-radius: 20px;
    padding: 1rem 0.5rem;
    text-align: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    border: 1px solid #e6edf2;
    transition: all 0.2s;
}

.stat-number {
    font-size: 1.7rem;
    font-weight: 800;
    color: #0b2b3f;
    line-height: 1.2;
}

.stat-label {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #6b8a9e;
    letter-spacing: 0.3px;
}

/* cards */
.custom-card {
    background: white;
    border-radius: 24px;
    border: 1px solid #e6edf2;
    margin-bottom: 1.2rem;
    overflow: hidden;
}

.card-title {
    padding: 1rem 1rem 0.2rem 1rem;
    font-weight: 700;
    font-size: 1rem;
    border-bottom: 2px solid #f4b40040;
    margin-bottom: 0;
}

.table-responsive-custom {
    overflow-x: auto;
}

.table-custom {
    width: 100%;
    font-size: 0.8rem;
}

.table-custom th {
    padding: 0.8rem 0.8rem 0.5rem;
    font-weight: 600;
    color: #5c7c94;
    border-bottom: 1px solid #edf2f7;
}

.table-custom td {
    padding: 0.6rem 0.8rem;
    border-bottom: 1px solid #f0f5fa;
}

.status-pending {
    background: #fff3e0;
    color: #e67e22;
    padding: 2px 10px;
    border-radius: 30px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-block;
}

.status-approved {
    background: #e8f5e9;
    color: #2e7d32;
    padding: 2px 10px;
    border-radius: 30px;
    font-size: 0.7rem;
    font-weight: 600;
}

/* desktop view */
@media (min-width: 992px) {
    .sidebar {
        left: 0;
        width: 260px;
    }
    
    .main-content {
        margin-left: 260px;
        padding: 1rem 1.5rem;
    }
    
    .menu-toggle {
        display: none;
    }
    
    .overlay {
        display: none !important;
    }
    
    .stats-wrapper {
        gap: 1rem;
    }
    
    .stat-number {
        font-size: 2rem;
    }
}

@media (max-width: 576px) {
    .stats-wrapper {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    
    .stat-number {
        font-size: 1.4rem;
    }
    
    .topbar {
        flex-direction: row;
    }
}

/* ── Welcome section ── */
.top-welcome{flex:1;min-width:0;padding:0 6px;}
.top-welcome .greeting{font-size:11px;color:#6b8a9e;}
.top-welcome .wname{font-size:14px;font-weight:800;color:#0b2b3f;line-height:1.1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.top-welcome .wdate{font-size:11px;color:#6b8a9e;margin-top:1px;}
@media(max-width:480px){.top-welcome .wdate{display:none;}}

/* ── Topbar right-side actions ── */
.topbar-actions {
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Notification bell */
.notif-btn {
    position: relative;
    background: #f4f7fc;
    border: 1px solid #e2edf2;
    border-radius: 50%;
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #0b2b3f;
    font-size: 17px;
    transition: background .15s;
    text-decoration: none;
}
.notif-btn:hover { background: #e9f0f7; color: #0b2b3f; }
.notif-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #ef4444;
    color: #fff;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 10px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #fff;
}

/* Profile dropdown */
.profile-wrap {
    position: relative;
}
.profile-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #f4f7fc;
    border: 1px solid #e2edf2;
    border-radius: 40px;
    padding: 4px 10px 4px 4px;
    cursor: pointer;
    transition: background .15s;
}
.profile-btn:hover { background: #e9f0f7; }
.avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0b2b3f, #1a5276);
    color: #fff;
    font-weight: 700;
    font-size: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.profile-btn .name-text {
    font-size: 12px;
    font-weight: 600;
    color: #1a1a2e;
    max-width: 110px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.profile-btn .chevron {
    font-size: 10px;
    color: #6b7280;
    transition: transform .2s;
}
.profile-btn.open .chevron { transform: rotate(180deg); }
.profile-dropdown {
    display: none;
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    background: #fff;
    border: 1px solid #e2edf2;
    border-radius: 14px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    min-width: 200px;
    z-index: 2000;
    overflow: hidden;
}
.profile-dropdown.open { display: block; }
.pd-header {
    padding: 12px 14px 10px;
    border-bottom: 1px solid #f0f5fa;
    font-size: 12px;
    color: #6b7280;
}
.pd-header .pd-name {
    font-weight: 700;
    font-size: 13px;
    color: #111827;
    margin-bottom: 2px;
}
.profile-dropdown a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    font-size: 13px;
    color: #111827;
    text-decoration: none;
    transition: background .12s;
}
.profile-dropdown a:hover { background: #f4f7fc; }
.profile-dropdown a i { font-size: 16px; width: 20px; }
.pd-logout { color: #ef4444 !important; border-top: 1px solid #f0f5fa; }
.pd-logout i { color: #ef4444; }

/* ── Grouped nav ── */
.nav-single { margin-bottom: 3px; }
.nav-single > a {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 12px; border-radius: 12px;
    color: #cbdbe0; text-decoration: none;
    font-size: 0.9rem; font-weight: 500; transition: all 0.2s;
}
.nav-single > a i { font-size: 1.15rem; width: 22px; flex-shrink: 0; }
.nav-single > a:hover { background: rgba(244,180,0,0.12); color: #fff; transform: translateX(3px); }
.nav-single > a.active { background: #f4b400; color: #0b2b3f; font-weight: 600; transform: none; }

.nav-group { margin-bottom: 3px; }
.nav-grp-hdr {
    display: flex; align-items: center; justify-content: space-between;
    padding: 8px 12px; border-radius: 10px;
    color: #7aa3bc; font-size: 0.72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.6px;
    cursor: pointer; user-select: none; transition: all 0.2s;
}
.nav-grp-hdr:hover { background: rgba(255,255,255,0.06); color: #cce3f0; }
.nav-grp-hdr.open { color: #f4b400; }
.nav-grp-hdr .gleft { display: flex; align-items: center; gap: 9px; }
.nav-grp-hdr .gleft i { font-size: 1rem; }
.nav-grp-hdr .gchev { font-size: 10px; transition: transform 0.2s; flex-shrink: 0; }
.nav-grp-hdr.open .gchev { transform: rotate(180deg); }

.nav-sub { display: none; padding-left: 8px; margin-top: 2px; }
.nav-sub.open { display: block; }
.nav-sub li { margin-bottom: 1px; list-style: none; }
.nav-sub a {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 12px; border-radius: 9px;
    color: #9bbfd4; text-decoration: none;
    font-size: 0.875rem; font-weight: 500; transition: all 0.18s;
}
.nav-sub a i { font-size: 1rem; width: 20px; flex-shrink: 0; }
.nav-sub a:hover { background: rgba(244,180,0,0.12); color: #fff; transform: translateX(3px); }
.nav-sub a.active { background: #f4b400; color: #0b2b3f; font-weight: 600; transform: none; }
</style>
</head>
<body>

<!-- Mobile Overlay -->
<div class="overlay" id="overlay"></div>

<!-- Sidebar Navigation -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h4><i class="bi bi-shield-lock-fill"></i> Kitukutu</h4>
        <small>Secondary School Admin</small>
    </div>
    <ul class="sidebar-nav" style="list-style:none;padding:0;">

        <!-- Dashboard -->
        <li class="nav-single">
            <a href="home.php" target="mainFrame"><i class="bi bi-speedometer2"></i> Dashboard</a>
        </li>

        <!-- People -->
        <li class="nav-group">
            <div class="nav-grp-hdr" onclick="toggleGroup(this)">
                <span class="gleft"><i class="bi bi-people-fill"></i> People</span>
                <i class="bi bi-chevron-down gchev"></i>
            </div>
            <ul class="nav-sub">
                <li><a href="manage_staff.php" target="mainFrame"><i class="bi bi-person-badge-fill"></i> Staff (Wafanyakazi)</a></li>
                <li><a href="add_student.php" target="mainFrame"><i class="bi bi-people"></i> Students</a></li>
                <li><a href="admissions.php" target="mainFrame"><i class="bi bi-journal-check"></i> Admissions</a></li>
            </ul>
        </li>

        <!-- Exams & Results -->
        <li class="nav-group">
            <div class="nav-grp-hdr" onclick="toggleGroup(this)">
                <span class="gleft"><i class="bi bi-clipboard2-check-fill"></i> Exams & Results</span>
                <i class="bi bi-chevron-down gchev"></i>
            </div>
            <ul class="nav-sub">
                <li><a href="exam_management.php" target="mainFrame"><i class="bi bi-pencil-square"></i> Exams</a></li>
                <li><a href="view_exam_results.php" target="mainFrame"><i class="bi bi-bar-chart-steps"></i> Results</a></li>
                <li><a href="unprocessed_results.php" target="mainFrame"><i class="bi bi-hourglass-split"></i> Unprocessed</a></li>
                <li><a href="admin_exam_comparison.php" target="mainFrame"><i class="bi bi-graph-up"></i> Comparison</a></li>
                <li><a href="admin_student_report.php" target="mainFrame"><i class="bi bi-file-text"></i> Reports</a></li>
            </ul>
        </li>

        <!-- Academic -->
        <li class="nav-group">
            <div class="nav-grp-hdr" onclick="toggleGroup(this)">
                <span class="gleft"><i class="bi bi-book-fill"></i> Academic</span>
                <i class="bi bi-chevron-down gchev"></i>
            </div>
            <ul class="nav-sub">
                <li><a href="manage_periods.php" target="mainFrame"><i class="bi bi-calendar-week"></i> Periods</a></li>
                <li><a href="teaching_progress_overview.php" target="mainFrame"><i class="bi bi-graph-up-arrow"></i> Teaching Progress</a></li>
            </ul>
        </li>

        <!-- Communication -->
        <li class="nav-group">
            <div class="nav-grp-hdr" onclick="toggleGroup(this)">
                <span class="gleft"><i class="bi bi-chat-dots-fill"></i> Communication</span>
                <i class="bi bi-chevron-down gchev"></i>
            </div>
            <ul class="nav-sub">
                <li><a href="announcements.php" target="mainFrame"><i class="bi bi-megaphone"></i> Announcements</a></li>
                <li><a href="messages.php" target="mainFrame"><i class="bi bi-envelope"></i> Messages <?php if($unread_messages > 0): ?><span class="badge-msg"><?= $unread_messages ?></span><?php endif; ?></a></li>
                <li><a href="manage_alerts.php" target="mainFrame"><i class="bi bi-bell-fill"></i> Notifications</a></li>
                <li><a href="send_results_sms.php" target="mainFrame"><i class="bi bi-chat-dots"></i> SMS</a></li>
            </ul>
        </li>

    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
        <button class="menu-toggle" id="menuToggle">
            <i class="bi bi-list"></i> Menu
        </button>

        <!-- Welcome -->
        <?php
          $hour = (int)date('H');
          $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
          $admin_display = explode('@', $admin_email)[0];
        ?>
        <div class="top-welcome">
          <div class="greeting"><?= $greeting ?>,</div>
          <div class="wname">Administrator</div>
          <div class="wdate"><?= date('D, d M Y') ?> &bull; <?= htmlspecialchars($active_year_name) ?></div>
        </div>

        <div class="topbar-actions">

            <!-- Notification bell -->
            <a class="notif-btn" href="#" title="Notifications" onclick="loadAdminFrame('manage_alerts.php');return false;">
                <i class="bi bi-bell-fill"></i>
                <?php if ($total_notifications > 0): ?>
                <span class="notif-badge"><?= $total_notifications > 9 ? '9+' : $total_notifications ?></span>
                <?php endif; ?>
            </a>

            <!-- Profile dropdown -->
            <div class="profile-wrap">
                <button class="profile-btn" id="profileBtn" type="button">
                    <span class="avatar">A</span>
                    <span class="name-text"><?= htmlspecialchars(explode('@', $admin_email)[0]) ?></span>
                    <i class="bi bi-chevron-down chevron"></i>
                </button>
                <div class="profile-dropdown" id="profileDrop">
                    <div class="pd-header">
                        <div class="pd-name">Administrator</div>
                        <?= htmlspecialchars($admin_email) ?>
                    </div>
                    <a href="#" onclick="loadAdminFrame('change_password.php');closeDrop();return false;">
                        <i class="bi bi-key-fill text-primary"></i> Change Password
                    </a>
                    <a href="../logout.php" class="pd-logout">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <iframe
        title="Admin Content"
        class="content-frame"
        name="mainFrame"
        id="mainFrame"
        src=""
        loading="eager"
        referrerpolicy="no-referrer"
    ></iframe>
</div>

<script>
const menuBtn = document.getElementById('menuToggle');
const sidebar  = document.getElementById('sidebar');
const overlay  = document.getElementById('overlay');

function closeSidebar() { sidebar.classList.remove('active'); overlay.classList.remove('active'); }
function openSidebar()  { sidebar.classList.add('active');    overlay.classList.add('active'); }

if (menuBtn) {
    menuBtn.addEventListener('click', function(e) {
        e.preventDefault();
        sidebar.classList.contains('active') ? closeSidebar() : openSidebar();
    });
}
overlay.addEventListener('click', closeSidebar);
window.addEventListener('resize', function() { if (window.innerWidth >= 992) closeSidebar(); });

/* ── Group accordion ── */
function toggleGroup(hdr) {
    const sub    = hdr.nextElementSibling;
    const isOpen = sub.classList.contains('open');
    // close all groups first
    document.querySelectorAll('.nav-grp-hdr').forEach(h => {
        h.classList.remove('open');
        h.nextElementSibling.classList.remove('open');
    });
    if (!isOpen) { hdr.classList.add('open'); sub.classList.add('open'); }
}

function openGroupForLink(link) {
    const sub = link.closest('.nav-sub');
    if (sub) {
        sub.classList.add('open');
        const hdr = sub.previousElementSibling;
        if (hdr) hdr.classList.add('open');
    }
}

/* ── Link activation ── */
const allLinks = document.querySelectorAll('.sidebar-nav a[target="mainFrame"]');

function setActive(href) {
    allLinks.forEach(l => l.classList.remove('active'));
    allLinks.forEach(l => {
        if (l.getAttribute('href') === href) {
            l.classList.add('active');
            openGroupForLink(l);
        }
    });
}

allLinks.forEach(link => {
    link.addEventListener('click', function() {
        setActive(this.getAttribute('href'));
        sessionStorage.setItem('adminActivePage', this.getAttribute('href'));
        if (window.innerWidth < 992) closeSidebar();
    });
});

/* ── Restore last page ── */
(function() {
    var saved = sessionStorage.getItem('adminActivePage');
    var frame = document.getElementById('mainFrame');
    var target = (saved && saved.trim()) ? saved : 'home.php';
    frame.src = target;
    setActive(target);
})();

/* ── Profile dropdown ── */
const profileBtn  = document.getElementById('profileBtn');
const profileDrop = document.getElementById('profileDrop');

function closeDrop() { profileDrop.classList.remove('open'); profileBtn.classList.remove('open'); }

profileBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    profileDrop.classList.contains('open') ? closeDrop() : (profileDrop.classList.add('open'), profileBtn.classList.add('open'));
});
document.addEventListener('click', function(e) {
    if (!profileBtn.contains(e.target) && !profileDrop.contains(e.target)) closeDrop();
});

function loadAdminFrame(url) {
    document.getElementById('mainFrame').src = url;
    setActive(url);
    sessionStorage.setItem('adminActivePage', url);
    if (window.innerWidth < 992) closeSidebar();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
