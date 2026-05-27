<?php
session_start();
include "../includes/config.php";

if (!isset($_SESSION['parent_id'])) {
    header("Location: login.php");
    exit();
}

$parent_id = intval($_SESSION['parent_id']);
$parent = mysqli_fetch_assoc(mysqli_query($conn, "SELECT first_name,last_name,email FROM parents WHERE id='$parent_id' LIMIT 1")) ?? [];
$parent_name = trim(($parent['first_name'] ?? '') . ' ' . ($parent['last_name'] ?? ''));

// Check if already linked (might have been linked by admin while on this page)
$check = mysqli_query($conn, "SELECT id FROM parent_students WHERE parent_id='$parent_id' LIMIT 1");
if ($check && mysqli_num_rows($check) > 0) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';
$linked_students = [];
$show_link_form = true;

// Handle link student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['link_student'])) {
    $regNo = mysqli_real_escape_string($conn, trim($_POST['registration_no']));
    $relationship = mysqli_real_escape_string($conn, trim($_POST['relationship'] ?: 'Mzazi'));

    if (empty($regNo)) {
        $error = "Tafadhali ingiza namba ya usajili ya mwanafunzi.";
    } else {
        $student = mysqli_query($conn, "SELECT id, first_name, second_name, last_name, form_level, stream FROM students WHERE registration_no='$regNo' LIMIT 1");
        if ($student && mysqli_num_rows($student) > 0) {
            $studData = mysqli_fetch_assoc($student);
            $sid = (int) $studData['id'];

            // Check if student already linked
            $linked = mysqli_query($conn, "SELECT ps.id, p.first_name AS pf, p.last_name AS pl FROM parent_students ps JOIN parents p ON p.id=ps.parent_id WHERE ps.student_id=$sid LIMIT 1");
            if ($linked && mysqli_num_rows($linked) > 0) {
                $existingParent = mysqli_fetch_assoc($linked);
                $error = "Mwanafunzi " . htmlspecialchars($studData['first_name'] . ' ' . $studData['last_name']) . " tayari ameunganishwa na mzazi mwingine.";
            } else {
                if (mysqli_query($conn, "INSERT INTO parent_students (parent_id, student_id, relationship) VALUES ($parent_id, $sid, '$relationship')")) {
                    $linked_students[] = $studData;
                    $success = "Umefanikiwa kuunganishwa na " . htmlspecialchars($studData['first_name'] . ' ' . $studData['last_name']) . ".";
                    $show_link_form = false;
                } else {
                    $error = "Kuna tatizo. Tafadhali jaribu tena.";
                }
            }
        } else {
            $error = "Namba ya usajili haikupatikana. Tafadhali wasiliana na shule kupata namba sahihi.";
        }
    }
}

// Add another
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_another'])) {
    $show_link_form = true;
    $success = '';
}

// Finish — check if at least one student linked
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finish'])) {
    $finalCheck = mysqli_query($conn, "SELECT id FROM parent_students WHERE parent_id='$parent_id' LIMIT 1");
    if ($finalCheck && mysqli_num_rows($finalCheck) > 0) {
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Tafadhali unganisha angalau mwanafunzi mmoja kabla ya kuendelea.";
        $show_link_form = true;
    }
}

