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
$has_active_col = mysqli_num_rows(mysqli_query($conn, "SHOW COLUMNS FROM students LIKE 'is_active'")) > 0;
$total_students = mysqli_fetch_assoc(mysqli_query($conn, $has_active_col ? "SELECT COUNT(*) as total FROM students WHERE is_active=1" : "SELECT COUNT(*) as total FROM students"))['total'];
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
<link href="../assets/css/shared.css" rel="stylesheet">
<link href="../assets/css/loader.css" rel="stylesheet">

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
                <li><a href="manage_staff.php" target="mainFrame"><i class="bi bi-person-badge-fill"></i> Manage Staff</a></li>
                <li><a href="add_teacher.php" target="mainFrame"><i class="bi bi-person-badge"></i> Add Teacher</a></li>
                <li><a href="add_student.php" target="mainFrame"><i class="bi bi-people"></i> Students</a></li>
                <li><a href="manage_student_subjects.php" target="mainFrame"><i class="bi bi-journal-bookmark-fill"></i> Student Subjects</a></li>
                <li><a href="admissions.php" target="mainFrame"><i class="bi bi-journal-check"></i> Admissions</a></li>
                <li><a href="manage_parents.php" target="mainFrame"><i class="bi bi-people-fill"></i> Parents</a></li>
                <li><a href="download_students.php" target="mainFrame"><i class="bi bi-download"></i> Download Students</a></li>
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
                <li><a href="bulk_student_results.php" target="mainFrame"><i class="bi bi-files"></i> Multi-Exam Results</a></li>
                <li><a href="term_ranking.php" target="mainFrame"><i class="bi bi-trophy"></i> Term Ranking</a></li>
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

        <!-- Teaching Docs -->
        <li class="nav-group">
            <div class="nav-grp-hdr" onclick="toggleGroup(this)">
                <span class="gleft"><i class="bi bi-book-fill"></i> Teaching Docs</span>
                <i class="bi bi-chevron-down gchev"></i>
            </div>
            <ul class="nav-sub">
                <li><a href="manage_curriculum.php" target="mainFrame"><i class="bi bi-book"></i> Curriculum</a></li>
                <li><a href="manage_syllabus.php" target="mainFrame"><i class="bi bi-journal-text"></i> Syllabus</a></li>
                <li><a href="manage_schemes.php" target="mainFrame"><i class="bi bi-calendar-week"></i> Scheme of Work</a></li>
                <li><a href="manage_lesson_plans.php" target="mainFrame"><i class="bi bi-file-earmark-text"></i> Lesson Plans</a></li>
                <li><a href="manage_subject_resources.php" target="mainFrame"><i class="bi bi-journal-richtext"></i> Books & Notes</a></li>
            </ul>
        </li>

        <!-- Calendar -->
        <li class="nav-group">
            <div class="nav-grp-hdr" onclick="toggleGroup(this)">
                <span class="gleft"><i class="bi bi-calendar3"></i> Calendar</span>
                <i class="bi bi-chevron-down gchev"></i>
            </div>
            <ul class="nav-sub">
                <li><a href="academic_years.php" target="mainFrame"><i class="bi bi-calendar-range"></i> Academic Years</a></li>
                <li><a href="academic_terms.php" target="mainFrame"><i class="bi bi-layers"></i> Terms</a></li>
                <li><a href="manage_events.php" target="mainFrame"><i class="bi bi-calendar-event"></i> Events</a></li>
                <li><a href="academic_calendar.php" target="mainFrame"><i class="bi bi-calendar3"></i> Calendar View</a></li>
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

        <!-- Parent Features -->
        <li class="nav-group">
            <div class="nav-grp-hdr" onclick="toggleGroup(this)">
                <span class="gleft"><i class="bi bi-house-heart-fill"></i> Parents</span>
                <i class="bi bi-chevron-down gchev"></i>
            </div>
            <ul class="nav-sub">
                <li><a href="post_assignments.php" target="mainFrame"><i class="bi bi-journal-check"></i> Assignments</a></li>
                <li><a href="manage_contributions.php" target="mainFrame"><i class="bi bi-cash-stack"></i> Contributions</a></li>
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

    <div class="breadcrumb-bar" id="breadcrumbBar"></div>
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

function updateBreadcrumb(href) {
    var bar = document.getElementById('breadcrumbBar');
    if (!bar) return;
    var link = Array.from(allLinks).find(l => l.getAttribute('href') === href);
    if (!link) { bar.innerHTML = ''; return; }
    var pageText = link.textContent.trim().replace(/\s+/g, ' ');
    var sub = link.closest('.nav-sub');
    var group = sub ? sub.previousElementSibling.querySelector('.gleft') : null;
    var groupText = group ? group.textContent.trim() : 'Admin';
    bar.innerHTML = '<span>' + groupText + '</span>'
      + '<span class="bc-sep">›</span>'
      + '<span class="bc-page">' + pageText + '</span>';
}

function setActive(href) {
    allLinks.forEach(l => l.classList.remove('active'));
    allLinks.forEach(l => {
        if (l.getAttribute('href') === href) {
            l.classList.add('active');
            openGroupForLink(l);
        }
    });
    updateBreadcrumb(href);
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
<script src="../assets/js/loader.js"></script>
</body>
</html>
