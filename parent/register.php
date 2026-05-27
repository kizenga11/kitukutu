<?php
session_start();
include "../includes/config.php";

$step = 1;
$error = '';
$success = '';

// ── Step 1: Register parent account ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_parent'])) {
    $fname = mysqli_real_escape_string($conn, trim($_POST['first_name']));
    $lname = mysqli_real_escape_string($conn, trim($_POST['last_name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($fname) || empty($lname) || empty($email) || empty($password)) {
        $error = "Tafadhali jaza taarifa zote muhimu.";
    } elseif ($password !== $confirm) {
        $error = "Nenosiri hazilingani.";
    } elseif (strlen($password) < 6) {
        $error = "Nenosiri lazima iwe angalau herufi 6.";
    } else {
        $existing = mysqli_query($conn, "SELECT id FROM parents WHERE email='$email'");
        if ($existing && mysqli_num_rows($existing) > 0) {
            $error = "Barua pepe hii tayari imesajiliwa. Tafadhali ingia.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            if (mysqli_query($conn, "INSERT INTO parents (first_name, last_name, email, password, phone) VALUES ('$fname', '$lname', '$email', '$hash', '$phone')")) {
                $_SESSION['parent_reg_id'] = mysqli_insert_id($conn);
                $_SESSION['parent_reg_name'] = $fname . ' ' . $lname;
                $step = 2;
            } else {
                $error = "Kuna tatizo la database. Tafadhali jaribu tena.";
            }
        }
    }
}

// ── Step 2: Link student registration_no ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['link_student'])) {
    if (empty($_SESSION['parent_reg_id'])) {
        $error = "Kikao kimeisha. Tafadhali anza upya.";
        $step = 1;
    } else {
        $regNo = mysqli_real_escape_string($conn, trim($_POST['registration_no']));
        $relationship = mysqli_real_escape_string($conn, trim($_POST['relationship'] ?: 'Mzazi'));

        if (empty($regNo)) {
            $error = "Tafadhali ingiza namba ya usajili wa mwanafunzi.";
        } else {
            $student = mysqli_query($conn, "SELECT id, first_name, second_name, last_name, form_level, stream, registration_no FROM students WHERE registration_no='$regNo' LIMIT 1");
            if ($student && mysqli_num_rows($student) > 0) {
                $studData = mysqli_fetch_assoc($student);
                $sid = (int) $studData['id'];

                // Check if student already linked
                $linked = mysqli_query($conn, "SELECT id FROM parent_students WHERE student_id=$sid LIMIT 1");
                if ($linked && mysqli_num_rows($linked) > 0) {
                    $error = "Mwanafunzi huyu tayari ameunganishwa na mzazi mwingine.";
                } else {
                    $pid = (int) $_SESSION['parent_reg_id'];
                    if (mysqli_query($conn, "INSERT INTO parent_students (parent_id, student_id, relationship) VALUES ($pid, $sid, '$relationship')")) {
                        $success = "Hongera! Akaunti yako imeundwa na umeunganishwa na " . htmlspecialchars($studData['first_name'] . ' ' . $studData['last_name']) . ".";
                        $step = 3;
                    } else {
                        $error = "Kuna tatizo la kuunganisha mwanafunzi. Tafadhali jaribu tena.";
                    }
                }
            } else {
                $error = "Namba ya usajili haikupatikana. Tafadhali hakikisha umeingiza namba sahihi.";
            }
        }
    }
}

// ── Step 2b: Add another student (skip) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_another'])) {
    $step = 2;
}

