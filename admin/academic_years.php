<?php
session_start();
include "../includes/config.php";
include "../includes/calendar_functions.php";
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $open_date = mysqli_real_escape_string($conn, $_POST['school_open_date']);
        $close_date = mysqli_real_escape_string($conn, $_POST['school_close_date']);
        mysqli_query($conn, "INSERT INTO academic_years (title, year_name, school_open_date, school_close_date) VALUES ('$title', '$title', '$open_date', '$close_date')");
        $_SESSION['flash'] = ['msg' => 'Academic year created.', 'type' => 'success'];
    }

    if ($action === 'activate') {
        $id = intval($_POST['id']);
        ensureSingleActiveYear($conn, $id);
        mysqli_query($conn, "UPDATE academic_years SET is_active = 1 WHERE id = $id");
        $_SESSION['flash'] = ['msg' => 'Academic year activated.', 'type' => 'success'];
    }

    if ($action === 'deactivate') {
        $id = intval($_POST['id']);
        mysqli_query($conn, "UPDATE academic_years SET is_active = 0 WHERE id = $id");
        $_SESSION['flash'] = ['msg' => 'Academic year deactivated.', 'type' => 'success'];
    }

    if ($action === 'delete') {
        $id = intval($_POST['id']);
        mysqli_query($conn, "DELETE FROM academic_years WHERE id = $id");
        $_SESSION['flash'] = ['msg' => 'Academic year deleted.', 'type' => 'success'];
    }

    if ($action === 'update') {
        $id = intval($_POST['id']);
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $open_date = mysqli_real_escape_string($conn, $_POST['school_open_date']);
        $close_date = mysqli_real_escape_string($conn, $_POST['school_close_date']);
        mysqli_query($conn, "UPDATE academic_years SET title='$title', year_name='$title', school_open_date='$open_date', school_close_date='$close_date' WHERE id=$id");
        $_SESSION['flash'] = ['msg' => 'Academic year updated.', 'type' => 'success'];
    }

    header("Location: academic_years.php");
    exit();
}

