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
<style>
@media print{.no-print{display:none!important;}}
</style>
</head>
<body>

<form method="GET" class="no-print">
    <label>Form Level
        <select name="form_level" onchange="this.form.submit()">
            <option value="">All Forms</option>
            <?php foreach(['Form One','Form Two','Form Three','Form Four'] as $fl): ?>
            <option value="<?= $fl ?>" <?= $form_level_filter===$fl?'selected':'' ?>><?= str_replace('Form ','F. ',$fl) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Display Mode
        <select name="mode">
            <option value="grade" <?= $mode=='grade'?'selected':'' ?>>Grades</option>
            <option value="marks" <?= $mode=='marks'?'selected':'' ?>>Marks</option>
        </select>
    </label>

    <button type="submit">Load Data</button>

<?php
$exams_q = mysqli_query($conn,"SELECT id, exam_name FROM exams ORDER BY start_date DESC");
$exams_all = [];
while($e=mysqli_fetch_assoc($exams_q)) $exams_all[] = $e;

$students_list = [];
if ($form_level_filter !== '' || true) {
    $fl_sql = $form_level_filter ? "AND s.form_level='$form_level_filter'" : '';
    $stu_q = mysqli_query($conn,"
        SELECT s.id, s.first_name, s.second_name, s.last_name, s.sex, s.form_level, s.stream
        FROM students s
        WHERE s.is_active=1 $fl_sql
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
    <p><strong>Select Students</strong>
        <label><input type="checkbox" id="selectAllStu" onchange="toggleAll('stu')"> Select All</label>
    </p>
    <div>
        <?php foreach($students_list as $st): ?>
        <label>
            <input type="checkbox" name="students[]" value="<?= $st['id'] ?>" class="stu-cb"
                <?= in_array($st['id'], $selected_students)?'checked':'' ?>>
            <?= htmlspecialchars($st['display_name']) ?>
            (<?= $st['sex']=='Male'?'M':'F' ?> · <?= str_replace('Form ','F.',$st['form_level']) ?>)
        </label><br>
        <?php endforeach; ?>
    </div>

    <p><strong>Select Exams</strong>
        <label><input type="checkbox" id="selectAllEx" onchange="toggleAll('ex')"> Select All</label>
    </p>
    <div>
        <?php foreach($exams_all as $ex): ?>
        <label>
            <input type="checkbox" name="exams[]" value="<?= $ex['id'] ?>" class="ex-cb"
                <?= in_array($ex['id'], $selected_exams)?'checked':'' ?>>
            <?= htmlspecialchars($ex['exam_name']) ?>
        </label><br>
        <?php endforeach; ?>
    </div>
    <p>
        <button type="submit" onclick="return confirmGenerate()">Generate Reports</button>
        <span id="countDisplay"><?= count($selected_students) ?> student(s) · <?= count($selected_exams) ?> exam(s)</span>
    </p>
<?php endif; ?>
</form>

<?php if (!empty($students_list)): ?>
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
    $exam_names = [];
    foreach($exams_all as $e) $exam_names[$e['id']] = $e['exam_name'];
?>
    <p class="no-print">
        <a href="bulk_student_results_pdf.php?<?= http_build_query($_GET) ?>" target="_blank">Download PDF</a>
        <button type="button" onclick="window.print()">Print / Save PDF</button>
    </p>

    <?php foreach($selected_students as $sid):
        $st = null;
        foreach($students_list as $s){ if($s['id']==$sid){ $st=$s; break; } }
        if(!$st) continue;

        $full_name = $st['display_name'];
    ?>
    <div>
        <h4><?= htmlspecialchars($full_name) ?></h4>
        <p><?= htmlspecialchars($st['form_level']) ?> · <?= htmlspecialchars($st['stream']) ?> · <?= $st['sex'] ?></p>
        <table border="1">
            <tr>
                <th>#</th>
                <th>Subject</th>
                <?php foreach($selected_exams as $eid): ?>
                <th><?= htmlspecialchars($exam_names[$eid] ?? 'Exam '.$eid) ?></th>
                <?php endforeach; ?>
            </tr>
            <?php
            $subj_q = mysqli_query($conn,"
                SELECT DISTINCT sub.id, sub.subject_name, sub.subject_code
                FROM marks m
                JOIN subjects sub ON sub.id = m.subject_id
                WHERE m.student_id = '$sid' AND m.exam_id IN (".implode(',',$selected_exams).")
                ORDER BY sub.subject_code
            ");
            $subj_list = [];
            while($sb = mysqli_fetch_assoc($subj_q)) $subj_list[] = $sb;

            $i = 1;
            foreach($subj_list as $sb):
                $subj_name = $sb['subject_name'];
            ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($subj_name) ?></td>
                <?php foreach($selected_exams as $eid):
                    $mq = mysqli_query($conn,"SELECT marks FROM marks WHERE student_id='$sid' AND subject_id='{$sb['id']}' AND exam_id='$eid' LIMIT 1");
                    $mr = mysqli_fetch_assoc($mq);
                    $mark = $mr ? $mr['marks'] : null;
                    if($mark === null || $mark === ''):
                ?>
                <td>—</td>
                <?php elseif($mark == 'A' || $mark == 'ABSENT'): ?>
                <td>ABS</td>
                <?php else:
                    $val = $mode == 'marks' ? $mark : grade($mark);
                ?>
                <td><?= $val ?></td>
                <?php endif; endforeach; ?>
            </tr>
            <?php endforeach; ?>

            <?php
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
            <tr>
                <td></td>
                <td>Average</td>
                <?php foreach($selected_exams as $eid): ?>
                <td><?= $summary_row[$eid] ? $summary_row[$eid]['avg'] : '—' ?></td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td></td>
                <td>Grade</td>
                <?php foreach($selected_exams as $eid): ?>
                <td><?= $summary_row[$eid] ? $summary_row[$eid]['grd'] : '—' ?></td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td></td>
                <td>Points</td>
                <?php foreach($selected_exams as $eid): ?>
                <td><?= $summary_row[$eid] ? $summary_row[$eid]['pts'] : '—' ?></td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td></td>
                <td>Division</td>
                <?php foreach($selected_exams as $eid): ?>
                <td><?= $summary_row[$eid] ? $summary_row[$eid]['div'] : '—' ?></td>
                <?php endforeach; ?>
            </tr>
        </table>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
