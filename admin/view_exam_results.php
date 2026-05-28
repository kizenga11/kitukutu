<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

$exams = mysqli_query($conn,"SELECT id, exam_name FROM exams ORDER BY start_date DESC");
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Exam Results Dashboard</title>
<style>
@media print{.no-print{display:none!important;}}
</style>
</head>
<body>

<table border="1">
    <tr><th>Exam</th><th>Status</th><th>Actions</th></tr>
    <?php while($e=mysqli_fetch_assoc($exams)){
        $exam_id=$e['id'];
        $chk=mysqli_query($conn,"SELECT COUNT(*) total FROM exam_results_summary WHERE exam_id='$exam_id'");
        $processed=mysqli_fetch_assoc($chk)['total']>0;
    ?>
    <tr>
        <td><?= $e['exam_name'] ?></td>
        <td><?= $processed?'Processed':'Not Processed' ?></td>
        <td class="no-print">
            <button onclick="confirmAction('process','<?= $exam_id ?>','<?= htmlspecialchars($e['exam_name'],ENT_QUOTES) ?>')">Process</button>
            <a href="view_results.php?exam_id=<?= $exam_id ?>">View</a>
            <a href="stream_results.php?exam_id=<?= $exam_id ?>&stream=General">Gen</a>
            <a href="stream_results.php?exam_id=<?= $exam_id ?>&stream=Vocational">Voc</a>
            <?php if($processed){ ?>
            <a href="bulk_student_results.php?exams%5B%5D=<?= $exam_id ?>">Multi</a>
            <button onclick="confirmAction('reprocess','<?= $exam_id ?>','<?= htmlspecialchars($e['exam_name'],ENT_QUOTES) ?>')">Reprocess</button>
            <?php } ?>
        </td>
    </tr>
    <?php } ?>
</table>

<div id="confirmModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:999;" onclick="closeModal(event)">
<div style="background:#fff;margin:100px auto;max-width:400px;padding:20px;border-radius:8px;" onclick="event.stopPropagation()">
    <h5 id="modalTitle">Confirm</h5>
    <p id="modalBody">Are you sure?</p>
    <p>
        <button onclick="document.getElementById('confirmModal').style.display='none'">Cancel</button>
        <a href="#" id="modalConfirmBtn" style="background:#27ae60;color:#fff;padding:5px 10px;text-decoration:none;">Yes, Process</a>
    </p>
</div>
</div>

<script>
function confirmAction(action, examId, examName){
    var isProcess = action === 'process';
    document.getElementById('modalTitle').textContent = isProcess ? 'Process Exam' : 'Reprocess Exam';
    document.getElementById('modalBody').innerHTML = 'Are you sure you want to ' + (isProcess ? 'process' : 'reprocess') + ' <strong>' + examName + '</strong>?';
    var btn = document.getElementById('modalConfirmBtn');
    btn.href = (isProcess ? 'process_results.php' : 'reprocess_results.php') + '?exam_id=' + examId;
    btn.textContent = isProcess ? 'Yes, Process' : 'Yes, Reprocess';
    btn.style.background = isProcess ? '#27ae60' : '#e74c3c';
    document.getElementById('confirmModal').style.display = 'block';
}
function closeModal(e){
    if(e.target === e.currentTarget) document.getElementById('confirmModal').style.display='none';
}
</script>

</body>
</html>
