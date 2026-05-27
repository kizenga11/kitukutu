<?php
session_start();
include "../includes/config.php";

if (!isset($_SESSION['parent_id'])) {
    header("Location: login.php");
    exit();
}

$parent_id = intval($_SESSION['parent_id']);
$student_id = intval($_GET['student_id'] ?? 0);

if (!$student_id) {
    echo "<div class='alert alert-warning'>Hakuna mwanafunzi aliyechaguliwa.</div>";
    exit();
}

$check = mysqli_query($conn, "SELECT id FROM parent_students WHERE parent_id='$parent_id' AND student_id='$student_id'");
if (!$check || mysqli_num_rows($check) == 0) {
    echo "<div class='alert alert-danger'>Huna ruhusa ya kuona matokeo ya mwanafunzi huyu.</div>";
    exit();
}

$stu = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM students WHERE id='$student_id'"));
if (!$stu) {
    echo "<div class='alert alert-danger'>Mwanafunzi hakuwa amepatikana.</div>";
    exit();
}

$exams = mysqli_query($conn, "
    SELECT e.id, e.exam_name, e.start_date, ers.average_marks, ers.position, ers.division, ers.total_points, ers.grade
    FROM exam_results_summary ers
    JOIN exams e ON e.id = ers.exam_id
    WHERE ers.student_id = '$student_id'
    ORDER BY e.start_date DESC
");

function gradeLet($m) {
    if ($m >= 75) return 'A';
    elseif ($m >= 65) return 'B';
    elseif ($m >= 45) return 'C';
    elseif ($m >= 30) return 'D';
    else return 'F';
}
function gradePoint($m) {
    if ($m >= 75) return 1;
    elseif ($m >= 65) return 2;
    elseif ($m >= 45) return 3;
    elseif ($m >= 30) return 4;
    else return 5;
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Matokeo · <?= htmlspecialchars($stu['first_name']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{--primary:#065f46;--bg:#f0fdf4;}
body{background:var(--bg);font-family:system-ui,-apple-system,sans-serif;padding:16px;}
h2{color:var(--primary);font-weight:700;font-size:1.2rem;}
table{font-size:0.88rem;margin-bottom:0;}
th{background:var(--primary);color:#fff;font-size:0.8rem;text-transform:uppercase;letter-spacing:.3px;}
.grade-a{color:#16a34a;font-weight:700;}
.grade-b{color:#2563eb;font-weight:700;}
.grade-c{color:#ca8a04;font-weight:700;}
.grade-d{color:#ea580c;font-weight:700;}
.grade-f{color:#dc2626;font-weight:700;}
.stu-info{background:#fff;border-radius:12px;padding:14px 16px;border:1px solid #e5e7eb;margin-bottom:14px;}
.stu-info .name{font-size:1.1rem;font-weight:700;color:#111827;}
.stu-info .det{font-size:0.85rem;color:#6b7280;}
.empty{text-align:center;padding:40px;color:#9ca3af;}
.exam-card{background:#fff;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:10px;overflow:hidden;}
.exam-hdr{display:flex;align-items:center;justify-content:space-between;padding:12px 14px;cursor:pointer;user-select:none;transition:background .15s;}
.exam-hdr:hover{background:#f0fdf4;}
.exam-hdr .left{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.exam-hdr .left .name{font-weight:700;color:#111827;}
.exam-hdr .left .avg{font-weight:700;font-size:1rem;}
.exam-hdr .chevron{transition:transform .2s;color:#9ca3af;}
.exam-hdr.open .chevron{transform:rotate(180deg);}
.exam-body{display:none;border-top:1px solid #e5e7eb;padding:10px 14px;}
.exam-body.open{display:block;}
.subj-table th{background:#065f46;padding:6px 8px;font-size:0.75rem;}
.subj-table td{padding:5px 8px;font-size:0.82rem;}
.subj-table tr:nth-child(even){background:#f8fafc;}
.gpa-row{background:#f0fdf4;font-weight:700;font-size:0.85rem;}
.gpa-row td{padding:6px 8px;border-top:2px solid var(--primary);}
</style>
</head>
<body>

<div class="stu-info d-flex justify-content-between align-items-center">
  <div>
    <div class="name"><?= htmlspecialchars($stu['first_name'].' '.($stu['second_name']?$stu['second_name'].' ':'').$stu['last_name']) ?></div>
    <div class="det"><?= htmlspecialchars($stu['form_level']) ?> &bull; <?= htmlspecialchars($stu['stream']) ?> &bull; <?= htmlspecialchars($stu['sex']) ?></div>
  </div>
  <a href="home.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Nyumbani</a>
</div>

<?php if (mysqli_num_rows($exams) == 0): ?>
<div class="empty">
  <i class="bi bi-inbox" style="font-size:2.5rem;display:block;margin-bottom:8px;"></i>
  <p>Hakuna matokeo ya mitihani bado.</p>
</div>
<?php else:
  $exam_count = 0;
  while ($e = mysqli_fetch_assoc($exams)):
    $exam_count++;
    $exam_id = $e['id'];
    $avg = floatval($e['average_marks']);
    if ($avg >= 75) $gclass = 'grade-a';
    elseif ($avg >= 65) $gclass = 'grade-b';
    elseif ($avg >= 45) $gclass = 'grade-c';
    elseif ($avg >= 30) $gclass = 'grade-d';
    else $gclass = 'grade-f';

    $subs = mysqli_query($conn, "
        SELECT sub.subject_name, sub.short_name, m.marks
        FROM marks m
        JOIN subjects sub ON sub.id = m.subject_id
        JOIN student_subjects ss ON ss.student_id = m.student_id AND ss.subject_id = m.subject_id
        WHERE m.exam_id = $exam_id AND m.student_id = $student_id
        ORDER BY sub.subject_name
    ");
?>
<div class="exam-card">
  <div class="exam-hdr" onclick="toggleExam(this)">
    <div class="left">
      <span class="name"><i class="bi bi-journal-text"></i> <?= htmlspecialchars($e['exam_name']) ?></span>
      <span class="badge bg-secondary"><?= htmlspecialchars(date('d/m/Y', strtotime($e['start_date']))) ?></span>
      <span class="avg <?= $gclass ?>"><?= number_format($avg, 1) ?>%</span>
      <span class="badge bg-secondary">Daraja: <?= htmlspecialchars($e['division'] ?: '-') ?></span>
      <span class="badge bg-secondary">Nafasi: <?= (int)$e['position'] ?></span>
      <span class="badge bg-secondary">Pointi: <?= (int)$e['total_points'] ?></span>
    </div>
    <i class="bi bi-chevron-down chevron"></i>
  </div>
  <div class="exam-body">
    <div class="table-responsive">
    <table class="subj-table table" style="width:100%;">
      <tr>
        <th style="text-align:left;width:50%;">Somo</th>
        <th>Alama</th>
        <th>Daraja</th>
        <th>Pointi</th>
      </tr>
      <?php
      $total_pts = 0;
      $total_subs = 0;
      while ($s = mysqli_fetch_assoc($subs)):
        $mk = floatval($s['marks']);
        $g = gradeLet($mk);
        $pt = gradePoint($mk);
        $total_pts += $pt;
        $total_subs++;
        $mark_class = $mk >= 75 ? 'grade-a' : ($mk >= 65 ? 'grade-b' : ($mk >= 45 ? 'grade-c' : ($mk >= 30 ? 'grade-d' : 'grade-f')));
      ?>
      <tr>
        <td style="text-align:left;"><?= htmlspecialchars($s['subject_name']) ?></td>
        <td class="<?= $mark_class ?>"><?= number_format($mk, 1) ?></td>
        <td><?= $g ?></td>
        <td><?= $pt ?></td>
      </tr>
      <?php endwhile; ?>
      <?php if ($total_subs > 0):
        $gpa = round($total_pts / $total_subs, 2);
      ?>
      <tr class="gpa-row">
        <td colspan="4">
          <div class="d-flex justify-content-between">
            <span><strong>Jumla ya Pointi:</strong> <?= $total_pts ?> / <?= $total_subs * 5 ?></span>
            <span><strong>GPA:</strong> <?= number_format($gpa, 2) ?></span>
            <span><strong>Wastani:</strong> <?= number_format($avg, 1) ?>%</span>
            <span><strong>Daraja:</strong> <?= htmlspecialchars($e['division'] ?: '-') ?></span>
          </div>
        </td>
      </tr>
      <?php endif; ?>
    </table>
    </div>
  </div>
</div>
<?php endwhile; ?>
<?php endif; ?>

<script>
function toggleExam(hdr) {
  hdr.classList.toggle('open');
  var body = hdr.nextElementSibling;
  body.classList.toggle('open');
}
// Open first exam by default
document.addEventListener('DOMContentLoaded', function() {
  var first = document.querySelector('.exam-hdr');
  if (first) { first.classList.add('open'); first.nextElementSibling.classList.add('open'); }
});
</script>

</body>
</html>
