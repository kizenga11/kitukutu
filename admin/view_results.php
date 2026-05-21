<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

$exam_id = intval($_GET['exam_id'] ?? 0);
$mode = $_GET['mode'] ?? 'grade';

if(!$exam_id){ die("No exam selected"); }

$school_name = "KITUKUTU TECHNICAL SCHOOL";

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

$chk = mysqli_query($conn,"SELECT COUNT(*) total FROM exam_results_summary WHERE exam_id='$exam_id'");
$processed = mysqli_fetch_assoc($chk)['total'] > 0;

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
*{margin:0;padding:0;box-sizing:border-box;}
body{
    font-family:system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif;
    background:#e8ecf1;
    padding:12px;
    font-size:13px;
    color:#1a1a2e;
}
.container{max-width:100%;margin:0 auto;}

/* HEADER */
.header-card{
    background:linear-gradient(135deg,#1a1a2e 0%,#16213e 100%);
    border-radius:10px;
    padding:14px 18px;
    margin-bottom:12px;
    color:#fff;
    display:flex;
    flex-wrap:wrap;
    align-items:center;
    justify-content:space-between;
}
.header-left h2{font-size:15px;font-weight:700;letter-spacing:0.3px;}
.header-left p{font-size:12px;opacity:0.85;margin-top:2px;}
.header-right{display:flex;gap:6px;flex-wrap:wrap;}
.btn{
    display:inline-flex;align-items:center;gap:4px;
    padding:6px 12px;border:none;border-radius:6px;
    font-size:12px;font-weight:500;cursor:pointer;
    text-decoration:none;transition:all .15s;
}
.btn-light{background:rgba(255,255,255,0.15);color:#fff;}
.btn-light:hover{background:rgba(255,255,255,0.25);}
.btn-light.active{background:#fff;color:#1a1a2e;font-weight:600;}
.btn-print{background:#27ae60;color:#fff;}
.btn-print:hover{background:#219a52;}
.btn-back{background:rgba(255,255,255,0.1);color:#fff;}
.btn-back:hover{background:rgba(255,255,255,0.2);}

/* SUMMARY ROW */
.summary-row{
    display:grid;
    grid-template-columns:auto 1fr;
    gap:12px;
    margin-bottom:12px;
}
.summary-card{
    background:#fff;
    border-radius:10px;
    padding:12px 14px;
    box-shadow:0 1px 4px rgba(0,0,0,0.06);
}
.summary-title{
    font-size:11px;text-transform:uppercase;letter-spacing:0.5px;
    color:#7f8c8d;margin-bottom:8px;font-weight:600;
}
.summary-grid{display:flex;gap:14px;flex-wrap:wrap;}

.stat-item{
    text-align:center;min-width:56px;
}
.stat-value{
    font-size:18px;font-weight:700;color:#1a1a2e;line-height:1.2;
}
.stat-label{
    font-size:10px;color:#95a5a6;text-transform:uppercase;letter-spacing:0.3px;
}

.div-table{width:100%;border-collapse:collapse;font-size:12px;}
.div-table th,.div-table td{
    padding:5px 8px;text-align:center;border-bottom:1px solid #ecf0f1;
}
.div-table th{
    font-size:10px;text-transform:uppercase;letter-spacing:0.5px;
    color:#7f8c8d;font-weight:600;border-bottom:2px solid #bdc3c7;
}
.div-table td{color:#2c3e50;}

/* SUBJECT GRADES */
.subj-section{
    background:#fff;
    border-radius:10px;
    padding:12px 14px;
    margin-bottom:12px;
    box-shadow:0 1px 4px rgba(0,0,0,0.06);
}
.subj-table{width:100%;border-collapse:collapse;font-size:11px;}
.subj-table th,.subj-table td{
    padding:4px 6px;text-align:center;border:1px solid #ecf0f1;
}
.subj-table th{
    background:#34495e;color:#fff;font-size:10px;
    text-transform:uppercase;letter-spacing:0.3px;
}
.subj-table td{font-size:11px;}
.subj-table tr:nth-child(even){background:#f8f9fa;}

/* MAIN TABLE */
.table-card{
    background:#fff;
    border-radius:10px;
    box-shadow:0 1px 4px rgba(0,0,0,0.06);
    overflow:hidden;
}
.table-wrap{overflow-x:auto;}
.results-table{width:100%;border-collapse:collapse;font-size:12px;}
.results-table th{
    background:#1a1a2e;color:#fff;padding:8px 6px;
    font-size:11px;text-transform:uppercase;letter-spacing:0.5px;
    white-space:nowrap;position:sticky;top:0;z-index:1;
}
.results-table td{
    padding:7px 5px;text-align:center;border-bottom:1px solid #ecf0f1;
    vertical-align:middle;
}
.results-table td.name-col{text-align:left;}
.results-table td.subj-col{text-align:left;}
.results-table tr:nth-child(even){background:#f8f9fa;}
.results-table tr:hover{background:#eef2f7;}
.name-col{text-align:left;white-space:nowrap;}
.subj-col{font-size:11px;text-align:left;word-break:break-word;line-height:1.4;}
.avg-val{font-weight:600;color:#2c3e50;}
.grade-val{font-weight:700;font-size:13px;}
.pts-val{font-weight:600;color:#8e44ad;}
.div-val{font-weight:600;color:#c0392b;}
.pos-val{font-weight:700;color:#1a1a2e;}

.no-data{
    text-align:center;padding:30px 10px;
    color:#95a5a6;font-size:14px;
}
.no-data p{margin-top:6px;font-size:12px;color:#bdc3c7;}

.footer{
    text-align:center;font-size:10px;
    color:#95a5a6;padding:10px 0 4px;
}
.print-footer{display:none;}
@media print{
    .print-footer{
        display:block;text-align:center;
        font-size:8pt;color:#666;margin-top:8px;
        border-top:1px solid #999;padding-top:6px;
    }
}

/* RESPONSIVE */
@media screen and (max-width:768px){
    body{padding:8px;font-size:12px;}
    .header-card{flex-direction:column;gap:10px;align-items:stretch;padding:12px;}
    .header-left h2{font-size:14px;}
    .header-right{justify-content:center;}
    .summary-row{grid-template-columns:1fr;}
    .summary-grid{gap:10px;}
    .stat-value{font-size:16px;}
    .results-table,.results-table thead,.results-table tbody,
    .results-table th,.results-table td,.results-table tr{display:block;}
    .results-table thead{display:none;}
    .results-table tr{
        margin-bottom:8px;border:1px solid #e0e0e0;
        border-radius:8px;background:#fff;padding:6px 0;
    }
    .results-table td{
        display:flex;justify-content:space-between;align-items:center;
        padding:6px 10px;border:none;border-bottom:1px solid #f0f0f0;text-align:right;
    }
    .results-table td:last-child{border-bottom:none;}
    .results-table td::before{
        content:attr(data-label);font-weight:600;font-size:10px;
        color:#7f8c8d;text-align:left;min-width:70px;text-transform:uppercase;
    }
    .name-col{white-space:normal;}
    .subj-col{font-size:10px;}
}

/* PRINT - OFFICIAL DOCUMENT */
@media print{
    @page{size:landscape;margin:10mm 15mm;}
    *{color:#000!important;}
    body{background:#fff;padding:0;margin:0;font-size:10px;font-family:'Times New Roman',Times,serif;}
    .container{max-width:100%;padding:0;}

    /* Hide non-printable */
    .btn,.btn-group,.header-right,.footer{display:none!important;}

    /* Official header */
    .header-card{
        background:transparent!important;color:#000!important;
        padding:0 0 8px 0!important;margin-bottom:4px!important;
        border-bottom:3px double #000;
        text-align:center;display:block;
    }
    .header-left{text-align:center;}
    .header-left h2{font-size:16pt;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin:0 0 2px;}
    .header-left p{font-size:12pt;margin:0;opacity:1;}
    .summary-row{display:flex;gap:0;margin-bottom:6px;}
    .summary-card{box-shadow:none;border-radius:0;padding:6px 10px;border:1px solid #000;flex:1;}
    .summary-title{font-size:9pt;font-weight:700;text-transform:uppercase;margin-bottom:4px;}
    .stat-value{font-size:14pt;font-weight:700;}
    .stat-label{font-size:8pt;}
    .div-table td,.div-table th{padding:3px 6px;border:1px solid #000;font-size:9pt;}
    .div-table th{background:#ccc!important;color:#000!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}

    .subj-section{box-shadow:none;border-radius:0;padding:6px 10px;border:1px solid #000;margin-bottom:6px;page-break-inside:avoid;}
    .subj-table{width:100%;border-collapse:collapse;}
    .subj-table th{background:#ccc!important;color:#000!important;font-size:8pt;border:1px solid #000;padding:4px 5px;}
    .subj-table td{border:1px solid #000;padding:3px 5px;font-size:8pt;}
    .subj-table tr:nth-child(even){background:#f5f5f5!important;}

    .table-card{box-shadow:none;border-radius:0;border:1px solid #000;}
    .results-table{font-size:9pt;width:100%;border-collapse:collapse;}
    .results-table thead{display:table-header-group;}
    .results-table tbody{display:table-row-group;}
    .results-table tr{display:table-row;border:none;margin:0;page-break-inside:avoid;}
    .results-table th{
        display:table-cell;background:#ccc!important;color:#000!important;
        padding:5px 4px;font-size:8pt;font-weight:700;
        -webkit-print-color-adjust:exact;print-color-adjust:exact;
        border:1px solid #000;text-align:center;
    }
    .results-table td{display:table-cell;text-align:center;border:1px solid #000;padding:4px 3px;}
    .results-table td.name-col,.results-table td.subj-col{text-align:left;}
    .results-table td::before{display:none;}
    .summary-row,.subj-section,.table-card{page-break-inside:avoid;}

    .name-col{white-space:nowrap;}
    .subj-col{font-size:8pt;text-align:left;}
    .avg-val,.grade-val,.pts-val,.div-val,.pos-val{color:#000!important;}

    /* Print footer */
    @bottom-center{
        content:"Generated: " attr(data-date);
        font-size:8pt;color:#666;
    }
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
        <p><?= htmlspecialchars($exam_name) ?></p>
    </div>
    <div class="header-right">
        <button onclick="history.back()" class="btn btn-back">← Back</button>
        <button onclick="window.print()" class="btn btn-print">🖨 Print</button>
        <a href="?exam_id=<?= $exam_id ?>&mode=grade" class="btn <?= $mode=='grade'?'btn-light active':'btn-light' ?>">Grades</a>
        <a href="?exam_id=<?= $exam_id ?>&mode=marks" class="btn <?= $mode=='marks'?'btn-light active':'btn-light' ?>">Marks</a>
    </div>
</div>

<?php if(!$processed): ?>
<div class="summary-card" style="text-align:center;padding:30px;">
    <div style="font-size:32px;margin-bottom:8px;">📋</div>
    <div class="no-data">
        <strong>Results Not Processed</strong>
        <p>Please process results first before viewing.</p>
    </div>
</div>
<?php else:

/* PARENT MESSAGE FORM */
?>
<div class="pm-card">
    <div class="pm-header" onclick="this.nextElementSibling.classList.toggle('pm-open');this.querySelector('.pm-toggle').textContent=this.nextElementSibling.classList.contains('pm-open')?'▲':'▼'">
        <span>✉ Ujumbe kwa Wazazi / Walezi</span>
        <span class="pm-toggle">▼</span>
    </div>
    <div class="pm-body">
        <form method="post">
            <textarea name="parent_message" rows="3" placeholder="Andika ujumbe utakaokwenda kwa wazazi wa wanafunzi wote kwenye ripoti za matokeo..." style="width:100%;padding:8px 10px;border:1px solid #ccc;border-radius:6px;font-size:13px;font-family:inherit;resize:vertical;"><?= htmlspecialchars($exam_parent_msg) ?></textarea>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px;">
                <span style="font-size:11px;color:#888;">Ujumbe huu utaonekana kwenye ripoti za kila mwanafunzi</span>
                <button type="submit" name="save_parent_message" class="btn btn-save">Hifadhi Ujumbe</button>
            </div>
        </form>
    </div>
</div>

<style>
.pm-card{background:#fff;border-radius:10px;margin-bottom:12px;box-shadow:0 1px 4px rgba(0,0,0,0.06);overflow:hidden;}
.pm-header{display:flex;justify-content:space-between;align-items:center;padding:10px 14px;cursor:pointer;background:#fef9e7;border-left:4px solid #f39c12;font-size:13px;font-weight:600;color:#7a5800;user-select:none;}
.pm-toggle{font-size:10px;color:#b7950b;}
.pm-body{display:none;padding:12px 14px;border-top:1px solid #f0f0f0;}
.pm-body.pm-open{display:block;}
.pm-body textarea:focus{outline:none;border-color:#f39c12;box-shadow:0 0 0 2px rgba(243,156,18,0.2);}
.btn-save{background:#f39c12;color:#fff;border:none;padding:6px 14px;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;transition:background .15s;}
.btn-save:hover{background:#d68910;}
@media print{.pm-card{display:none!important;}}
</style>

<?php
/* DIVISION & SCHOOL SUMMARY */
$divOrder = ['I','II','III','IV','0','N/A'];
if($summary_json && isset($summary_json['divisions'])){
    $divisions = $summary_json['divisions'];
} else {
    $divQ = mysqli_query($conn,"SELECT ers.division,s.sex,COUNT(*) as c FROM exam_results_summary ers JOIN students s ON s.id=ers.student_id WHERE ers.exam_id='$exam_id' GROUP BY ers.division,s.sex");
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

<div class="summary-row">
    <!-- DIVISION TABLE -->
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

    <!-- SCHOOL SUMMARY -->
    <div class="summary-card">
        <div class="summary-title">School Summary</div>
        <div class="summary-grid">
            <?php if($schoolInfo): $sc=$schoolInfo; ?>
            <div class="stat-item">
                <div class="stat-value"><?= number_format((float)$sc['school_avg'],2) ?></div>
                <div class="stat-label">Average</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= $sc['school_grade'] ?></div>
                <div class="stat-label">Grade</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= (int)($sc['total_students']??0) ?></div>
                <div class="stat-label">Students</div>
            </div>
            <?php else: ?>
            <div class="stat-item">
                <div class="stat-value">—</div>
                <div class="stat-label">No Data</div>
            </div>
            <?php endif; ?>
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
                   ers.total_points, ers.division, ers.position, ers.average_marks
            FROM exam_results_summary ers
            JOIN students s ON s.id = ers.student_id
            WHERE ers.exam_id = '$exam_id'
            ORDER BY ers.position ASC
        ");

        while($st = mysqli_fetch_assoc($students)):
            $id = $st['id'];
            $first = trim($st['first_name'] ?? '');
            $second = trim($st['second_name'] ?? '');
            $last = trim($st['last_name'] ?? '');
            $full_name = $first;
            if($second) $full_name .= ' ' . $second;
            if($last) $full_name .= ' ' . $last;
            $full_name = trim($full_name);

            $subq = mysqli_query($conn,"
                SELECT sub.subject_code, m.marks
                FROM marks m
                JOIN subjects sub ON sub.id = m.subject_id
                WHERE m.student_id = '$id' AND m.exam_id = '$exam_id'
                ORDER BY sub.subject_code
            ");

            $subs = [];
            while($sb = mysqli_fetch_assoc($subq)):
                $subject_code = ucwords(strtolower($sb['subject_code']));
                if($mode == 'marks'):
                    $display_value = ($sb['marks'] == 'A' || $sb['marks'] == 'ABSENT') ? 'ABS' : $sb['marks'];
                else:
                    if($sb['marks'] == 'A' || $sb['marks'] == 'ABSENT'):
                        $display_value = 'ABS';
                    else:
                        $display_value = grade($sb['marks']);
                    endif;
                endif;
                $subs[] = $subject_code . "-" . $display_value;
            endwhile;

            $subject_string = implode(" ", $subs);
            $avg_grade = grade($st['average_marks']);
            $div_display = $st['division'] ?: '-';
        ?>
        <tr>
            <td data-label="#" class="pos-val"><?= (int)$st['position'] ?></td>
            <td data-label="Name" class="name-col"><?= htmlspecialchars($full_name) ?></td>
            <td data-label="Sex"><?= $st['sex']=='Male'?'M':'F' ?></td>
            <td data-label="Avg" class="avg-val"><?= number_format((float)$st['average_marks'],2) ?></td>
            <td data-label="Grade" class="grade-val"><?= $avg_grade ?></td>
            <td data-label="Pts" class="pts-val"><?= (int)$st['total_points'] ?></td>
            <td data-label="Div" class="div-val"><?= $div_display ?></td>
            <td data-label="Subjects" class="subj-col"><?= htmlspecialchars($subject_string) ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
</div>
</div>

<!-- SUBJECT PERFORMANCE SUMMARY -->
<?php if($summary_json && isset($summary_json['subject_grades'])): $sgs=$summary_json['subject_grades']; ?>
<div class="subj-section">
    <div class="summary-title" style="margin-bottom:8px;">Subject Performance Summary</div>
    <div class="table-wrap">
        <table class="subj-table">
            <tr><th>#</th><th>Subject</th><th>A</th><th>B</th><th>C</th><th>D</th><th>F</th><th>Avg</th><th>Grade</th></tr>
            <?php foreach($sgs as $sg): ?>
            <tr>
                <td style="font-weight:700;"><?= (int)($sg['pos']??0) ?></td>
                <td style="text-align:left;font-weight:600;"><?= htmlspecialchars($sg['name']??'') ?></td>
                <td style="color:#27ae60;"><?= (int)($sg['A']??0) ?></td>
                <td style="color:#2980b9;"><?= (int)($sg['B']??0) ?></td>
                <td style="color:#f39c12;"><?= (int)($sg['C']??0) ?></td>
                <td style="color:#e67e22;"><?= (int)($sg['D']??0) ?></td>
                <td style="color:#c0392b;"><?= (int)($sg['F']??0) ?></td>
                <td style="font-weight:700;color:#1a1a2e;"><?= number_format((float)($sg['avg']??0),2) ?></td>
                <td style="font-weight:800;font-size:13px;"><?= $sg['grade']??'-' ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<div class="footer">Generated: <?= date('d-m-Y H:i') ?></div>
<div class="print-footer">Document generated on <?= date('F d, Y \a\t H:i') ?> — Official results for <?= htmlspecialchars($exam_name) ?></div>

</div>

</body>
</html>