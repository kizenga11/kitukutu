<?php
session_start();
include "../includes/config.php";
include "../includes/calendar_functions.php";
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }

$active_year = getActiveAcademicYear($conn);
$year_id = $active_year['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $ay_id = intval($_POST['academic_year_id']);
        $term_name = mysqli_real_escape_string($conn, $_POST['term_name']);
        $opening = mysqli_real_escape_string($conn, $_POST['opening_date']);
        $mid_start = mysqli_real_escape_string($conn, $_POST['midterm_break_start']);
        $mid_end = mysqli_real_escape_string($conn, $_POST['midterm_break_end']);
        $closing = mysqli_real_escape_string($conn, $_POST['closing_date']);
        $days = intval($_POST['teaching_days']);
        mysqli_query($conn, "INSERT INTO terms (academic_year_id, term_name, opening_date, midterm_break_start, midterm_break_end, closing_date, teaching_days) VALUES ($ay_id, '$term_name', '$opening', '$mid_start', '$mid_end', '$closing', $days)");
        $_SESSION['flash'] = ['msg' => 'Term created.', 'type' => 'success'];
    }

    if ($action === 'update') {
        $id = intval($_POST['id']);
        $term_name = mysqli_real_escape_string($conn, $_POST['term_name']);
        $opening = mysqli_real_escape_string($conn, $_POST['opening_date']);
        $mid_start = mysqli_real_escape_string($conn, $_POST['midterm_break_start']);
        $mid_end = mysqli_real_escape_string($conn, $_POST['midterm_break_end']);
        $closing = mysqli_real_escape_string($conn, $_POST['closing_date']);
        $days = intval($_POST['teaching_days']);
        mysqli_query($conn, "UPDATE terms SET term_name='$term_name', opening_date='$opening', midterm_break_start='$mid_start', midterm_break_end='$mid_end', closing_date='$closing', teaching_days=$days WHERE id=$id");
        $_SESSION['flash'] = ['msg' => 'Term updated.', 'type' => 'success'];
    }

    if ($action === 'delete') {
        $id = intval($_POST['id']);
        mysqli_query($conn, "DELETE FROM terms WHERE id = $id");
        $_SESSION['flash'] = ['msg' => 'Term deleted.', 'type' => 'success'];
    }

    header("Location: academic_terms.php");
    exit();
}

