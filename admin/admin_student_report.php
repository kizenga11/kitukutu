<?php
/**
 * download_class_reports.php
 * 
 * Generates printable A4 reports for all students in a given exam.
 * Each student's report follows the same layout as the single student report.
 * 
 * Usage: download_class_reports.php?exam_id=X
 */

require_once "../includes/config.php";

// Helper function for grade points (same as original)
function gradePoint($m) {
    if ($m >= 75) return 1;
    elseif ($m >= 65) return 2;
    elseif ($m >= 45) return 3;
    elseif ($m >= 30) return 4;
    else return 5;
}

// Get exam_id from URL
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

// If no exam selected, show a selection form
if ($exam_id == 0) {
    // Fetch all exams that have results summary (non-empty)
    $exam_list_query = mysqli_query($conn, "
        SELECT DISTINCT e.id, e.exam_name, e.start_date 
        FROM exams e 
        INNER JOIN exam_results_summary ers ON e.id = ers.exam_id 
        ORDER BY e.start_date DESC, e.exam_name
    ");
    
    if (!$exam_list_query) {
        die("Error fetching exams: " . mysqli_error($conn));
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Select Exam - Class Reports</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f0f2f5;
                margin: 0;
                padding: 40px 20px;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
            }
            .card {
                background: white;
                border-radius: 12px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                padding: 30px;
                width: 100%;
                max-width: 500px;
                text-align: center;
            }
            h2 {
                color: #0b5e2e;
                margin-bottom: 20px;
            }
            select, button {
                width: 100%;
                padding: 12px;
                margin-top: 15px;
                border: 1px solid #ccc;
                border-radius: 6px;
                font-size: 16px;
            }
            button {
                background: #0b5e2e;
                color: white;
                border: none;
                cursor: pointer;
                font-weight: bold;
                transition: background 0.2s;
            }
            button:hover {
                background: #084d24;
            }
            .note {
                margin-top: 20px;
                font-size: 12px;
                color: #666;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <h2>📄 Generate Class Reports</h2>
            <form method="get" action="">
                <label for="exam_id">Select Examination:</label>
                <select name="exam_id" id="exam_id" required>
                    <option value="">-- Choose Exam --</option>
                    <?php while ($exam_row = mysqli_fetch_assoc($exam_list_query)): ?>
                        <option value="<?= $exam_row['id'] ?>">
                            <?= htmlspecialchars($exam_row['exam_name']) ?> (<?= $exam_row['start_date'] ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit">Generate Full Class Reports</button>
            </form>
            <div class="note">
                ⚡ This will generate a single document with all student reports.<br>
                Use browser's print (Ctrl+P) to save as PDF (A4 portrait).
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ----------------------------------------------------------------------
// Generate reports for all students in the selected exam
// ----------------------------------------------------------------------

// Fetch exam details
$exam_query = mysqli_query($conn, "SELECT exam_name, start_date, parent_message FROM exams WHERE id = $exam_id");
if (!$exam_query || mysqli_num_rows($exam_query) == 0) {
    die("Exam not found.");
}
$exam = mysqli_fetch_assoc($exam_query);

// Total number of students in this exam (for parent message)
$total_students_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM exam_results_summary WHERE exam_id = $exam_id");
$total_students = mysqli_fetch_assoc($total_students_query)['total'];

if ($total_students == 0) {
    die("No results found for this exam.");
}

// Fetch all students results summary for this exam (ordered by position, then name)
$all_summaries = mysqli_query($conn, "
    SELECT ers.*, s.first_name, s.second_name, s.last_name, s.sex, s.stream
    FROM exam_results_summary ers
    JOIN students s ON s.id = ers.student_id
    WHERE ers.exam_id = $exam_id
    ORDER BY ers.position ASC, s.first_name ASC
");

if (!$all_summaries) {
    die("Error fetching results: " . mysqli_error($conn));
}

// --------------------------------------------------------------
// Start HTML output (multi-page, each student one .page)
// --------------------------------------------------------------
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Class Reports - <?= htmlspecialchars($exam['exam_name']) ?></title>

<style>
    *{margin:0;padding:0;box-sizing:border-box;}
    body{background:#e2e3e5;font-family:'Times New Roman',Times,serif;padding:15px 0;}
    .page{
        width:210mm;min-height:297mm;background:#fff;
        margin:0 auto;padding:12mm 14mm 10mm 14mm;
        box-shadow:0 2px 8px rgba(0,0,0,0.1);position:relative;
        page-break-after:always;
    }
    .page:last-child{page-break-after:auto;}
    .hdr{text-align:center;margin-bottom:12px;border-bottom:2px solid #000;padding-bottom:8px;}
    .hdr .school{font-size:22px;font-weight:700;letter-spacing:1px;}
    .hdr .sub{font-size:14px;margin-top:3px;}
    .hdr .title{font-size:16px;font-weight:700;margin-top:4px;}
    .info{display:flex;flex-wrap:wrap;justify-content:space-between;padding:8px 0;margin:8px 0;border-top:1px solid #555;border-bottom:1px solid #555;font-size:13px;line-height:1.7;}
    table{width:100%;border-collapse:collapse;margin:8px 0;font-size:12px;}
    th,td{border:1px solid #000;padding:6px 6px;text-align:center;vertical-align:middle;}
    th{background:#f0f0f0;font-size:12px;font-weight:700;}
    .subject-table td:first-child{text-align:left;padding-left:10px;}
    .sec-title{margin:10px 0 5px;font-weight:700;font-size:14px;text-align:center;}
    .comment-box{padding:8px 12px;margin:5px 0;font-size:12px;line-height:1.6;border-left:4px solid #2c6e2f;background:#faf9f8;}
    .sign-row{margin-top:28px;display:flex;justify-content:space-between;font-size:11px;text-align:center;}
    .sign-item{width:30%;}
    .behavior-table td,.behavior-table th{padding:3px 4px;font-size:10px;}
    .behavior-table td:first-child{width:35px;}
    .gpa-info{font-size:12px;text-align:right;margin-top:-2px;margin-bottom:5px;padding:4px 6px;background:#f5f5f5;}
    .footer-date{font-size:10px;text-align:center;color:#666;margin-top:14px;border-top:1px solid #ddd;padding-top:8px;}
    .print-btn{position:fixed;bottom:20px;right:20px;padding:10px 20px;background:#0b5e2e;color:#fff;border:none;border-radius:40px;font-weight:700;cursor:pointer;font-size:14px;z-index:1000;box-shadow:0 4px 12px rgba(0,0,0,0.2);}
    @media print{
        @page{size:A4 portrait;margin:8mm 12mm;}
        body{background:#fff;margin:0;padding:0;font-size:11px;}
        .page{width:100%;min-height:100%;margin:0;padding:8mm 12mm 6mm 12mm;box-shadow:none;}
        .hdr .school{font-size:20px;}
        .hdr .title{font-size:15px;}
        .info{font-size:12px;padding:6px 0;}
        table{font-size:11px;margin:6px 0;}
        th,td{padding:5px 5px;}
        .sec-title{font-size:13px;margin:8px 0 4px;}
        .comment-box{font-size:11px;padding:6px 10px;}
        .sign-row{margin-top:22px;font-size:10px;}
        .gpa-info{font-size:11px;}
        .footer-date{font-size:9px;margin-top:12px;}
        .print-btn{display:none;}
    }
</style>
</head>
<body>

<?php
$student_counter = 0;
while ($sum = mysqli_fetch_assoc($all_summaries)) {
    $student_counter++;
    $student_id = $sum['student_id'];
    
    // Student details already in $sum (first_name, second_name, last_name, sex, stream)
    // but ensure we have all fields (joined from students)
    $full_name = trim($sum['first_name'] . " " . $sum['second_name'] . " " . $sum['last_name']);
    $sex = $sum['sex'];
    $stream = $sum['stream'];
    
    // Fetch marks for this student (exactly as original)
    $subs = mysqli_query($conn, "
        SELECT sub.subject_name, m.marks
        FROM marks m
        JOIN subjects sub ON sub.id = m.subject_id
        WHERE m.exam_id = $exam_id
        AND m.student_id = $student_id
        ORDER BY sub.subject_name
    ");
    
    // Compute GPA and subject points
    $total_points = 0;
    $total_sub = 0;
    $marks_data = [];
    while ($s = mysqli_fetch_assoc($subs)) {
        $mk = (float)$s['marks'];
        $point = gradePoint($mk);
        $total_points += $point;
        $total_sub++;
        
        // Determine grade letter
        if ($mk >= 75) $g = 'A';
        elseif ($mk >= 65) $g = 'B';
        elseif ($mk >= 45) $g = 'C';
        elseif ($mk >= 30) $g = 'D';
        else $g = 'F';
        
        $marks_data[] = [
            'subject' => $s['subject_name'],
            'marks' => $mk,
            'grade' => $g,
            'point' => $point
        ];
    }
    
    $gpa = ($total_sub > 0) ? ($total_points / $total_sub) : 0;
    
    // School comment based on average_marks from summary table
    $avg = (float)$sum['average_marks'];
    if ($avg >= 75) {
        $comment = "Ufaulu wa juu sana. Hongera mwanafunzi. Endelea kudumisha ukakamali wako.";
    } elseif ($avg >= 65) {
        $comment = "Ufaulu mzuri. Ongeza juhudi kidogo ili kufikia ubora zaidi.";
    } elseif ($avg >= 45) {
        $comment = "Ufaulu wa wastani. Jitahidi zaidi ili kuboresha matokeo yako.";
    } elseif ($avg >= 30) {
        $comment = "Ufaulu wa chini. Mzazi ashirikiane na shule ili kumsaidia mwanafunzi.";
    } else {
        $comment = "Ufaulu hafifu. Mzazi anashauriwa kufika shuleni kwa mazungumzo ya kina.";
    }
    
    // Parent message (generate if empty in DB)
    $parent_msg = trim($exam['parent_message'] ?? '');
    if (empty($parent_msg)) {
        $parent_msg = trim($sum['parent_message'] ?? '');
    }
    if (empty($parent_msg)) {
        $exam_name_disp = $exam['exam_name'];
        $avg_marks = number_format($sum['average_marks'], 2);
        $division = $sum['division'];
        $position = $sum['position'];
        $parent_msg = "Mzazi mpendwa wa {$full_name}, matokeo ya '{$exam_name_disp}' yamehitimishwa. ";
        $parent_msg .= "Amepata wastani wa {$avg_marks}% na daraja la {$division}, nafasi ya {$position} kati ya wanafunzi {$total_students}. ";
        if ($avg >= 75) {
            $parent_msg .= "Hongera kwa matokeo bora. Endelea kumhimiza mwanafunzi kudumisha ukakamali huu.";
        } elseif ($avg >= 65) {
            $parent_msg .= "Matokeo mazuri. Msaidie mwanafunzi kuongeza muda wa kusoma nyumbani.";
        } elseif ($avg >= 45) {
            $parent_msg .= "Matokeo ya wastani. Hakikisha anafanya kazi za nyumbani na kujisomea zaidi.";
        } elseif ($avg >= 30) {
            $parent_msg .= "Matokeo dhaifu. Tafadhali wasiliana na mwalimu wa darasa ili kujua changamoto.";
        } else {
            $parent_msg .= "Matokeo duni sana. Inashauriwa kufika shuleni kwa ushauri na kufuatilia maendeleo.";
        }
    }

    // Output a new page for this student
    ?>
    <div class="page">
        <div class="hdr">
            <div class="school">SHULE YA AMALI KITUKUTU</div>
            <div class="sub">S.L.P 27, KITUKUTU - NJOMBE</div>
            <div class="title">RIPOTI YA MATOKEO — <?= strtoupper(htmlspecialchars($exam['exam_name'])) ?></div>
        </div>

        <div class="info">
            <div><b>Jina:</b> <?= htmlspecialchars($full_name) ?> &nbsp;|&nbsp; <b>Jinsia:</b> <?= htmlspecialchars($sex) ?> &nbsp;|&nbsp; <b>Mkondo:</b> <?= htmlspecialchars($stream) ?></div>
            <div><b>Tarehe:</b> <?= htmlspecialchars($exam['start_date']) ?> &nbsp;|&nbsp; <b>Wastani:</b> <?= number_format($sum['average_marks'],2) ?>%</div>
        </div>

        <table class="subject-table">
            <tr><th style="width:50%">SOMO</th><th style="width:20%">ALAMA</th><th style="width:15%">DARAJA</th><th style="width:15%">POINTI</th></tr>
            <?php foreach ($marks_data as $subject): ?>
            <tr>
                <td style="text-align:left"><?= htmlspecialchars($subject['subject']) ?></td>
                <td><?= $subject['marks'] ?></td>
                <td><?= $subject['grade'] ?></td>
                <td><?= $subject['point'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <div class="gpa-info">
            <b>GPA:</b> <?= number_format($gpa,2) ?>
            <?php if($sum['division']): ?> &nbsp;|&nbsp; <b>Division:</b> <?= htmlspecialchars($sum['division']) ?><?php endif; ?>
            &nbsp;|&nbsp; <b>Nafasi:</b> <?= (int)$sum['position'] . " / " . (int)$total_students ?>
            <?php if($sum['total_points']): ?> &nbsp;|&nbsp; <b>Jumla Pointi:</b> <?= (int)$sum['total_points'] ?><?php endif; ?>
        </div>

        <div class="sec-title">TABIA NA MWENENDO</div>
        <table class="behavior-table">
            <tr><th>NO</th><th>MAELEZO</th><th>ALAMA</th></tr>
            <tr><td>1</td><td>KUFANYA KAZI KWA BIDII</td><td>____</td></tr>
            <tr><td>2</td><td>KUPENDA KUHESHIMU NA KUTHAMINI KAZI</td><td>____</td></tr>
            <tr><td>3</td><td>UANGALIFU WA MALI ZA UMA</td><td>____</td></tr>
            <tr><td>4</td><td>UELEWA NA USHIRIKIANO</td><td>____</td></tr>
            <tr><td>5</td><td>HESHIMA KWA WALIMU NA WANAFUNZI</td><td>____</td></tr>
            <tr><td>6</td><td>KUTII NA KUFUATA MAAGIZO</td><td>____</td></tr>
            <tr><td>7</td><td>USAFI BINAFSI</td><td>____</td></tr>
            <tr><td>8</td><td>KUSHIRIKI SHUGHULI ZA UTAMADUNI</td><td>____</td></tr>
        </table>

        <div class="sec-title">MAONI YA SHULE</div>
        <div class="comment-box"><?= htmlspecialchars($comment) ?></div>

        <div class="sec-title">UJUMBE KWA MZAZI / MLEZI</div>
        <div class="comment-box" style="background:#fff6e5;padding:8px 10px;">
            <div style="display:flex;gap:14px;flex-wrap:wrap;font-size:10px;font-weight:700;color:#7a5800;border-bottom:1px solid #f0d080;margin-bottom:6px;padding-bottom:5px;">
                <span>📊 Pointi: <?= (int)$sum['total_points'] ?></span>
                <span>📈 GPA: <?= number_format($gpa,2) ?></span>
                <?php if($sum['division']): ?><span>🏅 Daraja: <?= htmlspecialchars($sum['division']) ?></span><?php endif; ?>
                <span>🎯 Nafasi: <?= (int)$sum['position'] ?> / <?= (int)$total_students ?></span>
                <span>📚 Wastani: <?= number_format($sum['average_marks'],1) ?>%</span>
            </div>
            <div style="font-size:11px;color:#5a4000;line-height:1.5;"><?= htmlspecialchars($parent_msg) ?></div>
        </div>

        <div class="sign-row">
            <div class="sign-item">_____________________<br><strong>MWALIMU WA DARASA</strong></div>
            <div class="sign-item">_____________________<br><strong>MWALIMU WA TAALUMA</strong></div>
            <div class="sign-item">_____________________<br><strong>MKUU WA SHULE</strong></div>
        </div>

        <div class="footer-date">Ripoti hii imetolewa na Ofisi ya Taaluma | Tarehe: <?= date('d/m/Y') ?></div>
    </div>
    <?php
} // end while students

// If no students found (should have been caught earlier, but just in case)
if ($student_counter == 0) {
    echo "<div style='padding: 20px; text-align: center;'>No student results found for this exam.</div>";
}
?>

<button onclick="window.print()" class="print-btn">🖨️ PRINT / SAVE AS PDF</button>

</body>
</html>