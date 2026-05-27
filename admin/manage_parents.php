<?php
session_start();
include "../includes/config.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}
$admin_id = $_SESSION['admin_id'];
$check = mysqli_query($conn, "SELECT role FROM admins WHERE id='$admin_id'");
$data = mysqli_fetch_assoc($check);
if ($data['role'] != 'admin') {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

$msg = '';
$err = '';

// Handle add parent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_parent') {
        $fname = mysqli_real_escape_string($conn, $_POST['first_name']);
        $lname = mysqli_real_escape_string($conn, $_POST['last_name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $existing = mysqli_query($conn, "SELECT id FROM parents WHERE email='$email'");
        if ($existing && mysqli_num_rows($existing) > 0) {
            $err = "Barua pepe hii tayari ipo.";
        } else {
            mysqli_query($conn, "INSERT INTO parents (first_name, last_name, email, password, phone) VALUES ('$fname', '$lname', '$email', '$password', '$phone')");
            $parent_id = mysqli_insert_id($conn);

            // Link to students
            if (!empty($_POST['student_ids'])) {
                foreach ($_POST['student_ids'] as $sid) {
                    $sid = intval($sid);
                    $rel = mysqli_real_escape_string($conn, $_POST['relationship_' . $sid] ?? '');
                    mysqli_query($conn, "INSERT INTO parent_students (parent_id, student_id, relationship) VALUES ('$parent_id', '$sid', '$rel')");
                }
            }
            $msg = "Mzazi ameongezwa na kuunganishwa na wanafunzi.";
        }
    } elseif ($_POST['action'] === 'link_student') {
        $parent_id = intval($_POST['parent_id']);
        $student_id = intval($_POST['student_id']);
        $rel = mysqli_real_escape_string($conn, $_POST['relationship']);
        $existing = mysqli_query($conn, "SELECT ps.id, p.first_name AS pf, p.last_name AS pl FROM parent_students ps JOIN parents p ON p.id=ps.parent_id WHERE ps.student_id='$student_id'");
        $existingRow = ($existing && mysqli_num_rows($existing) > 0) ? mysqli_fetch_assoc($existing) : null;
        if ($existingRow) {
            $err = "Mwanafunzi huyu tayari ana mzazi (" . htmlspecialchars($existingRow['pf'] ?? '') . ' ' . htmlspecialchars($existingRow['pl'] ?? '') . "). Mzazi mmoja tu kwa kila mwanafunzi.";
        } else {
            $existing_parent = mysqli_query($conn, "SELECT id FROM parent_students WHERE parent_id='$parent_id' AND student_id='$student_id'");
            if ($existing_parent && mysqli_num_rows($existing_parent) > 0) {
                $err = "Mwanafunzi tayari ameunganishwa na mzazi huyu.";
            } else {
                mysqli_query($conn, "INSERT INTO parent_students (parent_id, student_id, relationship) VALUES ('$parent_id', '$student_id', '$rel')");
                $msg = "Mwanafunzi ameunganishwa na mzazi.";
            }
        }
    } elseif ($_POST['action'] === 'unlink_student') {
        $pid = intval($_POST['parent_id']);
        $sid = intval($_POST['student_id']);
        mysqli_query($conn, "DELETE FROM parent_students WHERE parent_id='$pid' AND student_id='$sid'");
        $msg = "Mwanafunzi ameondolewa kutoka kwa mzazi.";
    }
}

$parents = mysqli_query($conn, "SELECT * FROM parents ORDER BY first_name");
$students = mysqli_query($conn, "SELECT id, first_name, second_name, last_name, form_level, stream, registration_no FROM students ORDER BY first_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Parents</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{--primary:#1a2b4c;--bg:#f3f4f6;}
body{background:var(--bg);font-family:system-ui,-apple-system,sans-serif;padding:16px;font-size:13px;}
h2{font-size:1.2rem;font-weight:700;color:var(--primary);}
.card{border-radius:10px;border:1px solid #e5e7eb;}
.btn-sm{font-size:11px;border-radius:6px;}
table{font-size:12px;}
th{background:var(--primary);color:#fff;font-size:11px;}
</style>
</head>
<body>

<h2><i class="bi bi-people-fill"></i> Manage Parents</h2>

<?php if ($msg): ?><div class="alert alert-success py-2"><?= $msg ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger py-2"><?= $err ?></div><?php endif; ?>

<!-- Add Parent Form -->
<div class="card p-3 mb-4">
  <h5 style="font-size:0.95rem;font-weight:700;margin-bottom:12px;">Add New Parent</h5>
  <form method="POST" class="row g-2">
    <input type="hidden" name="action" value="add_parent">
    <div class="col-md-3">
      <input type="text" name="first_name" class="form-control form-control-sm" placeholder="First Name" required>
    </div>
    <div class="col-md-3">
      <input type="text" name="last_name" class="form-control form-control-sm" placeholder="Last Name" required>
    </div>
    <div class="col-md-3">
      <input type="email" name="email" class="form-control form-control-sm" placeholder="Email" required>
    </div>
    <div class="col-md-2">
      <input type="text" name="phone" class="form-control form-control-sm" placeholder="Phone">
    </div>
    <div class="col-md-1">
      <input type="password" name="password" class="form-control form-control-sm" placeholder="Password" required>
    </div>
    <div class="col-12 mt-2">
      <label style="font-size:12px;font-weight:600;color:#374151;">Link to Students (optional)</label>
      <div class="row g-1 mt-1" style="max-height:150px;overflow-y:auto;">
        <?php mysqli_data_seek($students, 0); while ($s = mysqli_fetch_assoc($students)): ?>
        <div class="col-md-3 col-6">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="student_ids[]" value="<?= $s['id'] ?>" id="s_<?= $s['id'] ?>">
            <label class="form-check-label" for="s_<?= $s['id'] ?>" style="font-size:11px;">
                <?= htmlspecialchars($s['first_name'].' '.$s['last_name']) ?> <small>(<?= htmlspecialchars($s['form_level'].' '.$s['stream']) ?>)</small>
                <?php if ($s['registration_no']): ?><span style="color:#6366f1;"> <?= htmlspecialchars($s['registration_no']) ?></span><?php endif; ?>
              </label>
          </div>
          <input type="text" name="relationship_<?= $s['id'] ?>" class="form-control form-control-sm mt-1" placeholder="e.g. Mzazi, Mlezi" style="font-size:10px;display:none;" disabled>
        </div>
        <?php endwhile; ?>
      </div>
    </div>
    <div class="col-12 mt-2">
      <button type="submit" class="btn btn-sm" style="background:var(--primary);color:#fff;"><i class="bi bi-plus-circle"></i> Add Parent</button>
    </div>
  </form>
</div>

<!-- Parents List -->
<?php if (mysqli_num_rows($parents) == 0): ?>
<div class="alert alert-info">No parents registered yet.</div>
<?php else: ?>
<?php while ($p = mysqli_fetch_assoc($parents)):
  $children = mysqli_query($conn, "
    SELECT s.id, s.first_name, s.second_name, s.last_name, s.form_level, s.stream, s.registration_no, ps.relationship
    FROM parent_students ps
    JOIN students s ON s.id = ps.student_id
    WHERE ps.parent_id='{$p['id']}'
  ");
?>
<div class="card p-3 mb-3">
  <div class="d-flex justify-content-between align-items-center">
    <div>
      <strong><?= htmlspecialchars($p['first_name'].' '.$p['last_name']) ?></strong>
      <span class="text-muted ms-2" style="font-size:11px;"><?= htmlspecialchars($p['email']) ?></span>
      <?php if ($p['phone']): ?><span class="text-muted ms-2" style="font-size:11px;"><i class="bi bi-telephone"></i> <?= htmlspecialchars($p['phone']) ?></span><?php endif; ?>
    </div>
    <span class="badge bg-secondary">ID: <?= $p['id'] ?></span>
  </div>

  <!-- Linked Students -->
  <div class="mt-2">
    <strong style="font-size:12px;">Linked Students:</strong>
    <?php if (mysqli_num_rows($children) == 0): ?>
    <span class="text-muted" style="font-size:11px;">None</span>
    <?php else: ?>
    <ul class="list-unstyled mt-1" style="font-size:12px;">
      <?php while ($ch = mysqli_fetch_assoc($children)): ?>
      <li class="d-flex align-items-center gap-2 mb-1">
        <i class="bi bi-person-circle"></i>
        <?= htmlspecialchars($ch['first_name'].' '.$ch['last_name']) ?>
        <span class="text-muted">(<?= htmlspecialchars($ch['form_level'].' '.$ch['stream']) ?>)</span>
        <?php if ($ch['registration_no']): ?><span class="badge bg-primary" style="font-size:9px;"><?= htmlspecialchars($ch['registration_no']) ?></span><?php endif; ?>
        <?php if ($ch['relationship']): ?><span class="badge bg-info"><?= htmlspecialchars($ch['relationship']) ?></span><?php endif; ?>
        <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this student?')">
          <input type="hidden" name="action" value="unlink_student">
          <input type="hidden" name="parent_id" value="<?= $p['id'] ?>">
          <input type="hidden" name="student_id" value="<?= $ch['id'] ?>">
          <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" style="font-size:10px;"><i class="bi bi-x"></i></button>
        </form>
      </li>
      <?php endwhile; ?>
    </ul>
    <?php endif; ?>
  </div>

  <!-- Link More Students -->
  <details class="mt-2">
    <summary style="font-size:12px;cursor:pointer;color:var(--primary);font-weight:600;"><i class="bi bi-link"></i> Link Student</summary>
    <form method="POST" class="row g-2 mt-1">
      <input type="hidden" name="action" value="link_student">
      <input type="hidden" name="parent_id" value="<?= $p['id'] ?>">
      <div class="col-md-4">
        <select name="student_id" class="form-select form-select-sm" required>
          <option value="">— Select Student —</option>
          <?php mysqli_data_seek($students, 0); while ($s = mysqli_fetch_assoc($students)): ?>
          <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['first_name'].' '.$s['last_name'].' ('.$s['form_level'].' '.$s['stream'].')') ?> <?= $s['registration_no'] ? '[' . htmlspecialchars($s['registration_no']) . ']' : '' ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-md-3">
        <input type="text" name="relationship" class="form-control form-control-sm" placeholder="Relationship (e.g. Mzazi)">
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-link"></i> Link</button>
      </div>
    </form>
  </details>
</div>
<?php endwhile; ?>
<?php endif; ?>

</body>
</html>