$years = mysqli_query($conn, "SELECT * FROM academic_years ORDER BY created_at DESC");
$selected_year_id = isset($_GET['year_id']) ? intval($_GET['year_id']) : $year_id;
$terms = $selected_year_id ? getTermsByYear($conn, $selected_year_id) : [];

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Academic Terms</title>
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
.badge-current{background:#d1fae5;color:#065f46;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;}
.btn-sm{padding:5px 12px;font-size:12px;border-radius:8px;border:none;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px;}
.btn-primary-sm{background:#dbeafe;color:#1d4ed8;}
.btn-danger-sm{background:#fee2e2;color:#991b1b;}
.f-group{margin-bottom:12px;}
.f-label{display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:3px;}
.f-input{width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;}
.f-input:focus{border-color:#6366f1;outline:none;box-shadow:0 0 0 3px rgba(99,102,241,.1);}
.f-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.flash{padding:10px 14px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:12px;display:flex;align-items:center;gap:8px;transition:opacity .5s;}
.flash.success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;}
.flash.error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;}
.empty{text-align:center;padding:30px;color:#94a3b8;font-size:14px;}
.year-select{display:flex;gap:10px;align-items:center;margin-bottom:14px;}
.year-select select{padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;background:#fff;}
.modal-content{border-radius:12px;border:none;box-shadow:0 8px 30px rgba(0,0,0,.12);}
</style>
</head>
<body>

<div class="page-title"><i class="bi bi-layers"></i> Academic Terms</div>

<?php if ($flash): ?>
<div class="flash <?= $flash['type'] ?>" id="flashMsg"><i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill' ?>"></i> <?= htmlspecialchars($flash['msg']) ?></div>
<?php endif; ?>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
        <div style="font-size:14px;font-weight:700;color:#0b2b3f;">Terms</div>
        <div style="display:flex;gap:8px;align-items:center;">
            <form method="get" class="year-select">
                <select name="year_id" onchange="this.form.submit()" style="padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;background:#fff;">
                    <?php while ($y = mysqli_fetch_assoc($years)): ?>
                    <option value="<?= $y['id'] ?>" <?= $y['id'] == $selected_year_id ? 'selected' : '' ?>><?= htmlspecialchars($y['title'] ?? $y['year_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </form>
            <button class="btn-sm btn-primary-sm" data-bs-toggle="modal" data-bs-target="#createModal"><i class="bi bi-plus-lg"></i> New Term</button>
        </div>
    </div>

    <?php if (empty($terms)): ?>
    <div class="empty"><i class="bi bi-layers" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.3;"></i>No terms for this academic year.</div>
    <?php else: ?>
    <table class="table">
        <thead>
            <tr><th>Term</th><th>Opening</th><th>Midterm Start</th><th>Midterm End</th><th>Closing</th><th>Teaching Days</th><th style="text-align:right;">Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($terms as $t):
                $is_current = (strtotime($t['opening_date']) <= time() && strtotime($t['closing_date']) >= time());
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($t['term_name']) ?></strong> <?php if ($is_current): ?><span class="badge-current">Current</span><?php endif; ?></td>
                <td><?= htmlspecialchars($t['opening_date'] ?? '—') ?></td>
                <td><?= htmlspecialchars($t['midterm_break_start'] ?? '—') ?></td>
                <td><?= htmlspecialchars($t['midterm_break_end'] ?? '—') ?></td>
                <td><?= htmlspecialchars($t['closing_date'] ?? '—') ?></td>
                <td><?= intval($t['teaching_days']) ?></td>
                <td style="text-align:right;">
                    <button class="btn-sm btn-primary-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $t['id'] ?>"><i class="bi bi-pencil"></i> Edit</button>
                    <button class="btn-sm btn-danger-sm" onclick="confirmDelete('Delete this term? This cannot be undone.', <?= $t['id'] ?>)"><i class="bi bi-trash3"></i></button>
                </td>
            </tr>
            <?php endforeach; ?>
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
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title"><i class="bi bi-plus-square"></i> New Term</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="academic_year_id" value="<?= $selected_year_id ?>">
                    <div class="f-group">
                        <label class="f-label">Term Name</label>
                        <input class="f-input" name="term_name" required placeholder="e.g. Term I">
                    </div>
                    <div class="f-row">
                        <div class="f-group">
                            <label class="f-label">Opening Date</label>
                            <input class="f-input" type="date" name="opening_date" required>
                        </div>
                        <div class="f-group">
                            <label class="f-label">Closing Date</label>
                            <input class="f-input" type="date" name="closing_date" required>
                        </div>
                    </div>
                    <div class="f-row">
                        <div class="f-group">
                            <label class="f-label">Midterm Break Start</label>
                            <input class="f-input" type="date" name="midterm_break_start">
                        </div>
                        <div class="f-group">
                            <label class="f-label">Midterm Break End</label>
                            <input class="f-input" type="date" name="midterm_break_end">
                        </div>
                    </div>
                    <div class="f-group">
                        <label class="f-label">Teaching Days</label>
                        <input class="f-input" type="number" name="teaching_days" min="1" placeholder="e.g. 96">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modals -->
<?php foreach ($terms as $t): ?>
<div class="modal fade" id="editModal<?= $t['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Term</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <div class="f-group">
                        <label class="f-label">Term Name</label>
                        <input class="f-input" name="term_name" value="<?= htmlspecialchars($t['term_name']) ?>" required>
                    </div>
                    <div class="f-row">
                        <div class="f-group">
                            <label class="f-label">Opening Date</label>
                            <input class="f-input" type="date" name="opening_date" value="<?= $t['opening_date'] ?? '' ?>" required>
                        </div>
                        <div class="f-group">
                            <label class="f-label">Closing Date</label>
                            <input class="f-input" type="date" name="closing_date" value="<?= $t['closing_date'] ?? '' ?>" required>
                        </div>
                    </div>
                    <div class="f-row">
                        <div class="f-group">
                            <label class="f-label">Midterm Break Start</label>
                            <input class="f-input" type="date" name="midterm_break_start" value="<?= $t['midterm_break_start'] ?? '' ?>">
                        </div>
                        <div class="f-group">
                            <label class="f-label">Midterm Break End</label>
                            <input class="f-input" type="date" name="midterm_break_end" value="<?= $t['midterm_break_end'] ?? '' ?>">
                        </div>
                    </div>
                    <div class="f-group">
                        <label class="f-label">Teaching Days</label>
                        <input class="f-input" type="number" name="teaching_days" value="<?= intval($t['teaching_days']) ?>" min="1">
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
<?php endforeach; ?>

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
