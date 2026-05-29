<?php
session_start();
include "../includes/config.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$check = mysqli_query($conn, "SELECT role FROM admins WHERE id='$admin_id'");
$data = mysqli_fetch_assoc($check);
if (($data['role'] ?? '') != 'admin') {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

$export_type = $_POST['export_type'] ?? 'both';

function esc($v) {
    if ($v === null) return 'NULL';
    return "'" . str_replace(["'", "\n", "\r"], ["''", '\n', '\r'], $v) . "'";
}

function streamDump($conn, $export_type) {
    global $db;

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="kitukutu_db_' . date('Y-m-d_H-i-s') . '.sql"');
    header('Cache-Control: no-cache, no-store, must-revalidate');

    $out = fopen('php://output', 'w');

    fwrite($out, "-- Kitukutu Database Export\n");
    fwrite($out, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
    fwrite($out, "-- Database: `" . $db . "`\n");
    fwrite($out, "-- Server: " . mysqli_get_server_info($conn) . "\n");
    fwrite($out, "--\n\n");
    fwrite($out, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
    fwrite($out, "SET AUTOCOMMIT = 0;\n");
    fwrite($out, "START TRANSACTION;\n");
    fwrite($out, "SET time_zone = '+00:00';\n\n");

    $tables = mysqli_query($conn, "SHOW TABLES");
    if (!$tables) {
        fwrite($out, "-- Error: " . mysqli_error($conn) . "\n");
        fclose($out);
        return;
    }

    while ($row = mysqli_fetch_array($tables)) {
        $table = $row[0];

        if ($export_type !== 'data') {
            $cr = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
            if ($cr) {
                $crr = mysqli_fetch_array($cr);
                fwrite($out, "--\n-- Table structure for table `$table`\n--\n");
                fwrite($out, "DROP TABLE IF EXISTS `$table`;\n");
                fwrite($out, $crr[1] . ";\n\n");
            } else {
                fwrite($out, "-- Error: " . mysqli_error($conn) . "\n");
            }
        }

        if ($export_type !== 'structure') {
            $res = mysqli_query($conn, "SELECT * FROM `$table`");
            if ($res && mysqli_num_rows($res) > 0) {
                $fields = mysqli_fetch_fields($res);
                $cols = [];
                foreach ($fields as $f) $cols[] = "`" . $f->name . "`";
                $col_str = implode(", ", $cols);

                fwrite($out, "--\n-- Dumping data for table `$table`\n--\n");

                $batch = [];
                $cnt = 0;
                mysqli_data_seek($res, 0);

                while ($it = mysqli_fetch_assoc($res)) {
                    $vals = [];
                    foreach ($fields as $f) $vals[] = esc($it[$f->name]);
                    $batch[] = "(" . implode(", ", $vals) . ")";
                    $cnt++;

                    if ($cnt % 200 === 0) {
                        fwrite($out, "INSERT INTO `$table` ($col_str) VALUES\n  " . implode(",\n  ", $batch) . ";\n");
                        $batch = [];
                    }
                }

                if (!empty($batch)) {
                    fwrite($out, "INSERT INTO `$table` ($col_str) VALUES\n  " . implode(",\n  ", $batch) . ";\n");
                }

                fwrite($out, "\n");
            }
        }
    }

    fwrite($out, "COMMIT;\n");
    fclose($out);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export'])) {
    set_time_limit(0);
    ini_set('memory_limit', '512M');
    ob_clean();
    flush();
    streamDump($conn, $export_type);
    exit();
}

$tables = mysqli_query($conn, "SHOW TABLE STATUS FROM `$db`");
$table_count = 0;
$total_rows = 0;
$total_size = 0;
if ($tables) {
    while ($t = mysqli_fetch_assoc($tables)) {
        $table_count++;
        $total_rows += $t['Rows'] ?? 0;
        $total_size += ($t['Data_length'] ?? 0) + ($t['Index_length'] ?? 0);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Database · Kitukutu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #f4f7fc;
            font-family: 'Inter', sans-serif;
            padding: 1.5rem;
            min-height: 100vh;
        }
        .dashboard-container { max-width: 800px; margin: 0 auto; }
        .header-card {
            background: white;
            border-radius: 28px;
            padding: 1.2rem 1.8rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px -8px rgba(0,20,40,0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
            border: 1px solid rgba(0,0,0,0.02);
        }
        .header-card .icon-wrap {
            width: 48px; height: 48px;
            background: linear-gradient(135deg,#1a2a4a,#2c3e6b);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 22px;
            flex-shrink: 0;
        }
        .header-card h2 { font-weight: 700; color: #0a1e32; font-size: 1.3rem; margin: 0; }
        .header-card p { color: #6b7280; font-size: 0.82rem; margin: 2px 0 0; }

        .card {
            background: white;
            border-radius: 28px;
            padding: 1.8rem;
            box-shadow: 0 10px 25px -8px rgba(0,20,40,0.1);
            border: 1px solid rgba(0,0,0,0.02);
            margin-bottom: 1.5rem;
        }
        .card-title {
            font-weight: 700; color: #0a1e32; font-size: 1rem;
            margin-bottom: 1.2rem; display: flex; align-items: center; gap: 0.5rem;
        }

        .form-label { font-weight: 600; font-size: 0.85rem; color: #374151; margin-bottom: 0.4rem; }
        .form-check { margin-bottom: 0.6rem; }
        .form-check-input:checked { background-color: #1a2a4a; border-color: #1a2a4a; }
        .form-check-label { font-size: 0.9rem; color: #374151; }
        .form-text { font-size: 0.78rem; color: #9ca3af; margin-top: 0.25rem; }

        .btn-export {
            background: linear-gradient(135deg,#1a2a4a,#2c3e6b);
            color: white; border: none; border-radius: 14px;
            padding: 0.7rem 2rem; font-weight: 600; font-size: 0.9rem;
            transition: all 0.2s; cursor: pointer;
        }
        .btn-export:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(26,42,74,0.3); }
        .btn-export:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .btn-back {
            display: inline-flex; align-items: center; gap: 0.3rem;
            background: white; color: #374151; border: 1px solid #e5e7eb;
            border-radius: 14px; padding: 0.7rem 1.5rem; font-weight: 500; font-size: 0.85rem;
            text-decoration: none; transition: all 0.2s;
        }
        .btn-back:hover { background: #f9fafb; color: #111827; }

        .db-stats {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 0.8rem; margin-bottom: 1.5rem;
        }
        .stat-item {
            background: #f9fafb; border-radius: 16px;
            padding: 1rem; text-align: center;
            border: 1px solid #f3f4f6;
        }
        .stat-item .num { font-size: 1.5rem; font-weight: 700; color: #1a2a4a; }
        .stat-item .lbl { font-size: 0.72rem; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; }

        .loading-overlay {
            display: none;
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255,255,255,0.85);
            z-index: 9999;
            align-items: center; justify-content: center;
            flex-direction: column; gap: 1rem;
        }
        .loading-overlay.show { display: flex; }
        .loading-overlay .spinner-border { width: 3rem; height: 3rem; color: #1a2a4a; }
        .loading-overlay .msg { font-weight: 600; color: #1a2a4a; font-size: 1rem; }
        .loading-overlay .sub { color: #9ca3af; font-size: 0.82rem; }
    </style>
</head>
<body>

<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner-border" role="status"></div>
    <div class="msg">Generating database export...</div>
    <div class="sub">Please wait, this may take a moment for large databases.</div>
</div>

<div class="dashboard-container">

    <div class="header-card">
        <div class="icon-wrap"><i class="bi bi-database-down"></i></div>
        <div>
            <h2>Export Database</h2>
            <p>Download a complete SQL backup of the school system database</p>
        </div>
    </div>

    <div class="db-stats">
        <div class="stat-item">
            <div class="num"><?= $table_count ?></div>
            <div class="lbl">Tables</div>
        </div>
        <div class="stat-item">
            <div class="num"><?= number_format($total_rows) ?></div>
            <div class="lbl">Total Rows</div>
        </div>
        <div class="stat-item">
            <div class="num"><?= number_format($total_size / 1024, 1) ?> KB</div>
            <div class="lbl">Size</div>
        </div>
        <div class="stat-item">
            <div class="num"><?= htmlspecialchars($db) ?></div>
            <div class="lbl">Database</div>
        </div>
    </div>

    <div class="card">
        <div class="card-title"><i class="bi bi-gear"></i> Export Options</div>

        <form method="POST" action="export_database.php" id="exportForm">
            <div style="margin-bottom:1.2rem;">
                <div class="form-label">Export Type</div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="export_type" value="both" id="both" checked>
                    <label class="form-check-label" for="both"><strong>Structure & Data</strong> — Full database backup</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="export_type" value="structure" id="structure">
                    <label class="form-check-label" for="structure"><strong>Structure Only</strong> — Table schemas without data</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="export_type" value="data" id="data">
                    <label class="form-check-label" for="data"><strong>Data Only</strong> — Table contents without structure</label>
                </div>
                <div class="form-text">The SQL file will download automatically. It can be imported via phpMyAdmin or mysql CLI.</div>
            </div>

            <div style="display:flex;gap:0.8rem;flex-wrap:wrap;">
                <button type="submit" name="export" value="1" class="btn-export" id="exportBtn">
                    <i class="bi bi-database-down"></i> Generate & Download
                </button>
                <a href="dashboard.php" class="btn-back"><i class="bi bi-house"></i> Dashboard</a>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-title"><i class="bi bi-info-circle"></i> How to Import</div>
        <ol style="color:#6b7280;font-size:0.85rem;padding-left:1.2rem;line-height:1.8;">
            <li>Open <strong>phpMyAdmin</strong> and select your database</li>
            <li>Click the <strong>Import</strong> tab</li>
            <li>Choose the downloaded <code>.sql</code> file</li>
            <li>Click <strong>Go</strong> to restore the backup</li>
        </ol>
    </div>

</div>

<script>
document.getElementById('exportForm')?.addEventListener('submit', function(e) {
    document.getElementById('loadingOverlay').classList.add('show');
    document.getElementById('exportBtn').disabled = true;
});
</script>
</body>
</html>
