<?php
session_start();
include "../includes/config.php";
include "../includes/calendar_functions.php";
if (!isset($_SESSION['teacher_id'])) { header("Location: ../login.php"); exit(); }
$teacher_id = intval($_SESSION['teacher_id']);

/* Teacher info (name shown in parent topbar, not here) */

/* Active year & term */
$year = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id, year_name FROM academic_years WHERE is_active=1 LIMIT 1")) ?? [];
$term = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id, term_name FROM terms WHERE is_active=1 LIMIT 1")) ?? [];
$year_id = intval($year['id'] ?? 0);
$term_id = intval($term['id'] ?? 0);

/* My subjects */
$subs_q = mysqli_query($conn,"
    SELECT ta.*, s.subject_name, s.stream, ta.class_stream, ta.form_level,
           (SELECT COUNT(DISTINCT ss2.id) FROM student_subjects ss2 WHERE ss2.subject_id=s.id) AS student_count
    FROM teacher_assignments ta
    JOIN subjects s ON s.id = ta.subject_id
    WHERE ta.teacher_id = $teacher_id
    ORDER BY s.subject_name, ta.form_level
");
$subjects = [];
while ($r = mysqli_fetch_assoc($subs_q)) $subjects[] = $r;
$total_subjects = count($subjects);
$total_students = array_sum(array_column($subjects, 'student_count'));

/* Teaching progress (current term) */
$tp = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT COUNT(t.id) AS total_topics,
           SUM(t.teaching_status='Taught') AS taught,
           SUM(t.teaching_status='In Progress') AS inprog
    FROM subject_settings ss
    JOIN topics t ON t.subject_setting_id = ss.id
    WHERE ss.teacher_id = $teacher_id
      AND ss.academic_year_id = $year_id
      AND ss.term_id = $term_id
      AND ss.is_active = 1
")) ?? ['total_topics'=>0,'taught'=>0,'inprog'=>0];
$taught_pct = $tp['total_topics'] > 0 ? round($tp['taught'] / $tp['total_topics'] * 100) : 0;

/* Unread notifications */
$unread_count = intval(mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT COUNT(*) AS c FROM notifications n
    WHERE (n.teacher_id=$teacher_id OR n.teacher_id IS NULL)
      AND NOT EXISTS (SELECT 1 FROM notification_reads nr WHERE nr.notification_id=n.id AND nr.teacher_id=$teacher_id)
"))['c'] ?? 0);

/* Recent notifications (last 3 unread) */
$notifs_q = mysqli_query($conn,"
    SELECT n.title, n.message, n.type, n.created_at
    FROM notifications n
    LEFT JOIN notification_reads nr ON nr.notification_id=n.id AND nr.teacher_id=$teacher_id
    WHERE (n.teacher_id=$teacher_id OR n.teacher_id IS NULL)
      AND nr.teacher_id IS NULL
    ORDER BY n.created_at DESC LIMIT 3
");
$notifs = [];
while ($r = mysqli_fetch_assoc($notifs_q)) $notifs[] = $r;

/* Calendar — Academic year & term */
$cal_year = getActiveAcademicYear($conn);
$cal_year_id = intval($cal_year['id'] ?? 0);
$cal_year_title = htmlspecialchars($cal_year['title'] ?? $cal_year['year_name'] ?? '');
$current_term = getCurrentTerm($conn, $cal_year_id);
$ongoing_events = getOngoingEvents($conn, $cal_year_id, 3);
$upcoming_events = getUpcomingEvents($conn, $cal_year_id, 3);

/* Active exams */
$exams_q = mysqli_query($conn,"
    SELECT e.exam_name, e.start_date, e.end_date, ec.category_name
    FROM exams e
    LEFT JOIN exam_categories ec ON ec.id = e.category_id
    WHERE e.is_active = 1
    ORDER BY e.start_date DESC
");
$active_exams = [];
while ($r = mysqli_fetch_assoc($exams_q)) $active_exams[] = $r;

/* Timetable */
$periods_q = mysqli_query($conn,"
    SELECT p.*, s.subject_name
    FROM periods p
    LEFT JOIN subjects s ON s.id = p.subject_id
    WHERE p.teacher_id = $teacher_id AND p.academic_year_id = $year_id
    ORDER BY FIELD(p.day,'Monday','Tuesday','Wednesday','Thursday','Friday'), p.start_time
");
$periods = [];
while ($r = mysqli_fetch_assoc($periods_q)) $periods[] = $r;
$timetable = [];
foreach ($periods as $p) $timetable[$p['day']][] = $p;
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Home</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{
  --primary:#6366f1;--primary-dark:#4f46e5;--primary-light:#ede9fe;
  --success:#10b981;--warn:#f59e0b;--danger:#ef4444;
  --bg:#f3f4f6;--card:#fff;--border:#e5e7eb;--text:#111827;--muted:#6b7280;
  --navy:#0f2744;
  --radius:14px;
}
*{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--bg);font-family:system-ui,-apple-system,sans-serif;color:var(--text);padding:14px;}

/* Stats */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px;}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:14px 12px;text-align:center;}
.stat-card .stat-val{font-size:26px;font-weight:800;line-height:1;}
.stat-card .stat-lbl{font-size:11px;color:var(--muted);margin-top:3px;}
.stat-card .stat-sub{font-size:10px;margin-top:4px;font-weight:600;}

/* Section header */
.sec-hdr{font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;display:flex;align-items:center;gap:6px;}
.sec-hdr .badge-count{background:var(--primary-light);color:var(--primary-dark);border-radius:20px;padding:1px 9px;font-size:11px;text-transform:none;font-weight:700;}

/* Cards */
.panel{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:14px;margin-bottom:12px;}

/* Subject chips */
.subj-chip{
  display:flex;align-items:center;gap:8px;
  padding:9px 11px;border:1px solid var(--border);border-radius:10px;
  margin-bottom:7px;background:#fafafa;
}
.subj-chip:last-child{margin-bottom:0;}
.chip-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
.chip-name{font-size:13px;font-weight:600;flex:1;}
.chip-meta{font-size:11px;color:var(--muted);}
.chip-stu{font-size:11px;font-weight:700;color:var(--primary);}

/* Notification items */
.notif-item{display:flex;gap:10px;align-items:flex-start;padding:9px 0;border-bottom:1px solid var(--border);}
.notif-item:last-child{border-bottom:none;padding-bottom:0;}
.notif-icon{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;}
.notif-title{font-size:12px;font-weight:700;}
.notif-msg{font-size:11px;color:var(--muted);margin-top:1px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.notif-time{font-size:10px;color:var(--muted);margin-top:2px;}

/* Timetable */
.day-block{margin-bottom:10px;}
.day-label{font-size:11px;font-weight:800;color:var(--navy);text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px;padding-left:2px;}
.period-row{
  display:flex;align-items:center;gap:10px;
  background:var(--bg);border-radius:10px;padding:8px 10px;margin-bottom:4px;
}
.period-row:last-child{margin-bottom:0;}
.period-time{font-size:11px;font-weight:700;color:var(--muted);min-width:80px;}
.period-subj{font-size:13px;font-weight:600;flex:1;}

/* Active exam badge */
.exam-row{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border);}
.exam-row:last-child{border-bottom:none;padding-bottom:0;}
.exam-dot{width:8px;height:8px;border-radius:50%;background:var(--success);flex-shrink:0;}
.exam-name{font-size:13px;font-weight:600;flex:1;}
.exam-dates{font-size:11px;color:var(--muted);}

/* Empty */
.empty-msg{text-align:center;padding:20px 10px;color:var(--muted);font-size:13px;}
.empty-msg i{font-size:2rem;display:block;margin-bottom:6px;opacity:.3;}

@media(max-width:600px){
  body{padding:10px;}
  .stats-grid{grid-template-columns:repeat(2,1fr);gap:8px;}
  .welcome-banner{flex-wrap:wrap;gap:10px;}
  .date-pill{margin-left:0;}
  .stat-card .stat-val{font-size:22px;}
}
</style>
</head>
<body>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-val" style="color:var(--primary)"><?= $total_subjects ?></div>
    <div class="stat-lbl">My Subjects</div>
  </div>
  <div class="stat-card">
    <div class="stat-val" style="color:var(--navy)"><?= $total_students ?></div>
    <div class="stat-lbl">Students</div>
  </div>
  <div class="stat-card">
    <div class="stat-val" style="color:<?= $taught_pct >= 70 ? 'var(--success)' : ($taught_pct >= 30 ? 'var(--warn)' : 'var(--danger)') ?>"><?= $taught_pct ?>%</div>
    <div class="stat-lbl">Topics Taught</div>
    <div class="stat-sub" style="color:var(--muted)"><?= intval($tp['taught']) ?>/<?= intval($tp['total_topics']) ?> topics</div>
  </div>
  <div class="stat-card">
    <div class="stat-val" style="color:<?= $unread_count > 0 ? 'var(--danger)' : 'var(--success)' ?>"><?= $unread_count ?></div>
    <div class="stat-lbl">Notifications</div>
    <div class="stat-sub" style="color:<?= $unread_count > 0 ? 'var(--danger)' : 'var(--muted)' ?>"><?= $unread_count > 0 ? 'Unread' : 'All read' ?></div>
  </div>
</div>

<div class="row g-3">

  <!-- LEFT: Timetable -->
  <div class="col-md-7">
    <div class="panel">
      <div class="sec-hdr"><i class="bi bi-calendar3"></i> My Timetable</div>
      <?php if (empty($timetable)): ?>
      <div class="empty-msg"><i class="bi bi-calendar-x"></i>No timetable set for this year.</div>
      <?php else: ?>
      <?php foreach ($days as $day): if (empty($timetable[$day])) continue; ?>
      <div class="day-block">
        <div class="day-label"><?= $day ?></div>
        <?php foreach ($timetable[$day] as $p): ?>
        <div class="period-row">
          <div class="period-time"><?= date('H:i', strtotime($p['start_time'])) ?> – <?= date('H:i', strtotime($p['end_time'])) ?></div>
          <div class="period-subj"><?= htmlspecialchars($p['subject_name']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- RIGHT: Subjects + Notifications + Active Exams -->
  <div class="col-md-5">

    <!-- My Subjects -->
    <div class="panel">
      <div class="sec-hdr"><i class="bi bi-book-fill"></i> My Subjects <span class="badge-count"><?= $total_subjects ?></span></div>
      <?php if (empty($subjects)): ?>
      <div class="empty-msg"><i class="bi bi-book"></i>No subjects assigned.</div>
      <?php else: ?>
      <?php foreach ($subjects as $s):
        $col = strtoupper($s['stream']) === 'VOCATIONAL' ? '#9d174d' : '#1e40af';
        $bg  = strtoupper($s['stream']) === 'VOCATIONAL' ? '#fce7f3' : '#dbeafe';
        $fl  = str_replace('Form ','F. ',$s['form_level']??'F.1');
      ?>
      <div class="subj-chip">
        <div class="chip-dot" style="background:<?= $col ?>"></div>
        <div class="chip-name"><?= htmlspecialchars($s['subject_name']) ?></div>
        <div class="chip-meta"><?= htmlspecialchars($fl) ?><?= $s['class_stream'] ? ' · '.htmlspecialchars($s['class_stream']) : '' ?></div>
        <div class="chip-stu"><i class="bi bi-people-fill" style="font-size:10px"></i> <?= $s['student_count'] ?></div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Active Exams -->
    <?php if (!empty($active_exams)): ?>
    <div class="panel">
      <div class="sec-hdr"><i class="bi bi-clipboard-check"></i> Active Exams <span class="badge-count"><?= count($active_exams) ?></span></div>
      <?php foreach ($active_exams as $e): ?>
      <div class="exam-row">
        <div class="exam-dot"></div>
        <div class="exam-name"><?= htmlspecialchars($e['exam_name']) ?></div>
        <div class="exam-dates">
          <?= $e['start_date'] ? date('d M', strtotime($e['start_date'])) : '' ?>
          <?= $e['end_date'] ? ' – '.date('d M', strtotime($e['end_date'])) : '' ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Academic Year & Term -->
    <?php if ($cal_year_title || $current_term): ?>
    <div class="panel" style="background:linear-gradient(135deg,#059669,#047857);color:#fff;">
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <div>
          <div style="font-size:10px;opacity:.8;text-transform:uppercase;letter-spacing:.5px;">Academic Year</div>
          <div style="font-size:16px;font-weight:700;"><?= $cal_year_title ?></div>
        </div>
        <?php if ($current_term): ?>
        <div style="text-align:right;">
          <div style="font-size:10px;opacity:.8;text-transform:uppercase;letter-spacing:.5px;">Current Term</div>
          <div style="font-size:16px;font-weight:700;"><?= htmlspecialchars($current_term['term_name']) ?></div>
          <div style="font-size:10px;opacity:.8;"><?= htmlspecialchars($current_term['opening_date']) ?> — <?= htmlspecialchars($current_term['closing_date']) ?></div>
        </div>
        <?php endif; ?>
      </div>
      <div style="margin-top:8px;display:flex;gap:8px;">
        <a href="academic_calendar.php" target="mainFrame" style="background:rgba(255,255,255,.2);color:#fff;padding:6px 14px;border-radius:8px;text-decoration:none;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:4px;">
          <i class="bi bi-calendar3"></i> Full Calendar
        </a>
      </div>
    </div>
    <?php endif; ?>

    <!-- Ongoing Events -->
    <?php if (!empty($ongoing_events)): ?>
    <div class="panel">
      <div class="sec-hdr"><i class="bi bi-play-circle-fill" style="color:#10b981;"></i> Ongoing Events</div>
      <?php foreach ($ongoing_events as $ev):
        $_badge = getEventTypeBadge($ev['event_type']);
      ?>
      <div style="display:flex;gap:10px;padding:7px 0;border-bottom:1px solid var(--border);align-items:flex-start;">
        <div style="width:8px;height:8px;border-radius:50%;background:#10b981;margin-top:5px;flex-shrink:0;"></div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:12px;font-weight:700;"><?= htmlspecialchars($ev['title']) ?></div>
          <div style="font-size:10px;font-weight:600;color:<?= $_badge['color'] ?>;"><?= $_badge['label'] ?></div>
          <div style="font-size:10px;color:var(--muted);"><?= htmlspecialchars($ev['start_date']) ?> <?= $ev['end_date'] ? '— ' . htmlspecialchars($ev['end_date']) : '' ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Upcoming Events -->
    <?php if (!empty($upcoming_events)): ?>
    <div class="panel">
      <div class="sec-hdr"><i class="bi bi-arrow-right-circle-fill" style="color:#6366f1;"></i> Upcoming Events</div>
      <?php foreach ($upcoming_events as $ev):
        $_badge = getEventTypeBadge($ev['event_type']);
      ?>
      <div style="display:flex;gap:10px;padding:7px 0;border-bottom:1px solid var(--border);align-items:flex-start;">
        <div style="width:8px;height:8px;border-radius:50%;background:#6366f1;margin-top:5px;flex-shrink:0;"></div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:12px;font-weight:700;"><?= htmlspecialchars($ev['title']) ?></div>
          <div style="font-size:10px;font-weight:600;color:<?= $_badge['color'] ?>;"><?= $_badge['label'] ?></div>
          <div style="font-size:10px;color:var(--muted);"><?= htmlspecialchars($ev['start_date']) ?> <?= $ev['end_date'] ? '— ' . htmlspecialchars($ev['end_date']) : '' ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Recent Notifications -->
    <div class="panel">
      <div class="sec-hdr"><i class="bi bi-bell-fill"></i> Notifications
        <?php if ($unread_count > 0): ?>
        <span class="badge-count" style="background:#fee2e2;color:#991b1b"><?= $unread_count ?> unread</span>
        <?php endif; ?>
      </div>
      <?php if (empty($notifs)): ?>
      <div class="empty-msg"><i class="bi bi-bell-slash"></i>No new notifications.</div>
      <?php else: ?>
      <?php foreach ($notifs as $n):
        $ic  = $n['type'] === 'admin' ? 'bi-person-badge' : 'bi-robot';
        $ibg = $n['type'] === 'admin' ? '#dbeafe' : '#ede9fe';
        $ic_col = $n['type'] === 'admin' ? '#1d4ed8' : '#6366f1';
        $ago = (time() - strtotime($n['created_at']));
        $ago_str = $ago < 3600 ? round($ago/60).'m ago' : ($ago < 86400 ? round($ago/3600).'h ago' : date('d M', strtotime($n['created_at'])));
      ?>
      <div class="notif-item">
        <div class="notif-icon" style="background:<?= $ibg ?>;color:<?= $ic_col ?>"><i class="bi <?= $ic ?>"></i></div>
        <div style="flex:1;min-width:0;">
          <div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
          <div class="notif-msg"><?= htmlspecialchars($n['message']) ?></div>
          <div class="notif-time"><?= $ago_str ?></div>
        </div>
      </div>
      <?php endforeach; ?>
      <div style="margin-top:8px;text-align:right;">
        <a href="#" onclick="parent.loadTeacherFrame('notifications.php');return false;" style="font-size:12px;color:var(--primary);font-weight:600;text-decoration:none;">View all →</a>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>

</body>
</html>