// ── Step 2b: Finish (skip to done) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finish_reg'])) {
    $success = "Hongera! Akaunti yako imeundwa. Sasa unaweza kuingia.";
    $step = 3;
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>Jisajili · Mzazi · Amali Kitukutu</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
html,body{height:100%;}
body{
  font-family:system-ui,-apple-system,'Segoe UI',sans-serif;
  background:#0b2040;
  display:flex;min-height:100vh;
}
.lp{
  width:42%;flex-shrink:0;
  background:linear-gradient(160deg,#0b2040 0%,#0f2b4b 40%,#0d3460 100%);
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  padding:48px 40px;position:relative;overflow:hidden;
}
.lp::before{
  content:'';position:absolute;top:-100px;right:-100px;
  width:380px;height:380px;border-radius:50%;
  background:radial-gradient(circle,rgba(244,180,0,0.1) 0%,transparent 70%);
  pointer-events:none;
}
.lp-logo{width:90px;height:90px;object-fit:contain;border-radius:16px;margin-bottom:24px;filter:drop-shadow(0 8px 24px rgba(0,0,0,0.3));}
.lp-logo-fallback{width:90px;height:90px;background:rgba(244,180,0,0.15);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:2.2rem;margin-bottom:24px;}
.lp-school{font-size:1.5rem;font-weight:800;color:#fff;text-align:center;line-height:1.25;margin-bottom:8px;}
.lp-sub{font-size:0.82rem;color:#93b8d4;text-align:center;letter-spacing:0.3px;}
.lp-divider{width:40px;height:3px;background:#f4b400;border-radius:2px;margin:20px auto;}
.lp-quote{margin-top:28px;padding:14px 18px;background:rgba(244,180,0,0.08);border-left:3px solid #f4b400;border-radius:0 10px 10px 0;max-width:300px;}
.lp-quote p{font-size:0.8rem;color:#b8d4e8;font-style:italic;line-height:1.5;}
.rp{flex:1;background:#f8fafc;display:flex;align-items:center;justify-content:center;padding:40px 32px;overflow-y:auto;}
.form-card{width:100%;max-width:440px;background:#fff;border-radius:20px;padding:36px 32px;box-shadow:0 4px 24px rgba(11,32,64,0.08);border:1px solid #e8edf4;}
.steps{display:flex;justify-content:center;gap:6px;margin-bottom:24px;}
.step-dot{width:10px;height:10px;border-radius:50%;background:#d1d5db;transition:all .2s;}
.step-dot.active{background:#0b2040;transform:scale(1.3);}
.step-dot.done{background:#059669;}
.form-title{font-size:1.35rem;font-weight:800;color:#0b2040;margin-bottom:4px;}
.form-subtitle{font-size:0.85rem;color:#64748b;margin-bottom:24px;}
.form-subtitle strong{color:#0b2040;}
.error-box{background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;border-radius:10px;padding:10px 14px;font-size:0.85rem;display:flex;align-items:flex-start;gap:8px;margin-bottom:18px;}
.success-box{background:#f0fdf4;border:1px solid #86efac;color:#166534;border-radius:10px;padding:10px 14px;font-size:0.85rem;display:flex;align-items:flex-start;gap:8px;margin-bottom:18px;}
.field{margin-bottom:16px;}
.field label{display:block;font-size:0.82rem;font-weight:700;color:#374151;margin-bottom:6px;letter-spacing:0.2px;}
.field input,.field select{width:100%;padding:11px 14px;border:1.5px solid #d1d5db;border-radius:10px;font-size:0.95rem;color:#111827;background:#fff;outline:none;transition:border-color 0.2s,box-shadow 0.2s;-webkit-appearance:none;appearance:none;}
.field input:focus,.field select:focus{border-color:#0b2040;box-shadow:0 0 0 3px rgba(11,32,64,0.08);}
.field .pw-wrap{position:relative;}
.field .pw-wrap input{padding-right:46px;}
.field .pw-eye{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9ca3af;font-size:1rem;padding:6px;display:flex;align-items:center;min-width:36px;min-height:36px;border-radius:6px;}
.field .pw-eye:hover{color:#0b2040;background:#f1f5f9;}
.btn-primary{width:100%;padding:13px;border:none;border-radius:10px;background:linear-gradient(135deg,#0b2040 0%,#0f3460 100%);color:#fff;font-size:0.95rem;font-weight:700;cursor:pointer;transition:all 0.2s;box-shadow:0 4px 12px rgba(11,32,64,0.25);margin-top:4px;letter-spacing:0.3px;}
.btn-primary:hover{background:linear-gradient(135deg,#0d2a56 0%,#143d75 100%);box-shadow:0 6px 18px rgba(11,32,64,0.3);transform:translateY(-1px);}
.btn-secondary{width:100%;padding:13px;border:2px solid #0b2040;border-radius:10px;background:#fff;color:#0b2040;font-size:0.95rem;font-weight:700;cursor:pointer;transition:all 0.2s;margin-top:4px;}
.btn-secondary:hover{background:#f8fafc;box-shadow:0 2px 8px rgba(11,32,64,0.1);}
.btn-success{width:100%;padding:13px;border:none;border-radius:10px;background:#059669;color:#fff;font-size:0.95rem;font-weight:700;cursor:pointer;transition:all 0.2s;display:block;text-align:center;text-decoration:none;margin-top:4px;box-shadow:0 4px 12px rgba(5,150,105,0.25);}
.btn-success:hover{background:#047857;transform:translateY(-1px);box-shadow:0 6px 18px rgba(5,150,105,0.3);}
.form-footer{margin-top:22px;padding-top:18px;border-top:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;}
.back-link{font-size:0.82rem;color:#0b2040;text-decoration:none;font-weight:600;display:flex;align-items:center;gap:5px;}
.back-link:hover{color:#f4b400;}
.student-preview{background:#f0fdf4;border:1px solid #86efac;border-radius:12px;padding:14px;margin-bottom:18px;display:flex;align-items:center;gap:12px;}
.student-preview .sp-av{width:42px;height:42px;border-radius:50%;background:#059669;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0;}
.student-preview .sp-name{font-weight:700;font-size:14px;color:#065f46;}
.student-preview .sp-detail{font-size:12px;color:#6b7280;}
.actions-row{display:flex;gap:8px;}
.actions-row form{flex:1;}
@media(max-width:768px){
  body{flex-direction:column;background:#f8fafc;}
  .lp{width:100%;padding:24px 24px 20px;flex-direction:row;flex-wrap:wrap;justify-content:flex-start;gap:14px;align-items:center;border-radius:0 0 24px 24px;}
  .lp::before,.lp::after{display:none;}
  .lp-logo{width:52px;height:52px;margin:0;border-radius:12px;}
  .lp-logo-fallback{width:52px;height:52px;margin:0;border-radius:12px;font-size:1.3rem;}
  .lp-info{flex:1;}
  .lp-school{font-size:1.1rem;text-align:left;margin:0;}
  .lp-sub{text-align:left;font-size:0.75rem;}
  .lp-divider,.lp-quote{display:none;}
  .rp{padding:20px 16px;align-items:flex-start;}
  .form-card{border-radius:16px;padding:24px 20px;}
  .form-title{font-size:1.2rem;}
}
</style>
</head>
<body>

<div class="lp">
  <img src="../assets/logo.png" alt="Logo" class="lp-logo" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
  <div class="lp-logo-fallback" style="display:none">🏫</div>
  <div class="lp-info">
    <div class="lp-school">Amali Kitukutu</div>
    <div class="lp-sub">Kitukutu Technical Secondary School</div>
  </div>
  <div class="lp-divider"></div>
  <div class="lp-quote">
    <p>"Kwa pamoja tunaweza — kujenga mustakabali bora kwa watoto wetu."</p>
  </div>
</div>

<div class="rp">
  <div class="form-card">

    <?php if ($step === 3): ?>
      <!-- ───── Step 3: Success ───── -->
      <div style="text-align:center;margin-bottom:20px;">
        <div style="width:64px;height:64px;border-radius:50%;background:#f0fdf4;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:2rem;">✅</div>
      </div>
      <div class="form-title">Umefanikiwa!</div>
      <div class="form-subtitle"><?= $success ?></div>

      <?php if (isset($_SESSION['parent_reg_id'])): ?>
      <div class="student-preview" style="border-color:#d1d5db;background:#f9fafb;">
        <div class="sp-av" style="background:#0b2040;"><?= strtoupper(substr($_SESSION['parent_reg_name'] ?? 'P', 0, 1)) ?></div>
        <div>
          <div class="sp-name"><?= htmlspecialchars($_SESSION['parent_reg_name'] ?? 'Mzazi') ?></div>
          <div class="sp-detail">Akaunti imeundwa</div>
        </div>
      </div>
      <?php endif; ?>

      <a href="login.php" class="btn-success" style="margin-top:16px;">
        <i class="fas fa-sign-in-alt" style="margin-right:6px"></i> Ingia Sasa
      </a>

      <div class="form-footer">
        <a href="../index.php" class="back-link"><i class="fas fa-arrow-left"></i> Nyumbani</a>
      </div>

    <?php elseif ($step === 2): ?>
      <!-- ───── Step 2: Link Student ───── -->
      <div class="steps">
        <span class="step-dot done"></span>
        <span class="step-dot active"></span>
        <span class="step-dot"></span>
      </div>
      <div style="text-align:center;margin-bottom:16px;"><span style="font-size:2rem;">👨‍👩‍👧‍👦</span></div>
      <div class="form-title">Unganisha Mtoto Wako</div>
      <div class="form-subtitle">Ingiza <strong>namba ya usajili (registration_no)</strong> ya mtoto wako. Unaweza kuongeza watoto zaidi ya mmoja.</div>

      <?php if ($error): ?>
      <div class="error-box"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if (isset($_SESSION['parent_reg_id'])): ?>
      <div class="student-preview" style="border-color:#d1d5db;background:#f9fafb;">
        <div class="sp-av" style="background:#0b2040;"><?= strtoupper(substr($_SESSION['parent_reg_name'] ?? 'P', 0, 1)) ?></div>
        <div>
          <div class="sp-name"><?= htmlspecialchars($_SESSION['parent_reg_name'] ?? 'Mzazi') ?></div>
          <div class="sp-detail">Akaunti imeundwa</div>
        </div>
      </div>
      <?php endif; ?>

      <form method="POST">
        <div class="field">
          <label for="registration_no">Namba ya Usajili wa Mwanafunzi <span style="color:#dc2626;">*</span></label>
          <input type="text" name="registration_no" id="registration_no" placeholder="Ingiza namba ya usajili" required style="text-transform:uppercase;font-weight:700;letter-spacing:0.5px;">
          <div class="hint" style="font-size:11px;color:#6b7280;margin-top:4px;">Namba hii utapata kutoka kwa mwalimu au ofisi ya shule.</div>
        </div>
        <div class="field">
          <label for="relationship">Uhusiano (hiari)</label>
          <input type="text" name="relationship" id="relationship" placeholder="Mzazi, Mlezi, Ndugu..." value="Mzazi">
        </div>
        <button type="submit" name="link_student" class="btn-primary">
          <i class="fas fa-link" style="margin-right:6px"></i> Unganisha Mtoto
        </button>
      </form>

      <div class="actions-row" style="margin-top:12px;">
        <form method="POST">
          <button type="submit" name="add_another" class="btn-secondary" style="font-size:0.85rem;">
            <i class="fas fa-plus"></i> Ongeza Mwingine
          </button>
        </form>
        <form method="POST">
          <button type="submit" name="finish_reg" class="btn-success" style="font-size:0.85rem;">
            <i class="fas fa-check"></i> Maliza
          </button>
        </form>
      </div>

      <div class="form-footer">
        <a href="login.php" class="back-link"><i class="fas fa-arrow-left"></i> Nishaingia</a>
      </div>

    <?php else: ?>
      <!-- ───── Step 1: Register Form ───── -->
      <div class="steps">
        <span class="step-dot active"></span>
        <span class="step-dot"></span>
        <span class="step-dot"></span>
      </div>
      <div style="text-align:center;margin-bottom:16px;"><span style="font-size:2rem;">📝</span></div>
      <div class="form-title">Jisajili kama Mzazi</div>
      <div class="form-subtitle">Jaza taarifa zako ili kuunda akaunti ya kufuatilia matokeo na taarifa za mtoto wako.</div>

      <?php if ($error): ?>
      <div class="error-box"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="row" style="display:flex;gap:10px;">
          <div class="field" style="flex:1;">
            <label for="first_name">Jina la Kwanza <span style="color:#dc2626;">*</span></label>
            <input type="text" name="first_name" id="first_name" placeholder="Jina" required>
          </div>
          <div class="field" style="flex:1;">
            <label for="last_name">Jina la Mwisho <span style="color:#dc2626;">*</span></label>
            <input type="text" name="last_name" id="last_name" placeholder="Jina la ukoo" required>
          </div>
        </div>
        <div class="field">
          <label for="email">Barua Pepe <span style="color:#dc2626;">*</span></label>
          <input type="email" name="email" id="email" placeholder="mzazi@example.com" required autocomplete="email">
        </div>
        <div class="field">
          <label for="phone">Namba ya Simu (hiari)</label>
          <input type="text" name="phone" id="phone" placeholder="0712 345 678">
        </div>
        <div class="row" style="display:flex;gap:10px;">
          <div class="field" style="flex:1;">
            <label for="password">Nenosiri <span style="color:#dc2626;">*</span></label>
            <div class="pw-wrap">
              <input type="password" name="password" id="password" placeholder="Angalau herufi 6" required minlength="6">
              <button type="button" class="pw-eye" onclick="togglePw('password','pwIcon')"><i class="fas fa-eye" id="pwIcon"></i></button>
            </div>
          </div>
          <div class="field" style="flex:1;">
            <label for="confirm_password">Rudia Nenosiri <span style="color:#dc2626;">*</span></label>
            <div class="pw-wrap">
              <input type="password" name="confirm_password" id="confirm_password" placeholder="Rudia nenosiri" required minlength="6">
              <button type="button" class="pw-eye" onclick="togglePw('confirm_password','pwIcon2')"><i class="fas fa-eye" id="pwIcon2"></i></button>
            </div>
          </div>
        </div>

        <button type="submit" name="register_parent" class="btn-primary">
          <i class="fas fa-user-plus" style="margin-right:6px"></i> Jisajili
        </button>
      </form>

      <div class="form-footer">
        <a href="../index.php" class="back-link"><i class="fas fa-arrow-left"></i> Nyumbani</a>
        <a href="login.php" style="font-size:0.82rem;color:#059669;text-decoration:none;font-weight:600;">Nishaingia</a>
      </div>

    <?php endif; ?>

  </div>
</div>

<script>
function togglePw(id, iconId) {
  var inp = document.getElementById(id), icon = document.getElementById(iconId);
  var show = inp.type === 'password';
  inp.type = show ? 'text' : 'password';
  icon.className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
}
</script>

</body>
</html>
