<?php
/*
 * ============================================================
 *  DATABASE SETUP / MIGRATION SCRIPT
 *  Run this file ONCE from your browser after pushing to
 *  Railway (or any new environment) to apply DB changes.
 *
 *  Usage:   https://your-domain.com/setup.php
 *  Safety:  All statements use IF NOT EXISTS / idempotent
 *           checks, so it is safe to re-run.
 * ============================================================
 */

require_once __DIR__ . '/includes/config.php';

$results = []; // each item: ['msg'=>'...', 'type'=>'ok'|'err'|'skip']
$hasError = false;

// ──────────────────────────────────────────────────────────
//  Helper: check if a column exists
// ──────────────────────────────────────────────────────────
function columnExists($conn, $table, $column) {
    $q = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $q && mysqli_num_rows($q) > 0;
}

// ──────────────────────────────────────────────────────────
//  1. Add `name` column to `admins` (if missing)
// ──────────────────────────────────────────────────────────
if (!columnExists($conn, 'admins', 'name')) {
    $sql = "ALTER TABLE `admins` ADD `name` VARCHAR(100) AFTER `id`";
    if (mysqli_query($conn, $sql)) {
        $results[] = ['msg'=>'Added `name` column to `admins` table.', 'type'=>'ok'];
    } else {
        $results[] = ['msg'=>'Failed to add `name` column: ' . mysqli_error($conn), 'type'=>'err'];
        $hasError = true;
    }
} else {
    $results[] = ['msg'=>'`name` column already exists in `admins`.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  2. Add first_name, second_name, last_name, sex, phone to `admins`
// ──────────────────────────────────────────────────────────
$personalColumns = [
    ['first_name',  'VARCHAR(50) AFTER `id`'],
    ['second_name', 'VARCHAR(50) AFTER `first_name`'],
    ['last_name',   'VARCHAR(50) AFTER `second_name`'],
    ['sex',         "ENUM('M','F') AFTER `last_name`"],
    ['phone',       'VARCHAR(20) AFTER `sex`'],
];

foreach ($personalColumns as $col) {
    $colName = $col[0];
    $colDef  = $col[1];
    if (!columnExists($conn, 'admins', $colName)) {
        $sql = "ALTER TABLE `admins` ADD `$colName` $colDef";
        if (mysqli_query($conn, $sql)) {
            $results[] = ['msg'=>"Added `$colName` column to `admins`.", 'type'=>'ok'];
        } else {
            $results[] = ['msg'=>'Failed to add `' . $colName . '`: ' . mysqli_error($conn), 'type'=>'err'];
            $hasError = true;
        }
    } else {
        $results[] = ['msg'=>"`$colName` column already exists in `admins`.", 'type'=>'skip'];
    }
}

// ──────────────────────────────────────────────────────────
//  3. Update `role` enum to include 'academic'
// ──────────────────────────────────────────────────────────
$roleCheck = mysqli_query($conn, "SHOW COLUMNS FROM `admins` LIKE 'role'");
if ($roleCheck) {
    $roleCol = mysqli_fetch_assoc($roleCheck);
    $currentType = $roleCol['Type'] ?? '';
    if (strpos($currentType, 'academic') === false) {
        $sql = "ALTER TABLE `admins` MODIFY `role` ENUM('admin','headmaster','academic') DEFAULT 'admin'";
        if (mysqli_query($conn, $sql)) {
            $results[] = ['msg'=>"Updated `role` enum to include 'academic'.", 'type'=>'ok'];
        } else {
            $results[] = ['msg'=>'Failed to update `role` enum: ' . mysqli_error($conn), 'type'=>'err'];
            $hasError = true;
        }
    } else {
        $results[] = ['msg'=>"`role` enum already includes 'academic'.", 'type'=>'skip'];
    }
} else {
    $results[] = ['msg'=>'Could not check `role` column.', 'type'=>'skip'];
}

// ──────────────────────────────────────────────────────────
//  Done
// ──────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Database Setup · Kitukutu</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{
    font-family:system-ui,-apple-system,'Segoe UI',sans-serif;
    background:#f4f7fc;padding:20px;color:#111827;
}
.card{
    max-width:640px;margin:20px auto;
    background:#fff;border:1px solid #e5e7eb;
    border-radius:12px;overflow:hidden;
}
.hdr{
    background:linear-gradient(135deg,#1a1a2e,#16213e);
    color:#fff;padding:14px 18px;
}
.hdr h2{margin:0;font-size:16px;font-weight:700;}
.hdr p{margin:4px 0 0;font-size:12px;opacity:.8;}
.body{padding:16px;}
.result{padding:8px 12px;margin-bottom:6px;border-radius:8px;font-size:13px;}
.result.ok{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;}
.result.err{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;}
.result.skip{background:#fef3c7;color:#92400e;border:1px solid #fde68a;}
.summary{margin-top:12px;padding-top:12px;border-top:1px solid #e5e7eb;font-size:13px;color:#6b7280;}
.btn{display:inline-block;margin-top:12px;padding:8px 18px;background:#1d4ed8;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;}
.btn:hover{background:#1e40af;}
</style>
</head>
<body>
<div class="card">
    <div class="hdr">
        <h2>⚙ Kitukutu Database Setup</h2>
        <p>Migration script — <?= date('d-m-Y H:i') ?></p>
    </div>
    <div class="body">
        <?php foreach ($results as $r): ?>
            <?php if ($r['type'] === 'ok'): ?>
                <div class="result ok">✅ <?= $r['msg'] ?></div>
            <?php elseif ($r['type'] === 'err'): ?>
                <div class="result err">❌ <?= $r['msg'] ?></div>
            <?php else: ?>
                <div class="result skip">⏩ <?= $r['msg'] ?></div>
            <?php endif; ?>
        <?php endforeach; ?>

        <div class="summary">
            <?php if ($hasError): ?>
                <strong style="color:#dc2626;">⚠ Some migrations failed.</strong>
                Check the errors above and fix any issues.
            <?php else: ?>
                <strong style="color:#059669;">✅ All migrations completed successfully.</strong>
                You may now delete this file or keep it for future use.
            <?php endif; ?>
        </div>

        <a href="admin/dashboard.php" class="btn">← Go to Dashboard</a>
    </div>
</div>
</body>
</html>
