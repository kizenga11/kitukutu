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
if ($data['role'] != 'admin') {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $amount = !empty($_POST['amount']) ? floatval($_POST['amount']) : 'NULL';
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $form_level = mysqli_real_escape_string($conn, $_POST['form_level'] ?? '');
    $stream = mysqli_real_escape_string($conn, $_POST['stream'] ?? '');
    $due_date = !empty($_POST['due_date']) ? mysqli_real_escape_string($conn, $_POST['due_date']) : 'NULL';

    if (empty($item_name)) {
        $err = "Tafadhali ingiza jina la kifaa/mchango.";
    } else {
        $amt_val = $amount !== 'NULL' ? "'$amount'" : "NULL";
        $due_val = $due_date !== 'NULL' ? "'$due_date'" : "NULL";
        $fl_val = $form_level ? "'$form_level'" : "NULL";
        $st_val = $stream ? "'$stream'" : "NULL";
        mysqli_query($conn, "INSERT INTO contributions (item_name, description, amount, type, form_level, stream, due_date, posted_by_type, posted_by_id)
            VALUES ('$item_name', '$desc', $amt_val, '$type', $fl_val, $st_val, $due_val, 'admin', '$admin_id')");
        $msg = "Taarifa imechapishwa.";
    }
}

$contributions = mysqli_query($conn, "SELECT * FROM contributions ORDER BY created_at DESC LIMIT 50");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Contributions</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{--primary:#1a2b4c;--bg:#f3f4f6;}
body{background:var(--bg);font-family:system-ui,-apple-system,sans-serif;padding:16px;font-size:13px;}
h2{font-size:1.2rem;font-weight:700;color:var(--primary);}
.card{border-radius:10px;border:1px solid #e5e7eb;}
table{font-size:12px;}
th{background:var(--primary);color:#fff;font-size:11px;}
</style>
</head>
<body>

<h2><i class="bi bi-cash-stack"></i> Manage Contributions / Debts / Items</h2>

<?php if ($msg): ?><div class="alert alert-success py-2"><?= $msg ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger py-2"><?= $err ?></div><?php endif; ?>

<div class="card p-3 mb-4">
  <form method="POST" class="row g-2">
    <div class="col-md-3">
      <input type="text" name="item_name" class="form-control form-control-sm" placeholder="Item Name" required>
    </div>
    <div class="col-md-2">
      <select name="type" class="form-select form-select-sm" required>
        <option value="item">Kifaa (Item)</option>
        <option value="contribution">Mchango (Contribution)</option>
        <option value="debt">Deni (Debt)</option>
      </select>
    </div>
    <div class="col-md-2">
      <input type="number" name="amount" class="form-control form-control-sm" placeholder="Amount (TSh)" step="0.01">
    </div>
    <div class="col-md-2">
      <select name="form_level" class="form-select form-select-sm">
        <option value="">— All Forms —</option>
        <?php foreach (['Form One','Form Two','Form Three','Form Four'] as $f): ?>
        <option value="<?= $f ?>"><?= $f ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-1">
      <select name="stream" class="form-select form-select-sm">
        <option value="">— All —</option>
        <option value="General">General</option>
        <option value="Vocational">Vocational</option>
      </select>
    </div>
    <div class="col-md-2">
      <input type="date" name="due_date" class="form-control form-control-sm">
    </div>
    <div class="col-12">
      <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Description (optional)"></textarea>
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-sm" style="background:var(--primary);color:#fff;"><i class="bi bi-send"></i> Post</button>
    </div>
  </form>
</div>

<?php if (mysqli_num_rows($contributions) > 0): ?>
<div class="table-responsive">
<table class="table table-bordered bg-white">
  <tr>
    <th>Item</th>
    <th>Type</th>
    <th>Amount</th>
    <th>Form</th>
    <th>Stream</th>
    <th>Due Date</th>
    <th>Posted</th>
  </tr>
  <?php while ($c = mysqli_fetch_assoc($contributions)):
    $type_badge = $c['type'] == 'contribution' ? 'bg-success' : ($c['type'] == 'debt' ? 'bg-danger' : 'bg-info');
    $type_label = $c['type'] == 'contribution' ? 'Mchango' : ($c['type'] == 'debt' ? 'Deni' : 'Kifaa');
  ?>
  <tr>
    <td><?= htmlspecialchars($c['item_name']) ?></td>
    <td><span class="badge <?= $type_badge ?>"><?= $type_label ?></span></td>
    <td><?= $c['amount'] !== null ? 'TSh '.number_format($c['amount'], 0) : '-' ?></td>
    <td><?= htmlspecialchars($c['form_level'] ?? 'All') ?></td>
    <td><?= htmlspecialchars($c['stream'] ?? 'All') ?></td>
    <td><?= $c['due_date'] ? htmlspecialchars(date('d/m/Y', strtotime($c['due_date']))) : '-' ?></td>
    <td><?= htmlspecialchars(date('d/m/Y', strtotime($c['created_at']))) ?></td>
  </tr>
  <?php endwhile; ?>
</table>
</div>
<?php endif; ?>

</body>
</html>
