<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

$exam_id = intval($_GET['exam_id'] ?? 0);

if(!$exam_id){
    die("Invalid exam");
}

/* ===== CHECK IF ALREADY PROCESSED ===== */
$check = mysqli_query($conn,"
SELECT COUNT(*) as total 
FROM exam_results_summary 
WHERE exam_id='$exam_id'
");

$row = mysqli_fetch_assoc($check);

if($row['total'] > 0){
    die("⚠️ Results already processed for this exam.");
}

/* ===== FUNCTIONS ===== */

function safe_num($v){
    return is_numeric($v) ? (float)$v : 0;
}

function grade($m){
    if($m >= 75) return 'A';
    if($m >= 65) return 'B';
    if($m >= 45) return 'C';
    if($m >= 30) return 'D';
    return 'F';
}

function points($g){
    return ['A'=>1,'B'=>2,'C'=>3,'D'=>4,'F'=>5][$g] ?? 0;
}

function division($p){
    if($p <= 7) return 'I';
    if($p <= 12) return 'II';
    if($p <= 17) return 'III';
    if($p <= 21) return 'IV';
    return '0';
}

/* ===== GET STUDENTS ===== */
$students = mysqli_query($conn,"
SELECT DISTINCT m.student_id FROM marks m
JOIN student_subjects ss ON ss.student_id = m.student_id AND ss.subject_id = m.subject_id
WHERE m.exam_id='$exam_id'
");

$data = [];

while($st=mysqli_fetch_assoc($students)){

    $id = $st['student_id'];

    $q = mysqli_query($conn,"
    SELECT m.marks FROM marks m
    JOIN student_subjects ss ON ss.student_id = m.student_id AND ss.subject_id = m.subject_id
    WHERE m.student_id='$id' AND m.exam_id='$exam_id'
    ");

    $total=0; $count=0; $pts=[];

    while($m=mysqli_fetch_assoc($q)){

        $raw = $m['marks'];

        if($raw === 'A') continue; // skip absent

        $mark = safe_num($raw);

        $g = grade($mark);
        $p = points($g);

        $total += $mark;
        $count++;
        $pts[] = $p;
    }

    $avg = $count ? round($total/$count,1) : 0;

    $total_points = array_sum($pts);
    $div = division($total_points);

    $data[]=[
        "student_id"=>$id,
        "avg"=>$avg,
        "points"=>$total_points,
        "division"=>$div,
        "subjects"=>$count
    ];
}

/* ===== SORT (CORRECT RANKING) ===== */
usort($data,function($a,$b){
    if($a['points']==$b['points']){
        return $b['avg'] <=> $a['avg'];
    }
    return $a['points'] <=> $b['points'];
});

/* ===== ASSIGN POSITIONS ===== */
$rank=1;
foreach($data as &$d){
    $d['position']=$rank++;
}

/* ===== INSERT ===== */
foreach($data as $d){

    mysqli_query($conn,"
    INSERT INTO exam_results_summary 
    (student_id, exam_id, total_points, division, position, average_marks, subject_count, processed_at)
    VALUES
    ('{$d['student_id']}','$exam_id','{$d['points']}','{$d['division']}','{$d['position']}','{$d['avg']}','{$d['subjects']}',NOW())
    ");
}

$_SESSION['success']="Results processed successfully!";
header("Location: exam_results.php?exam_id=$exam_id");
exit();