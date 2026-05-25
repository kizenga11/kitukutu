<?php
session_start();
include "../includes/config.php";
if (!isset($_SESSION['teacher_id'])) { header("Location: ../login.php"); exit(); }

$teacher_id = intval($_SESSION['teacher_id']);
$test_id    = intval($_GET['test_id'] ?? 0);

$test_q = mysqli_query($conn,
    "SELECT t.*, s.subject_name FROM teacher_weekly_tests t
     JOIN subjects s ON s.id=t.subject_id
     WHERE t.id='$test_id' AND t.teacher_id='$teacher_id'"
);
if (!$test_q || mysqli_num_rows($test_q) == 0) die("Test not found.");
$test = mysqli_fetch_assoc($test_q);

$subject_id   = $test['subject_id'];
$subject_name = $test['subject_name'];
$test_name    = $test['test_name'];
$test_date    = $test['date'] ? date('d M Y', strtotime($test['date'])) : '';

/* ── Save ── */
$success = false;
if (isset($_POST['save_marks'])) {
    foreach ($_POST['marks'] as $sid => $mark) {
        $sid  = intval($sid);
        $mark = intval($mark);
        if ($mark >= 0 && $mark <= 100) {
            $form_map = ['Form 1'=>'Form One','Form 2'=>'Form Two','Form 3'=>'Form Three','Form 4'=>'Form Four'];
        $fl = $form_map[$test['class_name']] ?? 'Form One';
        $chk = mysqli_query($conn, "SELECT id FROM marks WHERE student_id='$sid' AND subject_id='$subject_id' AND exam_id='$test_id'");
            if (mysqli_num_rows($chk) > 0) {
                mysqli_query($conn, "UPDATE marks SET marks='$mark', form_level='$fl' WHERE student_id='$sid' AND subject_id='$subject_id' AND exam_id='$test_id'");
            } else {
                mysqli_query($conn, "INSERT INTO marks(student_id,subject_id,exam_id,form_level,marks) VALUES('$sid','$subject_id','$test_id','$fl','$mark')");
            }
        }
    }
    $success = true;
}

/* ── Students ── */
$test_class = $test['class_name']; // e.g. "Form 1", "Form 2"
$formFilter = '';
if ($test_class) {
    $form_map = ['Form 1'=>'Form One','Form 2'=>'Form Two','Form 3'=>'Form Three','Form 4'=>'Form Four'];
    $fl = $form_map[$test_class] ?? '';
    if ($fl) $formFilter = "AND s.form_level='$fl'";
}
$students_q = mysqli_query($conn,
    "SELECT s.* FROM students s
     JOIN student_subjects ss ON ss.student_id=s.id
     WHERE ss.subject_id='$subject_id' $formFilter
     ORDER BY s.first_name, s.last_name"
);
$students = [];
while ($r = mysqli_fetch_assoc($students_q)) $students[] = $r;
$total = count($students);

