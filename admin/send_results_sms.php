<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include "../includes/config.php";

$school_name = "KITUKUTU TECHNICAL SECONDARY SCHOOL";

/* AUTH */
if(!isset($_SESSION['admin_id']) && !isset($_SESSION['teacher_id'])){
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
        return ['ok'=>false, 'error'=>'Invalid phone number'];
    }
    $data = [
        "username"=>SMS_USERNAME,
        "to"=>$phone,
        "message"=>$message,
        "from"=>SMS_SENDER
    ];
    $headers = [
        "apiKey: " . SMS_API_KEY,
        "Accept: application/json",
        "Content-Type: application/x-www-form-urlencoded"
    ];
    $ch = curl_init("https://api.africastalking.com/version1/messaging");
    curl_setopt($ch, CURLOPT_POST,true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,10);
    curl_setopt($ch, CURLOPT_TIMEOUT,20);
    $response = curl_exec($ch);
    $curl_err = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curl_err) {
        return ['ok'=>false, 'error'=>"cURL: $curl_err"];
    }
    if ($http_code !== 201) {
        return ['ok'=>false, 'error'=>"HTTP $http_code: $response"];
    }

    // Try JSON first, then XML fallback
    $parsed = json_decode($response, true);
    if ($parsed && isset($parsed['SMSMessageData'])) {
        $recipients = $parsed['SMSMessageData']['Recipients'] ?? [];
        if (empty($recipients)) {
            $msg = $parsed['SMSMessageData']['Message'] ?? 'Unknown error';
            return ['ok'=>false, 'error'=>$msg];
        }
        $status = $recipients[0]['status'] ?? 'Failure';
        if ($status !== 'Success') {
            return ['ok'=>false, 'error'=>"Status: $status"];
        }
        return ['ok'=>true];
    }

    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($response);
    if ($xml === false || !isset($xml->SMSMessageData)) {
        return ['ok'=>false, 'error'=>"Invalid API response: $response"];
    }

    $recipients = $xml->SMSMessageData->Recipients;
    if (!isset($recipients->Recipient)) {
        $msg = (string)($xml->SMSMessageData->Message ?? 'Unknown error');
        return ['ok'=>false, 'error'=>$msg];
    }

    $r = $recipients->Recipient[0];
    $status = trim((string)$r->status);
    $statusCode = trim((string)$r->statusCode);

    if ($status !== 'Success' || $statusCode !== '100') {
        return ['ok'=>false, 'error'=>"Status: $status, Code: $statusCode"];
    }

    return ['ok'=>true];
}

/* SEND RESULTS */
if(isset($_POST['send']) && !empty($_POST['sms'])){
    $success=0;
    $errors=[];
    $err_detail=[];
    foreach($_POST['sms'] as $item){
        list($phone,$msg)=explode("||",$item);
        $resp = sendSMS($phone,$msg);
        if($resp['ok']){
            $success++;
        } else {
            $errors[] = $phone;
            $err_detail[] = $phone . " → " . ($resp['error'] ?? 'unknown');
        }
    }
    $flash = "SMS sent: $success / " . count($_POST['sms']);
    if (!empty($errors)) {
        $flash .= "<br>Failed (" . count($errors) . "): " . htmlspecialchars(implode("; ", array_slice($err_detail,0,3)));
        if (count($err_detail) > 3) $flash .= "; ... and " . (count($err_detail)-3) . " more";
    }
    $_SESSION['flash']=$flash;
    header("Location: ".$_SERVER['PHP_SELF']."?exam_id=".($_GET['exam_id']??'')."&form_level=".urlencode($_GET['form_level']??''));
    exit();
}

