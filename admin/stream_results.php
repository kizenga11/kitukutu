<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

$exam_id = intval($_GET['exam_id'] ?? 0);
$stream_filter = isset($_GET['stream']) ? mysqli_real_escape_string($conn, $_GET['stream']) : '';
$mode = $_GET['mode'] ?? 'grade';

if(!$exam_id) die("No exam selected");
if(!$stream_filter) die("No stream selected");

$school_name = "Kitukutu Technical Secondary School";

$ex = mysqli_query($conn,"SELECT exam_name FROM exams WHERE id='$exam_id'");
$exam_data = $ex ? mysqli_fetch_assoc($ex) : null;
$exam_name = $exam_data['exam_name'] ?? '';

function grade($m){
    if($m >= 75) return 'A';
    if($m >= 65) return 'B';
    if($m >= 45) return 'C';
    if($m >= 30) return 'D';
    return 'F';
}
function competencyLabel($avg){
    if($avg >= 75) return 'Excellent';
    if($avg >= 65) return 'Very Good';
    if($avg >= 45) return 'Good';
    if($avg >= 30) return 'Fair';
    return 'Fail';
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stream Results - <?= htmlspecialchars($stream_filter) ?> - <?= htmlspecialchars($exam_name) ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{
    font-family:system-ui,-apple-system,'Segoe UI',sans-serif;
    background:#f0f2f5;padding:16px;font-size:13px;color:#1a1a2e;
}
.container{max-width:1400px;margin:0 auto;}
.header-card{
    background:linear-gradient(135deg,#1a2b4c,#2c3e6b);
    color:#fff;border-radius:14px;padding:16px 20px;
    display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;
    margin-bottom:14px;box-shadow:0 2px 12px rgba(26,43,76,0.2);
}
.header-left h2{font-size:16px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;}
.header-left p{font-size:12px;opacity:.8;margin-top:2px;}
.header-right{display:flex;flex-wrap:wrap;gap:5px;align-items:center;}
.btn{
    display:inline-flex;align-items:center;gap:4px;padding:6px 12px;
    border-radius:8px;font-size:11px;font-weight:600;text-decoration:none;
    transition:all .15s;border:none;cursor:pointer;
}
.btn-back{background:rgba(255,255,255,0.15);color:#fff;}
.btn-back:hover{background:rgba(255,255,255,0.25);}
.btn-light{background:rgba(255,255,255,0.12);color:#fff;}
.btn-light.active{background:#f4b400;color:#1a2b4c;}
.btn-light:hover{background:rgba(255,255,255,0.2);}
.btn-print{background:rgba(255,255,255,0.15);color:#fff;}
.btn-print:hover{background:rgba(255,255,255,0.25);}
.summary-row{display:grid;grid-template-columns:1fr 2fr;gap:12px;margin-bottom:14px;}
.summary-card{
    background:#fff;border-radius:12px;padding:14px 16px;
    box-shadow:0 1px 4px rgba(0,0,0,0.06);border:1px solid #eef0f4;
}
.summary-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;margin-bottom:8px;}
.summary-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;}
.stat-item{text-align:center;padding:8px 4px;background:#f8f9fc;border-radius:8px;}
.stat-value{font-size:20px;font-weight:800;color:#1a2b4c;}
.stat-label{font-size:10px;color:#6b7280;margin-top:2px;text-transform:uppercase;letter-spacing:.3px;}
.div-table{width:100%;border-collapse:collapse;font-size:12px;}
.div-table th{padding:5px 8px;border:1px solid #e5e7eb;font-size:10px;text-transform:uppercase;color:#6b7280;background:#f9fafb;}
.div-table td{padding:5px 8px;border:1px solid #e5e7eb;text-align:center;}
.table-card{background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.06);border:1px solid #eef0f4;margin-bottom:14px;overflow:hidden;}
.table-wrap{overflow-x:auto;}
.results-table{width:100%;border-collapse:collapse;font-size:12px;}
.results-table th{
    background:#1a2b4c;color:#fff;padding:8px 6px;font-size:10px;
    font-weight:700;text-transform:uppercase;letter-spacing:.4px;
    white-space:nowrap;text-align:center;border:1px solid #253b5e;
}
.results-table td{padding:6px;text-align:center;border:1px solid #eef0f4;}
.results-table tr:nth-child(even){background:#f8f9fc;}
.name-col{text-align:left;font-weight:600;white-space:nowrap;}
.subj-col{font-size:10px;text-align:left;max-width:300px;white-space:normal;word-break:break-word;}
.avg-val{font-weight:700;color:#1a1a2e;}
.grade-val{font-weight:800;font-size:13px;}
.pts-val{font-weight:600;color:#2c3e6b;}
.div-val{font-weight:700;font-size:13px;}
.pos-val{font-weight:700;color:#6b7280;}
.subj-section{background:#fff;border-radius:12px;padding:14px 16px;box-shadow:0 1px 4px rgba(0,0,0,0.06);border:1px solid #eef0f4;margin-bottom:14px;}
.subj-table{width:100%;border-collapse:collapse;font-size:11px;}
.subj-table th{background:#1a2b4c;color:#fff;padding:6px 5px;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;border:1px solid #253b5e;text-align:center;}
.subj-table td{padding:5px;text-align:center;border:1px solid #eef0f4;}
.subj-table tr:nth-child(even){background:#f8f9fc;}
.footer{text-align:center;font-size:10px;color:#95a5a6;padding:10px 0 4px;}
@media screen and (max-width:768px){
    body{padding:8px;font-size:12px;}
    .header-card{flex-direction:column;gap:10px;align-items:stretch;padding:12px;}
    .header-left h2{font-size:14px;}
    .summary-row{grid-template-columns:1fr;}
    .summary-grid{gap:10px;}
    .stat-value{font-size:16px;}
    .results-table,.results-table thead,.results-table tbody,
    .results-table th,.results-table td,.results-table tr{display:block;}
    .results-table thead{display:none;}
    .results-table tr{margin-bottom:8px;border:1px solid #e0e0e0;border-radius:8px;background:#fff;padding:6px 0;}
    .results-table td{display:flex;justify-content:space-between;align-items:center;padding:6px 10px;border:none;border-bottom:1px solid #f0f0f0;text-align:right;}
    .results-table td:last-child{border-bottom:none;}
    .results-table td::before{content:attr(data-label);font-weight:600;font-size:10px;color:#7f8c8d;text-align:left;min-width:70px;text-transform:uppercase;}
    .name-col{white-space:normal;}
    .subj-col{font-size:10px;}
}
@media print{
    @page{size:landscape;margin:10mm 15mm;}
    *{color:#000!important;}
    body{background:#fff;padding:0;margin:0;font-size:10px;font-family:'Times New Roman',Times,serif;}
    .container{max-width:100%;padding:0;}
    .btn,.btn-group,.header-right,.footer{display:none!important;}
    .header-card{background:transparent!important;color:#000!important;padding:0 0 8px 0!important;margin-bottom:4px!important;border-bottom:3px double #000;text-align:center;display:block;}
    .header-left{text-align:center;}
    .header-left h2{font-size:16pt;font-weight:700;text-transform:uppercase;margin:0 0 2px;}
    .header-left p{font-size:12pt;margin:0;opacity:1;}
    .summary-row{display:flex;gap:0;margin-bottom:6px;}
    .summary-card{box-shadow:none;border-radius:0;padding:6px 10px;border:1px solid #000;flex:1;}
    .summary-title{font-size:9pt;font-weight:700;text-transform:uppercase;margin-bottom:4px;}
    .stat-value{font-size:14pt;font-weight:700;}
    .stat-label{font-size:8pt;}
    .div-table td,.div-table th{padding:3px 6px;border:1px solid #000;font-size:9pt;}
    .div-table th{background:#ccc!important;color:#000!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
    .subj-section{box-shadow:none;border-radius:0;padding:6px 10px;border:1px solid #000;margin-bottom:6px;page-break-inside:avoid;}
    .subj-table{width:100%;border-collapse:collapse;font-size:7pt;}
    .subj-table th{background:#ccc!important;color:#000!important;font-size:7pt;border:1px solid #000;padding:3px 4px;}
    .subj-table td{border:1px solid #000;padding:2px 4px;font-size:7pt;}
    .subj-table tr:nth-child(even){background:#f5f5f5!important;}
    .table-card{box-shadow:none;border-radius:0;border:1px solid #000;}
    .results-table{font-size:9pt;width:100%;border-collapse:collapse;}
    .results-table thead{display:table-header-group;}
    .results-table tbody{display:table-row-group;}
    .results-table tr{display:table-row;border:none;margin:0;page-break-inside:avoid;}
    .results-table th{display:table-cell;background:#ccc!important;color:#000!important;padding:5px 4px;font-size:8pt;font-weight:700;-webkit-print-color-adjust:exact;print-color-adjust:exact;border:1px solid #000;text-align:center;}
    .results-table td{display:table-cell;text-align:center;border:1px solid #000;padding:4px 3px;}
    .results-table td.name-col,.results-table td.subj-col{text-align:left;}
    .results-table td::before{display:none;}
    .name-col{white-space:nowrap;}
    .subj-col{font-size:8pt;text-align:left;}
    .avg-val,.grade-val,.pts-val,.div-val,.pos-val{color:#000!important;}
}
</style>
</head>
<body>
<div class="container">

<?php if(isset($_SESSION['success'])): ?>
<div style="background:#e8f8e8;color:#27ae60;border:1px solid #c3e6cb;border-radius:6px;padding:8px 12px;font-size:12px;margin-bottom:10px;display:flex;align-items:center;gap:8px;">
    ✔ <?= $_SESSION['success'] ?>
    <button onclick="this.parentElement.remove()" style="background:none;border:none;color:inherit;cursor:pointer;font-size:14px;margin-left:auto;">&times;</button>
</div>
<?php unset($_SESSION['success']); endif; ?>

<!-- HEADER -->
<div class="header-card">
    <div class="header-left">
        <h2><?= htmlspecialchars($school_name) ?></h2>
        <p><?= htmlspecialchars($exam_name) ?> — Stream: <strong><?= htmlspecialchars($stream_filter) ?></strong></p>
    </div>
    <div class="header-right">
        <button onclick="history.back()" class="btn btn-back">← Back</button>
        <button onclick="window.print()" class="btn btn-print">🖨 Print</button>
        <a href="?exam_id=<?= $exam_id ?>&stream=<?= urlencode($stream_filter) ?>&mode=grade" class="btn <?= $mode=='grade'?'btn-light active':'btn-light' ?>">Grades</a>
        <a href="?exam_id=<?= $exam_id ?>&stream=<?= urlencode($stream_filter) ?>&mode=marks" class="btn <?= $mode=='marks'?'btn-light active':'btn-light' ?>">Marks</a>
    </div>
</div>

<!-- CHECK IF PROCESSED -->
<?php
$chk = mysqli_query($conn,"SELECT COUNT(*) as total FROM exam_results_summary ers JOIN students s ON s.id=ers.student_id WHERE ers.exam_id='$exam_id' AND s.is_active=1 AND s.stream='$stream_filter'");
$chkR = $chk ? mysqli_fetch_assoc($chk) : ['total'=>0];
$processed = $chkR['total'] > 0;

if(!$processed): ?>
<div class="summary-card" style="text-align:center;padding:30px;">
    <div style="font-size:32px;margin-bottom:8px;">📋</div>
    <div style="color:#999;"><strong>Results Not Processed</strong><p>Please process results first before viewing.</p></div>
</div>
<?php else:

// ── DIVISION & SCHOOL SUMMARY (stream-specific) ──
$divOrder = ['I','II','III','IV','0','N/A'];

$divQ = mysqli_query($conn,"SELECT ers.division,s.sex,COUNT(*) as c
FROM exam_results_summary ers JOIN students s ON s.id=ers.student_id
WHERE ers.exam_id='$exam_id' AND s.is_active=1 AND s.stream='$stream_filter' AND ers.division!='' AND ers.division IS NOT NULL
GROUP BY ers.division,s.sex");
$divisions = [];
while($d=mysqli_fetch_assoc($divQ)){
    $dn = $d['division'] ?: '0';
    if(!isset($divisions[$dn])) $divisions[$dn]=['boys'=>0,'girls'=>0,'total'=>0];
    $g = strtolower($d['sex'])=='male'?'boys':'girls';
    $divisions[$dn][$g]=(int)$d['c'];
    $divisions[$dn]['total']+=(int)$d['c'];
}

$schoolQ = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total, AVG(average_marks) as avg FROM exam_results_summary ers JOIN students s ON s.id=ers.student_id WHERE ers.exam_id='$exam_id' AND s.is_active=1 AND s.stream='$stream_filter'"));
$streamTotal = (int)($schoolQ['total']??0);
$streamAvg = $streamTotal > 0 ? round($schoolQ['avg'],2) : 0;
$streamGrade = grade($streamAvg);

$gpaQ = mysqli_query($conn,"SELECT ROUND(AVG(gpa), 2) as stream_gpa FROM (
    SELECT AVG(CASE 
        WHEN CAST(m.marks AS DECIMAL(5,1)) >= 75 THEN 1
        WHEN CAST(m.marks AS DECIMAL(5,1)) >= 65 THEN 2
        WHEN CAST(m.marks AS DECIMAL(5,1)) >= 45 THEN 3
        WHEN CAST(m.marks AS DECIMAL(5,1)) >= 30 THEN 4
        ELSE 5
    END) as gpa
    FROM marks m
    JOIN exam_results_summary ers ON ers.student_id = m.student_id AND ers.exam_id = m.exam_id
    JOIN student_subjects ss ON ss.student_id = m.student_id AND ss.subject_id = m.subject_id
    JOIN students s ON s.id = m.student_id
    WHERE m.exam_id = '$exam_id' AND m.marks != 'A' 
        AND ers.division != '' AND ers.division IS NOT NULL
        AND s.is_active=1 AND s.stream = '$stream_filter'
    GROUP BY m.student_id
) t");
$gpaR = mysqli_fetch_assoc($gpaQ);
$streamGpa = $gpaR ? round($gpaR['stream_gpa'], 2) : 0;
?>

<div class="summary-row">
    <div class="summary-card">
        <div class="summary-title">Division Summary</div>
        <table class="div-table">
            <tr><th>Div</th><th>Boys</th><th>Girls</th><th>Total</th></tr>
            <?php foreach($divOrder as $d):
                if(!isset($divisions[$d])) continue;
                $dd = $divisions[$d];
            ?>
            <tr>
                <td><strong><?= $d ?></strong></td>
                <td><?= (int)($dd['boys']??0) ?></td>
                <td><?= (int)($dd['girls']??0) ?></td>
                <td><strong><?= (int)($dd['total']??0) ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <div class="summary-card">
        <div class="summary-title">Stream Summary — <?= htmlspecialchars($stream_filter) ?></div>
        <div class="summary-grid">
            <div class="stat-item">
                <div class="stat-value"><?= number_format($streamAvg,2) ?></div>
                <div class="stat-label">Average</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= $streamGrade ?></div>
                <div class="stat-label">Grade</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= $streamTotal ?></div>
                <div class="stat-label">Students</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= number_format($streamGpa,2) ?></div>
                <div class="stat-label">GPA</div>
            </div>
        </div>
    </div>
</div>

<!-- RESULTS TABLE -->
<div class="table-card">
<div class="table-wrap">
<table class="results-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Student Name</th>
            <th>Sex</th>
            <th>Avg</th>
            <th>Grade</th>
            <th>Pts</th>
            <th>Div</th>
            <th>Subjects (<?= $mode=='marks'?'Marks':'Grades' ?>)</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $students = mysqli_query($conn,"
            SELECT s.id, s.first_name, s.second_name, s.last_name, s.sex,
                   ers.total_points, ers.division, ers.average_marks
            FROM exam_results_summary ers
            JOIN students s ON s.id = ers.student_id
            WHERE ers.exam_id = '$exam_id' AND s.is_active=1 AND s.stream = '$stream_filter'
            ORDER BY ers.average_marks DESC
        ");

        $ranked = [];
        while($st = mysqli_fetch_assoc($students)) $ranked[] = $st;
        $rank=1; $i=1; $prev_avg=null;
        foreach($ranked as &$r){
            if($i>1 && $r['average_marks']!=$prev_avg) $rank=$i;
            $r['stream_position']=$rank;
            $prev_avg=$r['average_marks'];
            $i++;
        }
        unset($r);

        foreach($ranked as $st):
            $id = $st['id'];
            $first = trim($st['first_name'] ?? '');
            $second = trim($st['second_name'] ?? '');
            $last = trim($st['last_name'] ?? '');
            $full_name = $first;
            if($second) $full_name .= ' ' . $second;
            if($last) $full_name .= ' ' . $last;
            $full_name = trim($full_name);

            $subq = mysqli_query($conn,"
                SELECT sub.short_name, sub.subject_code, m.marks
                FROM marks m
                JOIN subjects sub ON sub.id = m.subject_id
                JOIN student_subjects ss ON ss.student_id = m.student_id AND ss.subject_id = m.subject_id
                WHERE m.student_id = '$id' AND m.exam_id = '$exam_id'
                ORDER BY sub.subject_code
            ");

            $subs = [];
            while($sb = mysqli_fetch_assoc($subq)):
                $subject_name = $sb['short_name'] ?: ucwords(strtolower($sb['subject_code']));
                if($mode == 'marks'):
                    $display_value = ($sb['marks'] == 'A' || $sb['marks'] == 'ABSENT') ? 'ABS' : $sb['marks'];
                else:
                    if($sb['marks'] == 'A' || $sb['marks'] == 'ABSENT'):
                        $display_value = 'ABS';
                    else:
                        $display_value = grade($sb['marks']);
                    endif;
                endif;
                $subs[] = $subject_name . "-" . $display_value;
            endwhile;

            $subject_string = implode(" ", $subs);
            $avg_grade = grade($st['average_marks']);
            $div_display = $st['division'] !== '' && $st['division'] !== null ? $st['division'] : '-';
        ?>
        <tr>
            <td data-label="#" class="pos-val"><?= (int)$st['stream_position'] ?></td>
            <td data-label="Name" class="name-col"><?= htmlspecialchars($full_name) ?></td>
            <td data-label="Sex"><?= $st['sex']=='Male'?'M':'F' ?></td>
            <td data-label="Avg" class="avg-val"><?= number_format((float)$st['average_marks'],2) ?></td>
            <td data-label="Grade" class="grade-val"><?= $avg_grade ?></td>
            <td data-label="Pts" class="pts-val"><?= (int)$st['total_points'] ?></td>
            <td data-label="Div" class="div-val"><?= $div_display ?></td>
            <td data-label="Subjects" class="subj-col"><?= htmlspecialchars($subject_string) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<!-- SUBJECT PERFORMANCE SUMMARY (stream-specific) -->
<?php
$sg_q = mysqli_query($conn,"
    SELECT m.subject_id, sbj.subject_name,
           COUNT(*) as reg,
           SUM(CASE WHEN m.marks != 'A' THEN 1 ELSE 0 END) as sat,
           SUM(CASE WHEN m.marks >= 75 AND m.marks != 'A' THEN 1 ELSE 0 END) as grade_a,
           SUM(CASE WHEN m.marks >= 65 AND m.marks < 75 THEN 1 ELSE 0 END) as grade_b,
           SUM(CASE WHEN m.marks >= 45 AND m.marks < 65 THEN 1 ELSE 0 END) as grade_c,
           SUM(CASE WHEN m.marks >= 30 AND m.marks < 45 THEN 1 ELSE 0 END) as grade_d,
           SUM(CASE WHEN m.marks >= 0 AND m.marks < 30 AND m.marks != 'A' THEN 1 ELSE 0 END) as grade_f,
           AVG(CASE WHEN m.marks != 'A' THEN m.marks END) as avg_mark,
           SUM(CASE
               WHEN m.marks != 'A' AND m.marks >= 75 THEN 1
               WHEN m.marks != 'A' AND m.marks >= 65 THEN 2
               WHEN m.marks != 'A' AND m.marks >= 45 THEN 3
               WHEN m.marks != 'A' AND m.marks >= 30 THEN 4
               WHEN m.marks != 'A' THEN 5
               ELSE 0
           END) as points_sum,
           SUM(CASE WHEN m.marks != 'A' AND m.marks >= 45 THEN 1 ELSE 0 END) as pass_count
    FROM marks m
    JOIN subjects sbj ON sbj.id = m.subject_id
    JOIN student_subjects ss ON ss.student_id = m.student_id AND ss.subject_id = m.subject_id
    JOIN students st ON st.id = m.student_id
    WHERE m.exam_id = '$exam_id' AND st.is_active=1 AND st.stream = '$stream_filter'
    GROUP BY m.subject_id, sbj.subject_name
    ORDER BY avg_mark DESC
");
$sg_data = [];
if ($sg_q) {
    while ($sg_r = mysqli_fetch_assoc($sg_q)) {
        $sg_r['gpa'] = $sg_r['sat'] > 0 ? round($sg_r['points_sum'] / $sg_r['sat'], 2) : 0;
        $sg_data[] = $sg_r;
    }
}
?>

<?php if (!empty($sg_data)): ?>
<div class="subj-section">
    <div class="summary-title" style="margin-bottom:8px;">Subject Performance Summary — <?= htmlspecialchars($stream_filter) ?></div>
    <div class="table-wrap">
        <table class="subj-table">
            <tr>
                <th>#</th><th>Subject</th><th>A</th><th>B</th><th>C</th><th>D</th><th>F</th>
                <th>Avg</th><th>Grade</th>
                <th>REG</th><th>SAT</th><th>CLEAN</th><th>PASS</th><th>GPA</th><th>Competency Level</th>
            </tr>
            <?php $pos=1; foreach($sg_data as $sg): ?>
            <tr>
                <td style="font-weight:700;"><?= $pos++ ?></td>
                <td style="text-align:left;font-weight:600;"><?= htmlspecialchars($sg['subject_name']) ?></td>
                <td style="color:#27ae60;"><?= (int)$sg['grade_a'] ?></td>
                <td style="color:#2980b9;"><?= (int)$sg['grade_b'] ?></td>
                <td style="color:#f39c12;"><?= (int)$sg['grade_c'] ?></td>
                <td style="color:#e67e22;"><?= (int)$sg['grade_d'] ?></td>
                <td style="color:#c0392b;"><?= (int)$sg['grade_f'] ?></td>
                <td style="font-weight:700;color:#1a1a2e;"><?= number_format((float)$sg['avg_mark'],2) ?></td>
                <td style="font-weight:800;font-size:13px;"><?= grade($sg['avg_mark']) ?></td>
                <td><?= (int)$sg['reg'] ?></td>
                <td><?= (int)$sg['sat'] ?></td>
                <td><?= (int)$sg['sat'] ?></td>
                <td><?= (int)$sg['pass_count'] ?></td>
                <td style="font-weight:700;"><?= number_format((float)$sg['gpa'],2) ?></td>
                <td style="font-size:11px;"><?= competencyLabel($sg['avg_mark']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<div class="footer">Generated: <?= date('d-m-Y H:i') ?></div>

</div>
</body>
</html>
