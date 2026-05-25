<?php
session_start();
include "../includes/config.php";
include "../includes/calendar_functions.php";
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }

$active_year = getActiveAcademicYear($conn);
$year_id = $active_year['id'] ?? 0;
$year_title = htmlspecialchars($active_year['title'] ?? $active_year['year_name'] ?? 'Not Set');

$events = getAllEventsByYear($conn, $year_id);
$terms = getTermsByYear($conn, $year_id);

$calendar_events = [];

foreach ($events as $ev) {
    $badge = getEventTypeBadge($ev['event_type']);
    $end = $ev['end_date'] ?? $ev['start_date'];
    $calendar_events[] = [
        'title' => $ev['title'],
        'start' => $ev['start_date'],
        'end' => date('Y-m-d', strtotime($end . ' +1 day')),
        'backgroundColor' => $badge['color'],
        'borderColor' => $badge['color'],
        'textColor' => '#fff',
        'extendedProps' => [
            'type' => $badge['label'],
            'description' => $ev['description'] ?? '',
        ],
    ];
}

foreach ($terms as $t) {
    $calendar_events[] = [
        'title' => $t['term_name'] . ' Opens',
        'start' => $t['opening_date'],
        'backgroundColor' => '#059669',
        'borderColor' => '#059669',
        'textColor' => '#fff',
        'display' => 'list-item',
    ];
    $calendar_events[] = [
        'title' => $t['term_name'] . ' Closes',
        'start' => $t['closing_date'],
        'backgroundColor' => '#dc2626',
        'borderColor' => '#dc2626',
        'textColor' => '#fff',
        'display' => 'list-item',
    ];
    if (!empty($t['midterm_break_start']) && !empty($t['midterm_break_end'])) {
        $calendar_events[] = [
            'title' => 'Midterm Break (' . $t['term_name'] . ')',
            'start' => $t['midterm_break_start'],
            'end' => date('Y-m-d', strtotime($t['midterm_break_end'] . ' +1 day')),
            'backgroundColor' => '#f59e0b',
            'borderColor' => '#f59e0b',
            'textColor' => '#fff',
            'display' => 'background',
        ];
    }
}

