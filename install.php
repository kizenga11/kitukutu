<?php
/**
 * One-time installer — creates missing tables.
 *
 * RUN ONCE, then DELETE this file for security.
 * Access via: https://yourdomain.com/install.php
 */

require_once __DIR__ . '/includes/config.php';

$queries = [
    "CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
        `id`          INT AUTO_INCREMENT PRIMARY KEY,
        `token`       VARCHAR(64) NOT NULL,
        `otp`         VARCHAR(6) NOT NULL DEFAULT '',
        `user_type`   ENUM('admin','teacher') NOT NULL,
        `user_id`     INT NOT NULL,
        `expires_at`  DATETIME NOT NULL,
        `created_at`  DATETIME DEFAULT NOW(),
        UNIQUE KEY  `uq_token` (`token`),
        INDEX       `idx_expires` (`expires_at`),
        INDEX       `idx_otp` (`otp`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

$success = [];
$errors  = [];

foreach ($queries as $label => $sql) {
    if (mysqli_query($conn, $sql)) {
        $success[] = is_string($label) ? $label : 'Table created successfully';
    } else {
        $errors[] = mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Install · Kitukutu</title>
<style>
  body{font-family:system-ui,sans-serif;background:#0b2040;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;}
  .card{background:#fff;border-radius:16px;padding:36px;max-width:480px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,.3);}
  h1{font-size:1.3rem;color:#0b2040;margin:0 0 8px;}
  p{color:#6b7280;font-size:.9rem;margin:0 0 20px;}
  .ok{background:#f0fdf4;border:1px solid #86efac;color:#16a34a;border-radius:8px;padding:10px 14px;font-size:.85rem;}
  .err{background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;border-radius:8px;padding:10px 14px;font-size:.85rem;margin-top:8px;}
  .del-note{margin-top:20px;background:#fff7ed;border:1px solid #fdba74;color:#92400e;border-radius:8px;padding:10px 14px;font-size:.8rem;}
</style>
</head>
<body>
<div class="card">
  <h1>🔧 Database Installer</h1>
  <p>Creating required tables for Kitukutu School System…</p>

  <?php if ($success): ?>
    <div class="ok">✅ <?= implode('<br>✅ ', array_map('htmlspecialchars', $success)) ?></div>
  <?php endif; ?>
  <?php foreach ($errors as $e): ?>
    <div class="err">❌ <?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>

  <div class="del-note">
    <strong>⚠️ Security:</strong> Delete <code>install.php</code> after running!
  </div>
</div>
</body>
</html>