$years_q = mysqli_query($conn, "SELECT * FROM academic_years ORDER BY created_at DESC");
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Academic Years</title>
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
.badge-active{background:#d1fae5;color:#065f46;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;}
.badge-inactive{background:#f3f4f6;color:#6b7280;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;}
.btn-sm{padding:5px 12px;font-size:12px;border-radius:8px;border:none;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px;}
.btn-primary-sm{background:#dbeafe;color:#1d4ed8;}
.btn-success-sm{background:#d1fae5;color:#065f46;}
.btn-warning-sm{background:#fef3c7;color:#92400e;}
.btn-danger-sm{background:#fee2e2;color:#991b1b;}
.f-group{margin-bottom:12px;}
.f-label{display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:3px;}
.f-input{width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;}
.f-input:focus{border-color:#6366f1;outline:none;box-shadow:0 0 0 3px rgba(99,102,241,.1);}
.flash{padding:10px 14px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:12px;display:flex;align-items:center;gap:8px;transition:opacity .5s;}
.flash.success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;}
.flash.error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;}
.empty{text-align:center;padding:30px;color:#94a3b8;font-size:14px;}
.modal-content{border-radius:12px;border:none;box-shadow:0 8px 30px rgba(0,0,0,.12);}
</style>
</head>
<body>

<div class="page-title"><i class="bi bi-calendar-range"></i> Academic Years</div>

<?php if ($flash): ?>
<div class="flash <?= $flash['type'] ?>" id="flashMsg"><i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill' ?>"></i> <?= htmlspecialchars($flash['msg']) ?></div>
<?php endif; ?>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
        <div style="font-size:14px;font-weight:700;color:#0b2b3f;">All Academic Years</div>
        <button class="btn-sm btn-primary-sm" data-bs-toggle="modal" data-bs-target="#createModal"><i class="bi bi-plus-lg"></i> New Year</button>
    </div>

    <?php if (mysqli_num_rows($years_q) == 0): ?>
    <div class="empty"><i class="bi bi-calendar-x" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.3;"></i>No academic years yet.</div>
    <?php else: ?>
    <table class="table">
        <thead>
            <tr><th>Title</th><th>Open Date</th><th>Close Date</th><th>Status</th><th style="text-align:right;">Actions</th></tr>
        </thead>
        <tbody>
            <?php while ($yr = mysqli_fetch_assoc($years_q)):
                $is_active = $yr['is_active'] ?? 0;
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($yr['title'] ?? $yr['year_name']) ?></strong></td>
                <td><?= htmlspecialchars($yr['school_open_date'] ?? '—') ?></td>
                <td><?= htmlspecialchars($yr['school_close_date'] ?? '—') ?></td>
                <td>
                    <?php if ($is_active): ?>
                    <span class="badge-active">Active</span>
                    <?php else: ?>
                    <span class="badge-inactive">Inactive</span>
                    <?php endif; ?>
                </td>
                <td style="text-align:right;">
                    <?php if ($is_active): ?>
                    <button class="btn-sm btn-warning-sm" onclick="confirmAction('Deactivate this academic year?', 'deactivate', <?= $yr['id'] ?>)"><i class="bi bi-pause-circle"></i> Deactivate</button>
                    <?php else: ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="id" value="<?= $yr['id'] ?>">
                        <input type="hidden" name="action" value="activate">
                        <button class="btn-sm btn-success-sm"><i class="bi bi-play-circle"></i> Activate</button>
                    </form>
                    <?php endif; ?>
                    <button class="btn-sm btn-primary-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $yr['id'] ?>"><i class="bi bi-pencil"></i> Edit</button>
                    <button class="btn-sm btn-danger-sm" onclick="confirmAction('Delete this academic year? This cannot be undone.', 'delete', <?= $yr['id'] ?>)"><i class="bi bi-trash3"></i> Delete</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Confirm Action Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="mb-3 text-danger"><i class="bi bi-exclamation-triangle-fill" style="font-size:2.5rem;"></i></div>
                <h6 style="font-size:0.95rem;font-weight:700;" id="confirmTitle">Confirm</h6>
                <p class="text-muted small mb-0" id="confirmMsg">Are you sure?</p>
            </div>
            <div class="modal-footer border-0 justify-content-center pt-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmBtn">Yes, Proceed</button>
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
                    <h5 class="modal-title"><i class="bi bi-calendar-plus"></i> New Academic Year</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <div class="f-group">
                        <label class="f-label">Year Title</label>
                        <input class="f-input" name="title" required placeholder="e.g. 2026/2027">
                    </div>
                    <div class="f-group">
                        <label class="f-label">School Open Date</label>
                        <input class="f-input" type="date" name="school_open_date" required>
                    </div>
                    <div class="f-group">
                        <label class="f-label">School Close Date</label>
                        <input class="f-input" type="date" name="school_close_date" required>
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
<?php mysqli_data_seek($years_q, 0); while ($yr = mysqli_fetch_assoc($years_q)): ?>
<div class="modal fade" id="editModal<?= $yr['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Year</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= $yr['id'] ?>">
                    <div class="f-group">
                        <label class="f-label">Year Title</label>
                        <input class="f-input" name="title" value="<?= htmlspecialchars($yr['title'] ?? $yr['year_name']) ?>" required>
                    </div>
                    <div class="f-group">
                        <label class="f-label">School Open Date</label>
                        <input class="f-input" type="date" name="school_open_date" value="<?= $yr['school_open_date'] ?? '' ?>">
                    </div>
                    <div class="f-group">
                        <label class="f-label">School Close Date</label>
                        <input class="f-input" type="date" name="school_close_date" value="<?= $yr['school_close_date'] ?? '' ?>">
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
// Auto-dismiss flash
var flashEl = document.getElementById('flashMsg');
if (flashEl) {
    setTimeout(function() {
        flashEl.style.opacity = '0';
        setTimeout(function() { flashEl.style.display = 'none'; }, 500);
    }, 4000);
}

// Confirm modal
var pendingAction = null;
var pendingId = null;

function confirmAction(msg, action, id) {
    document.getElementById('confirmTitle').textContent = 'Confirm';
    document.getElementById('confirmMsg').textContent = msg;
    pendingAction = action;
    pendingId = id;
    new bootstrap.Modal(document.getElementById('confirmModal')).show();
}

document.getElementById('confirmBtn').addEventListener('click', function() {
    if (pendingAction && pendingId) {
        var form = document.createElement('form');
        form.method = 'post';
        form.style.display = 'none';
        form.innerHTML = '<input name="id" value="' + pendingId + '"><input name="action" value="' + pendingAction + '">';
        document.body.appendChild(form);
        form.submit();
    }
    bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
