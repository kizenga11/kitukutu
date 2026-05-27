<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

$selected_students = isset($_GET['students']) ? array_map('intval', (array)$_GET['students']) : [];
$selected_exams    = isset($_GET['exams']) ? array_map('intval', (array)$_GET['exams']) : [];
$form_level_filter = isset($_GET['form_level']) ? mysqli_real_escape_string($conn, $_GET['form_level']) : '';
$mode = $_GET['mode'] ?? 'grade';

$school_name = "KITUKUTU TECHNICAL SCHOOL";

function grade($m){
    if($m >= 75) return 'A';
    if($m >= 65) return 'B';
    if($m >= 45) return 'C';
    if($m >= 30) return 'D';
    return 'F';
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bulk Multi-Exam Results</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#f0f2f5;font-family:system-ui,-apple-system,sans-serif;padding:16px;font-size:13px;color:#1a1a2e;}
.container{max-width:1200px;margin:0 auto;}
.card{background:#fff;border-radius:12px;padding:16px;margin-bottom:14px;box-shadow:0 1px 4px rgba(0,0,0,0.06);border:1px solid #eef0f4;}
.card-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;margin-bottom:10px;}
.form-label{font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;display:block;}
.form-select,.form-control{width:100%;padding:8px 10px;border:1.5px solid #d1d5db;border-radius:8px;font-size:13px;outline:none;background:#fff;}
.form-select:focus,.form-control:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,0.12);}
.btn{display:inline-flex;align-items:center;gap:5px;padding:8px 16px;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;transition:all .15s;}
.btn-primary{background:#6366f1;color:#fff;}
.btn-primary:hover{background:#4f46e5;}
.btn-success{background:#10b981;color:#fff;}
.btn-success:hover{background:#059669;}
.btn-outline{background:transparent;color:#6b7280;border:1.5px solid #d1d5db;}
.btn-outline:hover{background:#f3f4f6;}
.btn-sm{padding:5px 10px;font-size:11px;}

.stu-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:6px;max-height:300px;overflow-y:auto;padding:4px;}
.stu-check{display:flex;align-items:center;gap:8px;padding:6px 10px;border:1.5px solid #e5e7eb;border-radius:8px;cursor:pointer;transition:all .12s;font-size:12px;}
.stu-check:hover{border-color:#6366f1;background:#f5f3ff;}
.stu-check input[type="checkbox"]{accent-color:#6366f1;width:16px;height:16px;cursor:pointer;flex-shrink:0;}
.stu-check .s-name{font-weight:500;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.stu-check .s-meta{font-size:10px;color:#9ca3af;flex-shrink:0;}

.exam-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:6px;max-height:250px;overflow-y:auto;padding:4px;}
.exam-check{display:flex;align-items:center;gap:8px;padding:6px 10px;border:1.5px solid #e5e7eb;border-radius:8px;cursor:pointer;transition:all .12s;font-size:12px;}
.exam-check:hover{border-color:#6366f1;background:#f5f3ff;}
.exam-check input[type="checkbox"]{accent-color:#f59e0b;width:16px;height:16px;cursor:pointer;flex-shrink:0;}

/* Report */
.report{padding:0;}
.report-card{background:#fff;border-radius:10px;border:1px solid #e5e7eb;margin-bottom:16px;padding:16px;page-break-inside:avoid;}
.report-card .r-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;padding-bottom:8px;border-bottom:2px solid #1a1a2e;}
.report-card .r-name{font-size:15px;font-weight:700;color:#1a1a2e;}
.report-card .r-detail{font-size:11px;color:#6b7280;}
.r-table{width:100%;border-collapse:collapse;font-size:11px;margin-top:6px;}
.r-table th{background:#1a2b4c;color:#fff;padding:5px 4px;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;border:1px solid #253b5e;text-align:center;}
.r-table td{padding:4px 3px;text-align:center;border:1px solid #eef0f4;font-size:10px;}
.r-table td.subj-name{text-align:left;font-weight:600;}
.r-summary{display:flex;gap:10px;flex-wrap:wrap;margin-top:8px;font-size:11px;font-weight:600;}
.r-summary span{padding:3px 10px;border-radius:6px;background:#f3f4f6;}

@media print{
    body{background:#fff;padding:0;margin:0;font-size:9px;}
    .no-print{display:none!important;}
    .container{max-width:100%;padding:0;}
    .report-card{border:1px solid #000;border-radius:0;padding:12px;page-break-inside:avoid;}
    .r-table th{background:#ccc!important;color:#000!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;border:1px solid #000;}
    .r-table td{border:1px solid #000;font-size:9px;}
    .report-card .r-header{border-bottom-color:#000;}
}
</style>
</head>
<body>
<div class="container">

<div class="card no-print" style="background:linear-gradient(135deg,#1a2b4c,#2c3e6b);color:#fff;">
    <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:8px;">
        <div style="display:flex;align-items:center;gap:12px;">
            <img src="../assets/logo.png" style="width:48px;height:48px;border-radius:8px;object-fit:cover;">
            <div>
                <h2 style="font-size:16px;font-weight:700;"><?= htmlspecialchars($school_name) ?> — Multi-Exam Results</h2>
                <p style="font-size:12px;opacity:.8;">Select students and multiple exams to print combined results</p>
            </div>
        </div>
        <button onclick="history.back()" class="btn btn-outline" style="background:rgba(255,255,255,0.15);color:#fff;border-color:rgba(255,255,255,0.2);">← Back</button>
    </div>
</div>

<form method="GET" class="no-print">
<div style="display:flex;flex-wrap:wrap;gap:10px;align-items:end;margin-bottom:14px;">
    <div style="flex:1;min-width:150px;">
        <label class="form-label">Form Level</label>
        <select name="form_level" class="form-select" onchange="this.form.submit()">
            <option value="">All Forms</option>
            <?php foreach(['Form One','Form Two','Form Three','Form Four'] as $fl): ?>
            <option value="<?= $fl ?>" <?= $form_level_filter===$fl?'selected':'' ?>><?= str_replace('Form ','F. ',$fl) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div style="flex:1;min-width:150px;">
        <label class="form-label">Display Mode</label>
        <select name="mode" class="form-select">
            <option value="grade" <?= $mode=='grade'?'selected':'' ?>>Grades</option>
            <option value="marks" <?= $mode=='marks'?'selected':'' ?>>Marks</option>
        </select>
    </div>
    <div>
        <button type="submit" class="btn btn-primary">Load Data</button>
    </div>
</div>

<?php
// Get exams
$exams_q = mysqli_query($conn,"SELECT id, exam_name FROM exams ORDER BY start_date DESC");
$exams_all = [];
while($e=mysqli_fetch_assoc($exams_q)) $exams_all[] = $e;

// Get students
$students_list = [];
if ($form_level_filter !== '' || true) {
    $fl_sql = $form_level_filter ? "AND s.form_level='$form_level_filter'" : '';
    $stu_q = mysqli_query($conn,"
        SELECT s.id, s.first_name, s.second_name, s.last_name, s.sex, s.form_level, s.stream
        FROM students s
        WHERE s.id IS NOT NULL $fl_sql
        ORDER BY s.form_level, s.stream, s.first_name
    ");
    while($s = mysqli_fetch_assoc($stu_q)){
        $name = trim($s['first_name'].' '.($s['second_name']?$s['second_name'].' ':'').$s['last_name']);
        $s['display_name'] = $name;
        $students_list[] = $s;
    }
}
?>

<?php if (!empty($students_list)): ?>
<div class="card no-print">
    <div class="card-title" style="display:flex;justify-content:space-between;align-items:center;">
        <span>Select Students</span>
        <span style="font-weight:400;font-size:11px;">
            <label style="cursor:pointer;"><input type="checkbox" id="selectAllStu" onchange="toggleAll('stu')"> Select All</label>
        </span>
    </div>
    <div class="stu-grid">
        <?php foreach($students_list as $st): ?>
        <label class="stu-check">
            <input type="checkbox" name="students[]" value="<?= $st['id'] ?>" class="stu-cb"
                <?= in_array($st['id'], $selected_students)?'checked':'' ?>>
            <span class="s-name"><?= htmlspecialchars($st['display_name']) ?></span>
            <span class="s-meta"><?= $st['sex']=='Male'?'M':'F' ?> · <?= str_replace('Form ','F.',$st['form_level']) ?></span>
        </label>
        <?php endforeach; ?>
    </div>
</div>

<div class="card no-print">
    <div class="card-title" style="display:flex;justify-content:space-between;align-items:center;">
        <span>Select Exams</span>
        <span style="font-weight:400;font-size:11px;">
            <label style="cursor:pointer;"><input type="checkbox" id="selectAllEx" onchange="toggleAll('ex')"> Select All</label>
        </span>
    </div>
    <div class="exam-grid">
        <?php foreach($exams_all as $ex): ?>
        <label class="exam-check">
            <input type="checkbox" name="exams[]" value="<?= $ex['id'] ?>" class="ex-cb"
                <?= in_array($ex['id'], $selected_exams)?'checked':'' ?>>
            <?= htmlspecialchars($ex['exam_name']) ?>
        </label>
        <?php endforeach; ?>
    </div>
    <div style="margin-top:12px;display:flex;gap:8px;align-items:center;">
        <button type="submit" class="btn btn-success" onclick="return confirmGenerate()">
            <i class="bi bi-file-earmark-text"></i> Generate Reports
        </button>
        <span style="font-size:11px;color:#6b7280;" id="countDisplay">
            <?= count($selected_students) ?> student(s) · <?= count($selected_exams) ?> exam(s)
        </span>
    </div>
</div>
</form>

<script>
function toggleAll(type){
    var checked = document.getElementById('selectAll'+(type==='stu'?'Stu':'Ex')).checked;
    document.querySelectorAll('.'+type+'-cb').forEach(c=>c.checked=checked);
    updateCount();
}
function updateCount(){
    var stu = document.querySelectorAll('.stu-cb:checked').length;
    var ex = document.querySelectorAll('.ex-cb:checked').length;
    document.getElementById('countDisplay').textContent = stu+' student(s) · '+ex+' exam(s)';
}
function confirmGenerate(){
    var stu = document.querySelectorAll('.stu-cb:checked').length;
    var ex = document.querySelectorAll('.ex-cb:checked').length;
    if(stu===0||ex===0){ alert('Please select at least one student and one exam.'); return false; }
    return true;
}
document.querySelectorAll('.stu-cb').forEach(c=>c.addEventListener('change',updateCount));
document.querySelectorAll('.ex-cb').forEach(c=>c.addEventListener('change',updateCount));
updateCount();
</script>
<?php endif; ?>

<?php if (!empty($selected_students) && !empty($selected_exams)):
    // Get exam names
    $exam_names = [];
    foreach($exams_all as $e) $exam_names[$e['id']] = $e['exam_name'];
?>
<div style="text-align:center;margin-bottom:10px;" class="no-print">
    <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer"></i> Print / Save PDF</button>
</div>

<div class="report">
    <?php foreach($selected_students as $sid):
        $st = null;
        foreach($students_list as $s){ if($s['id']==$sid){ $st=$s; break; } }
        if(!$st) continue;

        $full_name = $st['display_name'];
    ?>
    <div class="report-card">
        <div class="r-header">
            <div style="display:flex;align-items:center;gap:10px;">
                <img src="../assets/logo.png" style="width:42px;height:42px;border-radius:6px;object-fit:cover;">
                <div>
                    <div style="font-size:13px;font-weight:700;color:#1a2b4c;"><?= htmlspecialchars($school_name) ?></div>
                    <div class="r-name"><?= htmlspecialchars($full_name) ?></div>
                    <div class="r-detail"><?= htmlspecialchars($st['form_level']) ?> · <?= htmlspecialchars($st['stream']) ?> · <?= $st['sex'] ?></div>
                </div>
            </div>
        </div>
        <table class="r-table">
            <tr>
                <th>#</th>
                <th>Subject</th>
                <?php foreach($selected_exams as $eid): ?>
                <th><?= htmlspecialchars($exam_names[$eid] ?? 'Exam '.$eid) ?></th>
                <?php endforeach; ?>
            </tr>
            <?php
            // Get all subjects for this student across selected exams
            $subj_q = mysqli_query($conn,"
                SELECT DISTINCT sub.id, sub.subject_name, sub.subject_code
                FROM marks m
                JOIN subjects sub ON sub.id = m.subject_id
                WHERE m.student_id = '$sid' AND m.exam_id IN (".implode(',',$selected_exams).")
                ORDER BY sub.subject_code
            ");
            $subj_list = [];
            while($sb = mysqli_fetch_assoc($subj_q)) $subj_list[] = $sb;

            // For each subject, get marks per exam
            $i = 1;
            foreach($subj_list as $sb):
                $subj_name = $sb['subject_name'];
            ?>
            <tr>
                <td><?= $i++ ?></td>
                <td class="subj-name"><?= htmlspecialchars($subj_name) ?></td>
                <?php foreach($selected_exams as $eid):
                    $mq = mysqli_query($conn,"SELECT marks FROM marks WHERE student_id='$sid' AND subject_id='{$sb['id']}' AND exam_id='$eid' LIMIT 1");
                    $mr = mysqli_fetch_assoc($mq);
                    $mark = $mr ? $mr['marks'] : null;
                    if($mark === null || $mark === ''):
                ?>
                <td style="color:#d1d5db;">—</td>
                <?php elseif($mark == 'A' || $mark == 'ABSENT'): ?>
                <td style="color:#ef4444;font-weight:600;">ABS</td>
                <?php else:
                    $val = $mode == 'marks' ? $mark : grade($mark);
                ?>
                <td style="font-weight:700;"><?= $val ?></td>
                <?php endif; endforeach; ?>
            </tr>
            <?php endforeach; ?>

            <?php
            // Summary row per exam
            $summary_row = [];
            foreach($selected_exams as $eid){
                $ers = mysqli_fetch_assoc(mysqli_query($conn,"SELECT average_marks, total_points, division FROM exam_results_summary WHERE student_id='$sid' AND exam_id='$eid'"));
                if($ers){
                    $avg = number_format((float)$ers['average_marks'],2);
                    $pts = (int)$ers['total_points'];
                    $div = $ers['division'] ?: '-';
                    $grd = grade($ers['average_marks']);
                    $summary_row[$eid] = ['avg'=>$avg, 'pts'=>$pts, 'div'=>$div, 'grd'=>$grd];
                } else {
                    $summary_row[$eid] = null;
                }
            }
            ?>
            <tr style="background:#f8f9fc;font-weight:700;">
                <td></td>
                <td style="text-align:left;color:#6b7280;font-size:10px;">Average</td>
                <?php foreach($selected_exams as $eid): ?>
                <td><?= $summary_row[$eid] ? $summary_row[$eid]['avg'] : '—' ?></td>
                <?php endforeach; ?>
            </tr>
            <tr style="background:#f8f9fc;font-weight:700;">
                <td></td>
                <td style="text-align:left;color:#6b7280;font-size:10px;">Grade</td>
                <?php foreach($selected_exams as $eid): ?>
                <td><?= $summary_row[$eid] ? $summary_row[$eid]['grd'] : '—' ?></td>
                <?php endforeach; ?>
            </tr>
            <tr style="background:#f8f9fc;font-weight:700;">
                <td></td>
                <td style="text-align:left;color:#6b7280;font-size:10px;">Points</td>
                <?php foreach($selected_exams as $eid): ?>
                <td><?= $summary_row[$eid] ? $summary_row[$eid]['pts'] : '—' ?></td>
                <?php endforeach; ?>
            </tr>
            <tr style="background:#f8f9fc;font-weight:700;">
                <td></td>
                <td style="text-align:left;color:#6b7280;font-size:10px;">Division</td>
                <?php foreach($selected_exams as $eid): ?>
                <td><?= $summary_row[$eid] ? $summary_row[$eid]['div'] : '—' ?></td>
                <?php endforeach; ?>
            </tr>
        </table>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

</div>
</body>
</html>
