<?php
session_start();
include "../includes/config.php";
include "../includes/teaching_docs_functions.php";
include "../includes/calendar_functions.php";
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }

$active_year = getActiveAcademicYear($conn);
$year_id = $active_year['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $subject_id = intval($_POST['subject_id']);
        $teacher_id = intval($_POST['teacher_id']);
        $term_id = intval($_POST['term_id']);
        $form_level = mysqli_real_escape_string($conn, $_POST['form_level']);
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $fl = $form_level ? "'$form_level'" : 'NULL';
        $t = $teacher_id ? $teacher_id : 'NULL';
        mysqli_query($conn, "INSERT INTO scheme_of_work (subject_id, teacher_id, academic_year_id, term_id, form_level, title, created_by) VALUES ($subject_id, $t, $year_id, $term_id, $fl, '$title', $_SESSION[admin_id])");
        $scheme_id = mysqli_insert_id($conn);
        $num_created = generateSchemeWeeks($conn, $scheme_id, $term_id);
        $_SESSION['flash'] = ['msg' => "Scheme created with $num_created week entries.", 'type' => 'success'];
    }

    if ($action === 'generate_weeks') {
        $id = intval($_POST['id']);
        $scheme = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM scheme_of_work WHERE id=$id"));
        if ($scheme) {
            $num_created = generateSchemeWeeks($conn, $id, $scheme['term_id']);
            $_SESSION['flash'] = ['msg' => "Generated $num_created new week entries.", 'type' => 'success'];
        }
    }

    if ($action === 'update_status') {
        $id = intval($_POST['id']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        mysqli_query($conn, "UPDATE scheme_of_work SET status='$status' WHERE id=$id");
        $_SESSION['flash'] = ['msg' => 'Scheme status updated.', 'type' => 'success'];
    }

    if ($action === 'delete') {
        $id = intval($_POST['id']);
        mysqli_query($conn, "DELETE FROM scheme_weeks WHERE scheme_id=$id");
        mysqli_query($conn, "DELETE FROM scheme_of_work WHERE id=$id");
        $_SESSION['flash'] = ['msg' => 'Scheme of work deleted.', 'type' => 'success'];
    }

    header("Location: manage_schemes.php");
    exit();
}

