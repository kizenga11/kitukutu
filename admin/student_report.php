<?php
require_once "../includes/config.php";

$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

if ($exam_id == 0 || $student_id == 0) die("Invalid report parameters.");

$student_query = mysqli_query($conn, "SELECT * FROM students WHERE id = $student_id AND is_active=1");
if (!$student_query || mysqli_num_rows($student_query) == 0) die("Student not found.");
$stu = mysqli_fetch_assoc($student_query);

$exam_query = mysqli_query($conn, "SELECT exam_name, start_date, parent_message FROM exams WHERE id = $exam_id");
if (!$exam_query || mysqli_num_rows($exam_query) == 0) die("Exam not found.");
$exam = mysqli_fetch_assoc($exam_query);

$summary_query = mysqli_query($conn, "SELECT * FROM exam_results_summary WHERE exam_id = $exam_id AND student_id = $student_id");
if (!$summary_query || mysqli_num_rows($summary_query) == 0) die("Result summary not found for this student and exam.");
$sum = mysqli_fetch_assoc($summary_query);

$total_students_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM exam_results_summary WHERE exam_id = $exam_id");
$total_students = mysqli_fetch_assoc($total_students_query)['total'];

$subs = mysqli_query($conn, "
    SELECT sub.subject_name, m.marks
    FROM marks m
    JOIN subjects sub ON sub.id = m.subject_id
    JOIN student_subjects ss ON ss.student_id = m.student_id AND ss.subject_id = m.subject_id
    WHERE m.exam_id = $exam_id AND m.student_id = $student_id
    ORDER BY sub.subject_name
");

// Previous exams for same form level
$form_level = mysqli_real_escape_string($conn, $stu['form_level'] ?? '');
$prev_exams_query = mysqli_query($conn, "
    SELECT e.exam_name, e.start_date, ers.average_marks, ers.position, ers.total_points, ers.division
    FROM exam_results_summary ers
    JOIN exams e ON e.id = ers.exam_id
    WHERE ers.student_id = $student_id
      AND ers.form_level = '$form_level'
      AND ers.exam_id != $exam_id
    ORDER BY e.start_date DESC
    LIMIT 6
");
$prev_data = [];
if ($prev_exams_query) {
    while ($pr = mysqli_fetch_assoc($prev_exams_query)) {
        $prev_data[] = $pr;
    }
}

function gradePoint($m) {
    if ($m >= 75) return 1;
    elseif ($m >= 65) return 2;
    elseif ($m >= 45) return 3;
    elseif ($m >= 30) return 4;
    else return 5;
}

function gradeLet($m) {
    if ($m >= 75) return 'A';
    elseif ($m >= 65) return 'B';
    elseif ($m >= 45) return 'C';
    elseif ($m >= 30) return 'D';
    else return 'F';
}

$avg = $sum['average_marks'];
if ($avg >= 75) {
    $comment = "Ufaulu wa juu sana. Hongera mwanafunzi. Endelea kudumisha ukakamali wako.";
} elseif ($avg >= 65) {
    $comment = "Ufaulu mzuri. Ongeza juhudi kidogo ili kufikia ubora zaidi.";
} elseif ($avg >= 45) {
    $comment = "Ufaulu wa wastani. Jitahidi zaidi ili kuboresha matokeo yako.";
} elseif ($avg >= 30) {
    $comment = "Ufaulu wa chini. Mzazi ashirikiane na shule ili kumsaidia mwanafunzi.";
} else {
    $comment = "Ufaulu hafifu. Mzazi anashauriwa kufika shuleni kwa mazungumzo ya kina.";
}

// Always auto-generate based on the selected exam's actual data
$student_name = $stu['first_name'] . " " . $stu['second_name'] . " " . $stu['last_name'];
$avg_marks = number_format($sum['average_marks'], 2);
$division = $sum['division'];
$position = $sum['position'];

$parent_msg = "Mzazi mpendwa wa {$student_name}, matokeo ya '{$exam['exam_name']}' yamehitimishwa. ";
$parent_msg .= "Amepata wastani wa {$avg_marks}% ";
if ($division) $parent_msg .= "na daraja la {$division}, ";
$parent_msg .= "nafasi ya {$position} kati ya wanafunzi {$total_students}. ";

if ($avg >= 75)      $parent_msg .= "Hongera kwa matokeo bora. Endelea kumhimiza mwanafunzi kudumisha ukakamali huu.";
elseif ($avg >= 65)  $parent_msg .= "Matokeo mazuri. Msaidie mwanafunzi kuongeza muda wa kusoma nyumbani.";
elseif ($avg >= 45)  $parent_msg .= "Matokeo ya wastani. Hakikisha anafanya kazi za nyumbani na kujisomea zaidi.";
elseif ($avg >= 30)  $parent_msg .= "Matokeo dhaifu. Tafadhali wasiliana na mwalimu wa darasa ili kujua changamoto.";
else                 $parent_msg .= "Matokeo duni sana. Inashauriwa kufika shuleni kwa ushauri na kufuatilia maendeleo.";

// Append admin's custom note (if set) as additional info — never replace the auto-generated part
$custom_note = trim($exam['parent_message'] ?? '');
if (!empty($custom_note)) {
    $parent_msg .= " " . $custom_note;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Ripoti - <?= htmlspecialchars($stu['first_name']." ".$stu['last_name']) ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#e2e3e5;font-family:'Times New Roman',Times,serif;padding:15px 0;}
.page{
    width:210mm;min-height:297mm;background:#fff;
    margin:0 auto;padding:10mm 12mm 8mm 12mm;
    box-shadow:0 2px 8px rgba(0,0,0,0.1);position:relative;
}
.hdr{text-align:center;margin-bottom:8px;border-bottom:2px solid #000;padding-bottom:6px;}
.hdr .school{font-size:20px;font-weight:700;letter-spacing:1px;}
.hdr .sub{font-size:13px;margin-top:2px;}
.hdr .title{font-size:14px;font-weight:700;margin-top:3px;}
.info{display:flex;flex-wrap:wrap;justify-content:space-between;padding:5px 0;margin:5px 0;border-top:1px solid #555;border-bottom:1px solid #555;font-size:12px;line-height:1.6;}
table{width:100%;border-collapse:collapse;margin:5px 0;font-size:11.5px;}
th,td{border:1px solid #000;padding:4px 5px;text-align:center;vertical-align:middle;}
th{background:#f0f0f0;font-size:11.5px;font-weight:700;}
.subj-table td:first-child{text-align:left;padding-left:8px;}
.sec-title{margin:7px 0 4px;font-weight:700;font-size:12px;text-align:center;text-transform:uppercase;letter-spacing:.5px;}
.comment-box{padding:6px 10px;margin:4px 0;font-size:11px;line-height:1.5;border-left:4px solid #2c6e2f;background:#faf9f8;}
.sign-row{margin-top:20px;display:flex;justify-content:space-between;font-size:10.5px;text-align:center;}
.sign-item{width:30%;}
.behavior-table td,.behavior-table th{padding:2px 4px;font-size:9.5px;}
.behavior-table td:first-child{width:30px;}
.gpa-info{font-size:11px;text-align:right;margin-top:-2px;margin-bottom:4px;padding:3px 6px;background:#f5f5f5;}
.history-table th,.history-table td{padding:2px 5px;font-size:9.5px;}
.history-table td:first-child{text-align:left;max-width:90mm;}
.footer-date{font-size:9.5px;text-align:center;color:#666;margin-top:10px;border-top:1px solid #ddd;padding-top:6px;}
.print-btn{position:fixed;bottom:20px;right:20px;padding:10px 20px;background:#0b5e2e;color:#fff;border:none;border-radius:40px;font-weight:700;cursor:pointer;font-size:14px;z-index:1000;box-shadow:0 4px 12px rgba(0,0,0,0.2);}
.msg-stats{display:flex;gap:10px;flex-wrap:wrap;font-size:9.5px;font-weight:700;color:#7a5800;border-bottom:1px solid #f0d080;margin-bottom:5px;padding-bottom:4px;}
.no-history{font-size:10px;color:#888;padding:3px 8px;font-style:italic;}

@media print{
    @page{size:A4 portrait;margin:6mm 9mm;}
    body{background:#fff;margin:0;padding:0;}
    .page{width:100%;min-height:0;height:auto;margin:0;padding:0;box-shadow:none;}
    .hdr{margin-bottom:5px;padding-bottom:4px;}
    .hdr .school{font-size:17px;}
    .hdr .sub{font-size:11px;}
    .hdr .title{font-size:12px;}
    .info{font-size:10.5px;padding:3px 0;margin:3px 0;line-height:1.4;}
    table{font-size:10px;margin:3px 0;}
    th,td{padding:3px 4px;}
    .sec-title{font-size:11px;margin:5px 0 3px;}
    .comment-box{font-size:10px;padding:3px 8px;margin:3px 0;}
    .behavior-table th,.behavior-table td{padding:1px 3px;font-size:9px;}
    .history-table th,.history-table td{padding:1px 4px;font-size:9px;}
    .sign-row{margin-top:12px;font-size:9.5px;}
    .gpa-info{font-size:10px;padding:2px 5px;margin-bottom:3px;}
    .footer-date{font-size:8.5px;margin-top:7px;padding-top:4px;}
    .msg-stats{font-size:9px;gap:8px;margin-bottom:3px;padding-bottom:3px;}
    .print-btn{display:none;}
}
</style>
</head>
<body>

<div class="page">
    <div class="hdr">
        <div class="school">SHULE YA AMALI KITUKUTU</div>
        <div class="sub">S.L.P 27, KITUKUTU - NJOMBE</div>
        <div class="title">RIPOTI YA MATOKEO — <?= strtoupper(htmlspecialchars($exam['exam_name'])) ?></div>
    </div>

    <div class="info">
        <div><b>Jina:</b> <?= htmlspecialchars($stu['first_name']." ".$stu['second_name']." ".$stu['last_name']) ?></div>
        <div><b>Jinsia:</b> <?= htmlspecialchars($stu['sex']) ?> &nbsp;|&nbsp; <b>Kidato:</b> <?= htmlspecialchars($stu['form_level'] ?? '') ?> &nbsp;|&nbsp; <b>Mkondo:</b> <?= htmlspecialchars($stu['stream']) ?></div>
        <div><b>Tarehe:</b> <?= htmlspecialchars($exam['start_date']) ?> &nbsp;|&nbsp; <b>Wastani:</b> <?= number_format($sum['average_marks'],2) ?>%</div>
    </div>

    <table class="subj-table">
        <tr><th style="width:52%">SOMO</th><th style="width:18%">ALAMA</th><th style="width:15%">DARAJA</th><th style="width:15%">POINTI</th></tr>
        <?php
        $total_points=0; $total_sub=0;
        while ($s=mysqli_fetch_assoc($subs)){
            $mk=(float)$s['marks'];
            $g=gradeLet($mk);
            $pt=gradePoint($mk);
            $total_points+=$pt; $total_sub++;
            echo "<tr><td style='text-align:left;padding-left:8px'>".htmlspecialchars($s['subject_name'])."</td><td>".($mk?$mk:'—')."</td><td>$g</td><td>$pt</td></tr>";
        }
        $gpa=$total_sub>0?round($total_points/$total_sub,2):0;
        ?>
    </table>

    <div class="gpa-info">
        <b>GPA:</b> <?= number_format($gpa,2) ?>
        <?php if($sum['division']): ?> &nbsp;|&nbsp; <b>Division:</b> <?= htmlspecialchars($sum['division']) ?><?php endif; ?>
        &nbsp;|&nbsp; <b>Nafasi:</b> <?= (int)$sum['position']." / ".(int)$total_students ?>
        <?php if($sum['total_points']): ?> &nbsp;|&nbsp; <b>Jumla Pointi:</b> <?= (int)$sum['total_points'] ?><?php endif; ?>
    </div>

    <!-- HISTORIA YA MITIHANI -->
    <div class="sec-title">Historia ya Mitihani — <?= htmlspecialchars($stu['form_level'] ?? '') ?></div>
    <?php if (empty($prev_data)): ?>
        <div class="no-history">Hakuna matokeo ya mitihani ya awali kwa kidato hiki.</div>
    <?php else: ?>
    <table class="history-table">
        <tr>
            <th style="text-align:left;width:45%">MTIHANI</th>
            <th>TAREHE</th>
            <th>WASTANI</th>
            <th>NAFASI</th>
            <th>DARAJA</th>
            <th>POINTI</th>
        </tr>
        <?php foreach ($prev_data as $pr):
            $pa = (float)$pr['average_marks'];
            if ($pa >= 75) $pg = 'A'; elseif ($pa >= 65) $pg = 'B'; elseif ($pa >= 45) $pg = 'C'; elseif ($pa >= 30) $pg = 'D'; else $pg = 'F';
        ?>
        <tr>
            <td style="text-align:left"><?= htmlspecialchars($pr['exam_name']) ?></td>
            <td><?= htmlspecialchars(date('d/m/y', strtotime($pr['start_date']))) ?></td>
            <td><?= number_format($pa,1) ?>%</td>
            <td><?= (int)$pr['position'] ?></td>
            <td><?= htmlspecialchars($pr['division'] ?: $pg) ?></td>
            <td><?= (int)$pr['total_points'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <div class="sec-title">Tabia na Mwenendo</div>
    <table class="behavior-table">
        <tr><th>NO</th><th>MAELEZO</th><th>ALAMA</th><th>NO</th><th>MAELEZO</th><th>ALAMA</th></tr>
        <tr><td>1</td><td>KUFANYA KAZI KWA BIDII</td><td>____</td><td>5</td><td>HESHIMA KWA WALIMU NA WANAFUNZI</td><td>____</td></tr>
        <tr><td>2</td><td>KUPENDA KUHESHIMU NA KUTHAMINI KAZI</td><td>____</td><td>6</td><td>KUTII NA KUFUATA MAAGIZO</td><td>____</td></tr>
        <tr><td>3</td><td>UANGALIFU WA MALI ZA UMA</td><td>____</td><td>7</td><td>USAFI BINAFSI</td><td>____</td></tr>
        <tr><td>4</td><td>UELEWA NA USHIRIKIANO</td><td>____</td><td>8</td><td>KUSHIRIKI SHUGHULI ZA UTAMADUNI</td><td>____</td></tr>
    </table>

    <div class="sec-title">Maoni ya Shule</div>
    <div class="comment-box"><?= htmlspecialchars($comment) ?></div>

    <div class="sec-title">Ujumbe kwa Mzazi / Mlezi</div>
    <div class="comment-box" style="background:#fff6e5;padding:6px 10px;">
        <div class="msg-stats">
            <span>Pointi: <?= (int)($sum['total_points'] ?? $total_points) ?></span>
            <span>GPA: <?= number_format($gpa,2) ?></span>
            <?php if($sum['division']): ?><span>Daraja: <?= htmlspecialchars($sum['division']) ?></span><?php endif; ?>
            <span>Nafasi: <?= (int)$sum['position'] ?> / <?= (int)$total_students ?></span>
            <span>Wastani: <?= number_format($sum['average_marks'],1) ?>%</span>
        </div>
        <div style="font-size:10.5px;color:#5a4000;line-height:1.45;"><?= htmlspecialchars($parent_msg) ?></div>
    </div>

    <div class="sign-row">
        <div class="sign-item">_____________________<br><strong>MWALIMU WA DARASA</strong></div>
        <div class="sign-item">_____________________<br><strong>MWALIMU WA TAALUMA</strong></div>
        <div class="sign-item">_____________________<br><strong>MKUU WA SHULE</strong></div>
    </div>

    <div class="footer-date">Ripoti hii imetolewa na Ofisi ya Taaluma &nbsp;|&nbsp; Tarehe: <?= date('d/m/Y') ?></div>
</div>

<button onclick="window.print()" class="print-btn">Chapisha / Hifadhi PDF</button>

</body>
</html>
