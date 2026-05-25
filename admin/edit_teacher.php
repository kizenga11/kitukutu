<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

$id = $_GET['id'];

$teacher = mysqli_query($conn,"SELECT * FROM teachers WHERE id='$id'");
$data = mysqli_fetch_assoc($teacher);

// UPDATE
if(isset($_POST['update'])){

    $first = mysqli_real_escape_string($conn,$_POST['first_name']);
    $second = mysqli_real_escape_string($conn,$_POST['second_name']);
    $last = mysqli_real_escape_string($conn,$_POST['last_name']);
    $sex = $_POST['sex'];
    $email = mysqli_real_escape_string($conn,$_POST['email']);
    $phone = mysqli_real_escape_string($conn,$_POST['phone']);

    // Validate phone format
    if(!preg_match('/^255[0-9]{9}$/', $phone)){
        $error = "Phone number must be in format 2557XXXXXXXX";
    } else {

        // Check duplicate phone (exclude current teacher)
        $check_phone = mysqli_query($conn,"SELECT id FROM teachers WHERE phone='$phone' AND id!='".$id."'");

        if(mysqli_num_rows($check_phone) > 0){

            $error = "This phone number is already registered!";

        } else {

            mysqli_query($conn,"
                UPDATE teachers SET
                first_name='$first',
                second_name='$second',
                last_name='$last',
                sex='$sex',
                email='$email',
                phone='$phone'
                WHERE id='$id'
            ");

            // DELETE OLD ASSIGNMENTS
            mysqli_query($conn,"DELETE FROM teacher_assignments WHERE teacher_id='$id'");
            mysqli_query($conn,"DELETE FROM subject_settings WHERE teacher_id='$id'");

            // Get active year & term
            $yr = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM academic_years WHERE is_active=1 LIMIT 1"));
            $tm = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM terms WHERE is_active=1 LIMIT 1"));
            $yid = intval($yr['id']??0); $tid = intval($tm['id']??0);

            // INSERT NEW ASSIGNMENTS
            if(isset($_POST['assignments'])){
                foreach($_POST['assignments'] as $val){
                    $parts = explode('|', $val);
                    $sid = intval($parts[0]);
                    $fl = $parts[1] ?? 'Form One';

                    $sub = mysqli_query($conn,"SELECT stream FROM subjects WHERE id='$sid'");
                    $sub_data = mysqli_fetch_assoc($sub);
                    $stream = $sub_data['stream'];
                    $class_stream = ($stream == 'GENERAL') ? 'B' : 'A';

                    mysqli_query($conn,"
                        INSERT INTO teacher_assignments
                        (teacher_id, subject_id, form_level, stream, class_stream)
                        VALUES
                        ('$id','$sid','$fl','$stream','$class_stream')
                    ");
                    // Auto-create subject_settings
                    if ($yid && $tid) {
                        mysqli_query($conn,"INSERT IGNORE INTO subject_settings (teacher_id,subject_id,form_level,academic_year_id,term_id,is_active) VALUES ($id,$sid,'$fl',$yid,$tid,1)");
                    }
                }
            }

            $_SESSION['success'] = "Teacher updated successfully!";
            header("Location: manage_teachers.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Teacher - Kitukutu Secondary</title>
    <!-- Bootstrap 5 + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Font (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(145deg, #f0f5fa 0%, #e6ecf5 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            padding: 2rem 1rem;
        }
        .dashboard-container {
            max-width: 900px;
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
            padding: 2rem;
            box-shadow: 0 8px 20px rgba(0,0,0,0.02);
            border: 1px solid #f0f2f5;
        }
        .section-title {
            font-weight: 700;
            font-size: 1.5rem;
            color: #1a2b4c;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #f4b400;
            padding-bottom: 0.6rem;
        }
        .section-title i {
            font-size: 1.8rem;
            color: #f4b400;
        }
        .form-control, .form-select {
            border: 2px solid #e4e9f2;
            border-radius: 18px;
            padding: 0.75rem 1.2rem;
            font-size: 1rem;
            transition: all 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #1a2b4c;
            box-shadow: 0 0 0 4px rgba(26,43,76,0.1);
        }
        .form-check-custom {
            border: 2px solid #eef2f7;
            border-radius: 18px;
            padding: 0.8rem 1.2rem;
            margin-bottom: 0.8rem;
            transition: all 0.2s;
        }
        .form-check-custom:hover {
            background: #f8fafd;
            border-color: #cbd5e1;
        }
        .form-check-input {
            margin-right: 12px;
            width: 1.2rem;
            height: 1.2rem;
            border: 2px solid #cbd5e1;
        }
        .form-check-input:checked {
            background-color: #1e4a6d;
            border-color: #1e4a6d;
        }
        .btn-update {
            background: linear-gradient(135deg, #1e4a6d, #0f2b4b);
            color: white;
            border-radius: 40px;
            padding: 0.9rem;
            font-weight: 700;
            font-size: 1.2rem;
            border: none;
            transition: all 0.3s;
            box-shadow: 0 20px 30px -10px #1e4a6d;
        }
        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 25px 35px -8px #1e4a6d;
        }
        .btn-back {
            background: #6c757d;
            color: white;
            border-radius: 40px;
            padding: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            display: block;
            text-align: center;
        }
        .btn-back:hover {
            background: #5a6268;
        }
        .alert-modern {
            border-radius: 20px;
            border: none;
            background: white;
            box-shadow: 0 6px 14px rgba(0,40,60,0.05);
            padding: 1rem 1.5rem;
        }
        @media (max-width: 576px) {
            .content-card {
                padding: 1.5rem;
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
        .school-card {
            display: none;
        }
        .content-card {
            border-radius: 10px;
            padding: 16px;
            box-shadow: none;
            border: 1px solid #e5e7eb;
        }
        .section-title {
            font-size: 1rem;
            margin-bottom: 14px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 8px;
        }
        .section-title i {
            font-size: 1rem;
            color: #111827;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            padding: 0.55rem 0.9rem;
        }
        .form-control:focus, .form-select:focus {
            box-shadow: none;
            border-color: #111827;
        }
        .form-check-custom {
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            padding: 0.6rem 1rem;
        }
        .form-check-input {
            border-radius: 4px;
        }
        .btn-update {
            background: #111827;
            border-radius: 10px;
            box-shadow: none;
            padding: 0.6rem;
            font-size: 1rem;
        }
        .btn-update:hover {
            transform: none;
            box-shadow: none;
            background: #374151;
        }
        .btn-back {
            background: #fff;
            color: #111827;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 0.6rem;
            font-size: 0.9rem;
        }
        .btn-back:hover {
            background: #f3f4f6;
            color: #111827;
        }
        .alert-modern {
            border-radius: 10px;
        }

        /* ===== Compact form overrides ===== */
        .form-control, .form-select {
            padding: 0.4rem 0.7rem !important;
            font-size: 0.85rem !important;
        }
        .row.g-4 {
            --bs-gutter-x: 0.6rem !important;
            --bs-gutter-y: 0.4rem !important;
        }
        .mb-4 {
            margin-bottom: 0.6rem !important;
        }
        .mb-3 {
            margin-bottom: 0.5rem !important;
        }
        .my-4 {
            margin-top: 0.6rem !important;
            margin-bottom: 0.6rem !important;
        }
        .form-check-custom {
            padding: 0.4rem 0.8rem !important;
        }
        .btn-update {
            padding: 0.45rem !important;
            font-size: 0.9rem !important;
        }
        .btn-back {
            padding: 0.45rem !important;
            font-size: 0.85rem !important;
        }
        h5.fw-bold {
            font-size: 0.95rem !important;
            margin-bottom: 0.5rem !important;
        }
    </style>
</head>
<body>
<div class="dashboard-container">

    <!-- Main Edit Card -->
    <div class="content-card">
        <div class="section-title">
            <i class="bi bi-pencil-square"></i> Edit Teacher
        </div>

        <?php if(isset($error)){ ?>
            <div class="alert alert-danger alert-modern alert-dismissible d-flex align-items-center mb-3">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" style="padding:0.6rem;font-size:0.7rem;"></button>
            </div>
        <?php } ?>

        <form method="POST">
            <!-- Personal Information -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <label class="fw-semibold mb-1">First Name</label>
                    <input type="text" name="first_name" class="form-control"
                           value="<?= htmlspecialchars($data['first_name']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-1">Second Name</label>
                    <input type="text" name="second_name" class="form-control"
                           value="<?= htmlspecialchars($data['second_name']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-1">Last Name</label>
                    <input type="text" name="last_name" class="form-control"
                           value="<?= htmlspecialchars($data['last_name']) ?>" required>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <label class="fw-semibold mb-1">Gender</label>
                    <select name="sex" class="form-select">
                        <option value="Male" <?= $data['sex']=="Male"?"selected":"" ?>>Male</option>
                        <option value="Female" <?= $data['sex']=="Female"?"selected":"" ?>>Female</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-1">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($data['email']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-1">Phone (2557XXXXXXXX)</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?= htmlspecialchars($data['phone']) ?>"
                           pattern="255[0-9]{9}"
                           title="Enter phone in format 2557XXXXXXXX"
                           required>
                </div>
            </div>

            <hr class="my-4">

            <h5 class="fw-bold mb-3"><i class="bi bi-book me-2"></i>Teaching Assignments</h5>

            <div class="row g-3 mb-5">
                <?php
                $current = [];
                $old = mysqli_query($conn,"SELECT subject_id, form_level FROM teacher_assignments WHERE teacher_id='$id'");
                while($o=mysqli_fetch_assoc($old)){
                    $current[] = $o['subject_id'].'|'.$o['form_level'];
                }

                $forms = ['Form One','Form Two','Form Three','Form Four'];
                $short = ['F.1','F.2','F.3','F.4'];
                $subjects = mysqli_query($conn,"SELECT * FROM subjects ORDER BY stream, subject_name");
                while($row=mysqli_fetch_assoc($subjects)){
                ?>
                <div class="col-md-6">
                    <div class="form-check-custom" style="padding:0.6rem 1rem;">
                        <div style="display:flex;align-items:center;gap:8px;width:100%;">
                            <span style="font-weight:600;flex:1;font-size:0.9rem;"><?= htmlspecialchars($row['subject_name']) ?></span>
                            <span class="badge bg-primary"><?= $row['stream'] ?></span>
                        </div>
                        <div style="display:flex;gap:4px;margin-top:6px;">
                            <?php for ($fi=0;$fi<4;$fi++):
                                $checked = in_array($row['id'].'|'.$forms[$fi], $current) ? 'checked' : '';
                            ?>
                            <label style="display:flex;align-items:center;gap:3px;font-size:11px;padding:3px 6px;border:1px solid #e5e7eb;border-radius:6px;cursor:pointer;background:#f9fafb;">
                                <input type="checkbox" name="assignments[]" value="<?=$row['id'].'|'.$forms[$fi]?>" <?=$checked?> style="width:12px;height:12px;margin:0;cursor:pointer;">
                                <?=$short[$fi]?>
                            </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>

            <!-- Action Buttons -->
            <button type="submit" name="update" class="btn-update w-100 mb-3">
                <i class="bi bi-check-circle me-2"></i>Update Teacher
            </button>
            <a href="manage_teachers.php" class="btn-back" target="mainFrame">
                <i class="bi bi-arrow-left me-2"></i>Back
            </a>
        </form>
    </div>
</div>

<!-- Bootstrap JS (optional) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.alert-dismissible').forEach(function(a){setTimeout(function(){a.classList.remove('show');a.style.display='none';},5000);});
</script>
<script src="../assets/js/forms.js"></script>
<script src="../assets/js/loader.js"></script>
</body>
</html>