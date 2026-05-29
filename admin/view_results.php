<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

$exam_id = intval($_GET['exam_id'] ?? 0);
$mode = $_GET['mode'] ?? 'grade';
$form_level_filter = isset($_GET['form_level']) ? mysqli_real_escape_string($conn, $_GET['form_level']) : '';
$pdf_download = isset($_GET['pdf']);

if(!$exam_id){ die("No exam selected"); }

$school_name = "KITUKUTU TECHNICAL SCHOOL";

if ($pdf_download) {
    require_once "../vendor/autoload.php";
    ob_start();
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_parent_message'])){
    $msg = mysqli_real_escape_string($conn, $_POST['parent_message']);
    mysqli_query($conn, "UPDATE exams SET parent_message='$msg' WHERE id='$exam_id'");
    $_SESSION['success'] = "Ujumbe kwa wazazi umehifadhiwa.";
    header("Location: view_results.php?exam_id=$exam_id&mode=$mode");
    exit();
}

$ex = mysqli_query($conn,"SELECT exam_name,summary_json,parent_message FROM exams WHERE id='$exam_id'");
$summary_json = null;
if(!$ex){
    $ex = mysqli_query($conn,"SELECT exam_name FROM exams WHERE id='$exam_id'");
    $exam_data = $ex ? mysqli_fetch_assoc($ex) : null;
} else {
    $exam_data = mysqli_fetch_assoc($ex);
    $summary_json = $exam_data['summary_json'] ? json_decode($exam_data['summary_json'],true) : null;
}
$exam_name = $exam_data['exam_name'] ?? '';
$exam_parent_msg = $exam_data['parent_message'] ?? '';

$flFilterSql = $form_level_filter ? " AND COALESCE(ers.form_level, s.form_level)='$form_level_filter'" : '';

$chkCountSql = "SELECT COUNT(*) total FROM exam_results_summary ers JOIN students s ON s.id=ers.student_id WHERE ers.exam_id='$exam_id' AND s.is_active=1 $flFilterSql";
$chk = mysqli_query($conn, $chkCountSql);
$processed = $chk ? (mysqli_fetch_assoc($chk)['total'] > 0) : false;

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
<title>Exam Results - <?= htmlspecialchars($exam_name) ?></title>
<style>
@media print{
  .no-print{display:none!important;}
  .print-header{display:block!important;text-align:center;margin-bottom:16px;}
  .print-header .line1{font-size:11pt;font-weight:400;}
  .print-header .line2{font-size:16pt;font-weight:700;}
  .print-header .line3{font-size:11pt;font-weight:400;}
  .print-header .line4{font-size:13pt;font-weight:700;margin-top:8px;}
  .subject-summary-page{page-break-before:always;}
}
@media screen and (max-width:768px){table{display:block;overflow-x:auto;white-space:nowrap;}}
.print-header{display:none;}
</style>
</head>
<body>

<div class="print-header">
  <div class="line1">IRAMBA DISTRICT COUNCIL</div>
  <div class="line2">AMALI KITUKUTU SECONDARY SCHOOL</div>
  <div class="line3">P.O BOX 155, IRAMBA</div>
  <div class="line4"><?= strtoupper(htmlspecialchars($exam_name)) ?></div>
</div>

<?php if(isset($_SESSION['success'])): ?>
<div style="background:#e8f8e8;color:#27ae60;border:1px solid #c3e6cb;padding:8px 12px;font-size:12px;margin-bottom:10px;">
    ✔ <?= $_SESSION['success'] ?>
    <button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;margin-left:10px;">&times;</button>
</div>
<?php unset($_SESSION['success']); endif; ?>

<h3 class="no-print"><?= htmlspecialchars($school_name) ?></h3>
<h4 class="no-print"><?= htmlspecialchars($exam_name) ?></h4>

