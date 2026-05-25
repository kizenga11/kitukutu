<?php
session_start();
include "../includes/config.php";
include "../includes/teaching_docs_functions.php";
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create' || $action === 'update') {
        $subject_id = intval($_POST['subject_id']);
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $desc = mysqli_real_escape_string($conn, $_POST['description']);
        $resource_type = mysqli_real_escape_string($conn, $_POST['resource_type']);
        $form_level = mysqli_real_escape_string($conn, $_POST['form_level']);
        $doc_type = $_POST['doc_type'] === 'link' ? 'link' : 'file';
        $link_url = $doc_type === 'link' ? mysqli_real_escape_string($conn, $_POST['link_url']) : '';
        $file_path = '';
        if ($doc_type === 'file' && isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $file_path = time() . '_' . basename($_FILES['file']['name']);
            move_uploaded_file($_FILES['file']['tmp_name'], "../uploads/resources/$file_path");
        }
        if ($action === 'create') {
            $fp = $file_path ? "'$file_path'" : 'NULL';
            $lu = $link_url ? "'$link_url'" : 'NULL';
            $fl = $form_level ? "'$form_level'" : 'NULL';
            mysqli_query($conn, "INSERT INTO subject_resources (subject_id, form_level, title, description, resource_type, file_path, link_url, doc_type, created_by) VALUES ($subject_id, $fl, '$title', '$desc', '$resource_type', $fp, $lu, '$doc_type', $_SESSION[admin_id])");
            $_SESSION['flash'] = ['msg' => 'Resource added.', 'type' => 'success'];
        } else {
            $id = intval($_POST['id']);
            $fl = $form_level ? "'$form_level'" : 'NULL';
            if ($file_path) {
                mysqli_query($conn, "UPDATE subject_resources SET subject_id=$subject_id, form_level=$fl, title='$title', description='$desc', resource_type='$resource_type', file_path='$file_path', link_url='', doc_type='$doc_type' WHERE id=$id");
            } else {
                mysqli_query($conn, "UPDATE subject_resources SET subject_id=$subject_id, form_level=$fl, title='$title', description='$desc', resource_type='$resource_type', link_url='$link_url', doc_type='$doc_type' WHERE id=$id");
            }
            $_SESSION['flash'] = ['msg' => 'Resource updated.', 'type' => 'success'];
        }
    }
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT file_path FROM subject_resources WHERE id=$id"));
        if ($r && $r['file_path'] && file_exists("../uploads/resources/".$r['file_path'])) unlink("../uploads/resources/".$r['file_path']);
        mysqli_query($conn, "DELETE FROM subject_resources WHERE id=$id");
        $_SESSION['flash'] = ['msg' => 'Resource deleted.', 'type' => 'success'];
    }
    header("Location: manage_subject_resources.php"); exit();
}

