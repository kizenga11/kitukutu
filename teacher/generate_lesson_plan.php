<?php
session_start();
include "../includes/config.php";
include "../includes/teaching_docs_functions.php";
include "../includes/calendar_functions.php";

if (!isset($_SESSION['teacher_id'])) {
    header("Location: ../login.php");
    exit();
}
$teacher_id = intval($_SESSION['teacher_id']);
$teacher = mysqli_fetch_assoc(mysqli_query($conn, "SELECT first_name,last_name FROM teachers WHERE id='$teacher_id' LIMIT 1")) ?? [];

function toTime($min) {
    return sprintf('%02d:%02d', intdiv($min, 60), $min % 60);
}

function formName($class) {
    $map = ['Form I' => 'Form One', 'Form II' => 'Form Two', 'Form III' => 'Form Three', 'Form IV' => 'Form Four'];
    return $map[$class] ?? $class;
}

$generated = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $schoolName = mysqli_real_escape_string($conn, $_POST['schoolName']);
    $teacherName = mysqli_real_escape_string($conn, $_POST['teacherName']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $class = mysqli_real_escape_string($conn, $_POST['class']);
    $stream = mysqli_real_escape_string($conn, $_POST['stream']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $periods = intval($_POST['periods']);
    $startTime = $_POST['startTime'];
    $topic = mysqli_real_escape_string($conn, $_POST['topic']);
    $mainCompetence = mysqli_real_escape_string($conn, $_POST['mainCompetence']);
    $specificCompetence = mysqli_real_escape_string($conn, $_POST['specificCompetence']);
    $mainActivity = mysqli_real_escape_string($conn, $_POST['mainActivity']);
    $specificActivity = mysqli_real_escape_string($conn, $_POST['specificActivity']);
    $actionWord = mysqli_real_escape_string($conn, $_POST['actionWord']);
    $tlResources = mysqli_real_escape_string($conn, $_POST['tlResources']);
    $girlsReg = intval($_POST['girlsReg']);
    $boysReg = intval($_POST['boysReg']);
    $girlsPres = intval($_POST['girlsPres']);
    $boysPres = intval($_POST['boysPres']);
    $introType = $_POST['introType'];

    $totalMinutes = $periods * 40;
    $remaining = $totalMinutes - 5;
    $competenceTime = (int)round($remaining * 0.6);
    $designTime = (int)round($remaining * 0.25);
    $realizationTime = (int)round($remaining * 0.15);

    $startParts = explode(':', $startTime);
    $startMinutes = intval($startParts[0]) * 60 + intval($startParts[1]);
    $endMinutes = $startMinutes + $totalMinutes;
    $endTime = toTime($endMinutes);
    $timeStr = date('h:i A', strtotime($startTime)) . ' - ' . date('h:i A', strtotime($endTime));

    $introActivities = [
        'Question and Answer' => "Ask students questions related to $topic to assess their prior knowledge",
        'Brainstorming' => "Guide students to brainstorm ideas related to $topic",
        'Review of Previous Lesson' => "Review the previous lesson related to $topic",
        'Story Telling' => "Tell a story related to $topic",
        'Demonstration' => "Demonstrate something related to $topic",
        'Discussion' => "Lead a short discussion about $topic",
    ];

    $introTeaching = $introActivities[$introType] ?? $introActivities['Question and Answer'];
    $introLearning = match ($introType) {
        'Question and Answer' => "Students answer questions related to $topic",
        'Brainstorming' => "Students share ideas during brainstorming about $topic",
        'Review of Previous Lesson' => "Students respond and recall the previous lesson on $topic",
        'Story Telling' => "Students listen and respond to the story about $topic",
        'Demonstration' => "Students observe the demonstration related to $topic",
        'Discussion' => "Students participate in the discussion about $topic",
        default => "Students participate accordingly",
    };
    $introAssessment = "Questions about $topic are answered correctly";

    $compTeaching = "Provide students with T&L resources ($tlResources)\nGuide students to $actionWord $specificActivity\nUse group discussion / collaborative learning";
    $compLearning = "Students $actionWord $specificActivity in groups\nStudents share findings with the class";
    $compAssessment = "$specificActivity is correctly {$actionWord}ed";

    $designTeaching = "Ask students in groups to $actionWord $specificActivity\nGuide and supervise group work";
    $designLearning = "Students demonstrate $specificActivity in groups";
    $designAssessment = "$specificActivity are correctly answered";

    $realizationTeaching = "Ask each student individually to $actionWord $specificActivity\nProvide feedback";
    $realizationLearning = "Each student individually {$actionWord}s $specificActivity";
    $realizationAssessment = "$specificActivity are correctly answered";

    $remarks = "The students were able to $actionWord $specificActivity due to the use of interactive teaching and learning methods, activities and resources. However, some students failed to $actionWord $specificActivity. Therefore, I will clarify it next period.";

    $fName = formName($class);
    $reference = "Tanzania Institute of Education. (2023). $subject for secondary schools student's book, $fName. Tanzania Institute of Education.";

    $generated = [
        'header' => [
            'school' => $schoolName,
            'teacher' => $teacherName,
            'class' => $class,
            'stream' => $stream,
            'subject' => $subject,
            'time' => $timeStr,
            'date' => $date,
            'endTime' => $endTime,
        ],
        'students' => [
            'registered' => ['girls' => $girlsReg, 'boys' => $boysReg, 'total' => $girlsReg + $boysReg],
            'present' => ['girls' => $girlsPres, 'boys' => $boysPres, 'total' => $girlsPres + $boysPres],
        ],
        'syllabus' => [
            'mainCompetence' => $mainCompetence,
            'specificCompetence' => $specificCompetence,
            'mainActivity' => $mainActivity,
            'specificActivity' => $specificActivity,
            'tlResources' => $tlResources,
            'reference' => $reference,
        ],
        'stages' => [
            'introduction' => [
                'time' => 5,
                'teachingActivity' => $introTeaching,
                'learningActivity' => $introLearning,
                'assessment' => $introAssessment,
            ],
            'competenceDevelopment' => [
                'time' => $competenceTime,
                'teachingActivity' => $compTeaching,
                'learningActivity' => $compLearning,
                'assessment' => $compAssessment,
            ],
            'design' => [
                'time' => $designTime,
                'teachingActivity' => $designTeaching,
                'learningActivity' => $designLearning,
                'assessment' => $designAssessment,
            ],
            'realization' => [
                'time' => $realizationTime,
                'teachingActivity' => $realizationTeaching,
                'learningActivity' => $realizationLearning,
                'assessment' => $realizationAssessment,
            ],
        ],
        'remarks' => $remarks,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Generate Lesson Plan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{--primary:#065f46;--bg:#f0fdf4;}
body{background:var(--bg);font-family:system-ui,-apple-system,sans-serif;padding:14px;font-size:13px;color:#111827;}
.page-title{font-size:18px;font-weight:800;color:var(--primary);margin-bottom:12px;display:flex;align-items:center;gap:8px;}
.panel{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:14px;margin-bottom:12px;}
.sec-hdr{font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;display:flex;align-items:center;gap:6px;}
.f-group{margin-bottom:10px;}
.f-label{display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:3px;}
.f-input,.f-select{width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;background:#fff;}
.f-input:focus,.f-select:focus{border-color:var(--primary);outline:none;box-shadow:0 0 0 3px rgba(6,95,70,.1);}
textarea.f-input{resize:vertical;min-height:50px;}
.f-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.f-row3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;}
.btn-gen{background:var(--primary);color:#fff;padding:8px 28px;font-size:14px;font-weight:700;border:none;border-radius:10px;cursor:pointer;}
.btn-gen:hover{background:#047857;}
.btn-print{background:#1d4ed8;color:#fff;padding:8px 20px;font-size:13px;font-weight:600;border:none;border-radius:8px;cursor:pointer;}
.btn-print:hover{background:#1e40af;}
.plan-table{width:100%;border-collapse:collapse;font-size:12px;}
.plan-table td,.plan-table th{border:1px solid #d1d5db;padding:6px 10px;vertical-align:top;}
.plan-table th{background:var(--primary);color:#fff;font-weight:700;text-align:center;}
.plan-table .label{font-weight:700;background:#f9fafb;width:140px;}
.stage-row td:first-child{font-weight:700;background:#f0fdf4;width:100px;text-align:center;vertical-align:middle;}
.stage-time{font-size:11px;color:#6b7280;display:block;}
.json-box{background:#1f2937;color:#e5e7eb;padding:14px;border-radius:10px;font-family:'Courier New',monospace;font-size:11px;white-space:pre-wrap;overflow-x:auto;max-height:400px;overflow-y:auto;}
.copy-btn{background:#374151;color:#fff;border:none;padding:4px 12px;border-radius:6px;font-size:11px;cursor:pointer;float:right;}
.copy-btn:hover{background:#4b5563;}
@media print{body{background:#fff;}.panel{box-shadow:none;border:1px solid #d1d5db;}.btn-gen,.btn-print,.no-print{display:none!important;}}
</style>
</head>
<body>

<div class="page-title"><i class="bi bi-file-earmark-text"></i> Generate Lesson Plan (TIE 2023 Curriculum)</div>

<?php if (!$generated): ?>

<form method="POST">
<div class="row g-3">
  <div class="col-md-6">
    <div class="panel">
      <div class="sec-hdr"><i class="bi bi-info-circle"></i> School & Teacher Info</div>
      <div class="f-row">
        <div class="f-group"><label class="f-label">School Name</label><input class="f-input" name="schoolName" required placeholder="e.g. Kitukutu Secondary"></div>
        <div class="f-group"><label class="f-label">Teacher Name</label><input class="f-input" name="teacherName" required value="<?= htmlspecialchars(($teacher['first_name'] ?? '') . ' ' . ($teacher['last_name'] ?? '')) ?>"></div>
      </div>
      <div class="f-row">
        <div class="f-group"><label class="f-label">Date</label><input class="f-input" type="date" name="date" required value="<?= date('Y-m-d') ?>"></div>
        <div class="f-group"><label class="f-label">Starting Time</label><input class="f-input" type="time" name="startTime" required value="08:00"></div>
      </div>
      <div class="f-row3">
        <div class="f-group"><label class="f-label">Class</label><select class="f-select" name="class" required>
          <option value="">— Select —</option>
          <?php foreach (['Form I','Form II','Form III','Form IV'] as $f): ?>
          <option value="<?= $f ?>"><?= $f ?></option>
          <?php endforeach; ?>
        </select></div>
        <div class="f-group"><label class="f-label">Stream</label><input class="f-input" name="stream" placeholder="e.g. A"></div>
        <div class="f-group"><label class="f-label">Subject</label><input class="f-input" name="subject" required placeholder="e.g. Chemistry"></div>
      </div>
      <div class="f-row">
        <div class="f-group"><label class="f-label">Number of Periods</label><input class="f-input" type="number" name="periods" min="1" max="8" required value="1"></div>
        <div class="f-group"><label class="f-label">Total Time</label><input class="f-input" id="totalTimeDisplay" value="40 min" readonly style="background:#f3f4f6;"></div>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="panel">
      <div class="sec-hdr"><i class="bi bi-people"></i> Students</div>
      <div class="f-row">
        <div class="f-group"><label class="f-label">Girls Registered</label><input class="f-input" type="number" name="girlsReg" min="0" value="0" required></div>
        <div class="f-group"><label class="f-label">Boys Registered</label><input class="f-input" type="number" name="boysReg" min="0" value="0" required></div>
      </div>
      <div class="f-row">
        <div class="f-group"><label class="f-label">Girls Present</label><input class="f-input" type="number" name="girlsPres" min="0" value="0" required></div>
        <div class="f-group"><label class="f-label">Boys Present</label><input class="f-input" type="number" name="boysPres" min="0" value="0" required></div>
      </div>
    </div>
  </div>

  <div class="col-md-12">
    <div class="panel">
      <div class="sec-hdr"><i class="bi bi-journal-text"></i> Syllabus Details</div>
      <div class="f-row">
        <div class="f-group"><label class="f-label">Topic</label><input class="f-input" name="topic" required placeholder="Main topic"></div>
        <div class="f-group"><label class="f-label">Main Competence</label><input class="f-input" name="mainCompetence" required placeholder="e.g. Demonstrate understanding of..."></div>
      </div>
      <div class="f-row">
        <div class="f-group"><label class="f-label">Specific Competence</label><input class="f-input" name="specificCompetence" required placeholder="e.g. Identify..."></div>
        <div class="f-group"><label class="f-label">Main Activity</label><input class="f-input" name="mainActivity" required placeholder="e.g. Observing, Experimenting"></div>
      </div>
      <div class="f-row">
        <div class="f-group"><label class="f-label">Specific Activity</label><textarea class="f-input" name="specificActivity" rows="2" required placeholder="e.g. observing the reaction between acid and base"></textarea></div>
        <div class="f-group"><label class="f-label">Action Word</label><input class="f-input" name="actionWord" required placeholder="e.g. Explain, Describe, Calculate, Identify"></div>
      </div>
      <div class="f-row">
        <div class="f-group"><label class="f-label">T&L Resources</label><input class="f-input" name="tlResources" required placeholder="e.g. Charts, laboratory apparatus, textbooks"></div>
        <div class="f-group"><label class="f-label">Introduction Type</label><select class="f-select" name="introType" required>
          <option value="">— Select —</option>
          <option value="Question and Answer">Question and Answer</option>
          <option value="Brainstorming">Brainstorming</option>
          <option value="Review of Previous Lesson">Review of Previous Lesson</option>
          <option value="Story Telling">Story Telling</option>
          <option value="Demonstration">Demonstration</option>
          <option value="Discussion">Discussion</option>
        </select></div>
      </div>
    </div>
  </div>

  <div class="col-12 text-center no-print" style="margin-bottom:20px;">
    <button type="submit" name="generate" class="btn-gen"><i class="bi bi-magic"></i> Generate Lesson Plan</button>
  </div>
</div>
</form>

<script>
document.querySelector('select[name="class"]')?.addEventListener('change', function(){
  var s = document.querySelector('select[name="subject"]');
  var f = this.value;
  if (f) s.placeholder = 'e.g. ' + (f === 'Form I' || f === 'Form II' ? 'Basic Mathematics' : 'Chemistry');
});
document.querySelector('input[name="periods"]')?.addEventListener('input', function(){
  var total = parseInt(this.value || 1) * 40;
  document.getElementById('totalTimeDisplay').value = total + ' min (' + Math.floor(total/60) + 'h ' + (total%60) + 'min)';
});
document.querySelector('input[name="periods"]')?.dispatchEvent(new Event('input'));
</script>

<?php else: ?>

<div class="no-print" style="display:flex;gap:8px;margin-bottom:12px;">
  <button class="btn-print" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
  <button class="copy-btn" onclick="copyJson()">Copy JSON</button>
  <a href="generate_lesson_plan.php" class="btn-gen" style="padding:8px 20px;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;"><i class="bi bi-arrow-left"></i> New Plan</a>
</div>

<div class="panel">
  <div class="sec-hdr"><i class="bi bi-file-earmark-check"></i> LESSON PLAN — <?= htmlspecialchars($generated['header']['subject']) ?></div>

  <table class="plan-table">
    <tr><th colspan="2" style="font-size:14px;"><?= htmlspecialchars($generated['header']['school']) ?></th></tr>
    <tr><td class="label">Teacher</td><td><?= htmlspecialchars($generated['header']['teacher']) ?></td></tr>
    <tr><td class="label">Class</td><td><?= htmlspecialchars($generated['header']['class']) ?> Stream <?= htmlspecialchars($generated['header']['stream']) ?></td></tr>
    <tr><td class="label">Subject</td><td><?= htmlspecialchars($generated['header']['subject']) ?></td></tr>
    <tr><td class="label">Time</td><td><?= htmlspecialchars($generated['header']['time']) ?></td></tr>
    <tr><td class="label">Date</td><td><?= htmlspecialchars($generated['header']['date']) ?></td></tr>
  </table>

  <table class="plan-table" style="margin-top:8px;">
    <tr><th colspan="2">Students</th></tr>
    <tr><td class="label">Registered</td><td>Girls: <?= $generated['students']['registered']['girls'] ?>, Boys: <?= $generated['students']['registered']['boys'] ?>, Total: <?= $generated['students']['registered']['total'] ?></td></tr>
    <tr><td class="label">Present</td><td>Girls: <?= $generated['students']['present']['girls'] ?>, Boys: <?= $generated['students']['present']['boys'] ?>, Total: <?= $generated['students']['present']['total'] ?></td></tr>
  </table>

  <table class="plan-table" style="margin-top:8px;">
    <tr><th colspan="2">Syllabus Details</th></tr>
    <tr><td class="label">Main Competence</td><td><?= htmlspecialchars($generated['syllabus']['mainCompetence']) ?></td></tr>
    <tr><td class="label">Specific Competence</td><td><?= htmlspecialchars($generated['syllabus']['specificCompetence']) ?></td></tr>
    <tr><td class="label">Main Activity</td><td><?= htmlspecialchars($generated['syllabus']['mainActivity']) ?></td></tr>
    <tr><td class="label">Specific Activity</td><td><?= htmlspecialchars($generated['syllabus']['specificActivity']) ?></td></tr>
    <tr><td class="label">T&L Resources</td><td><?= htmlspecialchars($generated['syllabus']['tlResources']) ?></td></tr>
    <tr><td class="label">Reference</td><td style="font-style:italic;"><?= htmlspecialchars($generated['syllabus']['reference']) ?></td></tr>
  </table>

  <table class="plan-table" style="margin-top:8px;">
    <tr><th colspan="2">Lesson Stages</th></tr>
    <?php $stageLabels = [
      'introduction' => 'Introduction',
      'competenceDevelopment' => 'Competence Development',
      'design' => 'Design',
      'realization' => 'Realization',
    ]; ?>
    <?php foreach ($stageLabels as $key => $label):
      $s = $generated['stages'][$key];
    ?>
    <tr class="stage-row">
      <td><?= $label ?><span class="stage-time"><?= $s['time'] ?> min</span></td>
      <td>
        <strong>Teaching Activity:</strong><br><?= nl2br(htmlspecialchars($s['teachingActivity'])) ?><br><br>
        <strong>Learning Activity:</strong><br><?= nl2br(htmlspecialchars($s['learningActivity'])) ?><br><br>
        <strong>Assessment:</strong><br><?= htmlspecialchars($s['assessment']) ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>

  <table class="plan-table" style="margin-top:8px;">
    <tr><th colspan="2">Remarks</th></tr>
    <tr><td colspan="2"><?= htmlspecialchars($generated['remarks']) ?></td></tr>
  </table>
</div>

<div class="panel">
  <div class="sec-hdr"><i class="bi bi-code-slash"></i> JSON Output</div>
  <div class="json-box" id="jsonOutput"><?= json_encode($generated, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></div>
</div>

<script>
function copyJson() {
  var txt = document.getElementById('jsonOutput').textContent;
  navigator.clipboard.writeText(txt);
  var btn = document.querySelector('.copy-btn');
  btn.textContent = 'Copied!';
  setTimeout(function(){ btn.textContent = 'Copy JSON'; }, 2000);
}
</script>

<?php endif; ?>

</body>
</html>
