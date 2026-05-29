<?php
error_reporting(0);
@ini_set('display_errors', 0);
ob_start();
session_start();
include "../includes/config.php";
require_once "../vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

$selected_students = isset($_GET['students']) ? array_map('intval', (array)$_GET['students']) : [];
$selected_exams    = isset($_GET['exams']) ? array_map('intval', (array)$_GET['exams']) : [];
$form_level_filter = isset($_GET['form_level']) ? mysqli_real_escape_string($conn, $_GET['form_level']) : '';
$mode = $_GET['mode'] ?? 'grade';

$school_name = "Kitukutu Technical Secondary School";

function grade($m){
    if($m >= 75) return 'A';
    if($m >= 65) return 'B';
    if($m >= 45) return 'C';
    if($m >= 30) return 'D';
    return 'F';
}

$exams_q = mysqli_query($conn,"SELECT id, exam_name FROM exams ORDER BY start_date DESC");
$exams_all = [];
while($e=mysqli_fetch_assoc($exams_q)) $exams_all[] = $e;

$students_list = [];
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

$exam_names = [];
foreach($exams_all as $e) $exam_names[$e['id']] = $e['exam_name'];

$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
    body{font-family:DejaVu Sans,sans-serif;font-size:11px;color:#1a1a2e;padding:20px;}
    .school-header{text-align:center;margin-bottom:20px;padding-bottom:15px;border-bottom:2px solid #1a2b4c;}
    .school-header .school-name{font-size:18px;font-weight:700;color:#1a2b4c;}
    .school-header .school-desc{font-size:11px;color:#6b7280;margin-top:2px;}
    .report-card{margin-bottom:30px;page-break-inside:avoid;}
    .report-card .r-header{margin-bottom:8px;padding-bottom:6px;border-bottom:1px solid #ccc;}
    .report-card .r-name{font-size:14px;font-weight:700;color:#1a2b4c;}
    .report-card .r-detail{font-size:10px;color:#6b7280;}
    table{width:100%;border-collapse:collapse;font-size:10px;margin-top:6px;}
    th{background:#1a2b4c;color:#fff;padding:5px 4px;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;border:1px solid #253b5e;text-align:center;}
    td{padding:4px 3px;text-align:center;border:1px solid #ddd;font-size:10px;}
    td.subj-name{text-align:left;font-weight:600;}
    tr.summary-row td{background:#f0f2f5;font-weight:700;}
    .footer{text-align:center;font-size:9px;color:#9ca3af;margin-top:20px;padding-top:10px;border-top:1px solid #eee;}
</style></head><body>';

$html .= '<div class="school-header">';
$html .= '<div class="school-name">'.htmlspecialchars($school_name).'</div>';
$html .= '<div class="school-desc">Multi-Exam Results Report</div>';
$html .= '</div>';

foreach ($selected_students as $sid):
    $st = null;
    foreach($students_list as $s){ if($s['id']==$sid){ $st=$s; break; } }
    if(!$st) continue;

    $full_name = $st['display_name'];

    $html .= '<div class="report-card">';
    $html .= '<div class="r-header">';
    $html .= '<div class="r-name">'.htmlspecialchars($full_name).'</div>';
    $html .= '<div class="r-detail">'.htmlspecialchars($st['form_level']).' &middot; '.htmlspecialchars($st['stream']).' &middot; '.$st['sex'].'</div>';
    $html .= '</div>';

    $html .= '<table>';
    $html .= '<tr><th>#</th><th>Subject</th>';
    foreach($selected_exams as $eid){
        $html .= '<th>'.htmlspecialchars($exam_names[$eid] ?? 'Exam '.$eid).'</th>';
    }
    $html .= '</tr>';

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
        $html .= '<tr><td>'.$i++.'</td>';
        $html .= '<td class="subj-name">'.htmlspecialchars($sb['subject_name']).'</td>';
        foreach($selected_exams as $eid):
            $mq = mysqli_query($conn,"SELECT marks FROM marks WHERE student_id='$sid' AND subject_id='{$sb['id']}' AND exam_id='$eid' LIMIT 1");
            $mr = mysqli_fetch_assoc($mq);
            $mark = $mr ? $mr['marks'] : null;
            if($mark === null || $mark === ''):
                $html .= '<td>—</td>';
            elseif($mark == 'A' || $mark == 'ABSENT'):
                $html .= '<td>ABS</td>';
            else:
                $val = $mode == 'marks' ? $mark : grade($mark);
                $html .= '<td>'.$val.'</td>';
            endif;
        endforeach;
        $html .= '</tr>';
    endforeach;

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

    $html .= '<tr class="summary-row"><td></td><td style="text-align:left;">Average</td>';
    foreach($selected_exams as $eid):
        $html .= '<td>'.($summary_row[$eid] ? $summary_row[$eid]['avg'] : '—').'</td>';
    endforeach;
    $html .= '</tr>';

    $html .= '<tr class="summary-row"><td></td><td style="text-align:left;">Grade</td>';
    foreach($selected_exams as $eid):
        $html .= '<td>'.($summary_row[$eid] ? $summary_row[$eid]['grd'] : '—').'</td>';
    endforeach;
    $html .= '</tr>';

    $html .= '<tr class="summary-row"><td></td><td style="text-align:left;">Points</td>';
    foreach($selected_exams as $eid):
        $html .= '<td>'.($summary_row[$eid] ? $summary_row[$eid]['pts'] : '—').'</td>';
    endforeach;
    $html .= '</tr>';

    $html .= '<tr class="summary-row"><td></td><td style="text-align:left;">Division</td>';
    foreach($selected_exams as $eid):
        $html .= '<td>'.($summary_row[$eid] ? $summary_row[$eid]['div'] : '—').'</td>';
    endforeach;
    $html .= '</tr>';

    $html .= '</table></div>';
endforeach;

$html .= '<div class="footer">Generated by Kitukutu Secondary School Management System</div>';
$html .= '</body></html>';

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

ob_clean();
$dompdf->stream("multi_exam_results.pdf", array("Attachment" => true));
exit;
