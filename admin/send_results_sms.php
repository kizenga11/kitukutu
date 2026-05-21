<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include "../includes/config.php";

$school_name = "AMALI KITUKUTU";

/* AUTH */
if(!isset($_SESSION['teacher_id'])){
    header("Location: ../login.php");
    exit();
}

/* CURL CHECK */
if(!function_exists('curl_init')){
    die("cURL not enabled");
}

/* SEND SMS */
function sendSMS($phone,$message){
    $phone = preg_replace('/[^0-9]/','',$phone);

    if(substr($phone,0,1)=='0'){
        $phone = "255".substr($phone,1);
    }

    if(strlen($phone) < 12){
        return false;
    }

    $data = [
        "username"=>SMS_USERNAME,
        "to"=>$phone,
        "message"=>$message,
        "from"=>SMS_SENDER
    ];

    $headers = [
        "apiKey: ".SMS_API_KEY,
        "Content-Type: application/x-www-form-urlencoded"
    ];

    $ch = curl_init("https://api.africastalking.com/version1/messaging");

    curl_setopt($ch, CURLOPT_POST,true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

/* POINTS */
function calculatePoints($student_id,$exam_id,$conn){
$q=mysqli_query($conn,"
SELECT marks FROM marks
WHERE student_id='$student_id'
AND exam_id='$exam_id'
AND marks!='A'
");

$points=[];
while($r=mysqli_fetch_assoc($q)){
if(!is_numeric($r['marks'])) continue;

$m=floatval($r['marks']);

if($m>=75)$points[]=1;
elseif($m>=65)$points[]=2;
elseif($m>=45)$points[]=3;
elseif($m>=30)$points[]=4;
else $points[]=5;
}

sort($points);
return array_sum(array_slice($points,0,7));
}

/* DIVISION */
function getDivision($points){
if($points<=17)return "I";
elseif($points<=21)return "II";
elseif($points<=25)return "III";
elseif($points<=33)return "IV";
else return "0";
}

/* TREND */
function getTrend($student_id,$exam_id,$conn){
$q=mysqli_query($conn,"
SELECT average_marks FROM exam_results_summary
WHERE student_id='$student_id'
ORDER BY exam_id DESC
LIMIT 2
");

$data=[];
while($r=mysqli_fetch_assoc($q)){
$data[]=$r;
}

if(count($data)<2)return "";

$current=$data[0]['average_marks'];
$previous=$data[1]['average_marks'];

if($current>$previous)return "amepanda";
elseif($current<$previous)return "ameshuka";
else return "";
}

/* SEND RESULTS */
if(isset($_POST['send']) && !empty($_POST['sms'])){
$success=0;
foreach($_POST['sms'] as $item){
list($phone,$msg)=explode("||",$item);
if(sendSMS($phone,$msg)) $success++;
}
$_SESSION['flash']="SMS zimetumwa: $success";
header("Location: ".$_SERVER['PHP_SELF']."?exam_id=".$_GET['exam_id']);
exit();
}

/* CUSTOM SMS */
if(isset($_POST['send_custom']) && !empty($_POST['sms'])){
$msg=$school_name.": ".$_POST['custom_message'];
$success=0;
foreach($_POST['sms'] as $item){
list($phone,$old)=explode("||",$item);
if(sendSMS($phone,$msg)) $success++;
}
$_SESSION['flash']="Ujumbe umetumwa: $success";
header("Location: ".$_SERVER['PHP_SELF']);
exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SMS</title>

<style>
body{font-family:system-ui;background:#f4f6f9;margin:0;}
.topbar{display:flex;align-items:center;gap:10px;padding:10px;background:white;border-bottom:1px solid #e2e8f0;position:sticky;top:0;}
.back-btn{border:none;background:#1b3a5e;color:white;width:34px;height:34px;border-radius:50%;}
.school-name{font-weight:700;font-size:0.9rem;}
.page-title{font-size:0.75rem;color:#64748b;}
.container{padding:10px;max-width:900px;margin:auto;}
.card{background:white;padding:10px;border-radius:8px;margin-bottom:10px;}
table{width:100%;border-collapse:collapse;font-size:0.8rem;}
th,td{padding:6px;border:1px solid #ddd;text-align:center;}
th{background:#1b3a5e;color:white;}
.btn{padding:8px;background:#27ae60;color:white;border:none;border-radius:6px;}
textarea{width:100%;height:80px;}
@media(max-width:768px){table,thead,tbody,tr,td{display:block;} td{text-align:left;}}
</style>
</head>

<body>

<div class="topbar">
<button onclick="goBack()" class="back-btn">←</button>
<div>
<div class="school-name">SHULE YA AMALI KITUKUTU</div>
<div class="page-title">Mfumo wa Ujumbe (SMS)</div>
</div>
</div>

<div class="container">

<div class="card">
<form method="GET">
<select name="exam_id" onchange="this.form.submit()">
<option value="">Chagua Mtihani</option>
<?php
$exams=mysqli_query($conn,"SELECT id,exam_name FROM exams");
while($e=mysqli_fetch_assoc($exams)){
$sel = ($_GET['exam_id']??'')==$e['id']?'selected':'';
echo "<option value='{$e['id']}' $sel>{$e['exam_name']}</option>";
}
?>
</select>
</form>
</div>

<?php
if(isset($_SESSION['flash'])){
echo "<div class='card'>{$_SESSION['flash']}</div>";
unset($_SESSION['flash']);
}

if(isset($_GET['exam_id']) && $_GET['exam_id']!=''){

$exam_id=(int)$_GET['exam_id'];

$q=mysqli_query($conn,"SELECT exam_name FROM exams WHERE id='$exam_id'");
$exam_name=mysqli_fetch_assoc($q)['exam_name'] ?? '';

$res=mysqli_query($conn,"
SELECT s.id as student_id,s.first_name,s.second_name,s.parent_phone,r.*
FROM exam_results_summary r
JOIN students s ON s.id=r.student_id
WHERE r.exam_id='$exam_id'
ORDER BY r.average_marks DESC
");

$students=[];
while($row=mysqli_fetch_assoc($res)){
$students[]=$row;
}

$total_students=count($students);

$rank=1;
$prev_avg=null;
$same_rank_count=0;
?>

<form method="POST">

<div class="card">
<table>
<tr>
<th><input type="checkbox" onclick="toggleAll(this)"></th>
<th>Jina</th>
<th>Simu</th>
<th>Ujumbe</th>
</tr>

<?php foreach($students as $st){

$name=$st['first_name']." ".$st['second_name'];
$phone=$st['parent_phone'];

$points=calculatePoints($st['student_id'],$exam_id,$conn);
$division=getDivision($points);
$trend=getTrend($st['student_id'],$exam_id,$conn);

/* FIXED RANK */
$current_avg = round($st['average_marks'],1);

if($prev_avg === null){
$current_rank=1;
$same_rank_count=1;
}else{
if($current_avg == $prev_avg){
$current_rank=$rank;
$same_rank_count++;
}else{
$rank += $same_rank_count;
$current_rank=$rank;
$same_rank_count=1;
}
}

$prev_avg = $current_avg;

$msg="$school_name: Habari, matokeo ya $exam_name: ".
"$name ameshika nafasi $current_rank kati ya wanfunzi $total_students, ".
"wastani ".number_format($st['average_marks'],1)."%, ".
"DIV $division, points $points".
($trend ? ", $trend" : "").
". Asante.";
?>

<tr>
<td><input type="checkbox" name="sms[]" value="<?= $phone ?>||<?= htmlspecialchars($msg) ?>"></td>
<td><?= $name ?></td>
<td><?= $phone ?></td>
<td><?= $msg ?></td>
</tr>

<?php } ?>

</table>

<br>
<button type="submit" name="send" class="btn">Tuma Matokeo</button>
</div>

<div class="card">
<h4>Tuma Ujumbe wa Kawaida</h4>
<textarea name="custom_message" placeholder="Andika ujumbe..."></textarea>
<br><br>
<button type="submit" name="send_custom" class="btn">Tuma Ujumbe</button>
</div>

</form>

<?php } ?>

</div>

<script>
function goBack(){
if(document.referrer !== ""){
history.back();
}else{
window.location.href="dashboard.php";
}
}

function toggleAll(source){
document.querySelectorAll("input[name='sms[]']").forEach(x=>x.checked=source.checked);
}
</script>

</body>
</html>