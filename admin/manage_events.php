<?php
session_start();
include "../includes/config.php";
include "../includes/calendar_functions.php";
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }
$admin_id = intval($_SESSION['admin_id']);

$active_year = getActiveAcademicYear($conn);
$year_id = $active_year['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $ay_id = intval($_POST['academic_year_id'] ?? $year_id);
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $desc = mysqli_real_escape_string($conn, $_POST['description']);
        $type = mysqli_real_escape_string($conn, $_POST['event_type']);
        $start = mysqli_real_escape_string($conn, $_POST['start_date']);
        $end = !empty($_POST['end_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['end_date']) . "'" : 'NULL';

        if ($action === 'create') {
            mysqli_query($conn, "INSERT INTO school_events (academic_year_id, title, description, event_type, start_date, end_date, created_by) VALUES ($ay_id, '$title', '$desc', '$type', '$start', $end, $admin_id)");
            $_SESSION['flash'] = ['msg' => 'Event created.', 'type' => 'success'];
        } else {
            $id = intval($_POST['id']);
            mysqli_query($conn, "UPDATE school_events SET title='$title', description='$desc', event_type='$type', start_date='$start', end_date=$end WHERE id=$id");
            $_SESSION['flash'] = ['msg' => 'Event updated.', 'type' => 'success'];
        }
    }

    if ($action === 'delete') {
        $id = intval($_POST['id']);
        mysqli_query($conn, "DELETE FROM school_events WHERE id = $id");
        $_SESSION['flash'] = ['msg' => 'Event deleted.', 'type' => 'success'];
    }

    header("Location: manage_events.php");
    exit();
}

$events_q = mysqli_query($conn, "
    SELECT se.*, COALESCE(ay.title, ay.year_name) AS year_title
    FROM school_events se
    LEFT JOIN academic_years ay ON ay.id = se.academic_year_id
    ORDER BY se.start_date DESC
");
$years = mysqli_query($conn, "SELECT * FROM academic_years ORDER BY created_at DESC");

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>School Events</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#f0f2f5;font-family:system-ui,-apple-system,sans-serif;padding:20px;color:#111827;}
.page-title{font-size:18px;font-weight:800;color:#0b2b3f;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.card{background:#fff;border:1px solid #e2edf2;border-radius:16px;padding:20px;margin-bottom:16px;box-shadow:0 2px 8px rgba(0,0,0,.03);}
.table{width:100%;border-collapse:collapse;font-size:13px;}
.table th{background:#f8fafc;padding:10px 12px;text-align:left;font-weight:700;color:#475569;border-bottom:2px solid #e2e8f0;}
.table td{padding:10px 12px;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
.table tr:hover td{background:#f8fafc;}
.event-badge{display:inline-block;padding:2px 10px;border-radius:20px;font-size:10px;font-weight:700;}
.btn-sm{padding:5px 12px;font-size:12px;border-radius:8px;border:none;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px;}
.btn-primary-sm{background:#dbeafe;color:#1d4ed8;}
.btn-danger-sm{background:#fee2e2;color:#991b1b;}
.f-group{margin-bottom:12px;}
.f-label{display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:3px;}
.f-input,.f-select{width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;}
.f-input:focus,.f-select:focus{border-color:#6366f1;outline:none;box-shadow:0 0 0 3px rgba(99,102,241,.1);}
.f-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.flash{padding:10px 14px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:12px;display:flex;align-items:center;gap:8px;transition:opacity .5s;}
.flash.success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;}
.flash.error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;}
.empty{text-align:center;padding:30px;color:#94a3b8;font-size:14px;}
.event-ongoing{border-left:3px solid #10b981;}
.event-upcoming{border-left:3px solid #6366f1;}
.event-past{border-left:3px solid #d1d5db;}
textarea.f-input{resize:vertical;min-height:70px;}
.modal-content{border-radius:12px;border:none;box-shadow:0 8px 30px rgba(0,0,0,.12);}
</style>
</head>
<body>

<div class="page-title"><i class="bi bi-calendar-event"></i> School Events</div>

<?php if ($flash): ?>
<div class="flash <?= $flash['type'] ?>" id="flashMsg"><i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill' ?>"></i> <?= htmlspecialchars($flash['msg']) ?></div>
<?php endif; ?>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
        <div style="font-size:14px;font-weight:700;color:#0b2b3f;">All Events</div>
        <button class="btn-sm btn-primary-sm" data-bs-toggle="modal" data-bs-target="#createModal"><i class="bi bi-plus-lg"></i> New Event</button>
    </div>

    <?php if (mysqli_num_rows($events_q) == 0): ?>
    <div class="empty"><i class="bi bi-calendar2-x" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.3;"></i>No events yet.</div>
    <?php else: ?>
    <table class="table">
        <thead>
            <tr><th>Title</th><th>Type</th><th>Year</th><th>Start</th><th>End</th><th style="text-align:right;">Actions</th></tr>
        </thead>
        <tbody>
            <?php while ($ev = mysqli_fetch_assoc($events_q)):
                $badge = getEventTypeBadge($ev['event_type']);
                $now = time();
                $start = strtotime($ev['start_date']);
                $end = $ev['end_date'] ? strtotime($ev['end_date']) : $start;
                $row_class = ($now >= $start && $now <= $end) ? 'event-ongoing' : (($now < $start) ? 'event-upcoming' : 'event-past');
            ?>
            <tr class="<?= $row_class ?>">
                <td><strong><?= htmlspecialchars($ev['title']) ?></strong></td>
                <td><span class="event-badge" style="background:<?= $badge['bg'] ?>;color:<?= $badge['color'] ?>;"><?= $badge['label'] ?></span></td>
                <td style="font-size:11px;color:#64748b;"><?= htmlspecialchars($ev['year_title'] ?? '—') ?></td>
                <td><?= htmlspecialchars($ev['start_date']) ?></td>
                <td><?= htmlspecialchars($ev['end_date'] ?? '—') ?></td>
                <td style="text-align:right;">
                    <button class="btn-sm btn-primary-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $ev['id'] ?>"><i class="bi bi-pencil"></i> Edit</button>
                    <button class="btn-sm btn-danger-sm" onclick="confirmDelete('Delete this event? This cannot be undone.', <?= $ev['id'] ?>)"><i class="bi bi-trash3"></i></button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Confirm Delete Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="mb-3 text-danger"><i class="bi bi-exclamation-triangle-fill" style="font-size:2.5rem;"></i></div>
                <h6 style="font-size:0.95rem;font-weight:700;">Confirm Delete</h6>
                <p class="text-muted small mb-0" id="confirmMsg">Are you sure?</p>
            </div>
            <div class="modal-footer border-0 justify-content-center pt-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title"><i class="bi bi-calendar-plus"></i> New Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="academic_year_id" value="<?= $year_id ?>">
                    <div class="f-group">
                        <label class="f-label">Event Title</label>
                        <input class="f-input" name="title" required placeholder="Enter event title">
                    </div>
                    <div class="f-group">
                        <label class="f-label">Description</label>
                        <textarea class="f-input" name="description" rows="3" placeholder="Event description..."></textarea>
                    </div>
                    <div class="f-row">
                        <div class="f-group">
                            <label class="f-label">Event Type</label>
                            <select class="f-select" name="event_type">
                                <option value="exam">Exam</option>
                                <option value="mock">Mock</option>
                                <option value="terminal">Terminal</option>
                                <option value="annual">Annual</option>
                                <option value="national">National</option>
                                <option value="meeting">Meeting</option>
                                <option value="sports">Sports</option>
                                <option value="academic">Academic</option>
                                <option value="holiday">Holiday</option>
                                <option value="trip">Trip</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="f-group">
                            <label class="f-label">Academic Year</label>
                            <select class="f-select" name="academic_year_id">
                                <?php mysqli_data_seek($years, 0); while ($y = mysqli_fetch_assoc($years)): ?>
                                <option value="<?= $y['id'] ?>" <?= $y['id'] == $year_id ? 'selected' : '' ?>><?= htmlspecialchars($y['title'] ?? $y['year_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="f-row">
                        <div class="f-group">
                            <label class="f-label">Start Date</label>
                            <input class="f-input" type="date" name="start_date" required>
                        </div>
                        <div class="f-group">
                            <label class="f-label">End Date <span style="color:#94a3b8;font-weight:400;">(leave empty for single-day)</span></label>
                            <input class="f-input" type="date" name="end_date">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Create Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modals -->
<?php mysqli_data_seek($events_q, 0); while ($ev = mysqli_fetch_assoc($events_q)): ?>
<div class="modal fade" id="editModal<?= $ev['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= $ev['id'] ?>">
                    <div class="f-group">
                        <label class="f-label">Event Title</label>
                        <input class="f-input" name="title" value="<?= htmlspecialchars($ev['title']) ?>" required>
                    </div>
                    <div class="f-group">
                        <label class="f-label">Description</label>
                        <textarea class="f-input" name="description" rows="3"><?= htmlspecialchars($ev['description'] ?? '') ?></textarea>
                    </div>
                    <div class="f-row">
                        <div class="f-group">
                            <label class="f-label">Event Type</label>
                            <select class="f-select" name="event_type">
                                <?php foreach (['exam'=>'Exam','mock'=>'Mock','terminal'=>'Terminal','annual'=>'Annual','national'=>'National','meeting'=>'Meeting','sports'=>'Sports','academic'=>'Academic','holiday'=>'Holiday','trip'=>'Trip','other'=>'Other'] as $val => $label): ?>
                                <option value="<?= $val ?>" <?= $ev['event_type'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="f-group">
                            <label class="f-label">Academic Year</label>
                            <select class="f-select" name="academic_year_id">
                                <?php mysqli_data_seek($years, 0); while ($y = mysqli_fetch_assoc($years)): ?>
                                <option value="<?= $y['id'] ?>" <?= ($y['id'] == ($ev['academic_year_id'] ?? $year_id)) ? 'selected' : '' ?>><?= htmlspecialchars($y['title'] ?? $y['year_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="f-row">
                        <div class="f-group">
                            <label class="f-label">Start Date</label>
                            <input class="f-input" type="date" name="start_date" value="<?= $ev['start_date'] ?>" required>
                        </div>
                        <div class="f-group">
                            <label class="f-label">End Date</label>
                            <input class="f-input" type="date" name="end_date" value="<?= $ev['end_date'] ?? '' ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endwhile; ?>

<script>
var flashEl = document.getElementById('flashMsg');
if (flashEl) {
    setTimeout(function() {
        flashEl.style.opacity = '0';
        setTimeout(function() { flashEl.style.display = 'none'; }, 500);
    }, 4000);
}

var pendingId = null;

function confirmDelete(msg, id) {
    document.getElementById('confirmMsg').textContent = msg;
    pendingId = id;
    new bootstrap.Modal(document.getElementById('confirmModal')).show();
}

document.getElementById('confirmBtn').addEventListener('click', function() {
    if (pendingId) {
        var form = document.createElement('form');
        form.method = 'post';
        form.style.display = 'none';
        form.innerHTML = '<input name="id" value="' + pendingId + '"><input name="action" value="delete">';
        document.body.appendChild(form);
        form.submit();
    }
    bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
