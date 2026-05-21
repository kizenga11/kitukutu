<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

// DELETE TEACHER
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    if ($id > 0) {
        mysqli_query($conn,"DELETE FROM teacher_assignments WHERE teacher_id='$id'");
        $del = mysqli_query($conn,"DELETE FROM teachers WHERE id='$id'");
        if (mysqli_affected_rows($conn) > 0) {
            $_SESSION['success'] = "Teacher deleted successfully!";
        } else {
            $_SESSION['error'] = "Teacher not found or already deleted.";
        }
    } else {
        $_SESSION['error'] = "Invalid teacher ID.";
    }
    header("Location: manage_teachers.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers · Kitukutu Secondary</title>
    <!-- Bootstrap 5 + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Font (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f4f7fc;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            padding: 1.5rem;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* App-like header card */
        .header-card {
            background: white;
            border-radius: 28px;
            padding: 1.2rem 1.8rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px -8px rgba(0,20,40,0.1);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            border: 1px solid rgba(0,0,0,0.02);
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo-area img {
            height: 50px;
            width: auto;
        }

        .logo-area h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #0a1e32;
            margin: 0;
            line-height: 1.2;
        }

        .logo-area p {
            font-size: 0.8rem;
            color: #5e718d;
            margin: 0;
        }

        .btn-back {
            background: #edf2f9;
            color: #1b3a5e;
            border-radius: 40px;
            padding: 0.5rem 1.4rem;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid #d9e2ef;
        }

        .btn-back:hover {
            background: #dfe7f2;
            color: #0a1e32;
        }

        /* main content card */
        .content-card {
            background: white;
            border-radius: 32px;
            padding: 1.5rem 1.8rem;
            box-shadow: 0 20px 35px -10px rgba(0,25,50,0.15);
            border: 1px solid rgba(255,255,255,0.5);
        }

        /* title + add button */
        .section-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.8rem;
        }

        .section-header h2 {
            font-weight: 700;
            font-size: 1.6rem;
            color: #0a1e32;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin: 0;
        }

        .section-header h2 i {
            font-size: 2rem;
            color: #f4b400;
            background: rgba(244, 180, 0, 0.1);
            padding: 0.4rem;
            border-radius: 16px;
        }

        .btn-add {
            background: #1b3a5e;
            color: white;
            border-radius: 40px;
            padding: 0.6rem 1.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 8px 16px -6px #1b3a5e40;
        }

        .btn-add:hover {
            background: #0e2842;
            transform: translateY(-2px);
        }

        /* alert */
        .alert-modern {
            border-radius: 20px;
            border: none;
            background: #e2f0e8;
            color: #0f5722;
            padding: 1rem 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 2rem;
        }

        .alert-modern i {
            font-size: 1.5rem;
        }

        /* teacher cards / table */
        .teacher-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.2rem;
        }

        .teacher-card {
            background: #fafcff;
            border-radius: 24px;
            padding: 1.2rem 1.5rem;
            border: 1px solid #e5edf5;
            transition: all 0.2s;
            box-shadow: 0 4px 10px rgba(0,0,0,0.02);
        }

        .teacher-card:hover {
            border-color: #f4b400;
            box-shadow: 0 12px 22px -12px rgba(244,180,0,0.2);
            background: white;
        }

        .card-header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.8rem;
        }

        .teacher-name {
            font-weight: 700;
            font-size: 1.15rem;
            color: #0a1e32;
            line-height: 1.3;
        }

        .teacher-email {
            font-size: 0.85rem;
            color: #62748e;
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .teacher-email i {
            color: #f4b400;
            font-size: 0.9rem;
        }

        .subjects-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 1rem 0 1.2rem;
        }

        .subject-badge {
            background: white;
            border: 1px solid #dae3f0;
            color: #1b3a5e;
            font-weight: 600;
            font-size: 0.7rem;
            padding: 0.3rem 1rem;
            border-radius: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            border-left: 3px solid #f4b400;
        }

        .action-buttons {
            display: flex;
            gap: 0.7rem;
            flex-wrap: wrap;
        }

        .btn-edit, .btn-delete {
            border: none;
            border-radius: 30px;
            padding: 0.4rem 1.2rem;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.15s;
            text-decoration: none;
        }

        .btn-edit {
            background: #fef4d9;
            color: #9e7600;
            border: 1px solid #fbe2a0;
        }

        .btn-edit:hover {
            background: #fbe2a0;
            color: #7a5a00;
        }

        .btn-delete {
            background: #ffe4e2;
            color: #b52b38;
            border: 1px solid #fccac7;
        }

        .btn-delete:hover {
            background: #fccac7;
            color: #8a1f29;
        }

        /* empty state */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #8599b5;
            font-weight: 500;
        }

        /* mobile adjustments: keep cards */
        @media (max-width: 600px) {
            .teacher-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ===== Minimal UI overrides ===== */
        body {
            background: #fff;
            font-family: system-ui;
            padding: 16px;
            min-height: auto;
        }
        .dashboard-container {
            max-width: 100%;
        }
        .header-card {
            display: none;
        }
        .content-card {
            border-radius: 10px;
            padding: 16px;
            box-shadow: none;
            border: 1px solid #e5e7eb;
        }
        .section-header h2 {
            font-size: 1.1rem;
        }
        .section-header h2 i {
            font-size: 1.1rem;
            background: transparent;
            padding: 0;
            border-radius: 0;
            color: #111827;
        }
        .btn-add {
            border-radius: 10px;
            background: #111827;
            box-shadow: none;
            padding: 0.45rem 0.8rem;
            font-size: 0.9rem;
        }
        .btn-add:hover {
            background: #374151;
            transform: none;
        }
        .teacher-card {
            border-radius: 10px;
            background: #fff;
            border: 1px solid #e5e7eb;
            box-shadow: none;
            padding: 12px 14px;
        }
        .teacher-card:hover {
            border-color: #d1d5db;
            box-shadow: none;
            background: #fff;
        }
        .teacher-name {
            font-size: 1rem;
        }
        .subject-badge {
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            border-left: none;
            background: #f9fafb;
            font-size: 0.75rem;
            box-shadow: none;
        }
        .btn-edit, .btn-delete {
            border-radius: 10px;
            padding: 0.35rem 0.8rem;
        }
        .alert-modern {
            border-radius: 10px;
        }
        .empty-state {
            padding: 2rem;
        }
    </style>
</head>
<body>
<div class="dashboard-container">

    <!-- Main Content Card -->
    <div class="content-card">

        <!-- Section Header with Add Button -->
        <div class="section-header">
            <h2>
                <i class="bi bi-people-fill"></i> Teachers
            </h2>
            <a href="add_teacher.php" class="btn-add" target="mainFrame">
                <i class="bi bi-plus-circle"></i> Add Teacher
            </a>
        </div>

        <!-- Success / Error Alert -->
        <?php if(isset($_SESSION['success'])){ ?>
            <div class="alert-modern" style="position:relative;padding-right:2rem;">
                <i class="bi bi-check-circle-fill"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" onclick="this.parentElement.style.display='none'" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;font-size:1.2rem;cursor:pointer;">&times;</button>
            </div>
        <?php } ?>
        <?php if(isset($_SESSION['error'])){ ?>
            <div class="alert-modern" style="position:relative;padding-right:2rem;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;">
                <i class="bi bi-exclamation-triangle-fill"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" onclick="this.parentElement.style.display='none'" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;font-size:1.2rem;cursor:pointer;">&times;</button>
            </div>
        <?php } ?>

        <!-- Teacher Grid (Card Layout) -->
        <div class="teacher-grid">
            <?php
            $teachers = mysqli_query($conn,"SELECT * FROM teachers ORDER BY id DESC");
            if(mysqli_num_rows($teachers) > 0){
                while($row = mysqli_fetch_assoc($teachers)){
            ?>
                <div class="teacher-card">
                    <div class="card-header-row">
                        <span class="teacher-name">
                            <?= htmlspecialchars($row['first_name']." ".$row['second_name']." ".$row['last_name']); ?>
                        </span>
                    </div>
                    <div class="teacher-email">
                        <i class="bi bi-envelope"></i> <?= htmlspecialchars($row['email']); ?>
                    </div>

                    <!-- Subjects assigned -->
                    <div class="subjects-list">
                        <?php
                        $assign = mysqli_query($conn,"
                            SELECT s.subject_name, t.stream, t.class_stream
                            FROM teacher_assignments t
                            JOIN subjects s ON t.subject_id = s.id
                            WHERE t.teacher_id='{$row['id']}'
                        ");
                        if(mysqli_num_rows($assign) > 0){
                            while($a = mysqli_fetch_assoc($assign)){
                                echo "<span class='subject-badge'>".
                                     htmlspecialchars($a['subject_name']." (".$a['stream']." ".$a['class_stream'].")").
                                     "</span>";
                            }
                        } else {
                            echo "<span class='subject-badge' style='opacity:0.6;'>No subjects</span>";
                        }
                        ?>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <a href="edit_teacher.php?id=<?= $row['id'] ?>" class="btn-edit" target="mainFrame">
                            <i class="bi bi-pencil-square"></i> Edit
                        </a>
                        <button class="btn-delete" onclick="confirmDel(<?= $row['id'] ?>,'<?= addslashes(htmlspecialchars($row['first_name'].' '.$row['second_name'].' '.$row['last_name'])) ?>')">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </div>
                </div>
            <?php
                }
            } else {
                echo "<div class='empty-state'>No teachers found. <a href='add_teacher.php' style='color:#1b3a5e;'>Add one now</a>.</div>";
            }
            ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content" style="border-radius:12px;">
      <div class="modal-body text-center py-4">
        <i class="bi bi-exclamation-triangle-fill" style="font-size:2.5rem;color:#dc2626;"></i>
        <p style="font-weight:600;color:#111827;margin:10px 0 4px;font-size:15px;">Delete Teacher</p>
        <p style="font-size:13px;color:#6b7280;margin:0;" id="deleteName"></p>
        <p style="font-size:12px;color:#9ca3af;margin:4px 0 0;">This cannot be undone.</p>
      </div>
      <div class="modal-footer border-0 justify-content-center pt-0" style="gap:8px;">
        <button type="button" class="btn btn-secondary btn-sm" style="border-radius:8px;padding:6px 18px;font-weight:600;" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger btn-sm" style="border-radius:8px;padding:6px 18px;font-weight:600;" id="confirmDeleteBtn">Delete</button>
      </div>
    </div>
  </div>
</div>

<script>
var delId;
function confirmDel(id, name){
  delId = id;
  document.getElementById('deleteName').textContent = '"' + name + '"';
  new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function(){
  window.location.href = '?delete=' + delId;
});

document.querySelectorAll('.alert-modern').forEach(function(a){setTimeout(function(){a.style.display='none';},5000);});
</script>
</body>
</html>