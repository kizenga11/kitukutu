<?php
session_start();
include "../includes/config.php";
include "../includes/teaching_docs_functions.php";
include "../includes/calendar_functions.php";
if (!isset($_SESSION['teacher_id'])) { header("Location: ../login.php"); exit(); }
$teacher_id = intval($_SESSION['teacher_id']);

$active_year = getActiveAcademicYear($conn);
$year_id = $active_year['id'] ?? 0;
$current_term = getCurrentTerm($conn, $year_id);
$term_id = $current_term['id'] ?? 0;
$assignments = getTeacherAssignments($conn, $teacher_id);
$subject_ids = array_column($assignments, 'subject_id');
$sid_list = empty($subject_ids) ? '0' : implode(',', $subject_ids);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $subject_id = intval($_POST['subject_id']);
        $form_level = mysqli_real_escape_string($conn, $_POST['form_level']);
        $scheme_week_id = intval($_POST['scheme_week_id'] ?? 0);
        $date = mysqli_real_escape_string($conn, $_POST['date']);
        $topic = mysqli_real_escape_string($conn, $_POST['topic'] ?? '');
        $subtopic = mysqli_real_escape_string($conn, $_POST['subtopic'] ?? '');
        $objectives = mysqli_real_escape_string($conn, $_POST['objectives'] ?? '');
        $methods = mysqli_real_escape_string($conn, $_POST['teaching_methods'] ?? '');
        $activities = mysqli_real_escape_string($conn, $_POST['learning_activities'] ?? '');
        $materials = mysqli_real_escape_string($conn, $_POST['materials'] ?? '');
        $assessment = mysqli_real_escape_string($conn, $_POST['assessment'] ?? '');
        $fl = $form_level ? "'$form_level'" : 'NULL';
        $sw = $scheme_week_id ? $scheme_week_id : 'NULL';
        mysqli_query($conn, "INSERT INTO lesson_plans (subject_id, teacher_id, academic_year_id, term_id, form_level, scheme_week_id, date, topic, subtopic, objectives, teaching_methods, learning_activities, materials, assessment) VALUES ($subject_id, $teacher_id, $year_id, $term_id, $fl, $sw, '$date', '$topic', '$subtopic', '$objectives', '$methods', '$activities', '$materials', '$assessment')");
        $plan_id = mysqli_insert_id($conn);
        $_SESSION['flash'] = ['msg' => "Lesson plan created. <a href='lesson_plan.php?edit=$plan_id' target='_self' style='color:#fff;font-weight:700;'>View</a>", 'type' => 'success'];
    }

    if ($action === 'update') {
        $id = intval($_POST['id']);
        $topic = mysqli_real_escape_string($conn, $_POST['topic'] ?? '');
        $subtopic = mysqli_real_escape_string($conn, $_POST['subtopic'] ?? '');
        $objectives = mysqli_real_escape_string($conn, $_POST['objectives'] ?? '');
        $methods = mysqli_real_escape_string($conn, $_POST['teaching_methods'] ?? '');
        $activities = mysqli_real_escape_string($conn, $_POST['learning_activities'] ?? '');
        $materials = mysqli_real_escape_string($conn, $_POST['materials'] ?? '');
        $assessment = mysqli_real_escape_string($conn, $_POST['assessment'] ?? '');
        $reflection = mysqli_real_escape_string($conn, $_POST['reflection'] ?? '');
        $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'draft');
        mysqli_query($conn, "UPDATE lesson_plans SET topic='$topic', subtopic='$subtopic', objectives='$objectives', teaching_methods='$methods', learning_activities='$activities', materials='$materials', assessment='$assessment', reflection='$reflection', status='$status' WHERE id=$id AND teacher_id=$teacher_id");
        $_SESSION['flash'] = ['msg' => 'Lesson plan updated.', 'type' => 'success'];
    }

    if ($action === 'delete') {
        $id = intval($_POST['id']);
        mysqli_query($conn, "DELETE FROM lesson_plans WHERE id=$id AND teacher_id=$teacher_id");
        $_SESSION['flash'] = ['msg' => 'Lesson plan deleted.', 'type' => 'success'];
    }

    header("Location: lesson_plan.php");
    exit();
}

