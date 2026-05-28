<?php
include "../includes/config.php";
$q = mysqli_query($conn, "SELECT id, subject_name, short_name, subject_code, stream, category FROM subjects ORDER BY stream, subject_name");
echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Subject Name</th><th>Short Name</th><th>Code</th><th>Stream</th><th>Category</th></tr>";
while ($r = mysqli_fetch_assoc($q)) {
    echo "<tr><td>{$r['id']}</td><td>{$r['subject_name']}</td><td>{$r['short_name']}</td><td>{$r['subject_code']}</td><td>{$r['stream']}</td><td>{$r['category']}</td></tr>";
}
echo "</table>";
echo "<p><a href='add_student.php'>← Back</a></p>";
