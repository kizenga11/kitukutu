<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

$id = intval($_POST['id']);
$status = $_POST['action'];
$message = mysqli_real_escape_string($conn, $_POST['admin_message']);

mysqli_query($conn,"
UPDATE admissions
SET status='$status',
    admin_message='$message'
WHERE id='$id'
");

/* ── If approved, auto-create student record ── */
if ($status === 'Approved') {
    $adm = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM admissions WHERE id='$id'"));
    if ($adm) {
        $first = mysqli_real_escape_string($conn, trim($adm['first_name']));
        $middle = mysqli_real_escape_string($conn, trim($adm['middle_name'] ?? ''));
        $last = mysqli_real_escape_string($conn, trim($adm['last_name']));
        $gender = $adm['gender'] === 'M' ? 'Male' : 'Female';
        $entry_level = mysqli_real_escape_string($conn, $adm['entry_level']);
        $phone = mysqli_real_escape_string($conn, trim($adm['phone'] ?? ''));

        // Check if student already exists with same names
        $exists = mysqli_query($conn, "SELECT id FROM students WHERE first_name='$first' AND last_name='$last' LIMIT 1");
        if (mysqli_num_rows($exists) == 0) {
            // Determine stream based on entry level (default to General)
            $stream = 'General';

            mysqli_query($conn, "INSERT INTO students (first_name, second_name, last_name, sex, form_level, stream, parent_phone)
            VALUES ('$first', '$middle', '$last', '$gender', '$entry_level', '$stream', '$phone')");

            $student_id = mysqli_insert_id($conn);
            $regNo = 'KTTS-' . str_pad($student_id, 4, '0', STR_PAD_LEFT) . '-' . date('Y');
            mysqli_query($conn, "UPDATE students SET registration_no='" . mysqli_real_escape_string($conn, $regNo) . "' WHERE id=$student_id");

            // Auto-assign compulsory subjects for this stream
            $comp = mysqli_query($conn, "SELECT id FROM subjects WHERE LOWER(stream)=LOWER('$stream') AND LOWER(category)='compulsory'");
            while ($s = mysqli_fetch_assoc($comp)) {
                mysqli_query($conn, "INSERT IGNORE INTO student_subjects(student_id,subject_id) VALUES ('$student_id','{$s['id']}')");
            }
        }
    }
}

header("Location: admissions.php?success=1");
exit();
?>
