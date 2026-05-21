<?php
include "includes/config.php";

// Search for ANDREA PHINIAS MASINI
echo "<h2>Searching for ANDREA PHINIAS MASINI</h2>";

$students_query = mysqli_query($conn, "
    SELECT id, first_name, second_name, last_name 
    FROM students 
    WHERE first_name='ANDREA' AND last_name='MASINI'
");

echo "<h3>Student Records Found:</h3>";
$student_ids = [];
while($student = mysqli_fetch_assoc($students_query)){
    echo "ID: {$student['id']} - {$student['first_name']} {$student['second_name']} {$student['last_name']}<br>";
    $student_ids[] = $student['id'];
}

if(empty($student_ids)){
    echo "No students found with that name.<br>";
} else {
    echo "<h3>Checking marks for each student:</h3>";
    
    foreach($student_ids as $student_id){
        echo "<h4>Student ID: $student_id</h4>";
        
        // Check marks table
        $marks_query = mysqli_query($conn, "
            SELECT exam_id, subject_id, marks 
            FROM marks 
            WHERE student_id='$student_id'
        ");
        
        $mark_count = mysqli_num_rows($marks_query);
        echo "Number of marks records: $mark_count<br>";
        
        if($mark_count > 0){
            echo "Marks found:<br>";
            while($mark = mysqli_fetch_assoc($marks_query)){
                echo "  Exam ID: {$mark['exam_id']}, Subject ID: {$mark['subject_id']}, Marks: {$mark['marks']}<br>";
            }
        } else {
            echo "No marks found for this student.<br>";
        }
        
        // Check student_subjects table
        $subjects_query = mysqli_query($conn, "
            SELECT subject_id 
            FROM student_subjects 
            WHERE student_id='$student_id'
        ");
        
        $subject_count = mysqli_num_rows($subjects_query);
        echo "Number of subject assignments: $subject_count<br>";
        
        echo "<hr>";
    }
}

mysqli_close($conn);
?>
