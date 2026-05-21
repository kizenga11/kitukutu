<?php
require_once "../includes/config.php";
$col = mysqli_query($conn, "SHOW COLUMNS FROM exams LIKE 'parent_message'");
if (mysqli_num_rows($col) == 0) {
    mysqli_query($conn, "ALTER TABLE exams ADD COLUMN parent_message TEXT DEFAULT NULL AFTER summary_json");
    echo "<p style='color:green;'>✓ Column `parent_message` added to `exams` table.</p>";
} else {
    echo "<p style='color:blue;'>✓ Column `parent_message` already exists.</p>";
}
echo '<p><a href="view_exam_results.php">← Back to Exams</a></p>';
