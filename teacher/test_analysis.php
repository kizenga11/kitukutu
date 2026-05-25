<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['teacher_id'])){
header("Location: ../login.php");
exit();
}

$teacher_id = $_SESSION['teacher_id'];
$test_id = $_GET['test_id'] ?? 0;

/* =========================
GET TEST INFO
========================= */

$test = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT t.*, s.subject_name
FROM teacher_weekly_tests t
JOIN subjects s ON s.id=t.subject_id
WHERE t.id='$test_id'
AND t.teacher_id='$teacher_id'
"));

if(!$test){
die("Test not found.");
}

$subject_id = $test['subject_id'];
$class_stream = $test['class_stream'];
$subject_name = $test['subject_name'];
$test_name = $test['test_name'];

/* =========================
GET RESULTS
========================= */

$form_map = ['Form 1'=>'Form One','Form 2'=>'Form Two','Form 3'=>'Form Three','Form 4'=>'Form Four'];
$form_level = $form_map[$test['class_name']] ?? '';
$formFilter = $form_level ? " AND students.form_level='$form_level'" : '';

$results = mysqli_query($conn,"
SELECT 
students.id,
students.first_name,
students.second_name,
students.last_name,
students.sex,
students.form_level,
marks.marks
FROM marks
JOIN students ON students.id = marks.student_id
WHERE marks.subject_id='$subject_id'
AND marks.exam_id='$test_id' $formFilter
ORDER BY marks.marks DESC
") or die(mysqli_error($conn));


/* =========================
CALCULATIONS
========================= */

$total_marks = 0;
$total_students = 0;

$grade = [
"A"=>0,
"B"=>0,
"C"=>0,
"D"=>0,
"F"=>0
];

$male_total = 0;
$female_total = 0;
$male_count = 0;
$female_count = 0;

$data = [];

while($row = mysqli_fetch_assoc($results)){

$mark = $row['marks'];

$total_marks += $mark;
$total_students++;

if($mark >= 75){ $grade['A']++; }
elseif($mark >= 65){ $grade['B']++; }
elseif($mark >= 45){ $grade['C']++; }
elseif($mark >= 30){ $grade['D']++; }
else{ $grade['F']++; }

if($row['sex']=="Male"){
$male_total += $mark;
$male_count++;
}else{
$female_total += $mark;
$female_count++;
}

$data[] = $row;

}

$average = $total_students ? round($total_marks/$total_students,2) : 0;

$male_avg = $male_count ? round($male_total/$male_count,2) : 0;
$female_avg = $female_count ? round($female_total/$female_count,2) : 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Analysis - Kitukutu Secondary</title>
    <!-- Bootstrap 5 + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Font (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(145deg, #f0f5fa 0%, #e6ecf5 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            padding: 2rem 1rem;
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .school-card {
            background: white;
            border-radius: 28px;
            padding: 2rem 1.5rem;
            box-shadow: 0 20px 40px -12px rgba(0,20,50,0.15);
            margin-bottom: 2rem;
            border: 1px solid rgba(0,0,0,0.02);
        }
        .analysis-card {
            background: white;
            border-radius: 24px;
            padding: 1.8rem;
            box-shadow: 0 8px 20px rgba(0,0,0,0.02);
            border: 1px solid #f0f2f5;
            margin-bottom: 2rem;
        }
        .section-title {
            font-weight: 700;
            font-size: 1.5rem;
            color: #1a2b4c;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 1rem;
        }
        .section-title i {
            font-size: 2rem;
            color: #f4b400;
        }
        .test-info {
            background: #f8fafd;
            border-radius: 20px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
        }
        .test-info-item {
            font-size: 1rem;
            color: #2f405b;
        }
        .test-info-item i {
            color: #1e4a6d;
            margin-right: 8px;
        }
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
            border: 1px solid #f0f2f5;
            height: 100%;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(26,43,76,0.08);
        }
        .stat-card h5 {
            color: #5a6b89;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }
        .stat-card h3 {
            color: #1e4a6d;
            font-weight: 700;
            font-size: 2.2rem;
            margin: 0;
        }
        .grade-dist {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
            border: 1px solid #f0f2f5;
        }
        .grade-badge {
            background: #eef2fa;
            color: #1d2f50;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 30px;
            display: inline-block;
            min-width: 50px;
            text-align: center;
        }
        .table-custom {
            border-collapse: separate;
            border-spacing: 0 8px;
            width: 100%;
        }
        .table-custom thead th {
            background: #f8fafd;
            color: #1e2f50;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            padding: 1rem 0.75rem;
        }
        .table-custom tbody tr {
            background: white;
            border-radius: 18px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
            transition: all 0.2s;
        }
        .table-custom tbody tr:hover {
            box-shadow: 0 8px 16px rgba(26,43,76,0.08);
        }
        .table-custom td {
            padding: 1rem 0.75rem;
            vertical-align: middle;
            border: none;
            border-top: 1px solid #f0f3f7;
            color: #2f405b;
        }
        .btn-print {
            background: #1e4a6d;
            color: white;
            border-radius: 40px;
            padding: 0.5rem 1.8rem;
            font-weight: 600;
            border: none;
            transition: all 0.2s;
        }
        .btn-print:hover {
            background: #0f2b4b;
        }
        .btn-back {
            background: #6c757d;
            color: white;
            border-radius: 40px;
            padding: 0.5rem 1.8rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-back:hover {
            background: #5a6268;
        }
        /* mobile adjustments */
        @media (max-width: 768px) {
            .school-card {
                padding: 1.5rem;
            }
            .analysis-card {
                padding: 1.2rem;
            }
            .stat-card {
                margin-bottom: 1rem;
            }
            .test-info {
                flex-direction: column;
                align-items: start;
                gap: 0.5rem;
            }
            .table-custom thead {
                display: none;
            }
            .table-custom tbody tr {
                display: block;
                margin-bottom: 1.5rem;
                padding: 1rem;
            }
            .table-custom td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.7rem 0;
                border-bottom: 1px dashed #edf2f7;
            }
            .table-custom td:last-child {
                border-bottom: none;
            }
            .table-custom td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #5a6b89;
                width: 40%;
            }
        }
        /* print styles */
        @media print {
            .no-print, .btn-back, .btn-print { display: none; }
            body { background: white; padding: 1rem; }
            .school-card, .analysis-card { box-shadow: none; border: 1px solid #ccc; }
        }
    </style>
</head>
<body>
<div class="dashboard-container">

    <!-- School Header -->
    <div class="school-card d-flex flex-wrap align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <img src="../assets/logo.png" alt="School Logo" style="height: 70px; width: auto;">
            <div>
                <h3 class="text-primary mb-0 fw-bold">Kitukutu Secondary Technical School</h3>
                <p class="text-muted mb-0 mt-1"><i class="bi bi-quote"></i> Where Skills Become Careers</p>
            </div>
        </div>
        <div class="d-flex gap-2 no-print">
            <button onclick="window.print()" class="btn-print">
                <i class="bi bi-printer me-2"></i>Print
            </button>
            <a href="dashboard.php" class="btn-back">
                <i class="bi bi-arrow-left me-2"></i>Back
            </a>
        </div>
    </div>

    <!-- Main Analysis Card -->
    <div class="analysis-card">
        <div class="section-title">
            <i class="bi bi-bar-chart-fill"></i> Test Analysis
        </div>

        <!-- Test Info -->
        <div class="test-info">
            <span class="test-info-item"><i class="bi bi-file-text"></i> Test: <strong><?= htmlspecialchars($test_name) ?></strong></span>
            <span class="test-info-item"><i class="bi bi-book"></i> Subject: <strong><?= htmlspecialchars($subject_name) ?></strong></span>
            <span class="test-info-item"><i class="bi bi-people"></i> Stream: <strong><?= htmlspecialchars($class_stream) ?></strong></span>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <h5>Subject Average</h5>
                    <h3><?= $average ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <h5>Male Average</h5>
                    <h3><?= $male_avg ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <h5>Female Average</h5>
                    <h3><?= $female_avg ?></h3>
                </div>
            </div>
        </div>

        <!-- Grade Distribution -->
        <div class="grade-dist mb-4">
            <h5 class="mb-3"><i class="bi bi-pie-chart me-2"></i>Grade Distribution</h5>
            <div class="d-flex flex-wrap gap-3 justify-content-around">
                <div><span class="grade-badge">A</span> <strong><?= $grade['A'] ?></strong></div>
                <div><span class="grade-badge">B</span> <strong><?= $grade['B'] ?></strong></div>
                <div><span class="grade-badge">C</span> <strong><?= $grade['C'] ?></strong></div>
                <div><span class="grade-badge">D</span> <strong><?= $grade['D'] ?></strong></div>
                <div><span class="grade-badge">F</span> <strong><?= $grade['F'] ?></strong></div>
            </div>
        </div>

        <hr class="my-4">

        <h5 class="mb-3"><i class="bi bi-list-ul me-2"></i>Student Results</h5>

        <!-- Results Table -->
        <div class="table-responsive">
            <table class="table-custom w-100">
                <thead>
                    <tr>
                        <th>Position</th>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>Marks</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($data)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox" style="font-size:2.2rem;display:block;margin-bottom:8px;opacity:.25;"></i>
                            No marks entered for this test yet.
                        </td>
                    </tr>
                <?php else: $pos = 1; foreach($data as $row): ?>
                    <tr>
                        <td data-label="Position"><?= $pos++ ?></td>
                        <td data-label="Name"><?= htmlspecialchars($row['first_name']." ".$row['second_name']." ".$row['last_name']) ?></td>
                        <td data-label="Gender"><?= htmlspecialchars($row['sex']) ?></td>
                        <td data-label="Marks"><strong><?= $row['marks'] ?></strong></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <div class="text-center mt-4 no-print">
            <a href="dashboard.php" class="btn-back">
                <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Bootstrap JS (optional) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>