<p class="no-print">
    <button onclick="history.back()">← Back</button>
    <button onclick="window.print()">Print</button>
    <a href="?exam_id=<?= $exam_id ?>&mode=<?= $mode ?><?= $form_level_filter ? '&form_level='.urlencode($form_level_filter) : '' ?>&pdf=1" class="btn-pdf">Download PDF</a>
    <a href="?exam_id=<?= $exam_id ?>&mode=grade">Grades</a>
    <a href="?exam_id=<?= $exam_id ?>&mode=marks">Marks</a>
    <?php
    $fl_q = mysqli_query($conn,"SELECT form_level FROM exam_form_levels WHERE exam_id='$exam_id'");
    if ($fl_q && mysqli_num_rows($fl_q)>0):
    ?>
        <a href="?exam_id=<?= $exam_id ?>&mode=<?= $mode ?>&form_level=">All</a>
        <?php while($fl_r=mysqli_fetch_assoc($fl_q)):
            $fl_short = str_replace('Form ','F.',$fl_r['form_level']);
        ?>
        <a href="?exam_id=<?= $exam_id ?>&mode=<?= $mode ?>&form_level=<?= urlencode($fl_r['form_level']) ?>"><?= $fl_short ?></a>
        <?php endwhile; ?>
    <?php endif; ?>
    <?php
    $streams = [];
    $st_q = mysqli_query($conn,"SELECT DISTINCT s.stream FROM exam_results_summary ers JOIN students s ON s.id=ers.student_id WHERE ers.exam_id='$exam_id' AND s.is_active=1");
    if ($st_q) while($st_r=mysqli_fetch_assoc($st_q)) $streams[] = $st_r['stream'];
    if (!empty($streams)):
        foreach($streams as $st): ?>
        <a href="stream_results.php?exam_id=<?= $exam_id ?>&stream=<?= urlencode($st) ?>"><?= htmlspecialchars($st) ?></a>
        <?php endforeach;
    endif; ?>
</p>

<?php if(!$processed): ?>
<p>Results Not Processed. Please process results first before viewing.</p>
<?php else:

$divOrder = ['I','II','III','IV','0','N/A'];
if($summary_json && isset($summary_json['divisions'])){
    $divisions = $summary_json['divisions'];
} else {
    $divQ = mysqli_query($conn,"SELECT ers.division,s.sex,COUNT(*) as c FROM exam_results_summary ers JOIN students s ON s.id=ers.student_id WHERE ers.exam_id='$exam_id' AND s.is_active=1 $flFilterSql GROUP BY ers.division,s.sex");
    $divisions = [];
    while($d=mysqli_fetch_assoc($divQ)){
        $dn = $d['division'] ?: '0';
        if(!isset($divisions[$dn])) $divisions[$dn]=['boys'=>0,'girls'=>0,'total'=>0];
        $g = strtolower($d['sex'])=='male'?'boys':'girls';
        $divisions[$dn][$g]=(int)$d['c'];
        $divisions[$dn]['total']+=(int)$d['c'];
    }
}
$schoolInfo = $summary_json['school'] ?? null;
?>

<div class="no-print">
    <form method="post">
        <p><strong>Ujumbe kwa Wazazi / Walezi</strong></p>
        <textarea name="parent_message" rows="3" placeholder="Andika ujumbe utakaokwenda kwa wazazi wa wanafunzi wote kwenye ripoti za matokeo..." style="width:100%;padding:8px;border:1px solid #ccc;"><?= htmlspecialchars($exam_parent_msg) ?></textarea>
        <button type="submit" name="save_parent_message">Hifadhi Ujumbe</button>
    </form>
</div>

