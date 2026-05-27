<?php
session_start();
include "../includes/config.php";
include "../includes/teaching_docs_functions.php";
if (!isset($_SESSION['teacher_id'])) { header("Location: ../login.php"); exit(); }
$teacher_id = intval($_SESSION['teacher_id']);

$assignments = getTeacherAssignments($conn, $teacher_id);
$subject_ids = array_column($assignments, 'subject_id');
$sid_list = empty($subject_ids) ? '0' : implode(',', $subject_ids);

// Build subject options for dropdowns (only teacher's subjects)
$my_subjects = [];
foreach ($assignments as $a) {
    $my_subjects[] = $a;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
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
        $fp = $file_path ? "'$file_path'" : 'NULL';
        $lu = $link_url ? "'$link_url'" : 'NULL';
        $fl = $form_level ? "'$form_level'" : 'NULL';
        mysqli_query($conn, "INSERT INTO subject_resources (subject_id, form_level, title, description, resource_type, file_path, link_url, doc_type, created_by) VALUES ($subject_id, $fl, '$title', '$desc', '$resource_type', $fp, $lu, '$doc_type', $teacher_id)");
        $msg = "Resource imeongezwa.";
    }
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        // Ensure teacher can only delete their own
        $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT file_path FROM subject_resources WHERE id=$id AND created_by=$teacher_id"));
        if ($r) {
            if ($r['file_path'] && file_exists("../uploads/resources/".$r['file_path'])) unlink("../uploads/resources/".$r['file_path']);
            mysqli_query($conn, "DELETE FROM subject_resources WHERE id=$id");
            $msg = "Resource imefutwa.";
        }
    }
}

