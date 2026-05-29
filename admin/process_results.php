<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

session_start();
include "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

$exam_id = intval($_GET['exam_id'] ?? 0);
if(!$exam_id) die("Invalid exam");

// Delete old results
mysqli_query($conn,"DELETE FROM exam_results_summary WHERE exam_id='$exam_id'");
mysqli_query($conn,"UPDATE exams SET summary_json=NULL WHERE id='$exam_id'");

/* ===== FUNCTIONS ===== */
function grade($m){
    if($m>=75) return 'A';
    if($m>=65) return 'B';
    if($m>=45) return 'C';
    if($m>=30) return 'D';
    return 'F';
}
function points($g){
    return ['A'=>1,'B'=>2,'C'=>3,'D'=>4,'F'=>5][$g] ?? 0;
}
function division($p){
    if($p>=7 && $p<=17) return 'I';
    if($p>=18 && $p<=21) return 'II';
    if($p>=22 && $p<=25) return 'III';
    if($p>=26 && $p<=33) return 'IV';
    return '0';
}

/* ── Process one form level at a time ── */
$fl_q = mysqli_query($conn,"SELECT form_level FROM exam_form_levels WHERE exam_id='$exam_id'");
$form_levels = [];
if ($fl_q && mysqli_num_rows($fl_q)>0) {
    while ($fl_r = mysqli_fetch_assoc($fl_q)) $form_levels[] = $fl_r['form_level'];
}
if (empty($form_levels)) {
    $form_levels[] = 'Form One'; // fallback
}

$all_div_data = [];
$all_subj_grades = [];
$all_school_totals = 0;
$all_school_sum = 0;
$all_student_gpas = [];

