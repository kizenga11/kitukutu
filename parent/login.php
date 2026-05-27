<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include "../includes/config.php";

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])){
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $res = mysqli_query($conn, "SELECT * FROM parents WHERE email='$email'");
    if($res && mysqli_num_rows($res) > 0){
        $parent = mysqli_fetch_assoc($res);
        if(password_verify($password, $parent['password'])){
            session_regenerate_id(true);
            $_SESSION['parent_id'] = $parent['id'];
            header("Location: dashboard.php");
            exit();
        }
    }
    $error = "Barua pepe au nenosiri si sahihi.";
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>Ingia · Mzazi · Amali Kitukutu</title>
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
.lp-logo{
  width:90px;height:90px;object-fit:contain;
  border-radius:16px;margin-bottom:24px;
  filter:drop-shadow(0 8px 24px rgba(0,0,0,0.3));
}
.lp-logo-fallback{
  width:90px;height:90px;background:rgba(244,180,0,0.15);border-radius:16px;
  display:flex;align-items:center;justify-content:center;
  font-size:2.2rem;margin-bottom:24px;
}
.lp-school{font-size:1.5rem;font-weight:800;color:#fff;text-align:center;line-height:1.25;margin-bottom:8px;}
.lp-sub{font-size:0.82rem;color:#93b8d4;text-align:center;letter-spacing:0.3px;}
.lp-divider{width:40px;height:3px;background:#f4b400;border-radius:2px;margin:20px auto;}
.lp-quote{
  margin-top:28px;padding:14px 18px;
  background:rgba(244,180,0,0.08);border-left:3px solid #f4b400;
  border-radius:0 10px 10px 0;max-width:300px;
}
.lp-quote p{font-size:0.8rem;color:#b8d4e8;font-style:italic;line-height:1.5;}
.rp{
  flex:1;background:#f8fafc;
  display:flex;align-items:center;justify-content:center;
  padding:40px 32px;overflow-y:auto;
}
.form-card{
  width:100%;max-width:400px;
  background:#fff;border-radius:20px;
  padding:36px 32px;
  box-shadow:0 4px 24px rgba(11,32,64,0.08);
  border:1px solid #e8edf4;
}
.form-title{
  font-size:1.5rem;font-weight:800;color:#0b2040;
  margin-bottom:4px;
}
.form-subtitle{font-size:0.85rem;color:#64748b;margin-bottom:28px;}
.error-box{
  background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;
  border-radius:10px;padding:10px 14px;font-size:0.85rem;
  display:flex;align-items:center;gap:8px;margin-bottom:20px;
  position:relative;padding-right:36px;
}
.field{margin-bottom:18px;}
.field label{
  display:block;font-size:0.82rem;font-weight:700;
  color:#374151;margin-bottom:6px;letter-spacing:0.2px;
}
.field input{
  width:100%;padding:12px 14px;
  border:1.5px solid #d1d5db;border-radius:10px;
  font-size:0.95rem;color:#111827;background:#fff;
  outline:none;transition:border-color 0.2s,box-shadow 0.2s;
  -webkit-appearance:none;appearance:none;
}
.field input:focus{
  border-color:#0b2040;
  box-shadow:0 0 0 3px rgba(11,32,64,0.08);
}
.btn-login{
  width:100%;padding:13px;border:none;border-radius:10px;
  background:linear-gradient(135deg,#0b2040 0%,#0f3460 100%);
  color:#fff;font-size:0.95rem;font-weight:700;
  cursor:pointer;transition:all 0.2s;
  box-shadow:0 4px 12px rgba(11,32,64,0.25);
  margin-top:4px;letter-spacing:0.3px;
}
.btn-login:hover{
  background:linear-gradient(135deg,#0d2a56 0%,#143d75 100%);
  box-shadow:0 6px 18px rgba(11,32,64,0.3);transform:translateY(-1px);
}
.form-footer{
  margin-top:22px;padding-top:18px;border-top:1px solid #f1f5f9;
  display:flex;align-items:center;justify-content:space-between;
  flex-wrap:wrap;gap:8px;
}
.back-home{
  font-size:0.82rem;color:#0b2040;text-decoration:none;
  font-weight:600;display:flex;align-items:center;gap:5px;
}
.back-home:hover{color:#f4b400;}
@media(max-width:768px){
  body{flex-direction:column;background:#f8fafc;}
  .lp{
    width:100%;padding:28px 24px 24px;
    flex-direction:row;flex-wrap:wrap;justify-content:flex-start;
    gap:14px;align-items:center;border-radius:0 0 24px 24px;
  }
  .lp::before,.lp::after{display:none;}
  .lp-logo{width:56px;height:56px;margin:0;border-radius:12px;}
  .lp-logo-fallback{width:56px;height:56px;margin:0;border-radius:12px;font-size:1.4rem;}
  .lp-info{flex:1;}
  .lp-school{font-size:1.1rem;text-align:left;margin:0;}
  .lp-sub{text-align:left;font-size:0.75rem;}
  .lp-divider,.lp-quote{display:none;}
  .rp{padding:20px 16px;align-items:flex-start;}
  .form-card{border-radius:16px;padding:24px 20px;}
  .form-title{font-size:1.25rem;}
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
    <div class="form-title">Karibu Mzazi</div>
    <div class="form-subtitle">Ingia ili kuona matokeo na taarifa za mtoto wako</div>

    <?php if(isset($error)): ?>
    <div class="error-box">
      <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" autocomplete="on">
      <div class="field">
        <label for="email">Barua Pepe</label>
        <input type="email" name="email" id="email" placeholder="mzazi@example.com" required autocomplete="email">
      </div>
      <div class="field">
        <label for="password">Nenosiri</label>
        <input type="password" name="password" id="password" placeholder="Ingiza nenosiri lako" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn-login">
        <i class="fas fa-sign-in-alt" style="margin-right:6px"></i> Ingia
      </button>
    </form>

    <div class="form-footer">
      <a href="../index.php" class="back-home"><i class="fas fa-arrow-left"></i> Nyumbani</a>
    </div>
  </div>
</div>

</body>
</html>
