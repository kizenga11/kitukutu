<?php
session_start();
include "../includes/config.php";
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }
$admin_id = intval($_SESSION['admin_id']);

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current  = $_POST['current_password'] ?? '';
    $new      = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    $admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT password FROM admins WHERE id=$admin_id"));

    if (!password_verify($current, $admin['password'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($new) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } else {
        $hash = password_hash($new, PASSWORD_BCRYPT);
        $hash_esc = mysqli_real_escape_string($conn, $hash);
        mysqli_query($conn, "UPDATE admins SET password='$hash_esc' WHERE id=$admin_id");
        $success = 'Password changed successfully.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Change Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body{background:#f4f7fc;font-family:system-ui;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;}
.card{border:1px solid #e5e7eb;border-radius:18px;padding:32px 28px;max-width:420px;width:100%;background:#fff;box-shadow:0 4px 24px rgba(0,0,0,0.07);}
.avatar-circle{width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#0b2b3f,#1a5276);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:#fff;}
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
  <h5>Change Password</h5>
  <div class="sub">Admin Account</div>

  <?php if ($success): ?>
  <div class="alert alert-success py-2"><i class="bi bi-check-circle me-1"></i><?= $success ?></div>
  <?php elseif ($error): ?>
  <div class="alert alert-danger py-2"><i class="bi bi-exclamation-circle me-1"></i><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" autocomplete="off">
    <div class="mb-3">
      <label class="form-label fw-semibold">Current Password</label>
      <div class="pw-wrap">
        <input type="password" class="form-control" name="current_password" id="cp" required>
        <button type="button" class="toggle-pw" onclick="togglePw('cp',this)"><i class="bi bi-eye"></i></button>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label fw-semibold">New Password</label>
      <div class="pw-wrap">
        <input type="password" class="form-control" name="new_password" id="np" required minlength="6">
        <button type="button" class="toggle-pw" onclick="togglePw('np',this)"><i class="bi bi-eye"></i></button>
      </div>
    </div>
    <div class="mb-4">
      <label class="form-label fw-semibold">Confirm New Password</label>
      <div class="pw-wrap">
        <input type="password" class="form-control" name="confirm_password" id="cnp" required>
        <button type="button" class="toggle-pw" onclick="togglePw('cnp',this)"><i class="bi bi-eye"></i></button>
      </div>
    </div>
    <button type="submit" class="btn btn-primary w-100 fw-semibold">
      <i class="bi bi-save me-1"></i>Update Password
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
