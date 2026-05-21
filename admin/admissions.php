<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

$result = mysqli_query($conn,"SELECT * FROM admissions ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Admissions | Kitukutu Secondary</title>
    <!-- Bootstrap 5 + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f4f7fc;
            font-family: 'Inter', sans-serif;
            padding: 2rem 1rem;
        }
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .school-card {
            background: white;
            border-radius: 28px;
            padding: 2rem 1.5rem;
            box-shadow: 0 20px 40px -12px rgba(0,20,50,0.15);
            margin-bottom: 2rem;
            border: 1px solid rgba(0,0,0,0.02);
        }
        .content-card {
            background: white;
            border-radius: 24px;
            padding: 1.8rem;
            box-shadow: 0 8px 20px rgba(0,0,0,0.02);
            border: 1px solid #f0f2f5;
        }
        .section-title {
            font-weight: 600;
            font-size: 1.4rem;
            color: #1a2b4c;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 1.5rem;
        }
        .badge-pending {
            background: #ffc107;
            color: #1a2b4c;
            font-weight: 500;
            padding: 0.4rem 0.8rem;
            border-radius: 30px;
        }
        .badge-approved {
            background: #198754;
            color: white;
            font-weight: 500;
            padding: 0.4rem 0.8rem;
            border-radius: 30px;
        }
        .badge-rejected {
            background: #dc3545;
            color: white;
            font-weight: 500;
            padding: 0.4rem 0.8rem;
            border-radius: 30px;
        }
        .table-custom {
            border-collapse: separate;
            border-spacing: 0 12px;
            width: 100%;
        }
        .table-custom thead th {
            background: #f8fafd;
            color: #1e2f50;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: none;
            padding: 1rem 0.75rem;
        }
        .table-custom tbody tr {
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
            transition: all 0.2s;
        }
        .table-custom tbody tr:hover {
            box-shadow: 0 10px 20px rgba(26,43,76,0.08);
        }
        .table-custom td {
            padding: 1rem 0.75rem;
            vertical-align: middle;
            border: none;
            border-top: 1px solid #f0f3f7;
            color: #2f405b;
        }
        .action-form {
            display: inline-block;
            margin: 0 4px 4px 0;
        }
        .admin-message-textarea {
            width: 100%;
            border: 2px solid #e4e9f2;
            border-radius: 16px;
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
            resize: vertical;
            transition: border 0.2s;
        }
        .admin-message-textarea:focus {
            border-color: #1a2b4c;
            outline: none;
            box-shadow: 0 0 0 3px rgba(26,43,76,0.1);
        }
        .btn-action {
            border-radius: 40px;
            padding: 0.4rem 1.2rem;
            font-size: 0.85rem;
            font-weight: 500;
            border: none;
            transition: all 0.15s;
        }
        .btn-approve {
            background: #198754;
            color: white;
        }
        .btn-approve:hover {
            background: #0e6b3f;
        }
        .btn-reject {
            background: #dc3545;
            color: white;
        }
        .btn-reject:hover {
            background: #b52b38;
        }
        .btn-delete {
            background: #1a2b4c;
            color: white;
        }
        .btn-delete:hover {
            background: #0f1e36;
        }
        .btn-view {
            background: #0d6efd;
            color: white;
            border-radius: 40px;
            padding: 0.3rem 1rem;
            text-decoration: none;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .btn-view:hover {
            background: #0b5ed7;
            color: white;
        }
        /* mobile cards */
        .mobile-cards {
            display: none;
        }
        .admission-card {
            background: white;
            border-radius: 24px;
            padding: 1.5rem;
            margin-bottom: 1.2rem;
            box-shadow: 0 6px 16px rgba(0,0,0,0.03);
            border: 1px solid #f0f2f5;
        }
        @media (max-width: 768px) {
            .desktop-table {
                display: none;
            }
            .mobile-cards {
                display: block;
            }
        }

        /* ===== Minimal UI overrides ===== */
        body {
            background: #fff;
            font-family: system-ui;
            padding: 12px;
        }
        .dashboard-container {
            max-width: 100%;
        }
        .school-card {
            display: none;
        }
        .content-card {
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            box-shadow: none;
            padding: 14px;
        }
        .section-title {
            font-size: 1rem;
            margin-bottom: 12px;
        }
        .section-title i {
            font-size: 1rem;
            color: #111827;
        }
        .table-custom thead th {
            padding: 0.5rem 0.5rem;
            font-size: 0.75rem;
        }
        .table-custom td {
            padding: 0.5rem 0.5rem;
        }
        .table-custom tbody tr {
            border-radius: 10px;
        }
        .admin-message-textarea {
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            padding: 0.4rem 0.6rem;
        }
        .btn-action {
            border-radius: 10px;
            padding: 0.35rem 0.8rem;
            font-size: 0.8rem;
        }
        .btn-view {
            border-radius: 10px;
            padding: 0.25rem 0.7rem;
        }
        .badge-pending, .badge-approved, .badge-rejected {
            border-radius: 10px;
            padding: 0.25rem 0.6rem;
        }
        .admission-card {
            border-radius: 10px;
            padding: 12px;
        }
    </style>
</head>
<body>
<div class="dashboard-container">

    <!-- Admissions List Card -->
    <div class="content-card">
        <div class="section-title">
            <i class="bi bi-file-person-fill"></i> Admission Applications
        </div>

        <!-- DESKTOP TABLE (hidden on mobile) -->
        <div class="desktop-table table-responsive">
            <table class="table-custom w-100">
                <thead>
                    <tr>
                        <th>App No</th>
                        <th>Name</th>
                        <th>Level</th>
                        <th>Index</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php mysqli_data_seek($result,0); while($row = mysqli_fetch_assoc($result)){ ?>
                    <tr>
                        <td data-label="App No"><?php echo htmlspecialchars($row['application_no']); ?></td>
                        <td data-label="Name"><?php echo htmlspecialchars($row['first_name']." ".$row['middle_name']." ".$row['last_name']); ?></td>
                        <td data-label="Level"><?php echo htmlspecialchars($row['entry_level']); ?></td>
                        <td data-label="Index"><?php echo htmlspecialchars($row['psle_index_no'] ?: $row['form2_index_no']); ?></td>
                        <td data-label="Status">
                            <?php
                            $status = $row['status'];
                            $badgeClass = '';
                            if($status == 'Pending') $badgeClass = 'badge-pending';
                            elseif($status == 'Approved') $badgeClass = 'badge-approved';
                            elseif($status == 'Rejected') $badgeClass = 'badge-rejected';
                            ?>
                            <span class="<?php echo $badgeClass; ?>"><?php echo $status; ?></span>
                        </td>
                        <td data-label="Actions">
                            <?php if($row['document']){ ?>
                                <a class="btn-view mb-1" href="../uploads/admissions/<?php echo htmlspecialchars($row['document']); ?>" target="_blank">
                                    <i class="bi bi-file-earmark-pdf"></i> View Doc
                                </a>
                            <?php } ?>

                           <form action="update_admission_status.php" method="POST">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <textarea name="admin_message" class="admin-message-textarea mb-1" placeholder="Admin message..."><?php echo htmlspecialchars($row['admin_message']); ?></textarea>
                                <div class="d-flex gap-1">
                                    <button type="submit" name="action" value="Approved" class="btn-action btn-approve">
                                        <i class="bi bi-check-circle"></i> Approve
                                    </button>
                                    <button type="submit" name="action" value="Rejected" class="btn-action btn-reject">
                                        <i class="bi bi-x-circle"></i> Reject
                                    </button>
                                </div>
                            </form>

                            <form action="delete_admission.php" method="POST" class="action-form mt-1"
                                  onsubmit="return confirm('Are you sure you want to delete this application?');">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn-action btn-delete">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <!-- MOBILE CARDS (visible only on mobile) -->
        <div class="mobile-cards">
            <?php mysqli_data_seek($result,0); while($row = mysqli_fetch_assoc($result)){ ?>
            <div class="admission-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($row['application_no']); ?></h5>
                    <?php
                    $status = $row['status'];
                    $badgeClass = '';
                    if($status == 'Pending') $badgeClass = 'badge-pending';
                    elseif($status == 'Approved') $badgeClass = 'badge-approved';
                    elseif($status == 'Rejected') $badgeClass = 'badge-rejected';
                    ?>
                    <span class="<?php echo $badgeClass; ?>"><?php echo $status; ?></span>
                </div>

                <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($row['first_name']." ".$row['middle_name']." ".$row['last_name']); ?></p>
                <p class="mb-1"><strong>Level:</strong> <?php echo htmlspecialchars($row['entry_level']); ?></p>
                <p class="mb-2"><strong>Index:</strong> <?php echo htmlspecialchars($row['psle_index_no'] ?: $row['form2_index_no']); ?></p>

                <?php if($row['document']){ ?>
                    <a class="btn-view mb-2" href="../uploads/admissions/<?php echo htmlspecialchars($row['document']); ?>" target="_blank">
                        <i class="bi bi-file-earmark-pdf"></i> View Document
                    </a>
                <?php } ?>

                <form action="update_status.php" method="POST" class="mt-2">
                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                    <textarea name="admin_message" class="admin-message-textarea mb-2" placeholder="Admin message..."><?php echo htmlspecialchars($row['admin_message']); ?></textarea>
                    <div class="d-flex gap-2 mb-2">
                        <button type="submit" name="action" value="Approved" class="btn-action btn-approve flex-fill">
                            <i class="bi bi-check-circle"></i> Approve
                        </button>
                        <button type="submit" name="action" value="Rejected" class="btn-action btn-reject flex-fill">
                            <i class="bi bi-x-circle"></i> Reject
                        </button>
                    </div>
                </form>

                <form action="delete_admission.php" method="POST"
                      onsubmit="return confirm('Are you sure you want to delete this application?');">
                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                    <button type="submit" class="btn-action btn-delete w-100">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                </form>
            </div>
            <?php } ?>
        </div>
    </div>
</div>

<!-- Bootstrap JS (optional, for any future enhancements) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>