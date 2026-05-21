<?php
include "../includes/config.php";
$stream = mysqli_real_escape_string($conn, $_GET['stream'] ?? '');
$result = mysqli_query($conn,"SELECT * FROM subjects WHERE stream='$stream' AND LOWER(category)='optional' ORDER BY subject_name");
$rows = [];
while ($r = mysqli_fetch_assoc($result)) $rows[] = $r;
if (empty($rows)) exit();
?>
<div style="margin-top:10px;margin-bottom:10px;">
  <div style="font-size:12px;font-weight:600;color:#6b7280;margin-bottom:6px;">Optional Subjects</div>
  <?php foreach ($rows as $row): ?>
  <label class="opt-subject-label">
    <input type="checkbox" name="optional_subjects[]" value="<?= $row['id'] ?>" style="accent-color:#4f46e5;width:15px;height:15px;">
    <?= htmlspecialchars($row['subject_name']) ?>
  </label>
  <?php endforeach; ?>
</div>