foreach ($form_levels as $form_level) {
    // Get students for this form level (use student's current form_level)
    $students = mysqli_query($conn,"SELECT DISTINCT m.student_id FROM marks m JOIN students s ON s.id=m.student_id AND s.is_active=1 JOIN student_subjects ss ON ss.student_id=m.student_id AND ss.subject_id=m.subject_id WHERE m.exam_id='$exam_id' AND s.form_level='$form_level'");
    if (!$students) continue;

    $data=[];
    while($st=mysqli_fetch_assoc($students)){
        $id = $st['student_id'];
        $q = mysqli_query($conn,"SELECT marks,subject_id FROM marks WHERE student_id='$id' AND exam_id='$exam_id' AND subject_id IN (SELECT subject_id FROM student_subjects WHERE student_id='$id')");
        if(!$q) continue;

        $total=0; $count=0; $pts=[];
        while($m=mysqli_fetch_assoc($q)){
            $raw = $m['marks'];
            if($raw === 'A') continue;
            $mark = floatval($raw);
            $g = grade($mark);
            $p = points($g);
            $total += $mark;
            $count++;
            $pts[] = $p;
        }

        sort($pts);
        $best7 = array_slice($pts,0,7);
        $total_points = array_sum($best7);
        $avg = $count ? round($total/$count,2) : 0;
        $grd = $avg ? grade($avg) : '';

        if($count < 7){
            $data[]=["student_id"=>$id,"points"=>0,"avg"=>$avg,"grade"=>$grd,"division"=>"","total"=>$total,"form_level"=>$form_level];
        } else {
            $div = division($total_points);
            $student_gpa = round(array_sum($pts) / $count, 2);
            $all_student_gpas[] = $student_gpa;
            $data[]=["student_id"=>$id,"points"=>$total_points,"avg"=>$avg,"grade"=>$grd,"division"=>$div,"total"=>$total,"form_level"=>$form_level];
        }
    }

    // Sort by average descending
    usort($data,function($a,$b){
        return $b['avg'] <=> $a['avg'];
    });

    // Competition ranking: 1,2,2,4
    $rank=1; $i=1; $prev_avg=null;
    foreach($data as &$d){
        if($i>1 && $d['avg']!=$prev_avg) $rank=$i;
        $d['position']=$rank;
        $prev_avg=$d['avg'];
        $i++;
    }
    unset($d);

    // Save to exam_results_summary
    foreach($data as $d){
        $sid=intval($d['student_id']); $pts=intval($d['points']); $avg=floatval($d['avg']);
        $div=$d['division']; $pos=intval($d['position']); $tot=intval($d['total']);
        $grd=$d['grade']; $fl=$d['form_level'];
        mysqli_query($conn,"INSERT INTO exam_results_summary (student_id,exam_id,form_level,total_points,division,position,average_marks,total_marks,grade)
        VALUES ('$sid','$exam_id','$fl','$pts','$div','$pos','$avg','$tot','$grd')
        ON DUPLICATE KEY UPDATE form_level=VALUES(form_level),total_points=VALUES(total_points),division=VALUES(division),position=VALUES(position),average_marks=VALUES(average_marks),total_marks=VALUES(total_marks),grade=VALUES(grade)");
    }

    // Build division data per form
    $divQ = mysqli_query($conn,"SELECT ers.division,s.sex,COUNT(*) as c
    FROM exam_results_summary ers JOIN students s ON s.id=ers.student_id AND s.is_active=1
    WHERE ers.exam_id='$exam_id' AND ers.form_level='$form_level' AND ers.division!='' AND ers.division IS NOT NULL
    GROUP BY ers.division,s.sex");
    while($d=mysqli_fetch_assoc($divQ)){
        $divName = $d['division'] ?: '0';
        $key = $form_level.'|'.$divName;
        if(!isset($all_div_data[$key])) $all_div_data[$key]=['boys'=>0,'girls'=>0,'total'=>0,'form_level'=>$form_level,'division'=>$divName];
        $gender = strtolower($d['sex'])=='male' ? 'boys' : 'girls';
        $all_div_data[$key][$gender] = (int)$d['c'];
        $all_div_data[$key]['total'] += (int)$d['c'];
    }

    // School summary per form
    $schoolQ = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total, AVG(average_marks) as avg FROM exam_results_summary WHERE exam_id='$exam_id' AND form_level='$form_level'"));
    $all_school_totals += (int)$schoolQ['total'];
    $all_school_sum += (float)$schoolQ['avg'] * (int)$schoolQ['total'];
}

/* ===== COMPUTE AND STORE SUMMARY JSON ===== */
// Division counts (merged across forms)
$divData = [];
foreach ($all_div_data as $d) {
    $dn = $d['division'];
    if(!isset($divData[$dn])) $divData[$dn]=['boys'=>0,'girls'=>0,'total'=>0];
    $divData[$dn]['boys'] += $d['boys'];
    $divData[$dn]['girls'] += $d['girls'];
    $divData[$dn]['total'] += $d['total'];
}

// Grade counts & average per subject (merged across forms)
$subjGrades = [];
$subjQ = mysqli_query($conn,"SELECT m.subject_id,sbj.short_name,m.marks
FROM marks m JOIN subjects sbj ON sbj.id=m.subject_id JOIN student_subjects ss ON ss.student_id=m.student_id AND ss.subject_id=m.subject_id WHERE m.exam_id='$exam_id' AND m.marks!='A'");
$subjTotals = [];
while($s=mysqli_fetch_assoc($subjQ)){
    $sid = $s['subject_id'];
    $mark = floatval($s['marks']);
    $g = grade($mark);
    if(!isset($subjGrades[$sid])) $subjGrades[$sid]=['name'=>$s['short_name'],'A'=>0,'B'=>0,'C'=>0,'D'=>0,'F'=>0];
    $subjGrades[$sid][$g]++;
    if(!isset($subjTotals[$sid])) $subjTotals[$sid]=['sum'=>0,'count'=>0];
    $subjTotals[$sid]['sum'] += $mark;
    $subjTotals[$sid]['count']++;
}
foreach($subjTotals as $sid=>$t){
    $avg = $t['count'] ? round($t['sum']/$t['count'],2) : 0;
    $subjGrades[$sid]['avg'] = $avg;
    $subjGrades[$sid]['grade'] = grade($avg);
}

// NEW: Compute REG, SAT, PASS, GPA per subject
$extraQ = mysqli_query($conn,"SELECT m.subject_id, sbj.short_name,
    COUNT(*) as reg,
    SUM(CASE WHEN m.marks != 'A' THEN 1 ELSE 0 END) as sat,
    SUM(CASE
        WHEN m.marks != 'A' AND CAST(m.marks AS DECIMAL(5,1)) >= 75 THEN 1
        WHEN m.marks != 'A' AND CAST(m.marks AS DECIMAL(5,1)) >= 65 THEN 2
        WHEN m.marks != 'A' AND CAST(m.marks AS DECIMAL(5,1)) >= 45 THEN 3
        WHEN m.marks != 'A' AND CAST(m.marks AS DECIMAL(5,1)) >= 30 THEN 4
        WHEN m.marks != 'A' THEN 5
        ELSE 0
    END) as points_sum,
    SUM(CASE WHEN m.marks != 'A' AND CAST(m.marks AS DECIMAL(5,1)) >= 45 THEN 1 ELSE 0 END) as pass_count
FROM marks m JOIN subjects sbj ON sbj.id=m.subject_id JOIN student_subjects ss ON ss.student_id=m.student_id AND ss.subject_id=m.subject_id WHERE m.exam_id='$exam_id'
GROUP BY m.subject_id, sbj.short_name");
while($s=mysqli_fetch_assoc($extraQ)){
    $sid = $s['subject_id'];
    if(!isset($subjGrades[$sid])){
        $subjGrades[$sid]=['name'=>$s['short_name'],'A'=>0,'B'=>0,'C'=>0,'D'=>0,'F'=>0,'avg'=>0,'grade'=>'F','pos'=>0];
    }
    $sat = (int)$s['sat'];
    $subjGrades[$sid]['reg'] = (int)$s['reg'];
    $subjGrades[$sid]['sat'] = $sat;
    $subjGrades[$sid]['pass'] = (int)$s['pass_count'];
    $subjGrades[$sid]['gpa'] = $sat > 0 ? round($s['points_sum'] / $sat, 2) : 0;
}

uasort($subjGrades,function($a,$b){
    return ($b['avg']??0) <=> ($a['avg']??0);
});
$pos=1;
foreach($subjGrades as &$sg){
    $sg['pos']=$pos++;
}
unset($sg);

$schoolTotal = $all_school_totals;
$schoolAvg = $schoolTotal > 0 ? round($all_school_sum / $schoolTotal, 2) : 0;
$schoolGrade = grade($schoolAvg);

// Compute School GPA: avg of per-student GPA (each 1-5) for students with a division
$schoolGpa = !empty($all_student_gpas) ? round(array_sum($all_student_gpas) / count($all_student_gpas), 2) : 0;

$summaryJson = json_encode([
    'divisions' => $divData,
    'subject_grades' => $subjGrades,
    'school' => ['total_students'=>$schoolTotal,'school_avg'=>$schoolAvg,'school_grade'=>$schoolGrade,'school_gpa'=>$schoolGpa],
    'generated_at' => date('Y-m-d H:i:s')
]);

$colCheck = mysqli_query($conn,"SHOW COLUMNS FROM exams LIKE 'summary_json'");
if($colCheck && mysqli_num_rows($colCheck)>0){
    mysqli_query($conn,"UPDATE exams SET summary_json='".mysqli_real_escape_string($conn,$summaryJson)."' WHERE id='$exam_id'");
}

$_SESSION['success']="Results processed successfully!";
header("Location: view_results.php?exam_id=$exam_id");
exit();
?>
