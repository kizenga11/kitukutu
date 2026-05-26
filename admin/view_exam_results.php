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
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:system-ui,-apple-system,'Segoe UI',Arial,sans-serif;background:#e8ecf1;padding:12px;font-size:13px;color:#1a1a2e;}
.container{max-width:800px;margin:auto;}
.card{background:#fff;border-radius:8px;padding:10px 12px;margin-bottom:10px;box-shadow:0 1px 3px rgba(0,0,0,0.06);}
.hdr{background:linear-gradient(135deg,#1a1a2e,#16213e);border-radius:8px;padding:10px 14px;margin-bottom:10px;color:#fff;}
.hdr h3{margin:0;font-size:14px;font-weight:700;}
table{width:100%;border-collapse:collapse;font-size:12px;}
th,td{padding:6px 5px;border:1px solid #ddd;text-align:center;vertical-align:middle;}
th{background:#1a1a2e;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;}
tr:nth-child(even){background:#f8f9fa;}
.btn{display:inline-flex;align-items:center;gap:4px;padding:5px 10px;border:none;border-radius:5px;font-size:11px;cursor:pointer;text-decoration:none;transition:all .15s;color:#fff;}
.btn-pr{background:#3498db;}.btn-pr:hover{background:#2980b9;}
.btn-gr{background:#27ae60;}.btn-gr:hover{background:#219a52;}
.btn-dr{background:#e74c3c;}.btn-dr:hover{background:#c0392b;}
.btn-sm{padding:3px 8px;font-size:10px;}
.status-p{color:#27ae60;font-weight:600;font-size:11px;}
.status-n{color:#e74c3c;font-weight:600;font-size:11px;}
.modal-content{border-radius:8px;}
.modal-header{background:#1a1a2e;color:#fff;padding:10px 14px;}
.modal-header .btn-close{filter:invert(1);}
.modal-body{padding:14px;text-align:center;font-size:13px;}
.modal-footer{padding:8px 12px;justify-content:center;}
@media(max-width:768px){
    body{padding:8px;}
    table,thead,tbody,th,td,tr{display:block;}
    thead{display:none;}
    tr{margin-bottom:8px;background:#fff;border:1px solid #ddd;border-radius:5px;padding:4px 0;}
    td{display:flex;justify-content:space-between;align-items:center;padding:5px 8px;border:none;border-bottom:1px solid #eee;text-align:right;}
    td:last-child{border-bottom:none;}
    td::before{content:attr(data-label);font-weight:600;font-size:10px;color:#7f8c8d;text-align:left;}
}
</style>
</head>
<body>

<div class="container">

<div class="hdr">
    <h3>Exam Results Dashboard</h3>
</div>

<div class="card">
    <table>
        <tr><th>Exam</th><th>Status</th><th>Actions</th></tr>
        <?php while($e=mysqli_fetch_assoc($exams)){
            $exam_id=$e['id'];
            $chk=mysqli_query($conn,"SELECT COUNT(*) total FROM exam_results_summary WHERE exam_id='$exam_id'");
            $processed=mysqli_fetch_assoc($chk)['total']>0;
        ?>
        <tr>
            <td data-label="Exam"><?= $e['exam_name'] ?></td>
            <td data-label="Status">
                <span class="<?= $processed?'status-p':'status-n' ?>"><?= $processed?'Processed':'Not Processed' ?></span>
            </td>
            <td data-label="Actions" style="white-space:nowrap;">
                <button class="btn btn-gr btn-sm" onclick="confirmAction('process','<?= $exam_id ?>','<?= htmlspecialchars($e['exam_name'],ENT_QUOTES) ?>')">Process</button>
                <a href="view_results.php?exam_id=<?= $exam_id ?>" class="btn btn-pr btn-sm">View</a>
                <a href="stream_results.php?exam_id=<?= $exam_id ?>&stream=General" class="btn btn-sm" style="background:#17a2b8;color:#fff;">Gen</a>
                <a href="stream_results.php?exam_id=<?= $exam_id ?>&stream=Vocational" class="btn btn-sm" style="background:#6f42c1;color:#fff;">Voc</a>
                <?php if($processed){ ?>
                <button class="btn btn-dr btn-sm" onclick="confirmAction('reprocess','<?= $exam_id ?>','<?= htmlspecialchars($e['exam_name'],ENT_QUOTES) ?>')">Reprocess</button>
                <?php } ?>
            </td>
        </tr>
        <?php } ?>
    </table>
</div>

</div>

<!-- CONFIRM MODAL -->
<div class="modal fade" id="confirmModal" tabindex="-1">
<div class="modal-dialog modal-sm modal-dialog-centered">
<div class="modal-content">
<div class="modal-header"><h5 class="modal-title" id="modalTitle">Confirm</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body" id="modalBody">Are you sure?</div>
<div class="modal-footer">
    <button type="button" class="btn btn-sm" style="background:#95a5a6;color:#fff;" data-bs-dismiss="modal">Cancel</button>
    <a href="#" id="modalConfirmBtn" class="btn btn-sm" style="background:#27ae60;color:#fff;">Yes, Process</a>
</div>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmAction(action, examId, examName){
    var isProcess = action === 'process';
    document.getElementById('modalTitle').textContent = isProcess ? 'Process Exam' : 'Reprocess Exam';
    document.getElementById('modalBody').innerHTML = 'Are you sure you want to ' + (isProcess ? 'process' : 'reprocess') + ' <strong>' + examName + '</strong>?';
    var btn = document.getElementById('modalConfirmBtn');
    btn.href = (isProcess ? 'process_results.php' : 'reprocess_results.php') + '?exam_id=' + examId;
    btn.className = 'btn btn-sm ' + (isProcess ? 'btn-gr' : 'btn-dr');
    btn.textContent = isProcess ? 'Yes, Process' : 'Yes, Reprocess';
    btn.style.background = isProcess ? '#27ae60' : '#e74c3c';
    new bootstrap.Modal(document.getElementById('confirmModal')).show();
}
</script>

</body>
</html>