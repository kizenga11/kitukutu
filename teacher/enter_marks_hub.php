<?php
session_start();
include "../includes/config.php";
if (!isset($_SESSION['teacher_id'])) { header("Location: ../login.php"); exit(); }
$teacher_id = intval($_SESSION['teacher_id']);

// Active exams + teacher's subjects that belong to each exam
$active_exams_q = mysqli_query($conn, "
    SELECT e.id, e.exam_name, e.start_date, e.end_date, ec.category_name
    FROM exams e
    LEFT JOIN exam_categories ec ON ec.id = e.category_id
    WHERE e.is_active = 1
    ORDER BY e.start_date DESC
");
$active_exams = [];
while ($e = mysqli_fetch_assoc($active_exams_q)) {
    // Get teacher's subjects for this exam (intersection of teacher_assignments & exam_subjects)
    $subs_q = mysqli_query($conn, "
        SELECT s.id AS subject_id, s.subject_name, s.stream, ta.class_stream,
               (SELECT COUNT(*) FROM marks m WHERE m.subject_id=s.id AND m.exam_id={$e['id']}) AS entered_count,
               (SELECT COUNT(*) FROM student_subjects ss2 WHERE ss2.subject_id=s.id) AS total_students
        FROM teacher_assignments ta
        JOIN subjects s ON s.id = ta.subject_id
        WHERE ta.teacher_id = $teacher_id
          AND ta.subject_id IN (SELECT subject_id FROM exam_subjects WHERE exam_id={$e['id']})
        ORDER BY s.subject_name
    ");
    $subs = [];
    while ($s = mysqli_fetch_assoc($subs_q)) $subs[] = $s;
    if (!empty($subs)) {
        $e['subjects'] = $subs;
        $active_exams[] = $e;
    }
}

// Teacher's weekly tests (most recent first, limit 20)
$weekly_q = mysqli_query($conn, "
    SELECT twt.id, twt.test_name, twt.date, twt.class_stream, twt.class_name,
           s.subject_name, s.id AS subject_id,
           (SELECT COUNT(*) FROM marks m WHERE m.subject_id=twt.subject_id AND m.exam_id=twt.id) AS entered_count,
           (SELECT COUNT(*) FROM student_subjects ss WHERE ss.subject_id=twt.subject_id) AS total_students
    FROM teacher_weekly_tests twt
    JOIN subjects s ON s.id = twt.subject_id
    WHERE twt.teacher_id = $teacher_id
    ORDER BY twt.date DESC, twt.id DESC
    LIMIT 20
");
$weekly_tests = [];
while ($w = mysqli_fetch_assoc($weekly_q)) $weekly_tests[] = $w;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Enter Marks</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
* { box-sizing: border-box; }
body { background: #f3f4f6; font-family: system-ui, -apple-system, sans-serif; padding: 14px; margin: 0; }

/* Section header */
.sec-hdr {
    display: flex; align-items: center; gap: 8px;
    font-size: 13px; font-weight: 700; color: #374151;
    margin: 0 0 10px;
    padding-bottom: 8px;
    border-bottom: 2px solid #e5e7eb;
}
.sec-hdr i { font-size: 16px; }

/* Exam block */
.exam-block {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    margin-bottom: 14px;
    overflow: hidden;
}
.exam-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 11px 14px;
    background: linear-gradient(135deg, #1a1a2e, #16213e);
    color: #fff;
    flex-wrap: wrap; gap: 6px;
}
.exam-header .exam-name { font-weight: 700; font-size: 14px; }
.exam-header .exam-meta { font-size: 11px; opacity: .75; margin-top: 2px; }
.exam-badge {
    font-size: 10px; font-weight: 700; padding: 3px 10px; border-radius: 20px;
    background: #10b981; color: #fff;
}

/* Subject cards grid */
.subj-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
    gap: 10px;
    padding: 12px;
}
.subj-card {
    border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px;
    background: #fafafa; cursor: pointer; text-decoration: none;
    color: inherit; transition: all .15s; display: block;
}
.subj-card:hover {
    border-color: #6366f1; background: #fff;
    box-shadow: 0 0 0 3px rgba(99,102,241,.1);
    transform: translateY(-1px);
}
.subj-name { font-weight: 600; font-size: 13px; color: #111827; margin-bottom: 4px; line-height: 1.3; }
.subj-stream {
    font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 20px;
    display: inline-block; margin-bottom: 8px;
}
.stream-gen  { background: #dbeafe; color: #1e40af; }
.stream-voc  { background: #fce7f3; color: #9d174d; }
.subj-prog { margin-top: 6px; }
.prog-bar { height: 5px; border-radius: 4px; background: #e5e7eb; overflow: hidden; }
.prog-fill { height: 100%; border-radius: 4px; background: #6366f1; transition: width .4s; }
.prog-label { font-size: 10px; color: #9ca3af; margin-top: 3px; }
.enter-btn {
    display: flex; align-items: center; justify-content: center; gap: 5px;
    margin-top: 8px; padding: 6px; border-radius: 8px;
    background: #6366f1; color: #fff; font-size: 11px; font-weight: 700;
}
.subj-card:hover .enter-btn { background: #4f46e5; }
.done-btn { background: #10b981; }
.subj-card:hover .done-btn { background: #059669; }

/* Weekly test rows */
.test-row {
    display: flex; align-items: center; gap: 12px; padding: 11px 14px;
    border-bottom: 1px solid #f3f4f6; text-decoration: none; color: inherit;
    transition: background .12s;
}
.test-row:last-child { border-bottom: none; }
.test-row:hover { background: #f9fafb; }
.test-icon {
    width: 38px; height: 38px; border-radius: 10px;
    background: #ede9fe; color: #6366f1;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px; flex-shrink: 0;
}
.test-name { font-weight: 600; font-size: 13px; color: #111827; }
.test-meta { font-size: 11px; color: #9ca3af; margin-top: 2px; }
.test-pct { font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 20px; flex-shrink: 0; }

/* Empty state */
.empty-state { text-align: center; padding: 30px 16px; color: #9ca3af; }
.empty-state i { font-size: 2.2rem; display: block; margin-bottom: 8px; color: #d1d5db; }
.empty-state p { font-size: 13px; margin: 0; }

@media (max-width: 480px) {
    .subj-grid { grid-template-columns: 1fr 1fr; gap: 8px; }
    .exam-header { flex-direction: column; align-items: flex-start; }
}
</style>
</head>
<body>

<!-- Page title -->
<div class="d-flex align-items-center gap-2 mb-3">
    <div style="width:36px;height:36px;border-radius:10px;background:#6366f1;display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;flex-shrink:0">
        <i class="bi bi-pencil-square"></i>
    </div>
    <div>
        <div style="font-weight:800;font-size:15px;color:#111827">Enter Marks</div>
        <div style="font-size:11px;color:#9ca3af">Select an exam and subject to begin</div>
    </div>
</div>

<!-- ═══ ACTIVE EXAMS ═══ -->
<div class="sec-hdr">
    <i class="bi bi-clipboard-check text-primary"></i>
    Active Exams
    <?php if (!empty($active_exams)): ?>
    <span style="background:#dbeafe;color:#1e40af;border-radius:20px;padding:1px 9px;font-size:11px"><?= count($active_exams) ?></span>
    <?php endif; ?>
</div>

<?php if (empty($active_exams)): ?>
<div class="empty-state mb-3">
    <i class="bi bi-calendar-x"></i>
    <p>No active exams at the moment.<br>Exams will appear here when activated by the admin.</p>
</div>
<?php else: ?>
<?php foreach ($active_exams as $exam):
    $total_entered = array_sum(array_column($exam['subjects'], 'entered_count'));
    $total_possible = array_sum(array_column($exam['subjects'], 'total_students'));
    $exam_pct = $total_possible > 0 ? round($total_entered / $total_possible * 100) : 0;
?>
<div class="exam-block">
    <div class="exam-header">
        <div>
            <div class="exam-name"><?= htmlspecialchars($exam['exam_name']) ?></div>
            <div class="exam-meta">
                <i class="bi bi-calendar3 me-1"></i>
                <?= $exam['start_date'] ? date('d M Y', strtotime($exam['start_date'])) : '—' ?>
                <?php if ($exam['end_date']): ?> &ndash; <?= date('d M Y', strtotime($exam['end_date'])) ?><?php endif; ?>
                <?php if ($exam['category_name']): ?> &bull; <?= htmlspecialchars($exam['category_name']) ?><?php endif; ?>
            </div>
        </div>
        <span class="exam-badge"><i class="bi bi-activity me-1"></i>Active</span>
    </div>

    <div class="subj-grid">
        <?php foreach ($exam['subjects'] as $sub):
            $entered = intval($sub['entered_count']);
            $total   = intval($sub['total_students']);
            $pct     = $total > 0 ? round($entered / $total * 100) : 0;
            $done    = ($pct >= 100);
            $stream_cls = strtoupper($sub['stream']) === 'VOCATIONAL' ? 'stream-voc' : 'stream-gen';
        ?>
        <a href="enter_marks.php?exam_id=<?= $exam['id'] ?>&subject_id=<?= $sub['subject_id'] ?>" class="subj-card">
            <div class="subj-name"><?= htmlspecialchars($sub['subject_name']) ?></div>
            <span class="subj-stream <?= $stream_cls ?>"><?= ucfirst(strtolower($sub['stream'])) ?></span>
            <?php if ($sub['class_stream']): ?>
            <span style="font-size:10px;color:#6b7280;margin-left:2px">Class <?= htmlspecialchars($sub['class_stream']) ?></span>
            <?php endif; ?>
            <div class="subj-prog">
                <div class="prog-bar">
                    <div class="prog-fill" style="width:<?= $pct ?>%;<?= $done ? 'background:#10b981' : '' ?>"></div>
                </div>
                <div class="prog-label"><?= $entered ?>/<?= $total ?> students &bull; <?= $pct ?>%</div>
            </div>
            <div class="enter-btn <?= $done ? 'done-btn' : '' ?>">
                <i class="bi bi-<?= $done ? 'check-circle' : 'pencil' ?>"></i>
                <?= $done ? 'Update Marks' : 'Enter Marks' ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- ═══ WEEKLY TESTS ═══ -->
<div class="sec-hdr mt-2">
    <i class="bi bi-journal-text text-warning"></i>
    Weekly Tests
    <?php if (!empty($weekly_tests)): ?>
    <span style="background:#fef3c7;color:#92400e;border-radius:20px;padding:1px 9px;font-size:11px"><?= count($weekly_tests) ?></span>
    <?php endif; ?>
</div>

<?php if (empty($weekly_tests)): ?>
<div class="empty-state">
    <i class="bi bi-journal-x"></i>
    <p>No weekly tests found. Tests created in the system will appear here.</p>
</div>
<?php else: ?>
<div class="exam-block">
    <?php foreach ($weekly_tests as $wt):
        $entered = intval($wt['entered_count']);
        $total   = intval($wt['total_students']);
        $pct     = $total > 0 ? round($entered / $total * 100) : 0;
        $done    = ($pct >= 100);
        $pct_bg  = $done ? '#d1fae5' : ($pct > 0 ? '#fef3c7' : '#fee2e2');
        $pct_col = $done ? '#065f46' : ($pct > 0 ? '#92400e' : '#991b1b');
        $date_fmt = $wt['date'] ? date('d M Y', strtotime($wt['date'])) : '—';
    ?>
    <a href="enter_marks_test.php?test_id=<?= $wt['id'] ?>" class="test-row">
        <div class="test-icon"><i class="bi bi-journal-check"></i></div>
        <div class="flex-grow-1 min-width-0">
            <div class="test-name text-truncate"><?= htmlspecialchars($wt['test_name']) ?></div>
            <div class="test-meta">
                <?= htmlspecialchars($wt['subject_name']) ?>
                <?php if ($wt['class_stream']): ?>&bull; Class <?= htmlspecialchars($wt['class_stream']) ?><?php endif; ?>
                <?php if ($wt['class_name']): ?><?= htmlspecialchars($wt['class_name']) ?><?php endif; ?>
                &bull; <i class="bi bi-calendar3"></i> <?= $date_fmt ?>
            </div>
        </div>
        <div style="text-align:right;flex-shrink:0">
            <span class="test-pct" style="background:<?= $pct_bg ?>;color:<?= $pct_col ?>"><?= $pct ?>%</span>
            <div style="font-size:10px;color:#9ca3af;margin-top:3px"><?= $entered ?>/<?= $total ?></div>
        </div>
        <i class="bi bi-chevron-right text-muted" style="font-size:13px;flex-shrink:0"></i>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

</body>
</html>