$existing_map = [];
$em = mysqli_query($conn, "SELECT student_id, marks FROM marks WHERE subject_id='$subject_id' AND exam_id='$test_id'");
while ($r = mysqli_fetch_assoc($em)) $existing_map[$r['student_id']] = $r['marks'];
$filled = count($existing_map);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Weekly Test Marks</title>
<style>
:root {
    --primary: #f59e0b;
    --primary-light: #fef3c7;
    --primary-dark: #d97706;
    --success: #10b981;
    --bg: #f3f4f6;
    --card: #fff;
    --border: #e5e7eb;
    --text: #111827;
    --muted: #6b7280;
    --radius: 12px;
}
* { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
body { background: var(--bg); font-family: system-ui, -apple-system, sans-serif; color: var(--text); }

.topbar {
    position: sticky; top: 0; z-index: 100;
    background: var(--card); border-bottom: 1px solid var(--border);
    padding: 10px 14px;
    display: flex; align-items: center; gap: 10px;
}
.back-btn {
    display: flex; align-items: center; justify-content: center;
    width: 34px; height: 34px; border-radius: 10px;
    background: var(--bg); border: 1px solid var(--border);
    color: var(--text); text-decoration: none; font-size: 16px; flex-shrink: 0;
}
.type-badge {
    font-size: 10px; font-weight: 700; padding: 3px 9px; border-radius: 20px;
    background: var(--primary-light); color: #92400e; flex-shrink: 0;
}
.topbar-info { flex: 1; min-width: 0; }
.topbar-info .subject { font-weight: 700; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.topbar-info .exam    { font-size: 11px; color: var(--muted); }

.progress-strip {
    background: var(--card); padding: 8px 14px; border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: 10px;
}
.pbar { flex: 1; height: 6px; border-radius: 4px; background: var(--border); overflow: hidden; }
.pbar-fill { height: 100%; border-radius: 4px; background: var(--primary); transition: width .4s; }
.pbar-fill.full { background: var(--success); }
.prog-label { font-size: 11px; font-weight: 700; color: var(--muted); flex-shrink: 0; }

.toast {
    display: none; position: fixed; top: 14px; left: 50%; transform: translateX(-50%);
    background: var(--success); color: #fff; padding: 8px 20px; border-radius: 30px;
    font-size: 13px; font-weight: 600; z-index: 999; box-shadow: 0 4px 16px rgba(16,185,129,.35);
    white-space: nowrap;
}
.toast.show { display: block; animation: fadeUp .3s ease; }
@keyframes fadeUp { from { opacity:0; transform:translateX(-50%) translateY(8px); } to { opacity:1; transform:translateX(-50%) translateY(0); } }

.list { padding: 10px 12px 110px; }
.student-row {
    display: flex; align-items: center; gap: 10px;
    background: var(--card); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 10px 12px;
    margin-bottom: 7px; transition: border-color .15s;
}
.student-row.filled { border-color: #fcd34d; background: #fffbeb; }
.num  { font-size: 11px; font-weight: 700; color: var(--muted); min-width: 22px; }
.sname { flex: 1; font-size: 13px; font-weight: 500; min-width: 0; }
.sname .sex { font-size: 10px; color: var(--muted); margin-top: 1px; }

.mark-input {
    width: 68px; height: 38px; border: 1.5px solid var(--border);
    border-radius: 10px; text-align: center; font-size: 14px; font-weight: 700;
    background: #f9fafb; color: var(--text); outline: none;
    -webkit-appearance: none; appearance: none;
    transition: border-color .15s, background .15s;
}
.mark-input:focus { border-color: var(--primary); background: var(--primary-light); }
.mark-input.has-value { border-color: var(--primary-dark); color: #92400e; background: var(--primary-light); }

.save-bar {
    position: fixed; bottom: 0; left: 0; right: 0;
    padding: 10px 14px calc(10px + env(safe-area-inset-bottom));
    background: var(--card); border-top: 1px solid var(--border); z-index: 100;
}
.save-btn {
    width: 100%; padding: 13px; border: none; border-radius: var(--radius);
    background: var(--primary-dark); color: #fff; font-size: 15px; font-weight: 700;
    cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: background .15s;
}
.save-btn:active { background: #b45309; transform: scale(.98); }

.empty { text-align: center; padding: 50px 16px; color: var(--muted); }
</style>
</head>
<body>

<?php if ($success): ?>
<div class="toast show" id="toast">✓ Marks saved</div>
<script>setTimeout(()=>document.getElementById('toast').classList.remove('show'),2500)</script>
<?php endif; ?>

<div class="topbar">
    <a href="enter_marks_hub.php" class="back-btn">&#8592;</a>
    <div class="topbar-info">
        <div class="subject"><?= htmlspecialchars($subject_name) ?></div>
        <div class="exam"><?= htmlspecialchars($test_name) ?><?= $test_date ? ' &bull; '.$test_date : '' ?></div>
    </div>
    <span class="type-badge">Weekly</span>
</div>

<div class="progress-strip">
    <div class="pbar">
        <div class="pbar-fill <?= $filled >= $total && $total > 0 ? 'full' : '' ?>"
             style="width:<?= $total > 0 ? round($filled/$total*100) : 0 ?>%"></div>
    </div>
    <span class="prog-label" id="progLabel"><?= $filled ?>/<?= $total ?> filled</span>
</div>

<form method="POST" id="marksForm">
<div class="list">
<?php if (empty($students)): ?>
<div class="empty"><p>No students found for this subject.</p></div>
<?php else: ?>
<?php foreach ($students as $i => $row):
    $sid  = $row['id'];
    $name = trim($row['first_name'] . ' ' . ($row['second_name'] ? $row['second_name'].' ' : '') . $row['last_name']);
    $val  = $existing_map[$sid] ?? '';
?>
<div class="student-row <?= $val !== '' ? 'filled' : '' ?>" id="row-<?= $sid ?>">
    <div class="num"><?= $i + 1 ?></div>
    <div class="sname">
        <?= htmlspecialchars($name) ?>
        <div class="sex"><?= htmlspecialchars($row['sex'] ?? '') ?></div>
    </div>
    <input type="number" inputmode="numeric"
           class="mark-input <?= $val !== '' ? 'has-value' : '' ?>"
           name="marks[<?= $sid ?>]"
           id="inp-<?= $sid ?>"
           value="<?= htmlspecialchars($val) ?>"
           placeholder="—"
           min="0" max="100"
           autocomplete="off">
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<div class="save-bar">
    <button type="submit" name="save_marks" class="save-btn">
        ✓ &nbsp;Save Marks
    </button>
</div>
</form>

<script>
const inputs = Array.from(document.querySelectorAll('.mark-input'));
const total  = <?= $total ?>;

function updateProgress() {
    const filled = inputs.filter(i => i.value.trim() !== '').length;
    const pct  = total > 0 ? Math.round(filled / total * 100) : 0;
    const bar  = document.querySelector('.pbar-fill');
    bar.style.width = pct + '%';
    bar.classList.toggle('full', filled >= total && total > 0);
    document.getElementById('progLabel').textContent = filled + '/' + total + ' filled';
}

inputs.forEach((inp, idx) => {
    inp.addEventListener('input', function () {
        let v = parseInt(this.value);
        if (isNaN(v)) { this.value = ''; } else {
            if (v > 100) this.value = 100;
            if (v < 0)   this.value = 0;
        }
        const row = this.closest('.student-row');
        this.classList.toggle('has-value', this.value !== '');
        row.classList.toggle('filled', this.value !== '');
        updateProgress();
        if (String(this.value).length >= 2) {
            const next = inputs[idx + 1];
            if (next) { next.focus(); next.select(); }
        }
    });
    inp.addEventListener('keydown', e => {
        if (e.key === 'Enter')     { e.preventDefault(); inputs[idx+1]?.focus(); }
        if (e.key === 'ArrowDown') { e.preventDefault(); inputs[idx+1]?.focus(); }
        if (e.key === 'ArrowUp')   { e.preventDefault(); inputs[idx-1]?.focus(); }
    });
    inp.addEventListener('focus', function() { this.select(); });
});
</script>
</body>
</html>
