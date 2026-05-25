<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

$exam1 = isset($_GET['exam1']) ? (int)$_GET['exam1'] : 0;
$exam2 = isset($_GET['exam2']) ? (int)$_GET['exam2'] : 0;
$exam3 = isset($_GET['exam3']) ? (int)$_GET['exam3'] : 0;
$form_level = isset($_GET['form_level']) ? mysqli_real_escape_string($conn, $_GET['form_level']) : '';

$exam_list = [];
$exams_q = mysqli_query($conn, "SELECT id, exam_name FROM exams ORDER BY start_date DESC");
while($e = mysqli_fetch_assoc($exams_q)){
    $exam_list[] = $e;
}
$exam_lookup = [];
foreach($exam_list as $e){
    $exam_lookup[(int)$e['id']] = $e['exam_name'];
}

$selected = array_filter([$exam1, $exam2, $exam3]);
$selected_count = count($selected);

$fl_filter_sql = $form_level ? " AND form_level = '$form_level'" : '';

$school_name = "Kitukutu Technical School";
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Exam Comparison</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:system-ui,-apple-system,'Segoe UI',Arial,sans-serif;background:#e8ecf1;padding:12px;font-size:13px;color:#1a1a2e;}
.container{max-width:1100px;margin:auto;}

.card{background:#fff;border-radius:10px;padding:14px 16px;margin-bottom:12px;box-shadow:0 1px 4px rgba(0,0,0,0.06);}

h3{font-size:14px;margin:0 0 10px;font-weight:700;}

label{display:block;margin-bottom:4px;font-weight:600;font-size:12px;color:#555;}

table{width:100%;border-collapse:collapse;margin-top:8px;}
th,td{padding:6px 5px;border:1px solid #ddd;text-align:center;font-size:12px;}
th{background:#1a1a2e;color:#fff;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.3px;}
tr:nth-child(even){background:#f8f9fa;}

.btn{display:inline-flex;align-items:center;gap:4px;padding:6px 12px;text-decoration:none;font-size:12px;border:none;border-radius:6px;cursor:pointer;transition:all .15s;color:#fff;}
.btn-back{background:#555;}
.btn-back:hover{background:#444;}
.btn-print{background:#27ae60;}
.btn-print:hover{background:#219a52;}
.btn-analyze{background:#3498db;}
.btn-analyze:hover{background:#2980b9;}

.toolbar{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px;}
.exam-grid{display:flex;gap:10px;flex-wrap:wrap;}
.exam-grid > div{flex:1;min-width:180px;}
select{width:100%;padding:7px 8px;border:1px solid #ccc;border-radius:6px;background:#fff;font-size:13px;}
.summary-box{margin-top:8px;padding:8px 10px;border-radius:6px;background:#f0f4f8;border:1px solid #dde4ec;font-size:12px;}
.print-only{display:none;}
.no-print{display:block;}
.print-main-title{font-size:16px;font-weight:700;text-align:center;margin:0 0 4px;}
.print-sub-title{font-size:12px;text-align:center;margin:0 0 6px;}

.up{color:#27ae60;font-weight:600;}
.down{color:#e74c3c;font-weight:600;}
.same{color:#7f8c8d;font-weight:600;}

.compact-table{font-size:11px;}
.compact-table th,.compact-table td{padding:4px 4px;}

@media print{
    @page{size:A4 landscape;margin:8mm;}
    body{background:#fff;padding:0;font-size:10px;color:#000;}
    .card{box-shadow:none;border:1px solid #000;padding:6px 8px;margin-bottom:6px;border-radius:0;}
    .btn{display:none!important;}
    table{font-size:10px;}
    th,td{border:1px solid #000;padding:4px 3px;}
    th{background:#333!important;color:#fff!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
    .print-only{display:block;}
    .no-print{display:none!important;}
    .summary-box{border:1px solid #999;background:#f5f5f5;}
}

@media(max-width:700px){
    .exam-grid{flex-direction:column;}
    .exam-grid > div{min-width:100%;}
}
</style>
</head>
<body>

<div class="container">

<div class="card">
<div class="toolbar">
    <button onclick="window.print()" class="btn btn-print">Print Page</button>
</div>

<h3>Exam Comparison</h3>

<form method="get">
<div class="exam-grid">
    <div>
        <label>Exam 1</label>
        <select name="exam1">
            <option value="">-- Select Exam --</option>
            <?php foreach($exam_list as $e){ ?>
            <option value="<?= $e['id'] ?>" <?= $exam1===(int)$e['id']?'selected':'' ?>><?= $e['exam_name'] ?></option>
            <?php } ?>
        </select>
    </div>
    <div>
        <label>Exam 2</label>
        <select name="exam2">
            <option value="">-- Select Exam --</option>
            <?php foreach($exam_list as $e){ ?>
            <option value="<?= $e['id'] ?>" <?= $exam2===(int)$e['id']?'selected':'' ?>><?= $e['exam_name'] ?></option>
            <?php } ?>
        </select>
    </div>
    <div>
        <label>Exam 3 <span style="font-weight:400;color:#999;">(optional)</span></label>
        <select name="exam3">
            <option value="">-- Select Exam --</option>
            <?php foreach($exam_list as $e){ ?>
            <option value="<?= $e['id'] ?>" <?= $exam3===(int)$e['id']?'selected':'' ?>><?= $e['exam_name'] ?></option>
            <?php } ?>
        </select>
    </div>
</div>
<div style="margin-top:12px;display:flex;gap:10px;align-items:end;flex-wrap:wrap;">
    <div style="min-width:150px;">
        <label>Kidato (Form Level)</label>
        <select name="form_level">
            <option value="">-- All Forms --</option>
            <option value="Form One" <?= $form_level==='Form One'?'selected':'' ?>>Form One</option>
            <option value="Form Two" <?= $form_level==='Form Two'?'selected':'' ?>>Form Two</option>
            <option value="Form Three" <?= $form_level==='Form Three'?'selected':'' ?>>Form Three</option>
            <option value="Form Four" <?= $form_level==='Form Four'?'selected':'' ?>>Form Four</option>
        </select>
    </div>
    <button type="submit" class="btn btn-analyze">Analyze</button>
</div>
</form>
</div>

<?php if($selected_count >= 2):

$exam_ids = [$exam1, $exam2, $exam3];
$exam_names = [];
foreach($exam_ids as $eid){
    $exam_names[] = $exam_lookup[$eid] ?? "Exam #$eid";
}

echo "<div class='card' id='comparison-report'>";

$unique = array_unique(array_filter($exam_ids));
if(count($unique) !== count(array_filter($exam_ids))){
    echo "<p style='color:#e74c3c;'>Please select different exams for comparison.</p>";
    echo "</div>";
} else {

echo "<div class='print-only'>";
echo "<p class='print-main-title'>$school_name</p>";
echo "<p class='print-sub-title'>Exam Comparison: ".implode(" vs ", array_filter($exam_names, function($n){return $n!==null;}))."</p>";
echo "</div>";

/* ===== SCHOOL-WISE ===== */
$school_data = [];
foreach($exam_ids as $eid){
    if(!$eid) continue;
    $q = mysqli_query($conn, "SELECT AVG(CASE WHEN marks='A' THEN NULL ELSE marks END) as avg_mark FROM marks WHERE exam_id='$eid'$fl_filter_sql");
    $r = mysqli_fetch_assoc($q);
    $school_data[$eid] = round((float)($r['avg_mark'] ?? 0), 2);
}

echo "<h3>School-wise Comparison</h3>";
echo "<table class='compact-table'>";
echo "<tr><th>Metric</th>";
foreach($exam_ids as $eid){
    if(!$eid) continue;
    echo "<th>".htmlspecialchars($exam_lookup[$eid]??"Exam")."</th>";
}
echo "<th>Change (E1→E2)</th>";
if($exam3) echo "<th>Change (E1→E3)</th>";
echo "</tr>";

echo "<tr>";
echo "<td style='text-align:left;font-weight:600;'>School Average</td>";
$first_avg = null;
$first_eid = null;
foreach($exam_ids as $eid){
    if(!$eid) continue;
    $val = $school_data[$eid];
    echo "<td><strong>$val</strong></td>";
    if($first_avg === null){ $first_avg = $val; $first_eid = $eid; }
}

// Changes relative to first exam
$idx=0;
foreach($exam_ids as $eid){
    if(!$eid || $idx===0) {$idx++; continue;}
    $val = $school_data[$eid];
    if((float)$first_avg==0) $chg = $val>0 ? 100 : 0;
    else $chg = (($val-$first_avg)/$first_avg)*100;
    $chg = round($chg,1);
    $cls = $chg>0?'up':($chg<0?'down':'same');
    $txt = $chg>0?'+':''.$chg.'%';
    echo "<td class='$cls'>$txt</td>";
    $idx++;
}

echo "</tr></table>";

/* ===== SUBJECT-WISE ===== */
$subj_maps = [];
foreach($exam_ids as $eid){
    if(!$eid) continue;
    $q = mysqli_query($conn, "SELECT subject_id, AVG(CASE WHEN marks='A' THEN NULL ELSE marks END) as avg_mark FROM marks WHERE exam_id='$eid'$fl_filter_sql GROUP BY subject_id");
    $map = [];
    while($r=mysqli_fetch_assoc($q)) $map[(int)$r['subject_id']] = round((float)$r['avg_mark'],2);
    $subj_maps[$eid] = $map;
}

$subj_q = mysqli_query($conn, "SELECT id, subject_name, stream FROM subjects ORDER BY subject_name ASC");

$subj_improved=0; $subj_declined=0; $subj_same=0;

echo "<h3 style='margin-top:14px;'>Subject-wise Comparison</h3>";
echo "<table class='compact-table'>";
echo "<tr><th>#</th><th>Subject</th>";
foreach($exam_ids as $eid){
    if(!$eid) continue;
    echo "<th>".htmlspecialchars($exam_lookup[$eid]??"Exam")." Avg</th>";
}
echo "<th>Change (E1→E2)</th>";
if($exam3) echo "<th>Change (E1→E3)</th>";
echo "</tr>";

$si=1;
while($sub=mysqli_fetch_assoc($subj_q)){
    $sid = (int)$sub['id'];
    $vals = [];
    foreach($exam_ids as $eid){
        if(!$eid) continue;
        $v = $subj_maps[$eid][$sid] ?? null;
        $vals[$eid] = $v;
    }

    $sub_display = $sub['subject_name'];
    if(!empty($sub['stream'])) $sub_display .= " ({$sub['stream']})";

    echo "<tr><td>$si</td><td style='text-align:left;'>$sub_display</td>";
    $first_v=null;
    foreach($exam_ids as $eid){
        if(!$eid) continue;
        $v = $vals[$eid];
        echo "<td>".($v!==null?$v:'—')."</td>";
        if($first_v===null && $v!==null) $first_v=$v;
    }

    // Changes
    $idx=0;
    foreach($exam_ids as $eid){
        if(!$eid || $idx===0) {$idx++; continue;}
        $v = $vals[$eid];
        if($v===null || $first_v===null){ echo "<td class='same'>—</td>"; continue; }
        if((float)$first_v==0) $chg = $v>0?100:0;
        else $chg = (($v-$first_v)/$first_v)*100;
        $chg = round($chg,1);
        $cls = $chg>0?'up':($chg<0?'down':'same');
        if($chg>0){$txt='+'.$chg.'%';$subj_improved++;}
        elseif($chg<0){$txt=$chg.'%';$subj_declined++;}
        else{$txt='0%';$subj_same++;}
        echo "<td class='$cls'>$txt</td>";
        $idx++;
    }
    echo "</tr>";
    $si++;
}
echo "</table>";
echo "<div class='summary-box'><b>Subject Summary:</b> Improved: $subj_improved &nbsp;|&nbsp; Declined: $subj_declined &nbsp;|&nbsp; No Change: $subj_same</div>";

/* ===== STUDENT-WISE ===== */
$stud_maps = [];
foreach($exam_ids as $eid){
    if(!$eid) continue;
    $q = mysqli_query($conn, "SELECT student_id, AVG(CASE WHEN marks='A' THEN NULL ELSE marks END) as avg_mark FROM marks WHERE exam_id='$eid'$fl_filter_sql GROUP BY student_id");
    $map = [];
    while($r=mysqli_fetch_assoc($q)) $map[(int)$r['student_id']] = round((float)$r['avg_mark'],2);
    $stud_maps[$eid] = $map;
}

$stud_sql = "SELECT id, first_name, second_name, last_name FROM students";
if ($form_level) {
    $stud_sql .= " WHERE id IN (SELECT DISTINCT student_id FROM marks WHERE form_level = '$form_level')";
}
$stud_sql .= " ORDER BY first_name ASC, second_name ASC, last_name ASC";
$stud_q = mysqli_query($conn, $stud_sql);

$improved=0; $declined=0; $same=0;

echo "<h3 style='margin-top:14px;'>Student-wise Comparison</h3>";
echo "<table class='compact-table'>";
echo "<tr><th>#</th><th>Student</th>";
foreach($exam_ids as $eid){
    if(!$eid) continue;
    echo "<th>".htmlspecialchars($exam_lookup[$eid]??"Exam")." Avg</th>";
}
echo "<th>Change (E1→E2)</th>";
if($exam3) echo "<th>Change (E1→E3)</th>";
echo "</tr>";

$si=1;
while($s=mysqli_fetch_assoc($stud_q)){
    $sid = (int)$s['id'];
    $sname = trim($s['first_name']." ".$s['second_name']." ".$s['last_name']);
    $vals = [];
    foreach($exam_ids as $eid){
        if(!$eid) continue;
        $v = $stud_maps[$eid][$sid] ?? null;
        $vals[$eid] = $v;
    }

    echo "<tr><td>$si</td><td style='text-align:left;'>".htmlspecialchars($sname)."</td>";
    $first_v=null;
    foreach($exam_ids as $eid){
        if(!$eid) continue;
        $v = $vals[$eid];
        echo "<td>".($v!==null?$v:'Abs')."</td>";
        if($first_v===null && $v!==null) $first_v=$v;
    }

    $idx=0;
    foreach($exam_ids as $eid){
        if(!$eid || $idx===0) {$idx++; continue;}
        $v = $vals[$eid];
        if($v===null || $first_v===null){ echo "<td class='same'>—</td>"; continue; }
        if((float)$first_v==0) $chg = $v>0?100:0;
        else $chg = (($v-$first_v)/$first_v)*100;
        $chg = round($chg,1);
        $cls = $chg>0?'up':($chg<0?'down':'same');
        if($chg>0){$txt='+'.$chg.'%';$improved++;}
        elseif($chg<0){$txt=$chg.'%';$declined++;}
        else{$txt='0%';$same++;}
        echo "<td class='$cls'>$txt</td>";
        $idx++;
    }
    echo "</tr>";
    $si++;
}
echo "</table>";
echo "<div class='summary-box'><b>Student Summary:</b> Improved: $improved &nbsp;|&nbsp; Declined: $declined &nbsp;|&nbsp; No Change: $same</div>";

} // end different exams check
echo "</div>";
endif; ?>

</div>

</body>
</html>