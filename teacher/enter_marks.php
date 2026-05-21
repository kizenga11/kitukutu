<?php
session_start();
include "../includes/config.php";
if (!isset($_SESSION['teacher_id'])) { header("Location: ../login.php"); exit(); }

$teacher_id = $_SESSION['teacher_id'];
$exam_id    = intval($_GET['exam_id']  ?? 0);
$subject_id = intval($_GET['subject_id'] ?? 0);

/* ── Weekly test or normal exam ── */
$test = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT t.*, s.subject_name FROM teacher_weekly_tests t
     JOIN subjects s ON s.id=t.subject_id
     WHERE t.id='$exam_id' AND t.teacher_id='$teacher_id'"
));

if ($test) {
    $exam_name  = $test['test_name'];
    $subject_id = $test['subject_id'];
    $is_weekly  = true;
} else {
    $exam = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM exams WHERE id='$exam_id'"));
    if (!$exam) die("Exam not found.");
    $exam_name = $exam['exam_name'];
    $is_weekly = false;
}

/* ── Subject info ── */
$subject = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM subjects WHERE id='$subject_id'"));
if (!$subject) die("Subject not found.");
$subject_name = $subject['subject_name'];

/* ── Save marks ── */
$success = false;
$saved_count = 0;
if (isset($_POST['save_marks'])) {
    foreach ($_POST['marks'] as $sid => $mark) {
        $sid  = intval($sid);
        $mark = strtoupper(trim($mark));
        if ($mark === 'A') {
            $mv = 'A';
        } elseif (is_numeric($mark) && $mark >= 0 && $mark <= 100) {
            $mv = intval($mark);
        } else {
            continue;
        }
        $chk = mysqli_query($conn, "SELECT id FROM marks WHERE student_id='$sid' AND subject_id='$subject_id' AND exam_id='$exam_id'");
        if (mysqli_num_rows($chk) > 0) {
            mysqli_query($conn, "UPDATE marks SET marks='$mv' WHERE student_id='$sid' AND subject_id='$subject_id' AND exam_id='$exam_id'");
        } else {
            mysqli_query($conn, "INSERT INTO marks(student_id,subject_id,exam_id,marks) VALUES('$sid','$subject_id','$exam_id','$mv')");
        }
        $saved_count++;
    }
    $success = true;
    $saved_at = time();
}

/* ── Students ── */
$students_q = mysqli_query($conn,
    "SELECT s.* FROM students s
     JOIN student_subjects ss ON ss.student_id=s.id
     WHERE ss.subject_id='$subject_id'
     ORDER BY s.first_name, s.last_name"
);
$students = [];
while ($r = mysqli_fetch_assoc($students_q)) $students[] = $r;
$total = count($students);

