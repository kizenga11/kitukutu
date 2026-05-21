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
$del = mysqli_query($conn,"DELETE FROM exam_results_summary WHERE exam_id='$exam_id'");
if(!$del) die("Delete failed: ".mysqli_error($conn));

// Clear old summary JSON
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

$students = mysqli_query($conn,"SELECT DISTINCT student_id FROM marks WHERE exam_id='$exam_id'");
if(!$students) die("Students query failed: ".mysqli_error($conn));
$data=[];

while($st=mysqli_fetch_assoc($students)){
    $id = $st['student_id'];
    $q = mysqli_query($conn,"SELECT marks,subject_id FROM marks WHERE student_id='$id' AND exam_id='$exam_id'");
    if(!$q) die("Marks query failed: ".mysqli_error($conn));

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
        $data[]=["student_id"=>$id,"points"=>0,"avg"=>$avg,"grade"=>$grd,"division"=>"","total"=>$total];
    } else {
        $div = division($total_points);
        $data[]=["student_id"=>$id,"points"=>$total_points,"avg"=>$avg,"grade"=>$grd,"division"=>$div,"total"=>$total];
    }
}

// Sort by average descending only (ties share same rank)
usort($data,function($a,$b){
    return $b['avg'] <=> $a['avg'];
});

// Competition ranking: 1,2,2,4 (same avg = same rank, then skip)
$rank=1; $i=1; $prev_avg=null;
foreach($data as &$d){
    if($i>1 && $d['avg']!=$prev_avg) $rank=$i;
    $d['position']=$rank;
    $prev_avg=$d['avg'];
    $i++;
}

// Save to exam_results_summary
foreach($data as $d){
    $sid=intval($d['student_id']); $pts=intval($d['points']); $avg=floatval($d['avg']);
    $div=$d['division']; $pos=intval($d['position']); $tot=intval($d['total']);
    $grd=$d['grade'];
    mysqli_query($conn,"INSERT INTO exam_results_summary (student_id,exam_id,total_points,division,position,average_marks,total_marks,grade)
    VALUES ('$sid','$exam_id','$pts','$div','$pos','$avg','$tot','$grd')
    ON DUPLICATE KEY UPDATE total_points=VALUES(total_points),division=VALUES(division),position=VALUES(position),average_marks=VALUES(average_marks),total_marks=VALUES(total_marks),grade=VALUES(grade)");
}

/* ===== COMPUTE AND STORE SUMMARY JSON ===== */

// 1. Division counts by gender (only students with division set)
$divData = [];
$divQ = mysqli_query($conn,"SELECT ers.division,s.sex,COUNT(*) as c
FROM exam_results_summary ers JOIN students s ON s.id=ers.student_id
WHERE ers.exam_id='$exam_id' AND ers.division!='' AND ers.division IS NOT NULL
GROUP BY ers.division,s.sex");
while($d=mysqli_fetch_assoc($divQ)){
    $divName = $d['division'] ?: '0';
    if(!isset($divData[$divName])) $divData[$divName]=['boys'=>0,'girls'=>0,'total'=>0];
    $gender = strtolower($d['sex'])=='male' ? 'boys' : 'girls';
    $divData[$divName][$gender] = (int)$d['c'];
    $divData[$divName]['total'] += (int)$d['c'];
}

// 2. Grade counts & average per subject
$subjGrades = [];
$subjQ = mysqli_query($conn,"SELECT m.subject_id,sbj.short_name,m.marks
FROM marks m JOIN subjects sbj ON sbj.id=m.subject_id WHERE m.exam_id='$exam_id' AND m.marks!='A'");
$subjTotals = []; // subject_id => [sum, count]
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
// Compute average & grade per subject
foreach($subjTotals as $sid=>$t){
    $avg = $t['count'] ? round($t['sum']/$t['count'],2) : 0;
    $subjGrades[$sid]['avg'] = $avg;
    $subjGrades[$sid]['grade'] = grade($avg);
}
// Sort by avg descending, assign position
uasort($subjGrades,function($a,$b){
    return ($b['avg']??0) <=> ($a['avg']??0);
});
$pos=1;
foreach($subjGrades as &$sg){
    $sg['pos']=$pos++;
}

// 3. School summary
$schoolQ = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total, AVG(average_marks) as avg FROM exam_results_summary WHERE exam_id='$exam_id'"));
$schoolTotal = (int)$schoolQ['total'];
$schoolAvg = $schoolQ['avg'] ? round((float)$schoolQ['avg'],2) : 0;
$schoolGrade = grade($schoolAvg);

$summaryJson = json_encode([
    'divisions' => $divData,
    'subject_grades' => $subjGrades,
    'school' => ['total_students'=>$schoolTotal,'school_avg'=>$schoolAvg,'school_grade'=>$schoolGrade],
    'generated_at' => date('Y-m-d H:i:s')
]);

// Only write summary_json if column exists
$colCheck = mysqli_query($conn,"SHOW COLUMNS FROM exams LIKE 'summary_json'");
if($colCheck && mysqli_num_rows($colCheck)>0){
    mysqli_query($conn,"UPDATE exams SET summary_json='".mysqli_real_escape_string($conn,$summaryJson)."' WHERE id='$exam_id'");
}

$_SESSION['success']="Results processed successfully!";
header("Location: view_results.php?exam_id=$exam_id");
exit();
?>