/* CUSTOM SMS */
if(isset($_POST['send_custom']) && !empty($_POST['sms'])){
    if (empty(trim($_POST['custom_message']))) {
        $_SESSION['flash']="Error: Message cannot be empty";
        header("Location: ".$_SERVER['PHP_SELF']. "?exam_id=".($_GET['exam_id']??'')."&form_level=".urlencode($_GET['form_level']??''));
        exit();
    }
    $msg=$school_name.": ".trim($_POST['custom_message']);
    $success=0;
    $errors=[];
    $err_detail=[];
    foreach($_POST['sms'] as $item){
        list($phone,$old)=explode("||",$item);
        $resp = sendSMS($phone,$msg);
        if($resp['ok']){
            $success++;
        } else {
            $errors[] = $phone;
            $err_detail[] = $phone . " → " . ($resp['error'] ?? 'unknown');
        }
    }
    $flash = "Message sent: $success / " . count($_POST['sms']);
    if (!empty($errors)) {
        $flash .= "<br>Failed (" . count($errors) . "): " . htmlspecialchars(implode("; ", array_slice($err_detail,0,3)));
        if (count($err_detail) > 3) $flash .= "; ... and " . (count($err_detail)-3) . " more";
    }
    $_SESSION['flash']=$flash;
    header("Location: ".$_SERVER['PHP_SELF']. "?exam_id=".($_GET['exam_id']??'')."&form_level=".urlencode($_GET['form_level']??''));
    exit();
}

