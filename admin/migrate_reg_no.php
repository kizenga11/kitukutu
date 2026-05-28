<?php
include "../includes/config.php";

$students = mysqli_query($conn,"SELECT id, registration_no FROM students ORDER BY id");
$updated = 0;
$year = date('Y');
$prefix = 'S.8486/' . $year . '/';

echo "<h2>Updating registration numbers...</h2><ul>";
while ($r = mysqli_fetch_assoc($students)) {
    $old = $r['registration_no'] ?? '';
    if (str_starts_with($old, $prefix)) continue;
    $seqQ = mysqli_query($conn,"SELECT MAX(CAST(SUBSTRING(registration_no, LENGTH('$prefix') + 1) AS UNSIGNED)) as max_seq FROM students WHERE registration_no LIKE '$prefix%'");
    $seqR = mysqli_fetch_assoc($seqQ);
    $seq = ($seqR['max_seq'] ?? 0) + 1;
    $regNo = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    $regNoEsc = mysqli_real_escape_string($conn, $regNo);
    mysqli_query($conn, "UPDATE students SET registration_no='$regNoEsc' WHERE id=" . $r['id']);
    echo "<li>ID {$r['id']}: " . htmlspecialchars($old) . " → " . htmlspecialchars($regNo) . "</li>";
    $updated++;
}
echo "</ul><p><strong>$updated students updated.</strong></p>";
echo '<p><a href="add_student.php">← Rudi kwa wanafunzi</a></p>';
