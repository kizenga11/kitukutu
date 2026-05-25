<?php
session_start();
include "../includes/config.php";
include "../includes/teaching_docs_functions.php";
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }

$scheme_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$scheme = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT sw.*, sub.subject_name, t.first_name, t.last_name, tr.term_name
    FROM scheme_of_work sw
    JOIN subjects sub ON sub.id = sw.subject_id
    LEFT JOIN teachers t ON t.id = sw.teacher_id
    LEFT JOIN terms tr ON tr.id = sw.term_id
    WHERE sw.id = $scheme_id
"));
if (!$scheme) { echo "<div style='padding:40px;text-align:center;color:#94a3b8;'>Scheme not found.</div>"; exit(); }

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
    $_SESSION['flash'] = ['msg' => 'Scheme weeks saved.', 'type' => 'success'];
    header("Location: scheme_weeks.php?id=$scheme_id");
    exit();
}

$weeks = mysqli_query($conn, "SELECT * FROM scheme_weeks WHERE scheme_id = $scheme_id ORDER BY week_number ASC");
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Scheme Weeks — <?= htmlspecialchars($scheme['title']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#f0f2f5;font-family:system-ui,-apple-system,sans-serif;padding:20px;color:#111827;}
.page-title{font-size:18px;font-weight:800;color:#0b2b3f;margin-bottom:16px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.page-title .sub{font-size:12px;font-weight:400;color:#64748b;margin-left:8px;}
.card{background:#fff;border:1px solid #e2edf2;border-radius:16px;padding:20px;margin-bottom:16px;box-shadow:0 2px 8px rgba(0,0,0,.03);}
.flash{padding:10px 14px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:12px;display:flex;align-items:center;gap:8px;transition:opacity .5s;}
.flash.success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;}
.flash.error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;}
.week-card{background:#fff;border:1px solid #e2edf2;border-radius:12px;padding:16px;margin-bottom:12px;}
.week-hdr{font-size:13px;font-weight:700;color:#0b2b3f;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center;}
.week-dates{font-size:11px;font-weight:400;color:#64748b;}
.f-group{margin-bottom:8px;}
.f-label{display:block;font-size:11px;font-weight:700;color:#374151;margin-bottom:2px;}
.f-input{width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:12px;}
.f-input:focus{border-color:#6366f1;outline:none;box-shadow:0 0 0 2px rgba(99,102,241,.1);}
textarea.f-input{resize:vertical;min-height:50px;}
.f-row{display:grid;grid-template-columns:1fr 1fr;gap:8px;}
.btn-sm{padding:5px 14px;font-size:12px;border-radius:8px;border:none;font-weight:600;cursor:pointer;}
.btn-primary{background:#6366f1;color:#fff;}
.btn-secondary{background:#e5e7eb;color:#374151;}
.btn-sm:hover{opacity:.9;}
.back-link{font-size:12px;color:#6366f1;text-decoration:none;font-weight:600;}
.back-link:hover{text-decoration:underline;}
</style>
</head>
<body>

<div class="page-title">
    <a href="manage_schemes.php" target="mainFrame" class="back-link"><i class="bi bi-arrow-left"></i> Back</a>
    <span style="margin-left:8px;"><?= htmlspecialchars($scheme['title']) ?></span>
    <span class="sub"><?= htmlspecialchars($scheme['subject_name']) ?> · <?= htmlspecialchars($scheme['term_name'] ?? '') ?> · <?= htmlspecialchars($scheme['first_name'] ?? '') ?></span>
</div>

<?php if ($flash): ?>
<div class="flash <?= $flash['type'] ?>" id="flashMsg"><i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill' ?>"></i> <?= htmlspecialchars($flash['msg']) ?></div>
<?php endif; ?>

<form method="post">
<input type="hidden" name="save_weeks" value="1">

<?php if (mysqli_num_rows($weeks) == 0): ?>
<div class="card" style="text-align:center;padding:40px;color:#94a3b8;">
    <i class="bi bi-calendar-x" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.3;"></i>
    No week entries yet. Generate weeks from the scheme list.
</div>
<?php else: ?>
<?php while ($w = mysqli_fetch_assoc($weeks)): ?>
<div class="week-card">
    <div class="week-hdr">
        <span>Week <?= intval($w['week_number']) ?></span>
        <span class="week-dates"><?= htmlspecialchars($w['start_date'] ?? '') ?> — <?= htmlspecialchars($w['end_date'] ?? '') ?></span>
    </div>
    <div class="f-row">
        <div class="f-group">
            <label class="f-label">Topic</label>
            <input class="f-input" name="week[<?= $w['id'] ?>][topic]" value="<?= htmlspecialchars($w['topic'] ?? '') ?>" placeholder="Main topic">
        </div>
        <div class="f-group">
            <label class="f-label">Subtopic</label>
            <input class="f-input" name="week[<?= $w['id'] ?>][subtopic]" value="<?= htmlspecialchars($w['subtopic'] ?? '') ?>" placeholder="Subtopic">
        </div>
    </div>
    <div class="f-group">
        <label class="f-label">Objectives</label>
        <textarea class="f-input" name="week[<?= $w['id'] ?>][objectives]" rows="2" placeholder="Learning objectives for this week"><?= htmlspecialchars($w['objectives'] ?? '') ?></textarea>
    </div>
    <div class="f-row">
        <div class="f-group">
            <label class="f-label">Teaching Activities</label>
            <textarea class="f-input" name="week[<?= $w['id'] ?>][teaching_activities]" rows="2" placeholder="What the teacher will do"><?= htmlspecialchars($w['teaching_activities'] ?? '') ?></textarea>
        </div>
        <div class="f-group">
            <label class="f-label">Learning Activities</label>
            <textarea class="f-input" name="week[<?= $w['id'] ?>][learning_activities]" rows="2" placeholder="What students will do"><?= htmlspecialchars($w['learning_activities'] ?? '') ?></textarea>
        </div>
    </div>
    <div class="f-row">
        <div class="f-group">
            <label class="f-label">Resources</label>
            <textarea class="f-input" name="week[<?= $w['id'] ?>][resources]" rows="1" placeholder="Teaching aids, materials"><?= htmlspecialchars($w['resources'] ?? '') ?></textarea>
        </div>
        <div class="f-group">
            <label class="f-label">Assessment</label>
            <textarea class="f-input" name="week[<?= $w['id'] ?>][assessment]" rows="1" placeholder="Assessment methods"><?= htmlspecialchars($w['assessment'] ?? '') ?></textarea>
        </div>
    </div>
    <div class="f-group">
        <label class="f-label">Remarks</label>
        <textarea class="f-input" name="week[<?= $w['id'] ?>][remarks]" rows="1" placeholder="Teacher's remarks"><?= htmlspecialchars($w['remarks'] ?? '') ?></textarea>
    </div>
</div>
<?php endwhile; ?>

<div style="text-align:right;margin-top:8px;">
    <button type="submit" class="btn-sm btn-primary"><i class="bi bi-check-lg"></i> Save All Weeks</button>
</div>
<?php endif; ?>

</form>

<script>
var flashEl = document.getElementById('flashMsg');
if (flashEl) { setTimeout(function(){flashEl.style.opacity='0';setTimeout(function(){flashEl.style.display='none'},500)},4000); }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
