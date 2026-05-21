<?php
session_start();
include "../includes/config.php";

if (!isset($_SESSION['teacher_id'])) {
    header("Location: ../login.php"); exit();
}

$exams = mysqli_query($conn,"SELECT id, exam_name, start_date, end_date FROM exams WHERE summary_json IS NOT NULL ORDER BY start_date DESC");
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Exam Results</title>
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
.status-p{color:#27ae60;font-weight:600;font-size:11px;}
.status-n{color:#95a5a6;font-weight:600;font-size:11px;}
.empty{text-align:center;padding:24px;color:#95a5a6;font-size:13px;}
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
    <h3>Exam Results</h3>
</div>

<div class="card">
    <?php if(mysqli_num_rows($exams) > 0): ?>
    <table>
        <tr><th>Exam</th><th>Period</th><th>Status</th><th></th></tr>
        <?php while($e=mysqli_fetch_assoc($exams)): ?>
        <tr>
            <td data-label="Exam" style="font-weight:600;"><?= htmlspecialchars($e['exam_name']) ?></td>
            <td data-label="Period"><?= htmlspecialchars($e['start_date']) ?> — <?= htmlspecialchars($e['end_date']) ?></td>
            <td data-label="Status"><span class="status-p">Processed</span></td>
            <td data-label="">
                <a href="view_results.php?exam_id=<?= $e['id'] ?>" class="btn btn-pr">View</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
    <div class="empty">No processed exam results available yet.</div>
    <?php endif; ?>
</div>

</div>

</body>
</html>