<div style="display:flex;justify-content:center;margin:10px 0;">
  <table border="1" style="border-collapse:collapse;width:60%;min-width:280px;text-align:center;font-size:13px;">
    <tbody>
    <tr style="background:#0f2744;color:#fff;"><th style="padding:8px;">Div</th><th style="padding:8px;">Boys</th><th style="padding:8px;">Girls</th><th style="padding:8px;">Total</th></tr>
    <?php foreach($divOrder as $d):
        if(!isset($divisions[$d])) continue;
        $dd = $divisions[$d];
    ?>
    <tr>
        <td style="padding:6px;"><strong><?= $d ?></strong></td>
        <td style="padding:6px;"><?= (int)($dd['boys']??0) ?></td>
        <td style="padding:6px;"><?= (int)($dd['girls']??0) ?></td>
        <td style="padding:6px;"><strong><?= (int)($dd['total']??0) ?></strong></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div style="display:flex;justify-content:center;margin:14px 0;">
  <table border="1" style="border-collapse:collapse;width:60%;min-width:320px;text-align:center;font-size:13px;">
    <tbody>
    <tr style="background:#0f2744;color:#fff;"><th colspan="2" style="padding:8px;">School Summary</th></tr>
    <?php if($schoolInfo): $sc=$schoolInfo;
    $schoolGpaLive = 0;
    $gpaQL = mysqli_query($conn,"SELECT ROUND(AVG(gpa), 2) as school_gpa FROM (
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
        WHERE m.exam_id = '$exam_id' AND m.marks != 'A' AND ers.division != '' AND ers.division IS NOT NULL
        GROUP BY m.student_id
    ) t");
    $gpaRL = mysqli_fetch_assoc($gpaQL);
    if($gpaRL && $gpaRL['school_gpa']) $schoolGpaLive = round($gpaRL['school_gpa'], 2);
    ?>
    <tr><td style="padding:6px;font-weight:600;background:#f8f9fa;">Average</td><td style="padding:6px;"><?= number_format((float)$sc['school_avg'],2) ?>%</td></tr>
    <tr><td style="padding:6px;font-weight:600;background:#f8f9fa;">Grade</td><td style="padding:6px;"><?= $sc['school_grade'] ?></td></tr>
    <tr><td style="padding:6px;font-weight:600;background:#f8f9fa;">Students</td><td style="padding:6px;"><?= (int)($sc['total_students']??0) ?></td></tr>
    <tr><td style="padding:6px;font-weight:600;background:#f8f9fa;">School GPA</td><td style="padding:6px;"><?= number_format($schoolGpaLive,2) ?></td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<div style="text-align:center;margin-top:20px;">
  <span style="background:#0f2744;color:#fff;padding:6px 16px;border-radius:6px;font-size:13px;font-weight:700;display:inline-block;">Results</span>
