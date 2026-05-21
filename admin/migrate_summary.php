<?php
include "../includes/config.php";
$r = mysqli_query($conn, "SHOW COLUMNS FROM exams LIKE 'summary_json'");
if (mysqli_num_rows($r) > 0) {
    echo "Column already exists.";
} else {
    $sql = "ALTER TABLE exams ADD summary_json TEXT NULL AFTER school_message";
    if (mysqli_query($conn, $sql)) {
        echo "Column summary_json added successfully.";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>