<?php
session_start();
include "../includes/config.php";
include "../includes/teaching_docs_functions.php";
if (!isset($_SESSION['teacher_id'])) { header("Location: ../login.php"); exit(); }
$teacher_id = intval($_SESSION['teacher_id']);

$scheme_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$scheme = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT sw.*, sub.subject_name, tr.term_name, ay.title AS year_title
    FROM scheme_of_work sw
    JOIN subjects sub ON sub.id = sw.subject_id
    LEFT JOIN terms tr ON tr.id = sw.term_id
    LEFT JOIN academic_years ay ON ay.id = sw.academic_year_id
    WHERE sw.id = $scheme_id AND sw.teacher_id = $teacher_id
"));
if (!$scheme) { echo "<div style='padding:40px;text-align:center;color:#9ca3af;'>Scheme not found or access denied.</div>"; exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_weeks'])) {
    foreach ($_POST['week'] as $week_id => $data) {
        $week_id = intval($week_id);
        $topic = mysqli_real_escape_string($conn, $data['topic'] ?? '');
        $subtopic = mysqli_real_escape_string($conn, $data['subtopic'] ?? '');
        $objectives = mysqli_real_escape_string($conn, $data['objectives'] ?? '');
        $teaching = mysqli_real_escape_string($conn, $data['teaching_activities'] ?? '');
        $learning = mysqli_real_escape_string($conn, $data['learning_activities'] ?? '');
        $resources = mysqli_real_escape_string($conn, $data['resources'] ?? '');
        $assessment = mysqli_real_escape_string($conn, $data['assessment'] ?? '');
        $remarks = mysqli_real_escape_string($conn, $data['remarks'] ?? '');
        mysqli_query($conn, "UPDATE scheme_weeks SET topic='$topic', subtopic='$subtopic', objectives='$objectives', teaching_activities='$teaching', learning_activities='$learning', resources='$resources', assessment='$assessment', remarks='$remarks' WHERE id=$week_id AND scheme_id=$scheme_id");
    }
    $flash = 'Scheme weeks saved.';
    $flash_type = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_scheme'])) {
    mysqli_query($conn, "UPDATE scheme_of_work SET status='submitted' WHERE id=$scheme_id AND teacher_id=$teacher_id");
    $flash = 'Scheme submitted for review.';
    $flash_type = 'success';
}

