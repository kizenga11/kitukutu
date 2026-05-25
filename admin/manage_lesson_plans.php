<?php
session_start();
include "../includes/config.php";
include "../includes/teaching_docs_functions.php";
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }

$plans = mysqli_query($conn, "
    SELECT lp.*, sub.subject_name, t.first_name, t.last_name,
           tr.term_name, sww.week_number
    FROM lesson_plans lp
    JOIN subjects sub ON sub.id = lp.subject_id
    LEFT JOIN teachers t ON t.id = lp.teacher_id
    LEFT JOIN terms tr ON tr.id = lp.term_id
    LEFT JOIN scheme_weeks sww ON sww.id = lp.scheme_week_id
    ORDER BY lp.date DESC, lp.created_at DESC
");
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lesson Plans</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#f0f2f5;font-family:system-ui,-apple-system,sans-serif;padding:20px;color:#111827;}
.page-title{font-size:18px;font-weight:800;color:#0b2b3f;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.card{background:#fff;border:1px solid #e2edf2;border-radius:16px;padding:20px;margin-bottom:16px;box-shadow:0 2px 8px rgba(0,0,0,.03);}
.table{width:100%;border-collapse:collapse;font-size:13px;}
.table th{background:#f8fafc;padding:10px 12px;text-align:left;font-weight:700;color:#475569;border-bottom:2px solid #e2e8f0;}
.table td{padding:10px 12px;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
.table tr:hover td{background:#f8fafc;}
.flash{padding:10px 14px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:12px;display:flex;align-items:center;gap:8px;transition:opacity .5s;}
.flash.success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;}
.flash.error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;}
.empty{text-align:center;padding:30px;color:#94a3b8;font-size:14px;}
.badge-draft{background:#f3f4f6;color:#6b7280;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;}
.badge-submitted{background:#d1fae5;color:#065f46;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;}
.btn-sm{padding:5px 12px;font-size:12px;border-radius:8px;border:none;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px;}
.btn-primary-sm{background:#dbeafe;color:#1d4ed8;}
.btn-success-sm{background:#d1fae5;color:#065f46;}
.btn-danger-sm{background:#fee2e2;color:#991b1b;}
.uc-banner{display:flex;align-items:center;gap:8px;background:#fef3c7;border:1px solid #fde68a;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:13px;font-weight:600;color:#92400e;}
</style>
</head>
<body>

<div class="uc-banner"><i class="bi bi-tools"></i> 🚧 Under Construction — This section is being updated.</div>

<div class="page-title"><i class="bi bi-file-earmark-text"></i> Lesson Plans</div>

<?php if ($flash): ?>
<div class="flash <?= $flash['type'] ?>" id="flashMsg"><i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill' ?>"></i> <?= htmlspecialchars($flash['msg']) ?></div>
<?php endif; ?>

<div class="card">
    <div style="font-size:14px;font-weight:700;color:#0b2b3f;margin-bottom:14px;">All Lesson Plans</div>

    <?php if (mysqli_num_rows($plans) == 0): ?>
    <div class="empty"><i class="bi bi-file-earmark-x" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.3;"></i>No lesson plans yet. Teachers can create them from their dashboard.</div>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Subject</th><th>Topic</th><th>Teacher</th><th>Date</th><th>Form</th><th>Scheme Week</th><th>Status</th><th style="text-align:right;">Actions</th></tr></thead>
        <tbody>
            <?php while ($p = mysqli_fetch_assoc($plans)): ?>
            <tr>
                <td><?= htmlspecialchars($p['subject_name']) ?></td>
                <td><strong><?= htmlspecialchars($p['topic'] ?? '—') ?></strong></td>
                <td><?= htmlspecialchars(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')) ?></td>
                <td><?= htmlspecialchars($p['date']) ?></td>
                <td><?= htmlspecialchars($p['form_level'] ?? '—') ?></td>
                <td><?= $p['week_number'] ? 'Week ' . intval($p['week_number']) : '—' ?></td>
                <td><span class="badge-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
                <td style="text-align:right;">
                    <a href="view_lesson_plan.php?id=<?= $p['id'] ?>" target="mainFrame" class="btn-sm btn-primary-sm"><i class="bi bi-eye"></i> View</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<script>
var flashEl = document.getElementById('flashMsg');
if (flashEl) { setTimeout(function(){flashEl.style.opacity='0';setTimeout(function(){flashEl.style.display='none'},500)},4000); }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
