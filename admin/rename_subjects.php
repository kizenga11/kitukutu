<?php
include "../includes/config.php";

echo "<h2>Current Math-related subjects:</h2><table border='1' cellpadding='5'><tr><th>ID</th><th>Current Name</th><th>Short Name</th><th>Code</th><th>Stream</th></tr>";
$q = mysqli_query($conn, "SELECT id, subject_name, short_name, subject_code, stream FROM subjects WHERE subject_name LIKE '%Math%' OR subject_name LIKE '%math%' ORDER BY id");
$math_ids = [];
while ($r = mysqli_fetch_assoc($q)) {
    $math_ids[] = $r['id'];
    echo "<tr><td>{$r['id']}</td><td>{$r['subject_name']}</td><td>{$r['short_name']}</td><td>{$r['subject_code']}</td><td>{$r['stream']}</td></tr>";
}
echo "</table>";

echo "<h2>Current Business-related subjects:</h2><table border='1' cellpadding='5'><tr><th>ID</th><th>Current Name</th><th>Short Name</th><th>Code</th><th>Stream</th></tr>";
$q2 = mysqli_query($conn, "SELECT id, subject_name, short_name, subject_code, stream FROM subjects WHERE subject_name LIKE '%Business%' OR subject_name LIKE '%business%' ORDER BY id");
$bus_ids = [];
while ($r = mysqli_fetch_assoc($q2)) {
    $bus_ids[] = $r['id'];
    echo "<tr><td>{$r['id']}</td><td>{$r['subject_name']}</td><td>{$r['short_name']}</td><td>{$r['subject_code']}</td><td>{$r['stream']}</td></tr>";
}
echo "</table>";

if ($_GET['run'] === '1' && !empty($math_ids)) {
    $ids_str = implode(',', $math_ids);
    mysqli_query($conn, "UPDATE subjects SET subject_name='Math' WHERE id IN ($ids_str)");
    echo "<p style='color:green;font-weight:bold;'>✓ Math subjects renamed to 'Math'</p>";
}
if ($_GET['run'] === '1' && !empty($bus_ids)) {
    $ids_str = implode(',', $bus_ids);
    mysqli_query($conn, "UPDATE subjects SET subject_name='B/Studies' WHERE id IN ($ids_str)");
    echo "<p style='color:green;font-weight:bold;'>✓ Business subjects renamed to 'B/Studies'</p>";
}

$disabled = (empty($math_ids) && empty($bus_ids)) ? 'disabled' : '';
echo '<p><a href="rename_subjects.php?run=1" style="padding:8px 16px;background:#059669;color:#fff;text-decoration:none;border-radius:6px;' . ($disabled ? 'opacity:0.5;pointer-events:none;' : '') . '">Rename All to "Math" & "B/Studies"</a></p>';
echo '<p><a href="list_subjects.php">← View All Subjects</a></p>';
