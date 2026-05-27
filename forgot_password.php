<?php
session_start();
include "includes/config.php";
include "includes/mailer.php";

mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        token       VARCHAR(64) NOT NULL,
        otp         VARCHAR(6) NOT NULL DEFAULT '',
        user_type   ENUM('admin','teacher') NOT NULL,
        user_id     INT NOT NULL,
        expires_at  DATETIME NOT NULL,
        created_at  DATETIME DEFAULT NOW(),
        UNIQUE KEY  uq_token (token),
        INDEX       idx_expires (expires_at),
        INDEX       idx_otp (otp)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$cols = mysqli_query($conn, "SHOW COLUMNS FROM password_reset_tokens LIKE 'otp'");
if (mysqli_num_rows($cols) === 0) {
    mysqli_query($conn, "ALTER TABLE password_reset_tokens ADD COLUMN otp VARCHAR(6) NOT NULL DEFAULT '' AFTER token, ADD INDEX idx_otp (otp)");
}

$error        = '';
$success      = false;
$step         = 'email';
$otp_token_id = 0;

// ── Step 1: Send OTP ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && !isset($_POST['otp']) && !isset($_POST['new_password'])) {
    $email = trim(mysqli_real_escape_string($conn, $_POST['email']));

    $user_id   = null;
    $user_type = null;
    $user_name = '';

    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, name, email FROM admins WHERE email='$email' LIMIT 1"));
    if ($row) { $user_id = $row['id']; $user_type = 'admin'; $user_name = $row['name'] ?: 'Admin'; }

    if (!$user_id) {
        $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, first_name, last_name, email FROM teachers WHERE email='$email' LIMIT 1"));
        if ($row) { $user_id = $row['id']; $user_type = 'teacher'; $user_name = trim($row['first_name'] . ' ' . $row['last_name']); }
    }
    if (!$user_id) {
        $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, first_name, last_name, email FROM parents WHERE email='$email' LIMIT 1"));
        if ($row) { $user_id = $row['id']; $user_type = 'parent'; $user_name = trim($row['first_name'] . ' ' . $row['last_name']); }
    }

    if ($user_id) {
        mysqli_query($conn, "DELETE FROM password_reset_tokens WHERE user_type='$user_type' AND user_id=$user_id");

        $token      = bin2hex(random_bytes(32));
        $otp        = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $insert = mysqli_query($conn, "
            INSERT INTO password_reset_tokens (token, otp, user_type, user_id, expires_at)
            VALUES ('$token', '$otp', '$user_type', $user_id, '$expires_at')
        ");

        if ($insert) {
            $name_esc = htmlspecialchars($user_name);
            $html = <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f7fc;font-family:system-ui,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7fc;padding:32px 0;">
  <tr><td align="center">
    <table width="520" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.07);">
      <tr><td style="background:linear-gradient(135deg,#0b2040,#0f3460);padding:32px 36px;text-align:center;">
        <div style="font-size:28px;font-weight:900;color:#f4b400;">🏫 Kitukutu</div>
        <div style="color:rgba(255,255,255,.7);font-size:13px;margin-top:4px;">Technical Secondary School</div>
      </td></tr>
      <tr><td style="padding:36px;">
        <p style="font-size:16px;color:#0b2040;font-weight:700;margin:0 0 8px;">Hello, {$name_esc}</p>
        <p style="font-size:14px;color:#4b5563;margin:0 0 24px;line-height:1.6;">
          Use the code below to reset your password. Expires in <strong>1 hour</strong>.
        </p>
        <div style="text-align:center;margin:28px 0;">
          <div style="display:inline-block;background:#f8fafc;border:2px dashed #0b2040;
                      color:#0b2040;font-weight:900;font-size:36px;letter-spacing:10px;
                      padding:18px 36px;border-radius:12px;font-family:monospace;">{$otp}</div>
        </div>
        <p style="font-size:13px;color:#6b7280;line-height:1.6;">
          If you did not request this, ignore this email.
        </p>
      </td></tr>
      <tr><td style="background:#f8fafc;border-top:1px solid #e5e7eb;padding:16px 36px;text-align:center;">
        <p style="font-size:11px;color:#9ca3af;margin:0;">Amali Kitukutu Technical Secondary School</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>
HTML;
            $ok = sendMailZoho($email, $user_name, 'Password Reset Code — Kitukutu School System', $html);
            if ($ok) {
                $_SESSION['reset_email'] = $email;
                // Clear any old token session
                unset($_SESSION['reset_token_id']);
                $step = 'otp';
            } else {
                $error = 'Failed to send email. Please contact the system administrator.';
            }
        } else {
            $error = 'Database error. Please try again.';
        }
    } else {
        $error = 'Email not found in our system.';
    }
}

