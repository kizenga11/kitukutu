<?php
session_start();
include "../includes/config.php";
if (!isset($_SESSION['parent_id'])) { header("Location: login.php"); exit(); }
$parent_id = intval($_SESSION['parent_id']);

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $parent = mysqli_fetch_assoc(mysqli_query($conn, "SELECT password FROM parents WHERE id=$parent_id"));

    if (!password_verify($current, $parent['password'])) {
        $error = 'Nenosiri la sasa si sahihi.';
    } elseif (strlen($new) < 6) {
        $error = 'Nenosiri jipya lazima liwe angalau herufi 6.';
    } elseif ($new !== $confirm) {
        $error = 'Nenosiri jipya na uthibitisho havilingani.';
    } else {
        $hash = password_hash($new, PASSWORD_BCRYPT);
        $hash_esc = mysqli_real_escape_string($conn, $hash);
        mysqli_query($conn, "UPDATE parents SET password='$hash_esc' WHERE id=$parent_id");
        $success = 'Nenosiri limebadilishwa kwa mafanikio.';
    }
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Badilisha Nenosiri</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body{background:#f0fdf4;font-family:system-ui;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;}
.card{border:1px solid #e5e7eb;border-radius:18px;padding:32px 28px;max-width:420px;width:100%;background:#fff;box-shadow:0 4px 24px rgba(0,0,0,0.07);}
.avatar-circle{width:64px;height:64px;border-radius:50%;background:#065f46;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:#fff;}
h5{text-align:center;font-weight:700;margin-bottom:4px;}
.sub{text-align:center;color:#6b7280;font-size:13px;margin-bottom:24px;}
.toggle-pw{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:#9ca3af;cursor:pointer;padding:2px 6px;}
.pw-wrap{position:relative;}
@media(max-width:480px){
  body{padding:12px;align-items:flex-start;padding-top:30px;}
  .card{border-radius:14px;padding:24px 18px;}
}
</style>
</head>
<body>
<div class="card">
  <div class="avatar-circle"><i class="bi bi-shield-lock-fill"></i></div>
  <h5>Badilisha Nenosiri</h5>
  <div class="sub">Akaunti ya Mzazi</div>

  <?php if ($success): ?>
  <div class="alert alert-success py-2"><i class="bi bi-check-circle me-1"></i> <?= $success ?></div>
  <?php elseif ($error): ?>
  <div class="alert alert-danger py-2"><i class="bi bi-exclamation-circle me-1"></i> <?= $error ?></div>
  <?php endif; ?>

  <form method="POST" autocomplete="off">
    <div class="mb-3">
      <label class="form-label fw-semibold">Nenosiri la Sasa</label>
      <div class="pw-wrap">
        <input type="password" class="form-control" name="current_password" id="cp" required>
        <button type="button" class="toggle-pw" onclick="togglePw('cp',this)"><i class="bi bi-eye"></i></button>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label fw-semibold">Nenosiri Jipya</label>
      <div class="pw-wrap">
        <input type="password" class="form-control" name="new_password" id="np" required minlength="6">
        <button type="button" class="toggle-pw" onclick="togglePw('np',this)"><i class="bi bi-eye"></i></button>
      </div>
    </div>
    <div class="mb-4">
      <label class="form-label fw-semibold">Thibitisha Nenosiri Jipya</label>
      <div class="pw-wrap">
        <input type="password" class="form-control" name="confirm_password" id="cnp" required>
        <button type="button" class="toggle-pw" onclick="togglePw('cnp',this)"><i class="bi bi-eye"></i></button>
      </div>
    </div>
    <button type="submit" class="btn w-100 fw-semibold" style="background:#065f46;color:#fff;">
      <i class="bi bi-save me-1"></i> Badilisha Nenosiri
    </button>
  </form>
</div>
<script>
function togglePw(id, btn) {
  const inp = document.getElementById(id);
  const show = inp.type === 'password';
  inp.type = show ? 'text' : 'password';
  btn.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
}
</script>
</body>
</html>
