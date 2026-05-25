<?php
session_start();
include "includes/config.php";

// Ensure token table exists
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        token       VARCHAR(64) NOT NULL,
        user_type   ENUM('admin','teacher') NOT NULL,
        user_id     INT NOT NULL,
        expires_at  DATETIME NOT NULL,
        created_at  DATETIME DEFAULT NOW(),
        UNIQUE KEY  uq_token (token),
        INDEX       idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$token = trim($_GET['token'] ?? '');
$error = '';
$done  = false;

// Validate token
function getValidToken(mysqli $conn, string $token): ?array {
    $token = preg_replace('/[^a-fA-F0-9]/', '', $token);
    if (strlen($token) !== 64) {
        error_log("reset_token: invalid length " . strlen($token) . " for token=$token");
        return null;
    }
    $t = mysqli_real_escape_string($conn, $token);
    $result = mysqli_query($conn,
        "SELECT * FROM password_reset_tokens WHERE LOWER(token)=LOWER('$t') AND expires_at > NOW() LIMIT 1"
    );
    if (!$result) {
        error_log("reset_token: query failed — " . mysqli_error($conn));
        return null;
    }
    $row = mysqli_fetch_assoc($result);
    return $row ?: null;
}

$token_row = $token ? getValidToken($conn, $token) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $token     = trim($_POST['token'] ?? '');
    $token_row = getValidToken($conn, $token);

    if (!$token_row) {
        $error = 'This reset link is invalid or has expired. Please request a new one.';
    } else {
        $password = $_POST['password'];
        $confirm  = $_POST['confirm'];

        if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $hash      = password_hash($password, PASSWORD_DEFAULT);
            $user_id   = (int) $token_row['user_id'];
            $user_type = $token_row['user_type'];
            $table     = $user_type === 'teacher' ? 'teachers' : 'admins';

            mysqli_query($conn, "UPDATE $table SET password='$hash' WHERE id=$user_id");
            mysqli_query($conn, "DELETE FROM password_reset_tokens WHERE id={$token_row['id']}");

            $done = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>Reset Password · Kitukutu</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
html,body{height:100%;}
body{font-family:system-ui,-apple-system,'Segoe UI',sans-serif;background:#0b2040;display:flex;min-height:100vh;}
.lp{width:42%;flex-shrink:0;background:linear-gradient(160deg,#0b2040 0%,#0f2b4b 40%,#0d3460 100%);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 40px;position:relative;overflow:hidden;}
.lp::before{content:'';position:absolute;top:-100px;right:-100px;width:380px;height:380px;border-radius:50%;background:radial-gradient(circle,rgba(244,180,0,0.1) 0%,transparent 70%);pointer-events:none;}
.lp-logo{width:90px;height:90px;object-fit:contain;border-radius:16px;margin-bottom:24px;filter:drop-shadow(0 8px 24px rgba(0,0,0,0.3));}
.lp-school{font-size:1.5rem;font-weight:800;color:#fff;text-align:center;line-height:1.25;margin-bottom:8px;}
.lp-sub{font-size:0.82rem;color:#93b8d4;text-align:center;}
.lp-divider{width:40px;height:3px;background:#f4b400;border-radius:2px;margin:20px auto;}
.lp-quote{margin-top:28px;padding:14px 18px;background:rgba(244,180,0,0.08);border-left:3px solid #f4b400;border-radius:0 10px 10px 0;max-width:300px;}
.lp-quote p{font-size:0.8rem;color:#b8d4e8;font-style:italic;line-height:1.5;}
.rp{flex:1;background:#f8fafc;display:flex;align-items:center;justify-content:center;padding:40px 32px;overflow-y:auto;}
.form-card{width:100%;max-width:400px;background:#fff;border-radius:20px;padding:36px 32px;box-shadow:0 4px 24px rgba(11,32,64,0.08);border:1px solid #e8edf4;}
.form-title{font-size:1.5rem;font-weight:800;color:#0b2040;margin-bottom:4px;}
.form-subtitle{font-size:0.85rem;color:#64748b;margin-bottom:28px;}
.field{margin-bottom:18px;}
.field label{display:block;font-size:0.82rem;font-weight:700;color:#374151;margin-bottom:6px;}
.field input{width:100%;padding:12px 14px;border:1.5px solid #d1d5db;border-radius:10px;font-size:0.95rem;color:#111827;outline:none;transition:border-color .2s,box-shadow .2s;-webkit-appearance:none;}
.field input:focus{border-color:#0b2040;box-shadow:0 0 0 3px rgba(11,32,64,0.08);}
.pw-wrap{position:relative;}
.pw-wrap input{padding-right:46px;}
.pw-eye{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9ca3af;font-size:1rem;padding:6px;display:flex;align-items:center;min-width:36px;min-height:36px;border-radius:6px;}
.pw-eye:hover{color:#0b2040;background:#f1f5f9;}
.strength-bar{height:4px;border-radius:2px;margin-top:6px;background:#e5e7eb;overflow:hidden;}
.strength-fill{height:100%;border-radius:2px;width:0;transition:width .3s,background .3s;}
.strength-label{font-size:11px;color:#6b7280;margin-top:3px;}
.btn-primary{width:100%;padding:13px;border:none;border-radius:10px;background:linear-gradient(135deg,#0b2040 0%,#0f3460 100%);color:#fff;font-size:0.95rem;font-weight:700;cursor:pointer;transition:all .2s;box-shadow:0 4px 12px rgba(11,32,64,0.25);margin-top:4px;}
.btn-primary:hover{background:linear-gradient(135deg,#0d2a56 0%,#143d75 100%);transform:translateY(-1px);}
.alert-danger{background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;border-radius:10px;padding:12px 14px;font-size:0.85rem;display:flex;gap:8px;align-items:flex-start;margin-bottom:20px;}
.alert-invalid{background:#fff7ed;border:1px solid #fdba74;color:#92400e;border-radius:10px;padding:14px 16px;font-size:0.88rem;margin-bottom:20px;}
.form-footer{margin-top:22px;padding-top:18px;border-top:1px solid #f1f5f9;text-align:center;}
.back-link{font-size:0.85rem;color:#0b2040;text-decoration:none;font-weight:600;}
.back-link:hover{color:#f4b400;}
@media(max-width:768px){
  body{flex-direction:column;background:#f8fafc;}
  .lp{width:100%;padding:24px;flex-direction:row;gap:14px;align-items:center;border-radius:0 0 24px 24px;}
  .lp-logo{width:52px;height:52px;margin:0;border-radius:12px;}
  .lp-school{font-size:1.05rem;text-align:left;margin:0;}
  .lp-sub{text-align:left;}
  .lp-divider,.lp-quote{display:none;}
  .rp{padding:20px 16px;align-items:flex-start;}
  .form-card{border-radius:16px;padding:24px 20px;}
}
</style>
</head>
<body>

<div class="lp">
  <img src="assets/logo.png" alt="" class="lp-logo" onerror="this.style.display='none'">
  <div>
    <div class="lp-school">Amali Kitukutu</div>
    <div class="lp-sub">Technical Secondary School</div>
  </div>
  <div class="lp-divider"></div>
  <div class="lp-quote">
    <p>"Where skills become careers — building tomorrow's professionals today."</p>
  </div>
</div>

<div class="rp">
  <div class="form-card">

  <?php if ($done): ?>
    <div style="text-align:center;margin-bottom:20px;"><span style="font-size:3rem;">✅</span></div>
    <div class="form-title">Password updated!</div>
    <div class="form-subtitle" style="margin-bottom:24px;">
      Your password has been changed successfully. You can now sign in with your new password.
    </div>
    <a href="login.php" class="btn-primary" style="display:block;text-align:center;text-decoration:none;padding:13px;">
      <i class="fas fa-sign-in-alt" style="margin-right:6px"></i> Go to Login
    </a>

  <?php elseif (!$token_row): ?>
    <div style="text-align:center;margin-bottom:20px;"><span style="font-size:3rem;">⚠️</span></div>
    <div class="form-title">Invalid link</div>
    <div class="alert-invalid">
      This password reset link is invalid or has expired. Reset links are valid for 1 hour.
    </div>
    <a href="forgot_password.php" class="btn-primary" style="display:block;text-align:center;text-decoration:none;padding:13px;">
      Request a New Link
    </a>

  <?php else: ?>
    <div class="form-title">Set new password</div>
    <div class="form-subtitle">Choose a strong password for your account.</div>

    <?php if ($error): ?>
    <div class="alert-danger">
      <i class="fas fa-exclamation-circle" style="flex-shrink:0;margin-top:2px"></i>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off" id="resetForm">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

      <div class="field">
        <label for="password">New Password</label>
        <div class="pw-wrap">
          <input type="password" name="password" id="password"
                 placeholder="Minimum 8 characters" required minlength="8" autofocus>
          <button type="button" class="pw-eye" onclick="togglePw('password','icon1')" title="Show/hide">
            <i class="fas fa-eye" id="icon1"></i>
          </button>
        </div>
        <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
        <div class="strength-label" id="strengthLabel"></div>
      </div>

      <div class="field">
        <label for="confirm">Confirm Password</label>
        <div class="pw-wrap">
          <input type="password" name="confirm" id="confirm"
                 placeholder="Re-enter password" required minlength="8">
          <button type="button" class="pw-eye" onclick="togglePw('confirm','icon2')" title="Show/hide">
            <i class="fas fa-eye" id="icon2"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-primary" data-loading-text="Saving...">
        <i class="fas fa-lock" style="margin-right:6px"></i> Save New Password
      </button>
    </form>
  <?php endif; ?>

    <div class="form-footer">
      <a href="login.php" class="back-link">
        <i class="fas fa-arrow-left" style="margin-right:5px"></i>Back to Login
      </a>
    </div>
  </div>
</div>

<script>
function togglePw(inputId, iconId) {
  var inp  = document.getElementById(inputId);
  var icon = document.getElementById(iconId);
  var show = inp.type === 'password';
  inp.type = show ? 'text' : 'password';
  icon.className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
}

var pwInput = document.getElementById('password');
if (pwInput) {
  pwInput.addEventListener('input', function () {
    var val = this.value;
    var score = 0;
    if (val.length >= 8)  score++;
    if (val.length >= 12) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    var fill   = document.getElementById('strengthFill');
    var label  = document.getElementById('strengthLabel');
    var pct    = Math.round((score / 5) * 100);
    var colors = ['#ef4444','#f97316','#eab308','#22c55e','#16a34a'];
    var labels = ['Very weak','Weak','Fair','Strong','Very strong'];

    fill.style.width      = pct + '%';
    fill.style.background = colors[score - 1] || '#e5e7eb';
    label.textContent     = val.length > 0 ? (labels[score - 1] || '') : '';
  });
}
</script>
<script src="assets/js/forms.js"></script>
<script src="assets/js/loader.js"></script>
</body>
</html>