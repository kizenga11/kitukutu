<?php
session_start();
include "../includes/config.php";
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$p = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT lp.*, sub.subject_name, t.first_name, t.last_name,
           tr.term_name, ay.title AS year_title
    FROM lesson_plans lp
    JOIN subjects sub ON sub.id = lp.subject_id
    LEFT JOIN teachers t ON t.id = lp.teacher_id
    LEFT JOIN terms tr ON tr.id = lp.term_id
    LEFT JOIN academic_years ay ON ay.id = lp.academic_year_id
    WHERE lp.id = $id
"));
if (!$p) { echo "<div style='padding:40px;text-align:center;color:#94a3b8;'>Lesson plan not found.</div>"; exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lesson Plan — <?= htmlspecialchars($p['topic'] ?? '') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#f0f2f5;font-family:system-ui,-apple-system,sans-serif;padding:20px;color:#111827;}
.page-title{font-size:18px;font-weight:800;color:#0b2b3f;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.card{background:#fff;border:1px solid #e2edf2;border-radius:16px;padding:20px;margin-bottom:16px;box-shadow:0 2px 8px rgba(0,0,0,.03);}
.sec{font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;margin-top:14px;}
.sec:first-child{margin-top:0;}
.val{font-size:14px;color:#111827;margin-bottom:8px;white-space:pre-wrap;}
.back-link{font-size:12px;color:#6366f1;text-decoration:none;font-weight:600;}
.back-link:hover{text-decoration:underline;}
</style>
</head>
<body>

<div class="page-title">
    <a href="manage_lesson_plans.php" target="mainFrame" class="back-link"><i class="bi bi-arrow-left"></i> Back</a>
    <span style="margin-left:8px;">Lesson Plan Details</span>
</div>

<div class="card">
    <div class="sec">Subject</div>
    <div class="val"><?= htmlspecialchars($p['subject_name']) ?></div>

    <div class="sec">Teacher</div>
    <div class="val"><?= htmlspecialchars(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')) ?></div>

    <div class="sec">Date</div>
    <div class="val"><?= htmlspecialchars($p['date']) ?></div>

    <div class="sec">Form Level</div>
    <div class="val"><?= htmlspecialchars($p['form_level'] ?? '—') ?></div>

    <div class="sec">Term / Year</div>
    <div class="val"><?= htmlspecialchars($p['term_name'] ?? '—') ?> / <?= htmlspecialchars($p['year_title'] ?? '—') ?></div>

    <div class="sec">Topic</div>
    <div class="val"><?= htmlspecialchars($p['topic'] ?? '—') ?></div>

    <div class="sec">Subtopic</div>
    <div class="val"><?= htmlspecialchars($p['subtopic'] ?? '—') ?></div>

    <div class="sec">Objectives</div>
    <div class="val"><?= htmlspecialchars($p['objectives'] ?? '—') ?></div>

    <div class="sec">Teaching Methods</div>
    <div class="val"><?= htmlspecialchars($p['teaching_methods'] ?? '—') ?></div>

    <div class="sec">Learning Activities</div>
    <div class="val"><?= htmlspecialchars($p['learning_activities'] ?? '—') ?></div>

    <div class="sec">Materials / Resources</div>
    <div class="val"><?= htmlspecialchars($p['materials'] ?? '—') ?></div>

    <div class="sec">Assessment</div>
    <div class="val"><?= htmlspecialchars($p['assessment'] ?? '—') ?></div>

    <div class="sec">Reflection</div>
    <div class="val"><?= htmlspecialchars($p['reflection'] ?? '—') ?></div>

    <div class="sec">Status</div>
    <div class="val"><?= ucfirst($p['status']) ?></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
