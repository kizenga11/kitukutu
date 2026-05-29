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

if(!$exam_id){ die("No exam selected"); }

$flFilterSql = $form_level_filter ? " AND COALESCE(ers.form_level, s.form_level)='$form_level_filter'" : '';

$ex = mysqli_query($conn,"SELECT exam_name,summary_json FROM exams WHERE id='$exam_id'");
$summary_json = null;
if(!$ex){
    $ex = mysqli_query($conn,"SELECT exam_name FROM exams WHERE id='$exam_id'");
    $exam_data = $ex ? mysqli_fetch_assoc($ex) : null;
} else {
    $exam_data = mysqli_fetch_assoc($ex);
    $summary_json = $exam_data['summary_json'] ? json_decode($exam_data['summary_json'],true) : null;
}
$exam_name = $exam_data['exam_name'] ?? '';

function grade($m){
    if($m >= 75) return 'A';
    if($m >= 65) return 'B';
    if($m >= 45) return 'C';
    if($m >= 30) return 'D';
    return 'F';
}

$html = '<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
body{font-family:DejaVu Sans,sans-serif;margin:10px;font-size:10pt;}
.header{text-align:center;margin-bottom:10px;}
.header .l1{font-size:9pt;}
.header .l2{font-size:14pt;font-weight:700;}
.header .l3{font-size:9pt;}
.header .l4{font-size:11pt;font-weight:700;margin-top:6px;}
table{border-collapse:collapse;margin:0 auto;}
.results-table{width:100%;font-size:8pt;}
.results-table th,.results-table td{border:1px solid #000;padding:3px 4px;text-align:center;}
.results-table th{background:#0f2744;color:#fff;}
.results-table tr:nth-child(even){background:#f0f0f0;}
.summary-table{width:auto;min-width:250px;font-size:9pt;}
.summary-table th,.summary-table td{border:1px solid #000;padding:4px 8px;}
.section-title{text-align:center;font-weight:700;font-size:11pt;margin:12px 0 6px;}
.subject-page{page-break-before:always;}
.gen{text-align:center;font-size:8pt;margin-top:8px;color:#666;}
</style></head><body>';

$html .= '<div class="header">
  <div class="l1">IRAMBA DISTRICT COUNCIL</div>
  <div class="l2">AMALI KITUKUTU SECONDARY SCHOOL</div>
  <div class="l3">P.O BOX 155, IRAMBA</div>
  <div class="l4">'.strtoupper(htmlspecialchars($exam_name)).'</div>
</div>';

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

$html .= '<table class="summary-table"><tbody>
<tr style="background:#0f2744;color:#fff;"><th>Div</th><th>Boys</th><th>Girls</th><th>Total</th></tr>';
foreach($divOrder as $d){
    if(!isset($divisions[$d])) continue;
    $dd = $divisions[$d];
    $html .= '<tr><td><strong>'.htmlspecialchars($d).'</strong></td><td>'.(int)($dd['boys']??0).'</td><td>'.(int)($dd['girls']??0).'</td><td><strong>'.(int)($dd['total']??0).'</strong></td></tr>';
}
$html .= '</tbody></table>';

if($schoolInfo){
    $sc = $schoolInfo;
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
        WHERE m.exam_id = \'$exam_id\' AND m.marks != \'A\' AND ers.division != \'\' AND ers.division IS NOT NULL
        GROUP BY m.student_id
    ) t");
    $gpaRL = mysqli_fetch_assoc($gpaQL);
    if($gpaRL && $gpaRL['school_gpa']) $schoolGpaLive = round($gpaRL['school_gpa'], 2);
    $html .= '<div style="text-align:center;margin:8px 0;">
    <table class="summary-table"><tbody>
    <tr style="background:#0f2744;color:#fff;"><th colspan="2">School Summary</th></tr>
    <tr><td style="font-weight:600;">Average</td><td>'.number_format((float)$sc['school_avg'],2).'%</td></tr>
    <tr><td style="font-weight:600;">Grade</td><td>'.htmlspecialchars($sc['school_grade']).'</td></tr>
    <tr><td style="font-weight:600;">Students</td><td>'.(int)($sc['total_students']??0).'</td></tr>
    <tr><td style="font-weight:600;">School GPA</td><td>'.number_format($schoolGpaLive,2).'</td></tr>
    </tbody></table></div>';
}

$html .= '<div class="section-title">RESULTS</div>';

$students = mysqli_query($conn,"
    SELECT s.id, s.first_name, s.second_name, s.last_name, s.sex, s.form_level,
           ers.total_points, ers.division, ers.position, ers.average_marks
    FROM exam_results_summary ers
    JOIN students s ON s.id = ers.student_id
    WHERE ers.exam_id = \'$exam_id\' AND s.is_active=1 $flFilterSql
    ORDER BY ers.position ASC
");

$html .= '<table class="results-table">
<thead><tr>
    <th>#</th><th>Student Name</th><th>Sex</th><th>Avg</th><th>Grade</th><th>Pts</th><th>Div</th>
    <th>Subjects ('.($mode=='marks'?'Marks':'Grades').')</th>
</tr></thead><tbody>';

$row_i = 0;
if($students){
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
            WHERE m.student_id = \'$id\' AND m.exam_id = \'$exam_id\'
            ORDER BY sub.subject_code
        ");

        $subs = [];
        if($subq){
            while($sb = mysqli_fetch_assoc($subq)):
                $subject_name = $sb['short_name'] ?: ucwords(strtolower($sb['subject_code']));
                if($mode == 'marks'):
                    $display_value = ($sb['marks'] == 'A' || $sb['marks'] == 'ABSENT') ? 'ABS' : $sb['marks'];
                else:
                    $display_value = ($sb['marks'] == 'A' || $sb['marks'] == 'ABSENT') ? 'ABS' : grade($sb['marks']);
                endif;
                $subs[] = $subject_name . "-" . $display_value;
            endwhile;
        }

        $subject_string = implode(" ", $subs);
        $avg_grade = grade($st['average_marks']);
        $div_display = $st['division'] !== '' && $st['division'] !== null ? $st['division'] : '-';

        $bg = $row_i % 2 == 0 ? ' style="background:#f0f0f0;"' : '';
        $html .= '<tr'.$bg.'>
            <td>'.(int)$st['position'].'</td>
            <td style="text-align:left;">'.htmlspecialchars($full_name).'</td>
            <td>'.($st['sex']=='Male'?'M':'F').'</td>
            <td>'.number_format((float)$st['average_marks'],2).'</td>
            <td>'.$avg_grade.'</td>
            <td>'.(int)$st['total_points'].'</td>
            <td>'.$div_display.'</td>
            <td style="text-align:left;">'.htmlspecialchars($subject_string).'</td>
        </tr>';
    endwhile;
}
$html .= '</tbody></table>';

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

if (!empty($sg_data_a)):
$html .= '<div class="subject-page"><div class="section-title">SUBJECT PERFORMANCE SUMMARY</div>';
$html .= '<table class="results-table">
<thead><tr>
    <th>#</th><th>Subject</th><th>A</th><th>B</th><th>C</th><th>D</th><th>F</th>
    <th>Avg</th><th>Grade</th><th>REG</th><th>SAT</th><th>PASS</th><th>GPA</th><th>Competency</th>
</tr></thead><tbody>';
foreach($sg_data_a as $sg):
    $html .= '<tr>
        <td>'.$sg['pos'].'</td>
        <td style="text-align:left;">'.htmlspecialchars($sg['subject_name']).'</td>
        <td>'.(int)$sg['grade_a'].'</td>
        <td>'.(int)$sg['grade_b'].'</td>
        <td>'.(int)$sg['grade_c'].'</td>
        <td>'.(int)$sg['grade_d'].'</td>
        <td>'.(int)$sg['grade_f'].'</td>
        <td>'.number_format((float)$sg['avg_mark'],2).'</td>
        <td>'.grade($sg['avg_mark']).'</td>
        <td>'.(int)$sg['reg'].'</td>
        <td>'.(int)$sg['sat'].'</td>
        <td>'.(int)$sg['pass_count'].'</td>
        <td>'.number_format((float)$sg['gpa'],2).'</td>
        <td>'.competencyLabel($sg['avg_mark']).'</td>
    </tr>';
endforeach;
$html .= '</tbody></table></div>';
endif;

$html .= '<div class="gen">Generated: '.date('d-m-Y H:i').'</div>';
$html .= '</body></html>';

require_once "../vendor/autoload.php";
use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$dompdf->stream("Results_" . preg_replace('/[^a-zA-Z0-9_-]/', '_', $exam_name) . ".pdf", ["Attachment" => true]);
exit;