$ongoing = getOngoingEvents($conn, $year_id, 5);
$upcoming = getUpcomingEvents($conn, $year_id, 5);
$current_term = getCurrentTerm($conn, $year_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Academic Calendar</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#f0f2f5;font-family:system-ui,-apple-system,sans-serif;padding:20px;color:#111827;}
.page-title{font-size:18px;font-weight:800;color:#0b2b3f;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.cal-wrap{background:#fff;border:1px solid #e2edf2;border-radius:16px;padding:16px;box-shadow:0 2px 8px rgba(0,0,0,.03);}
#calendar{min-height:500px;}
.side-panel .panel{background:#fff;border:1px solid #e2edf2;border-radius:16px;padding:14px;margin-bottom:12px;box-shadow:0 2px 8px rgba(0,0,0,.03);}
.side-panel .panel-title{font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;display:flex;align-items:center;gap:6px;}
.event-card{display:flex;gap:10px;padding:8px 10px;margin-bottom:6px;border-radius:10px;align-items:flex-start;}
.event-card:last-child{margin-bottom:0;}
.event-dot{width:8px;height:8px;border-radius:50%;margin-top:5px;flex-shrink:0;}
.event-info{flex:1;min-width:0;}
.event-title{font-size:12px;font-weight:700;}
.event-date{font-size:10px;color:#94a3b8;}
.event-type{font-size:10px;font-weight:600;}
.ongoing{background:#ecfdf5;}
.upcoming{background:#eef2ff;}
.term-banner{background:linear-gradient(135deg,#059669,#047857);color:#fff;border-radius:12px;padding:12px 14px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;}
.term-banner .label{font-size:10px;opacity:.8;text-transform:uppercase;letter-spacing:.5px;}
.term-banner .value{font-size:15px;font-weight:700;}
.fc-toolbar-title{font-size:16px!important;font-weight:700!important;}
.fc-button{font-size:12px!important;padding:4px 10px!important;}
.fc-daygrid-day-frame{min-height:80px!important;}
.fc .fc-button-primary{background-color:#6366f1!important;border-color:#6366f1!important;}
.fc .fc-button-primary:not(:disabled).fc-button-active{background-color:#4f46e5!important;}
</style>
</head>
<body>

<div class="page-title"><i class="bi bi-calendar3"></i> Academic Calendar — <?= $year_title ?></div>

<div class="row g-3">
    <!-- Side Panel -->
    <div class="col-md-3 side-panel">
        <?php if ($current_term): ?>
        <div class="term-banner">
            <div>
                <div class="label">Current Term</div>
                <div class="value"><?= htmlspecialchars($current_term['term_name']) ?></div>
            </div>
            <div style="text-align:right;">
                <div class="label">Opened</div>
                <div class="value" style="font-size:13px;"><?= htmlspecialchars($current_term['opening_date']) ?></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Ongoing Events -->
        <div class="panel">
            <div class="panel-title"><i class="bi bi-play-circle-fill" style="color:#10b981;"></i> Ongoing Events</div>
            <?php if (empty($ongoing)): ?>
            <div style="font-size:12px;color:#94a3b8;text-align:center;padding:10px;">No ongoing events.</div>
            <?php else: ?>
            <?php foreach ($ongoing as $ev):
                $badge = getEventTypeBadge($ev['event_type']);
            ?>
            <div class="event-card ongoing">
                <div class="event-dot" style="background:#10b981;"></div>
                <div class="event-info">
                    <div class="event-title"><?= htmlspecialchars($ev['title']) ?></div>
                    <div class="event-type" style="color:<?= $badge['color'] ?>;"><?= $badge['label'] ?></div>
                    <div class="event-date"><?= htmlspecialchars($ev['start_date']) ?> <?= $ev['end_date'] ? '— ' . htmlspecialchars($ev['end_date']) : '' ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Upcoming Events -->
        <div class="panel">
            <div class="panel-title"><i class="bi bi-arrow-right-circle-fill" style="color:#6366f1;"></i> Upcoming Events</div>
            <?php if (empty($upcoming)): ?>
            <div style="font-size:12px;color:#94a3b8;text-align:center;padding:10px;">No upcoming events.</div>
            <?php else: ?>
            <?php foreach ($upcoming as $ev):
                $badge = getEventTypeBadge($ev['event_type']);
            ?>
            <div class="event-card upcoming">
                <div class="event-dot" style="background:#6366f1;"></div>
                <div class="event-info">
                    <div class="event-title"><?= htmlspecialchars($ev['title']) ?></div>
                    <div class="event-type" style="color:<?= $badge['color'] ?>;"><?= $badge['label'] ?></div>
                    <div class="event-date"><?= htmlspecialchars($ev['start_date']) ?> <?= $ev['end_date'] ? '— ' . htmlspecialchars($ev['end_date']) : '' ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Legend -->
        <div class="panel">
            <div class="panel-title"><i class="bi bi-palette"></i> Legend</div>
            <div style="font-size:11px;">
                <?php foreach (['exam'=>'Exam','mock'=>'Mock','terminal'=>'Terminal','annual'=>'Annual','national'=>'National','meeting'=>'Meeting','sports'=>'Sports','academic'=>'Academic','holiday'=>'Holiday','trip'=>'Trip'] as $type => $label):
                    $badge = getEventTypeBadge($type);
                ?>
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                    <span style="width:10px;height:10px;border-radius:50%;background:<?= $badge['color'] ?>;display:inline-block;"></span>
                    <?= $label ?>
                </div>
                <?php endforeach; ?>
                <div style="display:flex;align-items:center;gap:6px;margin-top:6px;padding-top:6px;border-top:1px solid #f1f5f9;">
                    <span style="width:10px;height:10px;border-radius:50%;background:#059669;display:inline-block;"></span>
                    Term Open
                </div>
                <div style="display:flex;align-items:center;gap:6px;">
                    <span style="width:10px;height:10px;border-radius:50%;background:#dc2626;display:inline-block;"></span>
                    Term Close
                </div>
                <div style="display:flex;align-items:center;gap:6px;">
                    <span style="width:16px;height:10px;border-radius:2px;background:#f59e0b;display:inline-block;"></span>
                    Midterm Break
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar -->
    <div class="col-md-9">
        <div class="cal-wrap">
            <div id="calendar"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="eventModalTitle">Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="eventModalBody"></div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var events = <?= json_encode($calendar_events) ?>;

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listMonth'
        },
        locale: 'en',
        firstDay: 1,
        height: 'auto',
        events: events,
        eventClick: function(info) {
            var props = info.event.extendedProps;
            var html = '<div style="margin-bottom:8px;"><strong>' + info.event.title + '</strong></div>';
            if (props.type) html += '<div style="font-size:12px;color:#64748b;margin-bottom:4px;"><span style="font-weight:600;">Type:</span> ' + props.type + '</div>';
            var start = info.event.start ? info.event.start.toLocaleDateString() : '';
            var end = info.event.end ? info.event.end.toLocaleDateString() : '';
            if (start) html += '<div style="font-size:12px;color:#64748b;margin-bottom:4px;"><span style="font-weight:600;">Start:</span> ' + start + '</div>';
            if (end && end !== start) html += '<div style="font-size:12px;color:#64748b;margin-bottom:4px;"><span style="font-weight:600;">End:</span> ' + end + '</div>';
            if (props.description) html += '<hr style="margin:10px 0;"><div style="font-size:13px;color:#374151;">' + props.description + '</div>';
            document.getElementById('eventModalTitle').textContent = info.event.title;
            document.getElementById('eventModalBody').innerHTML = html;
            new bootstrap.Modal(document.getElementById('eventModal')).show();
        },
        dayCellClassNames: function(arg) {
            var today = new Date();
            if (arg.date.getFullYear() === today.getFullYear() &&
                arg.date.getMonth() === today.getMonth() &&
                arg.date.getDate() === today.getDate()) {
                return ['fc-day-today-custom'];
            }
            return [];
        }
    });

    calendar.render();
});
</script>

<style>
.fc-day-today-custom .fc-daygrid-day-top {
    background: #6366f1 !important;
    color: #fff !important;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.fc .fc-day-today {
    background: #eef2ff !important;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
