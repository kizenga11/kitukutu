<?php
session_start();
include "../includes/config.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$form_levels = ['', 'Form One', 'Form Two', 'Form Three', 'Form Four'];
$streams = ['', 'General', 'Vocational'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Download Students List</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{--primary:#1a2b4c;--bg:#f3f4f6;}
body{background:var(--bg);font-family:system-ui,-apple-system,sans-serif;padding:16px;font-size:13px;}
h2{font-size:1.2rem;font-weight:700;color:var(--primary);}
.card{border-radius:10px;border:1px solid #e5e7eb;}
</style>
</head>
<body>

<h2><i class="bi bi-download"></i> Pakua Orodha ya Wanafunzi</h2>
<p class="text-muted" style="font-size:13px;">Chagua vigezo na ubonyeze kitufe kupakua PDF yenye majina na namba za usajili za wanafunzi.</p>

<div class="card p-3" style="max-width:500px;">
  <form method="GET" action="download_students_pdf.php" target="_blank">
    <div class="mb-2">
      <label class="form-label" style="font-size:12px;font-weight:600;">Kidato (Form Level)</label>
      <select name="form_level" class="form-select form-select-sm">
        <option value="">— Wote —</option>
        <?php foreach (array_slice($form_levels, 1) as $fl): ?>
        <option value="<?= $fl ?>"><?= $fl ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label" style="font-size:12px;font-weight:600;">Mkondo (Stream)</label>
      <select name="stream" class="form-select form-select-sm">
        <option value="">— Wote —</option>
        <?php foreach (array_slice($streams, 1) as $st): ?>
        <option value="<?= $st ?>"><?= $st ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-sm" style="background:var(--primary);color:#fff;">
      <i class="bi bi-filetype-pdf"></i> Pakua PDF
    </button>
  </form>
</div>

<div class="card p-3 mt-3" style="max-width:500px;">
  <h5 style="font-size:0.95rem;font-weight:700;margin-bottom:8px;"><i class="bi bi-info-circle"></i> Maelezo</h5>
  <p style="font-size:12px;color:#374151;margin:0;">
    PDF hii ina orodha ya wanafunzi wote walio hai pamoja na namba zao za usajili.
    Inaweza kutumika kuwapa wazazi ili kuwaunganisha kwenye mfumo.
    Wazazi wanahitaji namba ya usajili wa mwanafunzi ili kujiunga na kuangalia matokeo.
  </p>
</div>

</body>
</html>
