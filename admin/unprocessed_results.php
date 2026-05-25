<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

$sel_form = $_GET['form_level'] ?? '';
$sel_stream = $_GET['stream'] ?? '';
$sel_exam = intval($_GET['exam_id'] ?? 0);
$exam_name = '';
$total_students = 0;
$total_missing = 0;
$table_rows = [];

if ($sel_form && $sel_stream && $sel_exam) {
    $form_level = mysqli_real_escape_string($conn, $sel_form);
    $stream     = mysqli_real_escape_string($conn, $sel_stream);
    $exam_id    = $sel_exam;

    $en = mysqli_fetch_assoc(mysqli_query($conn, "SELECT exam_name FROM exams WHERE id='$exam_id'"));
    $exam_name = $en['exam_name'] ?? '';

    $stu_q = mysqli_query($conn, "
        SELECT id, first_name, second_name, last_name
        FROM students
        WHERE stream='$stream' AND form_level='$form_level'
        ORDER BY first_name
    ");

    $sub_q = mysqli_query($conn, "
        SELECT s.id, s.short_name
        FROM exam_subjects es
        JOIN subjects s ON s.id = es.subject_id
        WHERE es.exam_id='$exam_id' AND s.stream='$stream'
        ORDER BY s.short_name
    ");
    $subjects = [];
    while ($s = mysqli_fetch_assoc($sub_q)) $subjects[] = $s;

    while ($st = mysqli_fetch_assoc($stu_q)) {
        $row = ['name' => trim($st['first_name'] . ' ' . ($st['second_name'] ? $st['second_name'].' ' : '') . $st['last_name']), 'marks' => []];
        $has_missing = false;
        foreach ($subjects as $sub) {
            $hs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM student_subjects WHERE student_id='{$st['id']}' AND subject_id='{$sub['id']}'"));
            if (!$hs) {
                $row['marks'][] = null;
                continue;
            }
            $mk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT marks FROM marks WHERE student_id='{$st['id']}' AND subject_id='{$sub['id']}' AND exam_id='$exam_id'"));
            if ($mk) {
                $row['marks'][] = $mk['marks'];
            } else {
                $row['marks'][] = false;
                $has_missing = true;
            }
        }
        if ($has_missing) $total_missing++;
        $total_students++;
        $table_rows[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Unprocessed Results</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{--primary:#1a2b4c;--primary-light:#eef2f7;--bg:#f3f4f6;}
body{background:var(--bg);font-family:system-ui,-apple-system,sans-serif;}
.content{max-width:1400px;margin:0 auto;padding:16px;}
.filter-card{
    background:#fff;border:1px solid #e5e7eb;border-radius:12px;
    padding:16px;margin-bottom:16px;
}
.filter-card .form-label{font-size:12px;font-weight:600;color:#6b7280;margin-bottom:3px;}
.filter-card .form-select,.filter-card .form-control{
    font-size:13px;border-radius:8px;border:1.5px solid #e5e7eb;
}
.filter-card .form-select:focus,.filter-card .form-control:focus{border-color:var(--primary);box-shadow:0 0 0 2px rgba(26,43,76,.12);}
.info-bar{
    display:flex;flex-wrap:wrap;gap:10px;align-items:center;
    background:#fff;border:1px solid #e5e7eb;border-radius:12px;
    padding:12px 16px;margin-bottom:16px;
}
.info-chip{
    display:inline-flex;align-items:center;gap:5px;
    font-size:12px;font-weight:600;padding:4px 12px;
    background:var(--primary-light);border-radius:20px;color:var(--primary);
}
.table-wrap{background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow-x:auto;}
table{width:100%;border-collapse:collapse;font-size:13px;}
th{
    background:var(--primary);color:#fff;padding:10px 8px;
    font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;
    white-space:nowrap;text-align:center;border:1px solid #1f3460;
}
td{padding:8px;text-align:center;border:1px solid #e5e7eb;vertical-align:middle;}
tr:nth-child(even){background:#f8f9fc;}
.stu-name{text-align:left;font-weight:600;white-space:nowrap;}
.missing{color:#dc2626;font-weight:800;font-size:15px;}
.done{color:#16a34a;font-weight:600;}
.na{background:#f3f4f6;color:#9ca3af;font-size:11px;}
.empty-state{text-align:center;padding:40px 16px;color:#9ca3af;}
.empty-state i{font-size:2.5rem;display:block;margin-bottom:8px;color:#d1d5db;}
@media(max-width:768px){
    .content{padding:10px;}
    .filter-card{padding:12px;}
    th,td{padding:6px 4px;font-size:11px;}
    .stu-name{font-size:12px;}
}
@media print{
    .filter-card,.info-bar .no-print{display:none!important;}
    body{background:#fff;padding:0;}
    .table-wrap{border:none;border-radius:0;}
    th{background:#1a2b4c!important;color:#fff!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
    tr:nth-child(even){background:#f8f9fc!important;}
}
</style>
</head>
<body>

<div style="text-align:right;padding:10px 16px 0;">
    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer"></i> Print</button>
</div>
<div class="content">

    <div class="filter-card">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Form Level</label>
                <select name="form_level" class="form-select" required>
                    <option value="">— Select —</option>
                    <?php foreach (['Form One','Form Two','Form Three','Form Four'] as $f):
                        $sel = $sel_form === $f ? 'selected' : ''; ?>
                    <option value="<?= $f ?>" <?= $sel ?>><?= str_replace('Form ','F. ',$f) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Stream</label>
                <select name="stream" class="form-select" required>
                    <option value="">— Select —</option>
                    <?php foreach (['General','Vocational'] as $s):
                        $sel = $sel_stream === $s ? 'selected' : ''; ?>
                    <option value="<?= $s ?>" <?= $sel ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Exam</label>
                <select name="exam_id" class="form-select" required>
                    <option value="">— Select —</option>
                    <?php
                    $ex = mysqli_query($conn, "SELECT id, exam_name FROM exams WHERE is_published=1");
                    while ($e = mysqli_fetch_assoc($ex)):
                        $sel = $sel_exam == $e['id'] ? 'selected' : ''; ?>
                    <option value="<?= $e['id'] ?>" <?= $sel ?>><?= htmlspecialchars($e['exam_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn w-100" style="background:var(--primary);color:#fff;border-radius:8px;font-weight:600;">
                    <i class="bi bi-search"></i> Load
                </button>
            </div>
        </form>
    </div>

    <?php if ($sel_form && $sel_stream && $sel_exam && $exam_name): ?>

    <div class="info-bar">
        <span class="info-chip"><i class="bi bi-layers"></i> <?= str_replace('Form ','F. ',$sel_form) ?></span>
        <span class="info-chip"><i class="bi bi-diagram-3"></i> <?= $sel_stream ?></span>
        <span class="info-chip"><i class="bi bi-journal-text"></i> <?= htmlspecialchars($exam_name) ?></span>
        <span class="info-chip"><i class="bi bi-people"></i> <?= $total_students ?> students</span>
        <?php if ($total_missing): ?>
        <span class="info-chip" style="background:#fee2e2;color:#991b1b;"><i class="bi bi-exclamation-triangle"></i> <?= $total_missing ?> incomplete</span>
        <?php endif; ?>
        <span class="ms-auto no-print" style="font-size:12px;color:#6b7280;">
            <i class="bi bi-dot text-danger"></i> Missing &nbsp;
            <span style="background:#f3f4f6;padding:2px 8px;border-radius:4px;font-size:11px;">—</span> Not assigned
        </span>
    </div>

    <?php if (empty($table_rows)): ?>
    <div class="empty-state">
        <i class="bi bi-inbox"></i>
        <p style="font-weight:600;margin-bottom:4px;">No data found</p>
        <p style="font-size:13px;">No students match the selected form level and stream for this exam.</p>
    </div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <tr>
                <th>Student</th>
                <?php foreach ($subjects as $sub): ?>
                <th><?= htmlspecialchars($sub['short_name']) ?></th>
                <?php endforeach; ?>
            </tr>
            <?php foreach ($table_rows as $row): ?>
            <tr>
                <td class="stu-name"><?= htmlspecialchars($row['name']) ?></td>
                <?php foreach ($row['marks'] as $mk):
                    if ($mk === null): ?>
                <td class="na">—</td>
                    <?php elseif ($mk === false): ?>
                <td class="missing">-</td>
                    <?php else: ?>
                <td class="done"><?= htmlspecialchars($mk) ?></td>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>

    <?php elseif ($sel_form || $sel_stream || $sel_exam): ?>
    <div class="empty-state">
        <i class="bi bi-search"></i>
        <p style="font-weight:600;margin-bottom:4px;">No matching data</p>
        <p style="font-size:13px;">Select form level, stream, and exam to view results.</p>
    </div>
    <?php endif; ?>

</div>

</body>
</html>