$sel_exam = intval($_GET['exam_id'] ?? 0);
$sel_form = $_GET['form_level'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SMS Results</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{--primary:#1b3a5e;--bg:#f4f6f9;}
body{background:var(--bg);font-family:system-ui,-apple-system,sans-serif;margin:0;}
.topbar{
    background:linear-gradient(135deg,#1b3a5e,#16213e);color:#fff;
    padding:12px 16px;display:flex;align-items:center;gap:12px;
}
.topbar h5{margin:0;font-weight:700;font-size:15px;}
.topbar .sub{font-size:11px;opacity:.75;margin-top:1px;}
.content{max-width:1100px;margin:0 auto;padding:14px;}
.card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:14px;margin-bottom:12px;}
.sel-bar{display:flex;gap:10px;flex-wrap:wrap;align-items:end;}
.sel-bar .form-label{font-size:12px;font-weight:600;color:#6b7280;margin-bottom:2px;}
.sel-bar .form-select{font-size:13px;border-radius:8px;border:1.5px solid #e5e7eb;min-width:180px;}
.flash{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;}
.flash.err{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;}
table{width:100%;border-collapse:collapse;font-size:13px;}
th{background:var(--primary);color:#fff;padding:9px 7px;font-size:11px;text-transform:uppercase;letter-spacing:.3px;white-space:nowrap;}
td{padding:8px 7px;text-align:center;border-bottom:1px solid #e5e7eb;vertical-align:middle;}
tr:nth-child(even){background:#f8f9fc;}
.stu-name{text-align:left;font-weight:600;white-space:nowrap;}
.msg-preview{text-align:left;font-size:11px;color:#6b7280;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.msg-preview:hover{overflow:visible;white-space:normal;word-break:break-word;}
.form-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:start;}
@media(max-width:768px){
    .content{padding:10px;}
    table,thead,tbody,tr,td{display:block;}
    thead{display:none;}
    tr{margin-bottom:8px;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:6px 0;}
    td{display:flex;justify-content:space-between;align-items:center;padding:5px 10px;border:none;border-bottom:1px solid #f0f0f0;text-align:right;}
    td:last-child{border-bottom:none;}
    td::before{content:attr(data-label);font-weight:600;font-size:10px;color:#7f8c8d;text-align:left;min-width:80px;}
    .msg-preview{max-width:100%;}
}
@media print{
    .topbar,.sel-bar,.form-actions,.no-print{display:none!important;}
    body{background:#fff;padding:0;}
    .card{border:none;padding:0;}
}
</style>
</head>
<body>

<div class="topbar">
    <?php if ($sel_exam && $sel_form): ?>
    <span style="font-size:12px;background:rgba(255,255,255,.15);padding:4px 12px;border-radius:20px;">
        <?= str_replace('Form ','F. ',$sel_form) ?>
    </span>
    <?php endif; ?>
</div>

<div class="content">

<div class="card sel-bar">
    <form method="GET" class="row g-2 align-items-end w-100">
        <div class="col-auto">
            <label class="form-label">Exam</label>
            <select name="exam_id" class="form-select" onchange="this.form.submit()">
                <option value="">— Select —</option>
                <?php
                $exams = mysqli_query($conn,"SELECT id,exam_name FROM exams ORDER BY start_date DESC");
                while($e=mysqli_fetch_assoc($exams)):
                    $sel = $sel_exam == $e['id'] ? 'selected' : ''; ?>
                <option value="<?= $e['id'] ?>" <?= $sel ?>><?= htmlspecialchars($e['exam_name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label">Form Level</label>
            <select name="form_level" class="form-select" onchange="this.form.submit()">
                <option value="">All Forms</option>
                <?php if ($sel_exam):
                    $fl_q = mysqli_query($conn,"SELECT form_level FROM exam_form_levels WHERE exam_id='$sel_exam'");
                    while ($fl_r = mysqli_fetch_assoc($fl_q)):
                        $sel = $sel_form === $fl_r['form_level'] ? 'selected' : ''; ?>
                <option value="<?= $fl_r['form_level'] ?>" <?= $sel ?>><?= str_replace('Form ','F. ',$fl_r['form_level']) ?></option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>
    </form>
</div>

<?php
if(isset($_SESSION['flash'])){
    $is_err = strpos($_SESSION['flash'],'Error') !== false || stripos($_SESSION['flash'],'Failed') !== false;
    echo "<div class='card flash".($is_err?' err':'')."'>".$_SESSION['flash']."</div>";
    unset($_SESSION['flash']);
}

if ($sel_exam):

$en = mysqli_query($conn,"SELECT exam_name FROM exams WHERE id='$sel_exam'");
$exam_name = $en ? (mysqli_fetch_assoc($en)['exam_name'] ?? '') : '';

$fl_filter = $sel_form ? " AND ers.form_level='".mysqli_real_escape_string($conn,$sel_form)."'" : '';

$res = mysqli_query($conn,"
    SELECT s.id as student_id, s.first_name, s.second_name, s.last_name,
           s.parent_phone, s.form_level,
           ers.total_points, ers.division, ers.position, ers.average_marks
    FROM exam_results_summary ers
    JOIN students s ON s.id = ers.student_id
    WHERE ers.exam_id = '$sel_exam' AND s.is_active=1 $fl_filter
    ORDER BY ers.position ASC
");

if (!$res) {
    echo "<div class='card flash err'>Database error: " . htmlspecialchars(mysqli_error($conn)) . "</div>";
} elseif (mysqli_num_rows($res) === 0) {
    echo "<div class='card' style='text-align:center;padding:30px;color:#9ca3af;'>
        <i class='bi bi-inbox' style='font-size:2rem;display:block;margin-bottom:8px;'></i>
        No results found for this exam" . ($sel_form ? " (".str_replace('Form ','F. ',$sel_form).")" : "") . ".
        <br><small>Process results first if not yet done.</small>
    </div>";
} else {
    $students = [];
    while($row = mysqli_fetch_assoc($res)) $students[] = $row;
    $total_students = count($students);

    if ($total_students === 0) {
        echo "<div class='card' style='text-align:center;padding:30px;color:#9ca3af;'>No students found.</div>";
    } else {
?>
<form method="POST" onsubmit="return confirm('Send SMS to selected students?')">
<div style="margin-bottom:10px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
    <button type="submit" name="send" class="btn btn-sm" style="background:#10b981;color:#fff;border:none;border-radius:8px;padding:8px 18px;font-weight:600;">
        <i class="bi bi-send"></i> Send Results
    </button>
    <button type="submit" name="send_custom" class="btn btn-sm" style="background:#6366f1;color:#fff;border:none;border-radius:8px;padding:8px 18px;font-weight:600;">
        <i class="bi bi-chat-text"></i> Send Custom
    </button>
    <span class="no-print" style="font-size:12px;color:#6b7280;margin-left:4px;">
        <label><input type="checkbox" onclick="toggleAll(this)"> Select all</label>
        &middot; <?= $total_students ?> students
        <?php if ($sel_form): ?> &middot; <?= str_replace('Form ','F. ',$sel_form) ?><?php endif; ?>
    </span>
</div>

<?php if (isset($_POST['send_custom'])): ?>
<div class="card no-print" style="background:#eef2ff;border-color:#c7d2fe;">
    <label style="font-weight:600;font-size:13px;">Custom Message:</label>
    <textarea name="custom_message" class="form-control" rows="2" style="font-size:13px;margin-top:4px;" placeholder="Type message..."></textarea>
</div>
<?php endif; ?>

<div class="card" style="padding:0;overflow-x:auto;">
<table>
<tr>
    <th style="width:32px;"><input type="checkbox" onclick="toggleAll(this)"></th>
    <th>#</th>
    <th>Student</th>
    <th>Form</th>
    <th>Avg</th>
    <th>Div</th>
    <th>Pts</th>
    <th>Phone</th>
    <th>Message Preview</th>
</tr>
<?php foreach($students as $i => $st):
    $name = trim($st['first_name'] . ' ' . ($st['second_name'] ? $st['second_name'].' ' : '') . $st['last_name']);
    $phone = $st['parent_phone'] ?? '';
    $avg = number_format((float)$st['average_marks'],1);
    $div = $st['division'] !== '' && $st['division'] !== null ? $st['division'] : '-';
    $pts = (int)$st['total_points'];
    $pos = (int)$st['position'];
    $fl_display = str_replace('Form ','F. ',$st['form_level']);

    $trend = '';
    $tr_q = mysqli_fetch_assoc(mysqli_query($conn,"SELECT average_marks FROM exam_results_summary WHERE student_id='{$st['student_id']}' ORDER BY exam_id DESC LIMIT 2"));
    if ($tr_q) {
        $tr_q2 = mysqli_fetch_assoc(mysqli_query($conn,"SELECT average_marks FROM exam_results_summary WHERE student_id='{$st['student_id']}' AND exam_id<'$sel_exam' ORDER BY exam_id DESC LIMIT 1"));
        if ($tr_q2) {
            $prev = (float)$tr_q2['average_marks'];
            $curr = (float)$st['average_marks'];
            if ($curr > $prev) $trend = ", amepanda";
            elseif ($curr < $prev) $trend = ", ameshuka";
        }
    }

    $msg = "$school_name: Habari, matokeo ya $exam_name: ".
           "$name ameshika nafasi $pos kati ya wanafunzi $total_students, ".
           "wastani $avg%, DIV $div, points $pts".
           ($trend ? $trend : "").
           ". Asante.";
?>
<tr>
    <td data-label=""><input type="checkbox" name="sms[]" value="<?= htmlspecialchars($phone) ?>||<?= htmlspecialchars($msg) ?>"></td>
    <td data-label="#"><?= $i + 1 ?></td>
    <td data-label="Name" class="stu-name"><?= htmlspecialchars($name) ?></td>
    <td data-label="Form"><?= $fl_display ?></td>
    <td data-label="Avg"><?= $avg ?>%</td>
    <td data-label="Div"><?= htmlspecialchars($div) ?></td>
    <td data-label="Pts"><?= $pts ?></td>
    <td data-label="Phone"><?= htmlspecialchars($phone ?: '—') ?></td>
    <td data-label="Message" class="msg-preview"><?= htmlspecialchars($msg) ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;">
    <button type="submit" name="send" class="btn btn-sm" style="background:#10b981;color:#fff;border:none;border-radius:8px;padding:8px 18px;font-weight:600;">
        <i class="bi bi-send"></i> Send Results
    </button>
    <button type="submit" name="send_custom" class="btn btn-sm" style="background:#6366f1;color:#fff;border:none;border-radius:8px;padding:8px 18px;font-weight:600;">
        <i class="bi bi-chat-text"></i> Send Custom
    </button>
</div>

</form>
<?php } } endif; ?>

</div>

<script>
function toggleAll(source){
    document.querySelectorAll("input[name='sms[]']").forEach(x=>x.checked=source.checked);
}
</script>
</body>
</html>