</div>
<div style="display:flex;justify-content:center;margin:10px 0;">
<table border="1" style="border-collapse:collapse;width:100%;text-align:center;font-size:12px;">
    <thead>
        <tr style="background:#0f2744;color:#fff;">
            <th style="padding:7px;">#</th>
            <th style="padding:7px;">Student Name</th>
            <th style="padding:7px;">Sex</th>
            <th style="padding:7px;">Avg</th>
            <th style="padding:7px;">Grade</th>
            <th style="padding:7px;">Pts</th>
            <th style="padding:7px;">Div</th>
            <th style="padding:7px;">Subjects (<?= $mode=='marks'?'Marks':'Grades' ?>)</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $students = mysqli_query($conn,"
            SELECT s.id, s.first_name, s.second_name, s.last_name, s.sex, s.form_level,
                   ers.total_points, ers.division, ers.position, ers.average_marks
            FROM exam_results_summary ers
            JOIN students s ON s.id = ers.student_id
            WHERE ers.exam_id = '$exam_id' AND s.is_active=1 $flFilterSql
            ORDER BY ers.position ASC
        ");

        $row_i = 0;
        while($st = mysqli_fetch_assoc($students)):
            $row_i++;
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
        <tr style="background:<?= $row_i % 2 == 0 ? '#f8f9fa' : '#fff' ?>;">
            <td style="padding:6px;"><?= (int)$st['position'] ?></td>
            <td style="padding:6px;text-align:left;"><?= htmlspecialchars($full_name) ?></td>
            <td style="padding:6px;"><?= $st['sex']=='Male'?'M':'F' ?></td>
            <td style="padding:6px;"><?= number_format((float)$st['average_marks'],2) ?></td>
            <td style="padding:6px;"><?= $avg_grade ?></td>
            <td style="padding:6px;"><?= (int)$st['total_points'] ?></td>
            <td style="padding:6px;"><?= $div_display ?></td>
            <td style="padding:6px;text-align:left;"><?= htmlspecialchars($subject_string) ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
</div>

<div class="subject-summary-page">
<div style="text-align:center;margin-top:20px;">
  <span style="background:#0f2744;color:#fff;padding:6px 16px;border-radius:6px;font-size:13px;font-weight:700;display:inline-block;">Subject Performance Summary</span>
</div>
<div style="display:flex;justify-content:center;margin:10px 0;">
<table border="1" style="border-collapse:collapse;width:95%;min-width:500px;text-align:center;font-size:12px;">
<?php
function competencyLabel($avg){
    if($avg >= 75) return 'Excellent';
    if($avg >= 65) return 'Very Good';
    if($avg >= 45) return 'Good';
    if($avg >= 30) return 'Fair';
    return 'Fail';
}

$sg_q_a = mysqli_query($conn, "
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
    WHERE m.exam_id = '$exam_id'
    GROUP BY m.subject_id, sbj.subject_name
    ORDER BY avg_mark DESC
");
$raw_subjects = [];
if ($sg_q_a) {
    while ($sg_r = mysqli_fetch_assoc($sg_q_a)) {
        $sg_r['gpa'] = $sg_r['sat'] > 0 ? round($sg_r['points_sum'] / $sg_r['sat'], 2) : 0;
        $raw_subjects[] = $sg_r;
    }
}

$merged = [];
foreach($raw_subjects as $sg){
    $name = strtoupper(trim($sg['subject_name']));
    if(!isset($merged[$name])){
        $merged[$name] = $sg;
    } else {
        $m = &$merged[$name];
        $m['grade_a'] += $sg['grade_a'];
        $m['grade_b'] += $sg['grade_b'];
        $m['grade_c'] += $sg['grade_c'];
        $m['grade_d'] += $sg['grade_d'];
        $m['grade_f'] += $sg['grade_f'];
        $m['reg'] += $sg['reg'];
        $m['pass_count'] += $sg['pass_count'];
        $c1 = $m['sat'];
        $c2 = $sg['sat'];
        $totalSat = $c1 + $c2;
        $m['avg_mark'] = $totalSat > 0 ? ($m['avg_mark'] * $c1 + $sg['avg_mark'] * $c2) / $totalSat : 0;
        $m['points_sum'] += $sg['points_sum'];
        $m['sat'] = $totalSat;
        $m['gpa'] = $totalSat > 0 ? round($m['points_sum'] / $totalSat, 2) : 0;
    }
}
$sg_data_a = array_values($merged);
usort($sg_data_a, function($a, $b){
    return ($b['avg_mark']??0) <=> ($a['avg_mark']??0);
});
$pos = 1;
foreach($sg_data_a as &$sg){
    $sg['pos'] = $pos++;
}
unset($sg);
?>
<?php if (!empty($sg_data_a)): ?>
  <tbody>
  <tr style="background:#0f2744;color:#fff;">
    <th style="padding:7px;">#</th>
    <th style="padding:7px;">Subject</th>
    <th style="padding:7px;">A</th>
    <th style="padding:7px;">B</th>
    <th style="padding:7px;">C</th>
    <th style="padding:7px;">D</th>
    <th style="padding:7px;">F</th>
    <th style="padding:7px;">Avg</th>
    <th style="padding:7px;">Grade</th>
    <th style="padding:7px;">REG</th>
    <th style="padding:7px;">SAT</th>
    <th style="padding:7px;">PASS</th>
    <th style="padding:7px;">GPA</th>
    <th style="padding:7px;">Competency</th>
  </tr>
  <?php foreach($sg_data_a as $sg): ?>
  <tr>
    <td style="padding:5px;"><?= $sg['pos'] ?></td>
    <td style="padding:5px;"><?= htmlspecialchars($sg['subject_name']) ?></td>
    <td style="padding:5px;"><?= (int)$sg['grade_a'] ?></td>
    <td style="padding:5px;"><?= (int)$sg['grade_b'] ?></td>
    <td style="padding:5px;"><?= (int)$sg['grade_c'] ?></td>
    <td style="padding:5px;"><?= (int)$sg['grade_d'] ?></td>
    <td style="padding:5px;"><?= (int)$sg['grade_f'] ?></td>
    <td style="padding:5px;"><?= number_format((float)$sg['avg_mark'],2) ?></td>
    <td style="padding:5px;"><?= grade($sg['avg_mark']) ?></td>
    <td style="padding:5px;"><?= (int)$sg['reg'] ?></td>
    <td style="padding:5px;"><?= (int)$sg['sat'] ?></td>
    <td style="padding:5px;"><?= (int)$sg['pass_count'] ?></td>
    <td style="padding:5px;"><?= number_format((float)$sg['gpa'],2) ?></td>
    <td style="padding:5px;"><?= competencyLabel($sg['avg_mark']) ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</div>
<?php endif; ?>

<p>Generated: <?= date('d-m-Y H:i') ?></p>

<?php endif; ?>

<?php if ($pdf_download):
$html = ob_get_clean();
$options = new Dompdf\Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf\Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$dompdf->stream("Results_" . preg_replace('/[^a-zA-Z0-9_-]/', '_', $exam_name) . ".pdf", ["Attachment" => true]);
exit;
endif; ?>

</body>
</html>