$resources = mysqli_query($conn, "SELECT sr.*, sub.subject_name FROM subject_resources sr JOIN subjects sub ON sub.id = sr.subject_id WHERE sr.created_by=$teacher_id ORDER BY sub.subject_name, sr.created_at DESC");
$forms = getForms();
$res_types = ['book' => 'Book', 'note' => 'Note', 'reference' => 'Reference', 'other' => 'Other'];
$msg = $msg ?? '';
?>
<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nyenzo za Masomo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{--primary:#065f46;--bg:#f0fdf4;}
body{background:var(--bg);font-family:system-ui,-apple-system,sans-serif;padding:16px;font-size:13px;}
h2{font-size:1.2rem;font-weight:700;color:var(--primary);}
.card{border-radius:10px;border:1px solid #e5e7eb;margin-bottom:14px;}
.card-hdr{background:var(--primary);color:#fff;padding:8px 14px;border-radius:10px 10px 0 0;font-weight:700;font-size:0.9rem;}
.card-body{padding:14px;}
table{font-size:12px;}
th{background:var(--primary);color:#fff;font-size:11px;}
.btn-sm{font-size:11px;border-radius:6px;}
.f-group{margin-bottom:10px;}
.f-label{display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:3px;}
</style>
</head>
<body>

<h2><i class="bi bi-journal-richtext"></i> Nyenzo za Masomo</h2>
<p style="color:#6b7280;font-size:0.9rem;margin-bottom:12px;">Ongeza vitabu, maelezo, na nyenzo za kusaidia wanafunzi na wazazi.</p>

<?php if ($msg): ?><div class="alert alert-success py-2"><?= $msg ?></div><?php endif; ?>

<?php if (empty($my_subjects)): ?>
<div class="alert alert-warning">Hujapewa masomo yoyote bado. Wasiliana na admin.</div>
<?php else: ?>

<div class="card">
  <div class="card-hdr"><i class="bi bi-plus-circle"></i> Ongeza Nyenzo</div>
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="create">
      <div class="row g-2">
        <div class="col-md-4">
          <div class="f-group">
            <label class="f-label">Somo</label>
            <select class="form-select form-select-sm" name="subject_id" required>
              <option value="">— Chagua —</option>
              <?php foreach ($my_subjects as $s): ?>
              <option value="<?= $s['subject_id'] ?>"><?= htmlspecialchars($s['subject_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="col-md-4">
          <div class="f-group">
            <label class="f-label">Jina la Nyenzo</label>
            <input class="form-control form-control-sm" name="title" required placeholder="e.g. Mathematics Book 3">
          </div>
        </div>
        <div class="col-md-2">
          <div class="f-group">
            <label class="f-label">Aina</label>
            <select class="form-select form-select-sm" name="resource_type">
              <?php foreach ($res_types as $val => $label): ?>
              <option value="<?= $val ?>"><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="col-md-2">
          <div class="f-group">
            <label class="f-label">Kidato</label>
            <select class="form-select form-select-sm" name="form_level">
              <option value="">— All —</option>
              <?php foreach ($forms as $f): ?>
              <option value="<?= $f ?>"><?= $f ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="col-12">
          <div class="f-group">
            <label class="f-label">Maelezo (si lazima)</label>
            <textarea class="form-control form-control-sm" name="description" rows="2"></textarea>
          </div>
        </div>
        <div class="col-md-4">
          <div class="f-group">
            <label class="f-label">Aina ya Hati</label>
            <select class="form-select form-select-sm" name="doc_type" onchange="toggleType()">
              <option value="file">Pakia Faili</option>
              <option value="link">Kiungo (Link)</option>
            </select>
          </div>
        </div>
        <div class="col-md-4" id="fileGroup">
          <div class="f-group">
            <label class="f-label">Pakia Faili</label>
            <input class="form-control form-control-sm" type="file" name="file">
          </div>
        </div>
        <div class="col-md-4" id="linkGroup" style="display:none;">
          <div class="f-group">
            <label class="f-label">Kiungo (URL)</label>
            <input class="form-control form-control-sm" type="url" name="link_url" placeholder="https://...">
          </div>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-sm" style="background:var(--primary);color:#fff;"><i class="bi bi-save"></i> Hifadhi</button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php if (mysqli_num_rows($resources) > 0): ?>
<div class="card">
  <div class="card-hdr"><i class="bi bi-list"></i> Nyenzo Zangu</div>
  <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-bordered mb-0">
      <tr>
        <th>Jina</th>
        <th>Somo</th>
        <th>Aina</th>
        <th>Kidato</th>
        <th>Hati</th>
        <th>Tarehe</th>
        <th></th>
      </tr>
      <?php while ($r = mysqli_fetch_assoc($resources)):
        $tc = ['book'=>'bg-primary','note'=>'bg-warning text-dark','reference'=>'bg-info','other'=>'bg-secondary'];
        $type_class = $tc[$r['resource_type']] ?? 'bg-secondary';
      ?>
      <tr>
        <td><strong><?= htmlspecialchars($r['title']) ?></strong></td>
        <td><?= htmlspecialchars($r['subject_name']) ?></td>
        <td><span class="badge <?= $type_class ?>"><?= htmlspecialchars(ucfirst($r['resource_type'])) ?></span></td>
        <td><?= htmlspecialchars($r['form_level'] ?? 'All') ?></td>
        <td>
          <?php if ($r['doc_type'] === 'file' && $r['file_path']): ?>
          <a href="../uploads/resources/<?= urlencode($r['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-success py-0"><i class="bi bi-eye"></i></a>
          <?php elseif ($r['doc_type'] === 'link' && $r['link_url']): ?>
          <a href="<?= htmlspecialchars($r['link_url']) ?>" target="_blank" class="btn btn-sm btn-outline-primary py-0"><i class="bi bi-link-45deg"></i></a>
          <?php endif; ?>
        </td>
        <td style="font-size:11px;color:#6b7280;"><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
        <td>
          <form method="POST" style="display:inline;" onsubmit="return confirm('Futa nyenzo hii?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger py-0"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endwhile; ?>
    </table>
    </div>
  </div>
</div>
<?php endif; ?>

<?php endif; ?>

<script>
function toggleType() {
  var v = document.querySelector('select[name="doc_type"]').value;
  document.getElementById('fileGroup').style.display = v === 'file' ? '' : 'none';
  document.getElementById('linkGroup').style.display = v === 'link' ? '' : 'none';
}
</script>

</body>
</html>