// Get schemes for dropdown
$schemes_q = mysqli_query($conn, "
    SELECT sw.id, sw.title, sub.subject_name, sw.form_level
    FROM scheme_of_work sw
    JOIN subjects sub ON sub.id = sw.subject_id
    WHERE sw.teacher_id = $teacher_id AND sw.status = 'approved'
    ORDER BY sub.subject_name
");

// Get my lesson plans
$my_plans = mysqli_query($conn, "
    SELECT lp.*, sub.subject_name
    FROM lesson_plans lp
    JOIN subjects sub ON sub.id = lp.subject_id
    WHERE lp.teacher_id = $teacher_id
    ORDER BY lp.date DESC, lp.created_at DESC
");

$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$edit_plan = null;
if ($edit_id) {
    $edit_plan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM lesson_plans WHERE id = $edit_id AND teacher_id = $teacher_id"));
}

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
body{background:#f3f4f6;font-family:system-ui,-apple-system,sans-serif;padding:14px;color:#111827;}
.page-title{font-size:18px;font-weight:800;color:#0b2b3f;margin-bottom:12px;display:flex;align-items:center;gap:8px;}
.panel{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:14px;margin-bottom:12px;}
.sec-hdr{font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;display:flex;align-items:center;gap:6px;}
.flash{padding:10px 14px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:12px;transition:opacity .5s;}
.flash.success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;display:flex;align-items:center;gap:8px;}
.flash.error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;}
.f-group{margin-bottom:10px;}
.f-label{display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:3px;}
.f-input,.f-select{width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;}
.f-input:focus,.f-select:focus{border-color:#6366f1;outline:none;box-shadow:0 0 0 3px rgba(99,102,241,.1);}
textarea.f-input{resize:vertical;min-height:60px;}
.f-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.btn-sm{padding:5px 14px;font-size:12px;border-radius:8px;border:none;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px;}
.btn-primary{background:#6366f1;color:#fff;}
.btn-secondary{background:#e5e7eb;color:#374151;}
.btn-success{background:#10b981;color:#fff;}
.btn-danger-sm{background:#fee2e2;color:#991b1b;}
.btn-primary-sm{background:#dbeafe;color:#1d4ed8;}
.plan-card{display:flex;gap:10px;padding:8px 10px;margin-bottom:6px;border-radius:10px;align-items:center;background:#fafafa;}
.plan-card:last-child{margin-bottom:0;}
.plan-info{flex:1;}
.plan-title{font-size:13px;font-weight:700;}
.plan-meta{font-size:10px;color:#6b7280;}
.badge-draft{background:#f3f4f6;color:#6b7280;padding:2px 10px;border-radius:20px;font-size:10px;font-weight:700;}
.badge-submitted{background:#d1fae5;color:#065f46;padding:2px 10px;border-radius:20px;font-size:10px;font-weight:700;}
.empty-msg{text-align:center;padding:20px;color:#9ca3af;font-size:13px;}
.empty-msg i{font-size:1.8rem;display:block;margin-bottom:6px;opacity:.3;}
.modal-content{border-radius:12px;border:none;box-shadow:0 8px 30px rgba(0,0,0,.12);}
</style>
</head>
<body>

<div class="page-title"><i class="bi bi-file-earmark-text"></i> Lesson Plans</div>

<?php if ($flash): ?>
<div class="flash <?= $flash['type'] ?>"><?= $flash['msg'] ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-md-<?= $edit_plan ? '7' : '12' ?>">

        <!-- My Lesson Plans -->
        <div class="panel">
            <div class="sec-hdr"><i class="bi bi-list-check"></i> My Lesson Plans</div>
            <?php if (mysqli_num_rows($my_plans) == 0): ?>
            <div class="empty-msg"><i class="bi bi-file-earmark-x"></i>No lesson plans yet.</div>
            <?php else: ?>
            <?php while ($p = mysqli_fetch_assoc($my_plans)): ?>
            <div class="plan-card">
                <div class="plan-info">
                    <div class="plan-title"><?= htmlspecialchars($p['subject_name']) ?> — <?= htmlspecialchars($p['topic'] ?? 'No topic') ?></div>
                    <div class="plan-meta"><?= htmlspecialchars($p['date']) ?> · <?= htmlspecialchars($p['form_level'] ?? '') ?> · <?= ucfirst($p['status']) ?></div>
                </div>
                <span class="badge-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span>
                <a href="lesson_plan.php?edit=<?= $p['id'] ?>" class="btn-sm btn-primary-sm"><i class="bi bi-pencil"></i></a>
                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this lesson plan?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button class="btn-sm btn-danger-sm"><i class="bi bi-trash3"></i></button>
                </form>
            </div>
            <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <!-- Create New -->
        <?php if (!$edit_plan): ?>
        <div class="panel">
            <div class="sec-hdr"><i class="bi bi-plus-square"></i> New Lesson Plan</div>
            <form method="post">
                <input type="hidden" name="action" value="create">
                <div class="f-row">
                    <div class="f-group">
                        <label class="f-label">Subject</label>
                        <select class="f-select" name="subject_id" required>
                            <option value="">— Select —</option>
                            <?php foreach ($assignments as $a): ?>
                            <option value="<?= $a['subject_id'] ?>"><?= htmlspecialchars($a['subject_name']) ?> (<?= htmlspecialchars($a['form_level']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="f-group">
                        <label class="f-label">Form Level</label>
                        <select class="f-select" name="form_level">
                            <option value="">— Select —</option>
                            <?php foreach (getForms() as $f): ?>
                            <option value="<?= $f ?>"><?= $f ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="f-row">
                    <div class="f-group">
                        <label class="f-label">Date</label>
                        <input class="f-input" type="date" name="date" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="f-group">
                        <label class="f-label">Scheme Week (optional)</label>
                        <select class="f-select" name="scheme_week_id">
                            <option value="0">— None —</option>
                            <?php while ($s = mysqli_fetch_assoc($schemes_q)):
                                $weeks_q = mysqli_query($conn, "SELECT id, week_number, topic FROM scheme_weeks WHERE scheme_id = {$s['id']} AND topic IS NOT NULL AND topic != '' ORDER BY week_number");
                                while ($w = mysqli_fetch_assoc($weeks_q)):
                            ?>
                            <option value="<?= $w['id'] ?>"><?= htmlspecialchars($s['subject_name']) ?> — Week <?= intval($w['week_number']) ?>: <?= htmlspecialchars($w['topic']) ?></option>
                            <?php endwhile; endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="f-row">
                    <div class="f-group">
                        <label class="f-label">Topic</label>
                        <input class="f-input" name="topic" placeholder="Main topic">
                    </div>
                    <div class="f-group">
                        <label class="f-label">Subtopic</label>
                        <input class="f-input" name="subtopic" placeholder="Subtopic">
                    </div>
                </div>
                <div class="f-group">
                    <label class="f-label">Objectives</label>
                    <textarea class="f-input" name="objectives" rows="2" placeholder="Learning objectives"></textarea>
                </div>
                <div class="f-group">
                    <label class="f-label">Teaching Methods</label>
                    <input class="f-input" name="teaching_methods" placeholder="e.g. Lecture, Discussion, Group Work">
                </div>
                <div class="f-group">
                    <label class="f-label">Learning Activities</label>
                    <textarea class="f-input" name="learning_activities" rows="2" placeholder="What students will do"></textarea>
                </div>
                <div class="f-row">
                    <div class="f-group">
                        <label class="f-label">Materials / Resources</label>
                        <input class="f-input" name="materials" placeholder="Books, charts, etc.">
                    </div>
                    <div class="f-group">
                        <label class="f-label">Assessment</label>
                        <input class="f-input" name="assessment" placeholder="Quiz, questions, etc.">
                    </div>
                </div>
                <div style="text-align:right;margin-top:8px;">
                    <button type="submit" class="btn-sm btn-primary"><i class="bi bi-check-lg"></i> Create Lesson Plan</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($edit_plan): ?>
    <div class="col-md-5">
        <div class="panel">
            <div class="sec-hdr"><i class="bi bi-pencil-square"></i> Edit Lesson Plan</div>
            <form method="post">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= $edit_plan['id'] ?>">
                <div class="f-group">
                    <label class="f-label">Topic</label>
                    <input class="f-input" name="topic" value="<?= htmlspecialchars($edit_plan['topic'] ?? '') ?>">
                </div>
                <div class="f-group">
                    <label class="f-label">Subtopic</label>
                    <input class="f-input" name="subtopic" value="<?= htmlspecialchars($edit_plan['subtopic'] ?? '') ?>">
                </div>
                <div class="f-group">
                    <label class="f-label">Objectives</label>
                    <textarea class="f-input" name="objectives" rows="2"><?= htmlspecialchars($edit_plan['objectives'] ?? '') ?></textarea>
                </div>
                <div class="f-group">
                    <label class="f-label">Teaching Methods</label>
                    <input class="f-input" name="teaching_methods" value="<?= htmlspecialchars($edit_plan['teaching_methods'] ?? '') ?>">
                </div>
                <div class="f-group">
                    <label class="f-label">Learning Activities</label>
                    <textarea class="f-input" name="learning_activities" rows="2"><?= htmlspecialchars($edit_plan['learning_activities'] ?? '') ?></textarea>
                </div>
                <div class="f-group">
                    <label class="f-label">Materials / Resources</label>
                    <input class="f-input" name="materials" value="<?= htmlspecialchars($edit_plan['materials'] ?? '') ?>">
                </div>
                <div class="f-group">
                    <label class="f-label">Assessment</label>
                    <input class="f-input" name="assessment" value="<?= htmlspecialchars($edit_plan['assessment'] ?? '') ?>">
                </div>
                <div class="f-group">
                    <label class="f-label">Reflection (after teaching)</label>
                    <textarea class="f-input" name="reflection" rows="2" placeholder="How did the lesson go?"><?= htmlspecialchars($edit_plan['reflection'] ?? '') ?></textarea>
                </div>
                <div class="f-group">
                    <label class="f-label">Status</label>
                    <select class="f-select" name="status">
                        <option value="draft" <?= $edit_plan['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="submitted" <?= $edit_plan['status'] === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                    </select>
                </div>
                <div style="display:flex;gap:8px;margin-top:8px;">
                    <button type="submit" class="btn-sm btn-primary"><i class="bi bi-check-lg"></i> Save</button>
                    <a href="lesson_plan.php" class="btn-sm btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
var flashEl = document.querySelector('.flash');
if (flashEl) { setTimeout(function(){flashEl.style.opacity='0';setTimeout(function(){flashEl.style.display='none'},500)},4000); }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