/* Pre-load existing marks */
$existing_map = [];
$em = mysqli_query($conn, "SELECT student_id, marks FROM marks WHERE subject_id='$subject_id' AND exam_id='$exam_id'");
while ($r = mysqli_fetch_assoc($em)) $existing_map[$r['student_id']] = $r['marks'];
$filled = count($existing_map);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Enter Marks</title>
<style>
:root {
    --primary: #4f46e5;
    --primary-light: #ede9fe;
    --success: #10b981;
    --bg: #f3f4f6;
    --card: #fff;
    --border: #e5e7eb;
    --text: #111827;
    --muted: #6b7280;
    --radius: 12px;
}
* { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
body { background: var(--bg); font-family: system-ui, -apple-system, sans-serif; color: var(--text); min-height: 100vh; }

/* ── Topbar ── */
.topbar {
    position: sticky; top: 0; z-index: 100;
    background: var(--card);
    border-bottom: 1px solid var(--border);
    padding: 10px 14px;
    display: flex; align-items: center; gap: 10px;
}
.back-btn {
    display: flex; align-items: center; justify-content: center;
    width: 34px; height: 34px; border-radius: 10px;
    background: var(--bg); border: 1px solid var(--border);
    color: var(--text); text-decoration: none; font-size: 16px; flex-shrink: 0;
}
.topbar-info { flex: 1; min-width: 0; }
.topbar-info .subject { font-weight: 700; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.topbar-info .exam    { font-size: 11px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* ── Progress strip ── */
.progress-strip {
    background: var(--card);
    padding: 8px 14px;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: 10px;
}
.pbar { flex: 1; height: 6px; border-radius: 4px; background: var(--border); overflow: hidden; }
.pbar-fill { height: 100%; border-radius: 4px; background: var(--primary); transition: width .4s; }
.pbar-fill.full { background: var(--success); }
.prog-label { font-size: 11px; font-weight: 700; color: var(--muted); flex-shrink: 0; }
.prog-saved { font-size: 10px; color: var(--muted); font-weight: 500; white-space: nowrap; }
.prog-saved i { font-style: normal; display: inline-block; }

/* ── Success toast ── */
.toast {
    display: none; position: fixed; top: 14px; left: 50%; transform: translateX(-50%);
    background: var(--success); color: #fff; padding: 8px 20px; border-radius: 30px;
    font-size: 13px; font-weight: 600; z-index: 999; box-shadow: 0 4px 16px rgba(16,185,129,.35);
    white-space: nowrap;
}
.toast.show { display: block; animation: fadeUp .3s ease; }
@keyframes fadeUp { from { opacity:0; transform: translateX(-50%) translateY(8px); } to { opacity:1; transform: translateX(-50%) translateY(0); } }

/* ── Student list ── */
.list { padding: 10px 12px 110px; }
.student-row {
    display: flex; align-items: center; gap: 10px;
    background: var(--card); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 10px 12px;
    margin-bottom: 7px; transition: border-color .15s;
}
.student-row.filled { border-color: #c4b5fd; background: #fafafe; }
.num { font-size: 11px; font-weight: 700; color: var(--muted); min-width: 22px; }
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
.mark-input.has-value { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
.mark-input.absent { border-color: #f59e0b; color: #92400e; background: #fef3c7; }
.mark-input.changed { border-color: #059669; background: #ecfdf5; }
.mark-input.changed.absent { border-color: #059669; background: #ecfdf5; color:#92400e; }
.mark-input.changed.has-value { border-color: #059669; color: #059669; background: #ecfdf5; }
.changed-dot { width: 6px; height: 6px; border-radius: 50%; background: #059669; flex-shrink: 0; display: none; }
.changed-dot.show { display: block; }

/* ── Save button ── */
.save-bar {
    position: fixed; bottom: 0; left: 0; right: 0;
    padding: 10px 14px calc(10px + env(safe-area-inset-bottom));
    background: var(--card); border-top: 1px solid var(--border);
    z-index: 100;
}
.save-btn {
    width: 100%; padding: 13px; border: none; border-radius: var(--radius);
    background: var(--primary); color: #fff; font-size: 15px; font-weight: 700;
    cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: background .15s; -webkit-tap-highlight-color: transparent;
}
.save-btn:active { background: #4338ca; transform: scale(.98); }
.save-btn svg { width: 18px; height: 18px; }

/* ── Empty state ── */
.empty { text-align: center; padding: 50px 16px; color: var(--muted); }
.empty svg { width: 48px; height: 48px; margin-bottom: 12px; opacity: .4; }
</style>
</head>
<body>

<?php if ($success): ?>
<div class="toast show" id="toast">✓ <?= $saved_count ?> mark<?= $saved_count !== 1 ? 's' : '' ?> saved</div>
<script>
setTimeout(()=>document.getElementById('toast').classList.remove('show'),3000);
<?php if(isset($saved_at)): ?>
localStorage.setItem('lastSaved_<?= $exam_id ?>_<?= $subject_id ?>', '<?= date('H:i:s') ?>');
<?php endif; ?>
</script>
<?php endif; ?>

<!-- Topbar -->
<div class="topbar">
    <a href="enter_marks_hub.php" class="back-btn">&#8592;</a>
    <div class="topbar-info">
        <div class="subject"><?= htmlspecialchars($subject_name) ?></div>
        <div class="exam"><?= htmlspecialchars($exam_name) ?></div>
    </div>
</div>

<!-- Progress -->
<div class="progress-strip">
    <div class="pbar">
        <div class="pbar-fill <?= $filled >= $total && $total > 0 ? 'full' : '' ?>"
             style="width:<?= $total > 0 ? round($filled/$total*100) : 0 ?>%"></div>
    </div>
    <span class="prog-label" id="progLabel"><?= $filled ?>/<?= $total ?> filled</span>
    <span class="prog-saved" id="savedLabel"></span>
</div>

<!-- Student list -->
<form method="POST" id="marksForm">
<div class="list">
<?php if (empty($students)): ?>
<div class="empty">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
    <p>No students found for this subject.</p>
</div>
<?php else: ?>
<?php foreach ($students as $i => $row):
    $sid   = $row['id'];
    $name  = trim($row['first_name'] . ' ' . ($row['second_name'] ? $row['second_name'].' ' : '') . $row['last_name']);
    $val   = $existing_map[$sid] ?? '';
    $isAbs = ($val === 'A');
    $haval = $val !== '';
    $rowCls = $haval ? 'filled' : '';
    $inpCls = $isAbs ? 'absent' : ($haval ? 'has-value' : '');
?>
<div class="student-row <?= $rowCls ?>" id="row-<?= $sid ?>">
    <div class="num"><?= $i + 1 ?></div>
    <div class="sname">
        <?= htmlspecialchars($name) ?>
        <div class="sex"><?= htmlspecialchars($row['sex'] ?? '') ?></div>
    </div>
    <span class="changed-dot" id="dot-<?= $sid ?>"></span>
    <input type="text" inputmode="numeric"
           class="mark-input <?= $inpCls ?>"
           name="marks[<?= $sid ?>]"
           id="inp-<?= $sid ?>"
           value="<?= htmlspecialchars($val) ?>"
           placeholder="—"
           maxlength="3"
           autocomplete="off">
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<!-- Save button -->
<div class="save-bar">
    <button type="submit" name="save_marks" class="save-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg>
        Save Marks
    </button>
</div>
</form>

<script>
const inputs     = Array.from(document.querySelectorAll('.mark-input'));
let filled       = <?= $filled ?>;
const total      = <?= $total ?>;
const originals  = new Map();

// Store original values to detect changes
inputs.forEach(inp => originals.set(inp, inp.value));

// Show last saved time
(function(){
    var key = 'lastSaved_<?= $exam_id ?>_<?= $subject_id ?>';
    var t = localStorage.getItem(key);
    if (t) document.getElementById('savedLabel').textContent = 'Saved ' + t;
})();

function updateProgress() {
    filled = inputs.filter(i => i.value.trim() !== '').length;
    const pct = total > 0 ? Math.round(filled / total * 100) : 0;
    const bar  = document.querySelector('.pbar-fill');
    bar.style.width = pct + '%';
    bar.classList.toggle('full', filled >= total && total > 0);
    document.getElementById('progLabel').textContent = filled + '/' + total + ' filled';
}

function hasUnsavedChanges() {
    return inputs.some(inp => inp.value !== originals.get(inp));
}

// Warn before leaving with unsaved changes
function onBeforeUnload(e) {
    if (hasUnsavedChanges()) {
        e.preventDefault();
        e.returnValue = '';
    }
}
window.addEventListener('beforeunload', onBeforeUnload);
// Remove warning when submitting form
document.getElementById('marksForm').addEventListener('submit', function() {
    window.removeEventListener('beforeunload', onBeforeUnload);
});

inputs.forEach((inp, idx) => {
    styleInput(inp);

    inp.addEventListener('input', function () {
        let v = this.value.toUpperCase().replace(/[^0-9A]/g, '');
        if (v !== 'A') {
            v = v.replace(/[^0-9]/g, '');
            if (v !== '' && parseInt(v) > 100) v = '100';
        }
        this.value = v;
        styleInput(this);
        checkChanged(this);
        updateProgress();
        if ((v.length >= 2 && v !== 'A') || v === 'A') {
            const next = inputs[idx + 1];
            if (next) { next.focus(); next.select(); }
        }
    });

    inp.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const next = inputs[idx + 1];
            if (next) { next.focus(); next.select(); }
        }
        if (e.key === 'ArrowDown') { e.preventDefault(); inputs[idx+1]?.focus(); }
        if (e.key === 'ArrowUp')   { e.preventDefault(); inputs[idx-1]?.focus(); }
    });

    inp.addEventListener('focus', function() { this.select(); });
});

function styleInput(inp) {
    const v   = inp.value.trim();
    const row = inp.closest('.student-row');
    inp.classList.toggle('has-value', v !== '' && v !== 'A');
    inp.classList.toggle('absent',    v === 'A');
    row.classList.toggle('filled',    v !== '');
}

function checkChanged(inp) {
    var changed = inp.value !== originals.get(inp);
    inp.classList.toggle('changed', changed);
    var dot = document.getElementById('dot-' + inp.name.match(/\d+/)[0]);
    if (dot) dot.classList.toggle('show', changed);
}

// Auto-focus first empty field on load
(function(){
    var empty = inputs.find(i => i.value.trim() === '');
    if (empty) { empty.focus(); empty.select(); }
})();
</script>
</body>
</html>