// ── Step 2: Verify OTP ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp']) && !isset($_POST['new_password'])) {
    $otp = preg_replace('/[^0-9]/', '', $_POST['otp']);
    $step = 'otp'; // default back to otp on error

    if (strlen($otp) !== 6) {
        $error = 'Code must be exactly 6 digits.';
    } else {
        $otp_esc = mysqli_real_escape_string($conn, $otp);
        $result  = mysqli_query($conn, "SELECT * FROM password_reset_tokens WHERE otp='$otp_esc' LIMIT 1");
        $row     = $result ? mysqli_fetch_assoc($result) : null;

        if (!$row) {
            $error = 'Invalid code. Please check and try again.';
        } elseif (strtotime($row['expires_at']) < time()) {
            $error = 'Code has expired. Please request a new one.';
            mysqli_query($conn, "DELETE FROM password_reset_tokens WHERE id={$row['id']}");
        } else {
            // ✅ OTP is valid — save token_id to SESSION (key fix!)
            $otp_token_id = (int) $row['id'];
            $_SESSION['reset_token_id'] = $otp_token_id;
            $step = 'password';
        }
    }
}

// ── Step 3: Save new password ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    // Get token_id from POST first, fallback to session
    $tid = isset($_POST['token_id']) ? (int) $_POST['token_id'] : 0;
    if ($tid === 0 && !empty($_SESSION['reset_token_id'])) {
        $tid = (int) $_SESSION['reset_token_id'];
    }

    $password = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($tid === 0) {
        $error = 'Session expired. Please start again.';
        $step  = 'email';
    } elseif (strlen($password) < 8) {
        $error        = 'Password must be at least 8 characters.';
        $step         = 'password';
        $otp_token_id = $tid; // keep token_id for the form
    } elseif ($password !== $confirm) {
        $error        = 'Passwords do not match.';
        $step         = 'password';
        $otp_token_id = $tid; // keep token_id for the form
    } else {
        // No expires_at re-check — OTP already verified above, just fetch by id
        $row = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT * FROM password_reset_tokens WHERE id=$tid LIMIT 1"
        ));

        if ($row) {
            $hash      = password_hash($password, PASSWORD_DEFAULT);
            $user_id   = (int) $row['user_id'];
            $user_type = $row['user_type'];
            $table     = $user_type === 'teacher' ? 'teachers' : ($user_type === 'parent' ? 'parents' : 'admins');

            mysqli_query($conn, "UPDATE $table SET password='$hash' WHERE id=$user_id");
            mysqli_query($conn, "DELETE FROM password_reset_tokens WHERE id=$tid");
            unset($_SESSION['reset_email'], $_SESSION['reset_token_id']);
            $success = true;
        } else {
            $error = 'Session expired. Please request a new code.';
            $step  = 'email';
            unset($_SESSION['reset_token_id']);
        }
    }
}