// Re-check before rendering (admin might have linked while on this page)
$rerecheck = mysqli_query($conn, "SELECT id FROM parent_students WHERE parent_id='$parent_id' LIMIT 1");
if ($rerecheck && mysqli_num_rows($rerecheck) > 0) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>Unganisha Mtoto · Mzazi · Amali Kitukutu</title>
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
  gap:12px;
}
.lp::before{content:'';position:absolute;top:-100px;right:-100px;width:380px;height:380px;border-radius:50%;background:radial-gradient(circle,rgba(244,180,0,0.1) 0%,transparent 70%);pointer-events:none;}
.lp-icon{font-size:3rem;background:rgba(244,180,0,0.12);width:90px;height:90px;border-radius:50%;display:flex;align-items:center;justify-content:center;}
.lp-school{font-size:1.5rem;font-weight:800;color:#fff;text-align:center;line-height:1.25;}
.lp-sub{font-size:0.82rem;color:#93b8d4;text-align:center;}
.lp-quote{margin-top:10px;padding:14px 18px;background:rgba(244,180,0,0.08);border-left:3px solid #f4b400;border-radius:0 10px 10px 0;max-width:300px;}
.lp-quote p{font-size:0.8rem;color:#b8d4e8;font-style:italic;line-height:1.5;}
.rp{flex:1;background:#f8fafc;display:flex;align-items:center;justify-content:center;padding:40px 32px;overflow-y:auto;}
.form-card{width:100%;max-width:440px;background:#fff;border-radius:20px;padding:36px 32px;box-shadow:0 4px 24px rgba(11,32,64,0.08);border:1px solid #e8edf4;}
.form-title{font-size:1.35rem;font-weight:800;color:#0b2040;margin-bottom:4px;}
.form-subtitle{font-size:0.85rem;color:#64748b;margin-bottom:24px;}
.form-subtitle strong{color:#0b2040;}
.error-box{background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;border-radius:10px;padding:10px 14px;font-size:0.85rem;display:flex;align-items:flex-start;gap:8px;margin-bottom:18px;}
.success-box{background:#f0fdf4;border:1px solid #86efac;color:#166534;border-radius:10px;padding:10px 14px;font-size:0.85rem;display:flex;align-items:flex-start;gap:8px;margin-bottom:18px;}
.field{margin-bottom:16px;}
.field label{display:block;font-size:0.82rem;font-weight:700;color:#374151;margin-bottom:6px;letter-spacing:0.2px;}
.field input{width:100%;padding:11px 14px;border:1.5px solid #d1d5db;border-radius:10px;font-size:0.95rem;color:#111827;background:#fff;outline:none;transition:border-color 0.2s,box-shadow 0.2s;-webkit-appearance:none;appearance:none;}
.field input:focus{border-color:#0b2040;box-shadow:0 0 0 3px rgba(11,32,64,0.08);}
.btn-primary{width:100%;padding:13px;border:none;border-radius:10px;background:linear-gradient(135deg,#0b2040 0%,#0f3460 100%);color:#fff;font-size:0.95rem;font-weight:700;cursor:pointer;transition:all 0.2s;box-shadow:0 4px 12px rgba(11,32,64,0.25);margin-top:4px;letter-spacing:0.3px;}
.btn-primary:hover{background:linear-gradient(135deg,#0d2a56 0%,#143d75 100%);transform:translateY(-1px);}
.btn-secondary{width:100%;padding:13px;border:2px solid #0b2040;border-radius:10px;background:#fff;color:#0b2040;font-size:0.95rem;font-weight:700;cursor:pointer;transition:all 0.2s;margin-top:4px;}
.btn-secondary:hover{background:#f8fafc;}
.btn-success{width:100%;padding:13px;border:none;border-radius:10px;background:#059669;color:#fff;font-size:0.95rem;font-weight:700;cursor:pointer;transition:all 0.2s;display:block;text-align:center;text-decoration:none;margin-top:4px;box-shadow:0 4px 12px rgba(5,150,105,0.25);}
.btn-success:hover{background:#047857;transform:translateY(-1px);}
.parent-card{background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:14px;margin-bottom:20px;display:flex;align-items:center;gap:12px;}
.pc-av{width:42px;height:42px;border-radius:50%;background:#0b2040;color:#f4b400;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:15px;flex-shrink:0;}
.pc-name{font-weight:700;font-size:14px;color:#0b2040;}
.pc-email{font-size:12px;color:#6b7280;}
.student-linked{background:#f0fdf4;border:1px solid #86efac;border-radius:12px;padding:14px;margin-bottom:18px;display:flex;align-items:center;gap:12px;}
.student-linked .sl-av{width:42px;height:42px;border-radius:50%;background:#059669;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0;}
.student-linked .sl-name{font-weight:700;font-size:14px;color:#065f46;}
.student-linked .sl-detail{font-size:12px;color:#6b7280;}
.actions-row{display:flex;gap:8px;margin-top:14px;}
.actions-row form{flex:1;}
.hint{font-size:11px;color:#6b7280;margin-top:4px;}
@media(max-width:768px){
  body{flex-direction:column;background:#f8fafc;}
  .lp{width:100%;padding:24px;flex-direction:row;flex-wrap:wrap;justify-content:flex-start;gap:12px;align-items:center;border-radius:0 0 24px 24px;}
  .lp::before{display:none;}
  .lp-icon{width:56px;height:56px;font-size:1.6rem;}
  .lp-school{font-size:1.1rem;text-align:left;}
  .lp-sub{text-align:left;font-size:0.75rem;}
  .lp-quote{display:none;}
  .rp{padding:20px 16px;align-items:flex-start;}
  .form-card{border-radius:16px;padding:24px 20px;}
}
</style>
</head>
<body>

<div class="lp">
  <div class="lp-icon">👨‍👩‍👧‍👦</div>
  <div class="lp-school">Amali Kitukutu</div>
  <div class="lp-sub">Kitukutu Technical Secondary School</div>
  <div class="lp-quote">
    <p>"Kwa pamoja tunaweza — kujenga mustakabali bora kwa watoto wetu."</p>
  </div>
</div>

<div class="rp">
  <div class="form-card">

    <div class="parent-card">
      <div class="pc-av"><?= strtoupper(substr($parent['first_name'] ?? 'P', 0, 1) . substr($parent['last_name'] ?? '', 0, 1)) ?></div>
      <div>
        <div class="pc-name"><?= htmlspecialchars($parent_name) ?></div>
        <div class="pc-email"><?= htmlspecialchars($parent['email'] ?? '') ?></div>
      </div>
    </div>

    <?php if ($error): ?>
    <div class="error-box"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="success-box"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($linked_students)): foreach ($linked_students as $ls): ?>
    <div class="student-linked">
      <div class="sl-av"><?= strtoupper(substr($ls['first_name'], 0, 1) . substr($ls['last_name'], 0, 1)) ?></div>
      <div>
        <div class="sl-name"><?= htmlspecialchars($ls['first_name'] . ' ' . $ls['last_name']) ?></div>
        <div class="sl-detail"><?= htmlspecialchars($ls['form_level'] . ' ' . $ls['stream']) ?></div>
      </div>
    </div>
    <?php endforeach; endif; ?>

    <?php if ($show_link_form): ?>
    <div class="form-title">Unganisha Mtoto Wako</div>
    <div class="form-subtitle">
      Una hitaji kuunganisha angalau mwanafunzi mmoja ili kuendelea.
      Namba ya usajili utapata kutoka shuleni.
    </div>

    <form method="POST">
      <div class="field">
        <label for="registration_no">Namba ya Usajili ya Mwanafunzi <span style="color:#dc2626;">*</span></label>
        <input type="text" name="registration_no" id="registration_no" placeholder="Ingiza namba ya usajili" required style="text-transform:uppercase;font-weight:700;letter-spacing:0.5px;">
        <div class="hint">Namba hii utapata kutoka kwa mwalimu au ofisi ya shule.</div>
      </div>
      <div class="field">
        <label for="relationship">Uhusiano</label>
        <input type="text" name="relationship" id="relationship" placeholder="Mzazi, Mlezi, Ndugu..." value="Mzazi">
      </div>
      <button type="submit" name="link_student" class="btn-primary">
        <i class="fas fa-link" style="margin-right:6px"></i> Unganisha
      </button>
    </form>
    <?php endif; ?>

    <?php if (!$show_link_form): ?>
    <div style="text-align:center;padding:8px 0;">
      <div style="font-size:0.85rem;color:#6b7280;margin-bottom:8px;">Je unataka kuongeza mtoto mwingine?</div>
    </div>
    <div class="actions-row">
      <form method="POST">
        <button type="submit" name="add_another" class="btn-secondary">
          <i class="fas fa-plus"></i> Ongeza Mwingine
        </button>
      </form>
      <form method="POST">
        <button type="submit" name="finish" class="btn-success">
          <i class="fas fa-arrow-right"></i> Nenda Dashboard
        </button>
      </form>
    </div>
    <?php endif; ?>

  </div>
</div>

</body>
</html>