$schemes = mysqli_query($conn, "
    SELECT sw.*, sub.subject_name, t.first_name, t.last_name,
           ay.title AS year_title, tr.term_name
    FROM scheme_of_work sw
    JOIN subjects sub ON sub.id = sw.subject_id
    LEFT JOIN teachers t ON t.id = sw.teacher_id
    LEFT JOIN academic_years ay ON ay.id = sw.academic_year_id
    LEFT JOIN terms tr ON tr.id = sw.term_id
    ORDER BY sw.created_at DESC
");
$subjects = getSubjects($conn);
$terms = $year_id ? getTermsByYear($conn, $year_id) : [];
$teachers = mysqli_query($conn, "SELECT id, first_name, last_name FROM teachers ORDER BY first_name ASC");
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$forms = getForms();
$statuses = ['draft' => 'Draft', 'submitted' => 'Submitted', 'approved' => 'Approved'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Scheme of Work</title>
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
.btn-sm{padding:5px 12px;font-size:12px;border-radius:8px;border:none;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px;}
.btn-primary-sm{background:#dbeafe;color:#1d4ed8;}
.btn-success-sm{background:#d1fae5;color:#065f46;}
.btn-warning-sm{background:#fef3c7;color:#92400e;}
.btn-danger-sm{background:#fee2e2;color:#991b1b;}
.f-group{margin-bottom:12px;}
.f-label{display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:3px;}
.f-input,.f-select{width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;}
.f-input:focus,.f-select:focus{border-color:#6366f1;outline:none;box-shadow:0 0 0 3px rgba(99,102,241,.1);}
.flash{padding:10px 14px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:12px;display:flex;align-items:center;gap:8px;transition:opacity .5s;}
.flash.success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;}
.flash.error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;}
.empty{text-align:center;padding:30px;color:#94a3b8;font-size:14px;}
.modal-content{border-radius:12px;border:none;box-shadow:0 8px 30px rgba(0,0,0,.12);}
.badge-draft{background:#f3f4f6;color:#6b7280;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;}
.badge-submitted{background:#fef3c7;color:#92400e;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;}
.badge-approved{background:#d1fae5;color:#065f46;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;}
.f-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.uc-banner{display:flex;align-items:center;gap:8px;background:#fef3c7;border:1px solid #fde68a;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:13px;font-weight:600;color:#92400e;}
</style>
</head>
<body>

<div class="uc-banner"><i class="bi bi-tools"></i> 🚧 Under Construction — This section is being updated.</div>

<div class="page-title"><i class="bi bi-calendar-week"></i> Scheme of Work</div>

<?php if ($flash): ?>
<div class="flash <?= $flash['type'] ?>" id="flashMsg"><i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill' ?>"></i> <?= htmlspecialchars($flash['msg']) ?></div>
<?php endif; ?>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
        <div style="font-size:14px;font-weight:700;color:#0b2b3f;">All Schemes</div>
        <button class="btn-sm btn-primary-sm" data-bs-toggle="modal" data-bs-target="#createModal"><i class="bi bi-plus-lg"></i> New Scheme</button>
    </div>

    <?php if (mysqli_num_rows($schemes) == 0): ?>
    <div class="empty"><i class="bi bi-calendar-week" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.3;"></i>No schemes yet.</div>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Title</th><th>Subject</th><th>Teacher</th><th>Term</th><th>Form</th><th>Weeks</th><th>Status</th><th style="text-align:right;">Actions</th></tr></thead>
        <tbody>
            <?php while ($s = mysqli_fetch_assoc($schemes)): ?>
            <tr>
                <td><strong><?= htmlspecialchars($s['title']) ?></strong></td>
                <td><?= htmlspecialchars($s['subject_name']) ?></td>
                <td><?= htmlspecialchars(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')) ?></td>
                <td><?= htmlspecialchars($s['term_name'] ?? '—') ?></td>
                <td><?= htmlspecialchars($s['form_level'] ?? 'All') ?></td>
                <td><?= intval($s['total_weeks']) ?></td>
                <td><span class="badge-<?= $s['status'] ?>"><?= $statuses[$s['status']] ?? $s['status'] ?></span></td>
                <td style="text-align:right;">
                    <a href="scheme_weeks.php?id=<?= $s['id'] ?>" target="mainFrame" class="btn-sm btn-primary-sm"><i class="bi bi-eye"></i> Weeks</a>
                    <form method="post" style="display:inline;" class="d-inline">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <input type="hidden" name="action" value="generate_weeks">
                        <button class="btn-sm btn-success-sm"><i class="bi bi-arrow-repeat"></i> Gen Weeks</button>
                    </form>
                    <button class="btn-sm btn-warning-sm" data-bs-toggle="modal" data-bs-target="#statusModal<?= $s['id'] ?>"><i class="bi bi-flag"></i></button>
                    <button class="btn-sm btn-danger-sm" onclick="confirmDelete('Delete this scheme?', <?= $s['id'] ?>)"><i class="bi bi-trash3"></i></button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

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

<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title"><i class="bi bi-plus-square"></i> New Scheme of Work</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <div class="f-group">
                        <label class="f-label">Title</label>
                        <input class="f-input" name="title" required placeholder="e.g. Mathematics Scheme of Work 2026">
                    </div>
                    <div class="f-row">
                        <div class="f-group">
                            <label class="f-label">Subject</label>
                            <select class="f-select" name="subject_id" required>
                                <option value="">— Select —</option>
                                <?php foreach ($subjects as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['subject_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="f-group">
                            <label class="f-label">Teacher (optional)</label>
                            <select class="f-select" name="teacher_id">
                                <option value="">— Unassigned —</option>
                                <?php mysqli_data_seek($teachers, 0); while ($t = mysqli_fetch_assoc($teachers)): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="f-row">
                        <div class="f-group">
                            <label class="f-label">Term</label>
                            <select class="f-select" name="term_id" required>
                                <option value="">— Select —</option>
                                <?php foreach ($terms as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['term_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="f-group">
                            <label class="f-label">Form Level</label>
                            <select class="f-select" name="form_level">
                                <option value="">All Forms</option>
                                <?php foreach ($forms as $f): ?>
                                <option value="<?= $f ?>"><?= $f ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Create & Generate Weeks</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php mysqli_data_seek($schemes, 0); while ($s = mysqli_fetch_assoc($schemes)): ?>
<div class="modal fade" id="statusModal<?= $s['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <form method="post">
                <div class="modal-body text-center py-4">
                    <h6 style="font-size:0.95rem;font-weight:700;">Update Status</h6>
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                    <select class="f-select mt-2" name="status">
                        <?php foreach ($statuses as $val => $label): ?>
                        <option value="<?= $val ?>" <?= $s['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-footer border-0 justify-content-center pt-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endwhile; ?>

<script>
var flashEl = document.getElementById('flashMsg');
if (flashEl) { setTimeout(function(){flashEl.style.opacity='0';setTimeout(function(){flashEl.style.display='none'},500)},4000); }
var pendingId = null;
function confirmDelete(msg, id) {
    document.getElementById('confirmMsg').textContent = msg;
    pendingId = id;
    new bootstrap.Modal(document.getElementById('confirmModal')).show();
}
document.getElementById('confirmBtn').addEventListener('click', function() {
    if (pendingId) {
        var form = document.createElement('form'); form.method = 'post'; form.style.display = 'none';
        form.innerHTML = '<input name="id" value="'+pendingId+'"><input name="action" value="delete">';
        document.body.appendChild(form); form.submit();
    }
    bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