// Restore token_id from session when re-displaying password form
if ($step === 'password' && empty($otp_token_id) && !empty($_SESSION['reset_token_id'])) {
    $otp_token_id = (int) $_SESSION['reset_token_id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>Forgot Password · Kitukutu</title>
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
.field input.code-input{font-size:1.8rem;letter-spacing:8px;text-align:center;font-family:monospace;font-weight:700;}
.pw-wrap{position:relative;}
.pw-wrap input{padding-right:46px;}
.pw-eye{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9ca3af;font-size:1rem;padding:6px;display:flex;align-items:center;min-width:36px;min-height:36px;border-radius:6px;}
.pw-eye:hover{color:#0b2040;background:#f1f5f9;}
.btn-primary{width:100%;padding:13px;border:none;border-radius:10px;background:linear-gradient(135deg,#0b2040 0%,#0f3460 100%);color:#fff;font-size:0.95rem;font-weight:700;cursor:pointer;transition:all .2s;box-shadow:0 4px 12px rgba(11,32,64,0.25);margin-top:4px;}
.btn-primary:hover{transform:translateY(-1px);}
.btn-success{width:100%;padding:13px;border:none;border-radius:10px;background:#16a34a;color:#fff;font-size:0.95rem;font-weight:700;cursor:pointer;transition:all .2s;display:block;text-align:center;text-decoration:none;margin-top:4px;}
.btn-success:hover{background:#15803d;transform:translateY(-1px);}
.alert-danger{background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;border-radius:10px;padding:12px 14px;font-size:0.85rem;display:flex;gap:8px;align-items:flex-start;margin-bottom:20px;}
.strength-bar{height:4px;border-radius:2px;margin-top:6px;background:#e5e7eb;overflow:hidden;}
.strength-fill{height:100%;border-radius:2px;width:0;transition:width .3s,background .3s;}
.strength-label{font-size:11px;color:#6b7280;margin-top:3px;}
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

  <?php if ($success): ?>
    <div style="text-align:center;margin-bottom:20px;"><span style="font-size:3rem;">✅</span></div>
    <div class="form-title">Password updated!</div>
    <div class="form-subtitle" style="margin-bottom:24px;">
      Your password has been changed successfully. You can now sign in.
    </div>
    <a href="login.php" class="btn-success"><i class="fas fa-sign-in-alt" style="margin-right:6px"></i> Go to Login</a>

  <?php elseif ($step === 'password'): ?>
    <div style="text-align:center;margin-bottom:20px;"><span style="font-size:2.5rem;">🔑</span></div>
    <div class="form-title">Set new password</div>
    <div class="form-subtitle">Code verified ✅ — choose a strong new password.</div>

    <?php if ($error): ?>
    <div class="alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <input type="hidden" name="token_id" value="<?= (int) $otp_token_id ?>">
      <div class="field">
        <label>New Password</label>
        <div class="pw-wrap">
          <input type="password" name="new_password" id="pw1" placeholder="Minimum 8 characters" required minlength="8" autofocus>
          <button type="button" class="pw-eye" onclick="togglePw('pw1','ic1')"><i class="fas fa-eye" id="ic1"></i></button>
        </div>
        <div class="strength-bar"><div class="strength-fill" id="sf"></div></div>
        <div class="strength-label" id="sl"></div>
      </div>
      <div class="field">
        <label>Confirm Password</label>
        <div class="pw-wrap">
          <input type="password" name="confirm" id="pw2" placeholder="Re-enter password" required minlength="8">
          <button type="button" class="pw-eye" onclick="togglePw('pw2','ic2')"><i class="fas fa-eye" id="ic2"></i></button>
        </div>
      </div>
      <button type="submit" class="btn-primary"><i class="fas fa-lock" style="margin-right:6px"></i> Save New Password</button>
    </form>

  <?php elseif ($step === 'otp'): ?>
    <div style="text-align:center;margin-bottom:20px;"><span style="font-size:2.5rem;">📬</span></div>
    <div class="form-title">Enter reset code</div>
    <div class="form-subtitle">
      A 6-digit code has been sent to <strong><?= htmlspecialchars($_SESSION['reset_email'] ?? '') ?></strong>.
      Check inbox and spam. Expires in <strong>1 hour</strong>.
    </div>

    <?php if ($error): ?>
    <div class="alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <div class="field">
        <label for="otp">Reset Code</label>
        <input type="text" name="otp" id="otp" class="code-input"
               placeholder="000000" required maxlength="6" inputmode="numeric" pattern="[0-9]{6}" autofocus>
      </div>
      <button type="submit" class="btn-primary"><i class="fas fa-check-circle" style="margin-right:6px"></i> Verify Code</button>
    </form>
    <div style="margin-top:14px;text-align:center;">
      <a href="forgot_password.php" style="font-size:0.82rem;color:#6b7280;">Request a new code</a>
    </div>

  <?php else: ?>
    <div class="form-title">Forgot password?</div>
    <div class="form-subtitle">Enter your account email and we'll send you a reset code.</div>

    <?php if ($error): ?>
    <div class="alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <div class="field">
        <label for="email">Email Address</label>
        <input type="email" name="email" id="email" placeholder="you@school.ac.tz" required autofocus>
      </div>
      <button type="submit" class="btn-primary">
        <i class="fas fa-paper-plane" style="margin-right:6px"></i> Send Reset Code
      </button>
    </form>
  <?php endif; ?>

    <div class="form-footer">
      <a href="login.php" class="back-link"><i class="fas fa-arrow-left" style="margin-right:5px"></i>Back to Login</a>
    </div>
  </div>
</div>

<script>
function togglePw(id, ic) {
  var inp = document.getElementById(id), icon = document.getElementById(ic);
  var show = inp.type === 'password';
  inp.type = show ? 'text' : 'password';
  icon.className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
}
var pw1 = document.getElementById('pw1');
if (pw1) pw1.addEventListener('input', function() {
  var v = this.value, s = 0;
  if (v.length >= 8) s++;
  if (v.length >= 12) s++;
  if (/[A-Z]/.test(v)) s++;
  if (/[0-9]/.test(v)) s++;
  if (/[^A-Za-z0-9]/.test(v)) s++;
  var fill = document.getElementById('sf'), lbl = document.getElementById('sl');
  fill.style.width = Math.round(s/5*100) + '%';
  fill.style.background = ['#ef4444','#f97316','#eab308','#22c55e','#16a34a'][s-1] || '#e5e7eb';
  lbl.textContent = v.length ? ['Very weak','Weak','Fair','Strong','Very strong'][s-1] || '' : '';
});
</script>
</body>
</html>