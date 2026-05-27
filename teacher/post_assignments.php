<?php
session_start();
include "../includes/config.php";

if (!isset($_SESSION['teacher_id'])) {
    header("Location: ../login.php");
    exit();
}

$teacher_id = intval($_SESSION['teacher_id']);

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $subject_id = intval($_POST['subject_id'] ?? 0);
    $form_level = mysqli_real_escape_string($conn, $_POST['form_level'] ?? '');
    $stream = mysqli_real_escape_string($conn, $_POST['stream'] ?? '');
    $due_date = !empty($_POST['due_date']) ? mysqli_real_escape_string($conn, $_POST['due_date']) : 'NULL';

    if (empty($title)) {
        $err = "Tafadhali ingiza jina la kazi.";
    } else {
        $due_val = $due_date !== 'NULL' ? "'$due_date'" : "NULL";
        $subj_val = $subject_id ? "'$subject_id'" : "NULL";
        $fl_val = $form_level ? "'$form_level'" : "NULL";
        $st_val = $stream ? "'$stream'" : "NULL";
        mysqli_query($conn, "INSERT INTO assignments (title, description, subject_id, form_level, stream, due_date, posted_by_type, posted_by_id)
            VALUES ('$title', '$desc', $subj_val, $fl_val, $st_val, $due_val, 'teacher', '$teacher_id')");
        $msg = "Kazi imechapishwa.";
    }
}

$subjects = mysqli_query($conn, "SELECT id, short_name, subject_name FROM subjects ORDER BY short_name");
$assignments = mysqli_query($conn, "
    SELECT a.*, s.short_name AS subject_name
    FROM assignments a
    LEFT JOIN subjects s ON s.id = a.subject_id
    WHERE a.posted_by_type='teacher' AND a.posted_by_id='$teacher_id'
    ORDER BY a.created_at DESC
    LIMIT 50
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Post Assignments</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{--primary:#065f46;--bg:#f0fdf4;}
body{background:var(--bg);font-family:system-ui,-apple-system,sans-serif;padding:16px;font-size:13px;}
h2{font-size:1.2rem;font-weight:700;color:var(--primary);}
.card{border-radius:10px;border:1px solid #e5e7eb;}
table{font-size:12px;}
th{background:var(--primary);color:#fff;font-size:11px;}
</style>
</head>
<body>

<h2><i class="bi bi-journal-check"></i> Post Assignments</h2>

<?php if ($msg): ?><div class="alert alert-success py-2"><?= $msg ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger py-2"><?= $err ?></div><?php endif; ?>

<div class="card p-3 mb-4">
  <form method="POST" class="row g-2">
    <div class="col-12">
      <input type="text" name="title" class="form-control form-control-sm" placeholder="Assignment Title" required>
    </div>
    <div class="col-12">
      <textarea name="description" class="form-control form-control-sm" rows="3" placeholder="Description (optional)"></textarea>
    </div>
    <div class="col-md-3">
      <select name="subject_id" class="form-select form-select-sm">
        <option value="">— All Subjects —</option>
        <?php while ($s = mysqli_fetch_assoc($subjects)): ?>
        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['short_name']) ?></option>
        <?php endwhile; mysqli_data_seek($subjects, 0); ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="form_level" class="form-select form-select-sm">
        <option value="">— All Forms —</option>
        <?php foreach (['Form One','Form Two','Form Three','Form Four'] as $f): ?>
        <option value="<?= $f ?>"><?= $f ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="stream" class="form-select form-select-sm">
        <option value="">— All Streams —</option>
        <option value="General">General</option>
        <option value="Vocational">Vocational</option>
      </select>
    </div>
    <div class="col-md-2">
      <input type="date" name="due_date" class="form-control form-control-sm">
    </div>
    <div class="col-md-1">
      <button type="submit" class="btn btn-sm" style="background:var(--primary);color:#fff;"><i class="bi bi-send"></i> Post</button>
    </div>
  </form>
</div>

<?php if (mysqli_num_rows($assignments) > 0): ?>
<div class="table-responsive">
<table class="table table-bordered bg-white">
  <tr>
    <th>Title</th>
    <th>Subject</th>
    <th>Form</th>
    <th>Stream</th>
    <th>Due Date</th>
    <th>Posted</th>
  </tr>
  <?php while ($a = mysqli_fetch_assoc($assignments)): ?>
  <tr>
    <td><?= htmlspecialchars($a['title']) ?></td>
    <td><?= htmlspecialchars($a['subject_name'] ?? 'All') ?></td>
    <td><?= htmlspecialchars($a['form_level'] ?? 'All') ?></td>
    <td><?= htmlspecialchars($a['stream'] ?? 'All') ?></td>
    <td><?= $a['due_date'] ? htmlspecialchars(date('d/m/Y', strtotime($a['due_date']))) : '-' ?></td>
    <td><?= htmlspecialchars(date('d/m/Y', strtotime($a['created_at']))) ?></td>
  </tr>
  <?php endwhile; ?>
</table>
</div>
<?php endif; ?>

</body>
</html>