$weeks = mysqli_query($conn, "SELECT * FROM scheme_weeks WHERE scheme_id = $scheme_id ORDER BY week_number ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Scheme of Work — <?= htmlspecialchars($scheme['title']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#f3f4f6;font-family:system-ui,-apple-system,sans-serif;padding:14px;color:#111827;}
.page-title{font-size:18px;font-weight:800;color:#0b2b3f;margin-bottom:12px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.page-title .sub{font-size:12px;font-weight:400;color:#6b7280;}
.info-bar{font-size:12px;color:#6b7280;margin-bottom:12px;display:flex;gap:16px;flex-wrap:wrap;}
.info-bar span{background:#fff;padding:6px 12px;border-radius:8px;border:1px solid #e5e7eb;}
.flash{padding:10px 14px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:12px;display:flex;align-items:center;gap:8px;}
.flash.success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;}
.week-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px;margin-bottom:10px;}
.week-hdr{font-size:13px;font-weight:700;color:#0b2b3f;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center;}
.week-dates{font-size:11px;font-weight:400;color:#6b7280;}
.f-group{margin-bottom:6px;}
.f-label{display:block;font-size:11px;font-weight:700;color:#374151;margin-bottom:2px;}
.f-input{width:100%;padding:5px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:12px;}
.f-input:focus{border-color:#6366f1;outline:none;box-shadow:0 0 0 2px rgba(99,102,241,.1);}
textarea.f-input{resize:vertical;min-height:40px;}
.f-row{display:grid;grid-template-columns:1fr 1fr;gap:8px;}
.btn-sm{padding:5px 14px;font-size:12px;border-radius:8px;border:none;font-weight:600;cursor:pointer;text-decoration:none;}
.btn-primary{background:#6366f1;color:#fff;}
.btn-success{background:#10b981;color:#fff;}
.btn-secondary{background:#e5e7eb;color:#374151;}
.btn-sm:hover{opacity:.9;}
.back-link{font-size:12px;color:#6366f1;text-decoration:none;font-weight:600;}
.back-link:hover{text-decoration:underline;}
.status-bar{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:10px 14px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;}
.status-label{font-size:12px;font-weight:600;}
.uc-banner{display:flex;align-items:center;gap:8px;background:#fef3c7;border:1px solid #fde68a;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:13px;font-weight:600;color:#92400e;}
</style>
</head>
<body>

<div class="uc-banner"><i class="bi bi-tools"></i> 🚧 Under Construction — This section is being updated.</div>

<div class="page-title">
    <a href="teaching_docs.php" target="mainFrame" class="back-link"><i class="bi bi-arrow-left"></i> Back</a>
    <span style="margin-left:8px;"><?= htmlspecialchars($scheme['title']) ?></span>
    <span class="sub"><?= htmlspecialchars($scheme['subject_name']) ?></span>
</div>

<div class="info-bar">
    <span>📅 <?= htmlspecialchars($scheme['term_name'] ?? '—') ?></span>
    <span>🎓 <?= htmlspecialchars($scheme['form_level'] ?? 'All') ?></span>
    <span>📆 <?= htmlspecialchars($scheme['year_title'] ?? '') ?></span>
    <span>📊 <?= intval($scheme['total_weeks']) ?> weeks</span>
    <span>🏷 <?= ucfirst($scheme['status']) ?></span>
</div>

<?php if (isset($flash)): ?>
<div class="flash success"><i class="bi bi-check-circle-fill"></i> <?= $flash ?></div>
<?php endif; ?>

<?php if ($scheme['status'] !== 'approved'): ?>
<div class="status-bar">
    <span class="status-label">Status: <strong><?= ucfirst($scheme['status']) ?></strong></span>
    <div>
        <form method="post" style="display:inline;">
            <button type="submit" name="submit_scheme" value="1" class="btn-sm btn-success" onclick="return confirm('Submit this scheme for review?')"><i class="bi bi-send"></i> Submit for Review</button>
        </form>
    </div>
</div>
<?php else: ?>
<div class="status-bar" style="background:#d1fae5;">
    <span class="status-label">✅ Approved — no further edits allowed.</span>
</div>
<?php endif; ?>

<form method="post">
<input type="hidden" name="save_weeks" value="1">

<?php if (mysqli_num_rows($weeks) == 0): ?>
<div style="text-align:center;padding:40px;color:#9ca3af;">
    <i class="bi bi-calendar-x" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.3;"></i>
    No week entries generated yet.
</div>
<?php else: ?>
<?php while ($w = mysqli_fetch_assoc($weeks)):
    $disabled = $scheme['status'] === 'approved' ? 'readonly style="background:#f9fafb;"' : '';
?>
<div class="week-card">
    <div class="week-hdr">
        <span>Week <?= intval($w['week_number']) ?></span>
        <span class="week-dates"><?= htmlspecialchars($w['start_date'] ?? '') ?> — <?= htmlspecialchars($w['end_date'] ?? '') ?></span>
    </div>
    <div class="f-row">
        <div class="f-group">
            <label class="f-label">Topic</label>
            <input class="f-input" name="week[<?= $w['id'] ?>][topic]" value="<?= htmlspecialchars($w['topic'] ?? '') ?>" <?= $disabled ?>>
        </div>
        <div class="f-group">
            <label class="f-label">Subtopic</label>
            <input class="f-input" name="week[<?= $w['id'] ?>][subtopic]" value="<?= htmlspecialchars($w['subtopic'] ?? '') ?>" <?= $disabled ?>>
        </div>
    </div>
    <div class="f-group">
        <label class="f-label">Objectives</label>
        <textarea class="f-input" name="week[<?= $w['id'] ?>][objectives]" rows="2" <?= $disabled ?>><?= htmlspecialchars($w['objectives'] ?? '') ?></textarea>
    </div>
    <div class="f-row">
        <div class="f-group">
            <label class="f-label">Teaching Activities</label>
            <textarea class="f-input" name="week[<?= $w['id'] ?>][teaching_activities]" rows="2" <?= $disabled ?>><?= htmlspecialchars($w['teaching_activities'] ?? '') ?></textarea>
        </div>
        <div class="f-group">
            <label class="f-label">Learning Activities</label>
            <textarea class="f-input" name="week[<?= $w['id'] ?>][learning_activities]" rows="2" <?= $disabled ?>><?= htmlspecialchars($w['learning_activities'] ?? '') ?></textarea>
        </div>
    </div>
    <div class="f-row">
        <div class="f-group">
            <label class="f-label">Resources</label>
            <textarea class="f-input" name="week[<?= $w['id'] ?>][resources]" rows="1" <?= $disabled ?>><?= htmlspecialchars($w['resources'] ?? '') ?></textarea>
        </div>
        <div class="f-group">
            <label class="f-label">Assessment</label>
            <textarea class="f-input" name="week[<?= $w['id'] ?>][assessment]" rows="1" <?= $disabled ?>><?= htmlspecialchars($w['assessment'] ?? '') ?></textarea>
        </div>
    </div>
    <div class="f-group">
        <label class="f-label">Remarks</label>
        <textarea class="f-input" name="week[<?= $w['id'] ?>][remarks]" rows="1" <?= $disabled ?>><?= htmlspecialchars($w['remarks'] ?? '') ?></textarea>
    </div>
</div>
<?php endwhile; ?>

<?php if ($scheme['status'] !== 'approved'): ?>
<div style="text-align:right;margin-top:8px;">
    <button type="submit" class="btn-sm btn-primary"><i class="bi bi-check-lg"></i> Save All Weeks</button>
</div>
<?php endif; ?>
<?php endif; ?>

</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