$resources = mysqli_query($conn, "SELECT sr.*, sub.subject_name FROM subject_resources sr JOIN subjects sub ON sub.id = sr.subject_id ORDER BY sub.subject_name, sr.resource_type, sr.created_at DESC");
$subjects = getSubjects($conn);
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$forms = getForms();
$res_types = ['book' => 'Book', 'note' => 'Note', 'reference' => 'Reference', 'other' => 'Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Subject Resources</title>
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
.modal-content{border-radius:12px;border:none;box-shadow:0 8px 30px rgba(0,0,0,.12);}
.subj-tag{display:inline-block;background:#ede9fe;color:#5b21b6;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700;}
.type-badge{display:inline-block;padding:2px 10px;border-radius:20px;font-size:10px;font-weight:700;}
.doc-link{text-decoration:none;font-weight:600;font-size:12px;}
.doc-link:hover{text-decoration:underline;}
.uc-banner{display:flex;align-items:center;gap:8px;background:#fef3c7;border:1px solid #fde68a;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:13px;font-weight:600;color:#92400e;}
</style>
</head>
<body>

<div class="uc-banner"><i class="bi bi-tools"></i> 🚧 Under Construction — This section is being updated.</div>

<div class="page-title"><i class="bi bi-journal-richtext"></i> Subject Resources — Books & Notes</div>

<?php if ($flash): ?>
<div class="flash <?= $flash['type'] ?>" id="flashMsg"><i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill' ?>"></i> <?= htmlspecialchars($flash['msg']) ?></div>
<?php endif; ?>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
        <div style="font-size:14px;font-weight:700;color:#0b2b3f;">All Resources</div>
        <button class="btn-sm btn-primary-sm" data-bs-toggle="modal" data-bs-target="#createModal"><i class="bi bi-plus-lg"></i> Add Resource</button>
    </div>

    <?php if (mysqli_num_rows($resources) == 0): ?>
    <div class="empty"><i class="bi bi-journal-x" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.3;"></i>No resources yet.</div>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Title</th><th>Subject</th><th>Type</th><th>Form</th><th>Link / File</th><th style="text-align:right;">Actions</th></tr></thead>
        <tbody>
            <?php while ($r = mysqli_fetch_assoc($resources)):
                $tcolors = ['book'=>['bg'=>'#dbeafe','fg'=>'#1d4ed8'],'note'=>['bg'=>'#fef3c7','fg'=>'#92400e'],'reference'=>['bg'=>'#ede9fe','fg'=>'#5b21b6'],'other'=>['bg'=>'#f3f4f6','fg'=>'#374151']];
                $tc = $tcolors[$r['resource_type']] ?? $tcolors['other'];
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($r['title']) ?></strong></td>
                <td><span class="subj-tag"><?= htmlspecialchars($r['subject_name']) ?></span></td>
                <td><span class="type-badge" style="background:<?= $tc['bg'] ?>;color:<?= $tc['fg'] ?>;"><?= $res_types[$r['resource_type']] ?? $r['resource_type'] ?></span></td>
                <td><?= htmlspecialchars($r['form_level'] ?? 'All') ?></td>
                <td>
                    <?php if ($r['doc_type'] === 'file' && $r['file_path']): ?>
                    <a class="doc-link" href="../uploads/resources/<?= urlencode($r['file_path']) ?>" target="_blank"><i class="bi bi-file-earmark"></i> View</a>
                    <?php elseif ($r['doc_type'] === 'link' && $r['link_url']): ?>
                    <a class="doc-link" href="<?= htmlspecialchars($r['link_url']) ?>" target="_blank"><i class="bi bi-link-45deg"></i> Open</a>
                    <?php else: ?>
                    <span style="color:#94a3b8;">—</span>
                    <?php endif; ?>
                </td>
                <td style="text-align:right;">
                    <button class="btn-sm btn-primary-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $r['id'] ?>"><i class="bi bi-pencil"></i> Edit</button>
                    <button class="btn-sm btn-danger-sm" onclick="confirmDelete('Delete this resource?', <?= $r['id'] ?>)"><i class="bi bi-trash3"></i></button>
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
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title"><i class="bi bi-plus-square"></i> Add Resource</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <div class="f-row">
                        <div class="f-group">
                            <label class="f-label">Title</label>
                            <input class="f-input" name="title" required placeholder="e.g. Mathematics Book 3">
                        </div>
                        <div class="f-group">
                            <label class="f-label">Resource Type</label>
                            <select class="f-select" name="resource_type">
                                <?php foreach ($res_types as $val => $label): ?>
                                <option value="<?= $val ?>"><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
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
                            <label class="f-label">Form Level</label>
                            <select class="f-select" name="form_level">
                                <option value="">All Forms</option>
                                <?php foreach ($forms as $f): ?>
                                <option value="<?= $f ?>"><?= $f ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="f-group">
                        <label class="f-label">Description</label>
                        <textarea class="f-input" name="description" rows="2"></textarea>
                    </div>
                    <div class="f-group">
                        <label class="f-label">Document Type</label>
                        <select class="f-select" name="doc_type" id="createDocType" onchange="toggleType()">
                            <option value="file">Upload File</option>
                            <option value="link">External Link</option>
                        </select>
                    </div>
                    <div class="f-group" id="createFileGroup">
                        <label class="f-label">Upload File</label>
                        <input class="f-input" type="file" name="file">
                    </div>
                    <div class="f-group" id="createLinkGroup" style="display:none;">
                        <label class="f-label">External URL</label>
                        <input class="f-input" type="url" name="link_url" placeholder="https://...">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php mysqli_data_seek($resources, 0); while ($r = mysqli_fetch_assoc($resources)): ?>
<div class="modal fade" id="editModal<?= $r['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Resource</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <div class="f-row">
                        <div class="f-group">
                            <label class="f-label">Title</label>
                            <input class="f-input" name="title" value="<?= htmlspecialchars($r['title']) ?>" required>
                        </div>
                        <div class="f-group">
                            <label class="f-label">Resource Type</label>
                            <select class="f-select" name="resource_type">
                                <?php foreach ($res_types as $val => $label): ?>
                                <option value="<?= $val ?>" <?= $r['resource_type'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="f-row">
                        <div class="f-group">
                            <label class="f-label">Subject</label>
                            <select class="f-select" name="subject_id" required>
                                <?php foreach ($subjects as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= $s['id'] == $r['subject_id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['subject_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="f-group">
                            <label class="f-label">Form Level</label>
                            <select class="f-select" name="form_level">
                                <option value="">All Forms</option>
                                <?php foreach ($forms as $f): ?>
                                <option value="<?= $f ?>" <?= ($r['form_level'] ?? '') === $f ? 'selected' : '' ?>><?= $f ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="f-group">
                        <label class="f-label">Description</label>
                        <textarea class="f-input" name="description" rows="2"><?= htmlspecialchars($r['description'] ?? '') ?></textarea>
                    </div>
                    <div class="f-group">
                        <label class="f-label">Document Type</label>
                        <select class="f-select" name="doc_type" onchange="toggleEditType(this, <?= $r['id'] ?>)">
                            <option value="file" <?= $r['doc_type'] === 'file' ? 'selected' : '' ?>>Upload File</option>
                            <option value="link" <?= $r['doc_type'] === 'link' ? 'selected' : '' ?>>External Link</option>
                        </select>
                    </div>
                    <div class="f-group" id="editFileGroup<?= $r['id'] ?>" style="<?= $r['doc_type'] === 'link' ? 'display:none;' : '' ?>">
                        <label class="f-label">Replace File</label>
                        <input class="f-input" type="file" name="file">
                    </div>
                    <div class="f-group" id="editLinkGroup<?= $r['id'] ?>" style="<?= $r['doc_type'] === 'file' ? 'display:none;' : '' ?>">
                        <label class="f-label">External URL</label>
                        <input class="f-input" type="url" name="link_url" value="<?= htmlspecialchars($r['link_url'] ?? '') ?>">
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
if (flashEl) { setTimeout(function(){flashEl.style.opacity='0';setTimeout(function(){flashEl.style.display='none'},500)},4000); }
function toggleType() {
    var v = document.getElementById('createDocType').value;
    document.getElementById('createFileGroup').style.display = v==='file'?'':'none';
    document.getElementById('createLinkGroup').style.display = v==='link'?'':'none';
}
function toggleEditType(el, id) {
    var v = el.value;
    document.getElementById('editFileGroup'+id).style.display = v==='file'?'':'none';
    document.getElementById('editLinkGroup'+id).style.display = v==='link'?'':'none';
}
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
