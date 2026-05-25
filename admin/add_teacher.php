<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

if(isset($_POST['register'])){

    $password_plain = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if($password_plain !== $confirm_password){

        $error = "Passwords do not match!";

    } else {

        $first  = mysqli_real_escape_string($conn,$_POST['first_name']);
        $second = mysqli_real_escape_string($conn,$_POST['second_name']);
        $last   = mysqli_real_escape_string($conn,$_POST['last_name']);
        $sex    = $_POST['sex'];
        $email  = mysqli_real_escape_string($conn,$_POST['email']);
        $phone  = mysqli_real_escape_string($conn,$_POST['phone']);
        $password = password_hash($password_plain, PASSWORD_DEFAULT);

        // Validate phone format (2557XXXXXXXX)
        if(!preg_match('/^255[0-9]{9}$/', $phone)){
            $error = "Phone number must be in format 2557XXXXXXXX";
        } else {

            // Check duplicate email
            $check = mysqli_query($conn,"SELECT id FROM teachers WHERE email='$email'");

            if(mysqli_num_rows($check) > 0){

                $error = "This email is already registered!";

            } else {

                // Check duplicate phone
                $check_phone = mysqli_query($conn,"SELECT id FROM teachers WHERE phone='$phone'");

                if(mysqli_num_rows($check_phone) > 0){

                    $error = "This phone number is already registered!";

                } else {

                    $insert = mysqli_query($conn,"
                        INSERT INTO teachers
                        (first_name, second_name, last_name, sex, email, phone, password)
                        VALUES
                        ('$first','$second','$last','$sex','$email','$phone','$password')
                    ");

                    if($insert){

                        $teacher_id = mysqli_insert_id($conn);

                        // Get active year & term
                        $yr = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM academic_years WHERE is_active=1 LIMIT 1"));
                        $tm = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM terms WHERE is_active=1 LIMIT 1"));
                        $yid = intval($yr['id']??0); $tid = intval($tm['id']??0);

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
                                    ('$teacher_id','$sid','$fl','$stream','$class_stream')
                                ");
                                // Auto-create subject_settings
                                if ($yid && $tid) {
                                    mysqli_query($conn,"INSERT IGNORE INTO subject_settings (teacher_id,subject_id,form_level,academic_year_id,term_id,is_active) VALUES ($teacher_id,$sid,'$fl',$yid,$tid,1)");
                                }
                            }
                        }

                        $success = "Teacher Registered Successfully!";

                    } else {

                        $error = "Error: ".mysqli_error($conn);

                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Teacher · Kitukutu Secondary</title>
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
            background: linear-gradient(145deg, #f6f9fc 0%, #eef2f6 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .register-container {
            max-width: 1000px;
            width: 100%;
            margin: 0 auto;
        }

        /* Modern glass card */
        .card-glass {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 40px;
            box-shadow: 0 30px 60px -20px rgba(0, 30, 60, 0.3), 0 0 0 1px rgba(255,255,255,0.6) inset;
            overflow: hidden;
        }

        /* Header with app‑like gradient */
        .app-header {
            background: linear-gradient(135deg, #0a1e32, #1b3a5e);
            padding: 2rem 2.2rem;
            color: white;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #f4b400;
        }

        .app-header h2 {
            font-weight: 700;
            font-size: 1.9rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            letter-spacing: -0.3px;
        }

        .app-header h2 i {
            background: #f4b400;
            color: #0a1e32;
            padding: 0.6rem;
            border-radius: 20px;
            font-size: 1.8rem;
        }

        .header-actions {
            display: flex;
            gap: 0.8rem;
        }

        .btn-header {
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.25);
            color: white;
            border-radius: 100px;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.2s;
            backdrop-filter: blur(4px);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-header:hover {
            background: rgba(255,255,255,0.25);
            border-color: rgba(255,255,255,0.4);
            color: white;
            transform: translateY(-2px);
        }

        /* main content area */
        .app-content {
            padding: 2.5rem;
        }

        /* modern form sections */
        .form-section {
            margin-bottom: 2.8rem;
        }

        .section-title {
            font-weight: 700;
            font-size: 1.3rem;
            color: #0a1e32;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 1.8rem;
            letter-spacing: -0.2px;
        }

        .section-title i {
            font-size: 1.8rem;
            color: #f4b400;
            background: rgba(244, 180, 0, 0.1);
            padding: 0.5rem;
            border-radius: 18px;
        }

        /* input styling */
        .form-floating-custom {
            position: relative;
            margin-bottom: 1.2rem;
        }

        .form-control, .form-select {
            border: 2px solid #e3eaf2;
            border-radius: 24px;
            padding: 0.9rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            background: white;
            transition: all 0.2s;
            box-shadow: 0 4px 8px rgba(0,0,0,0.02);
        }

        .form-control:focus, .form-select:focus {
            border-color: #1b3a5e;
            box-shadow: 0 8px 20px -8px rgba(27,58,94,0.3);
            outline: none;
        }

        .input-group-icon {
            position: relative;
        }

        .input-group-icon i {
            position: absolute;
            left: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            color: #8a9bb5;
            font-size: 1.3rem;
            z-index: 4;
        }

        .input-group-icon .form-control {
            padding-left: 3.2rem;
        }

        /* password toggle container */
        .password-wrapper {
            position: relative;
        }

        .password-wrapper .form-control {
            padding-right: 3.5rem;
        }

        .toggle-btn {
            position: absolute;
            right: 0.2rem;
            top: 50%;
            transform: translateY(-50%);
            background: #f0f4fa;
            border: none;
            border-radius: 30px;
            width: 2.8rem;
            height: 2.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1b3a5e;
            font-size: 1.3rem;
            cursor: pointer;
            transition: all 0.2s;
            z-index: 5;
        }

        .toggle-btn:hover {
            background: #e2e9f3;
            color: #0a1e32;
        }

        /* assignment cards */
        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .subject-card {
            background: #f9fcff;
            border: 2px solid #e3eaf2;
            border-radius: 24px;
            padding: 0.8rem 1.2rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .subject-card:hover {
            border-color: #f4b400;
            background: white;
            box-shadow: 0 12px 20px -12px rgba(244,180,0,0.2);
        }

        .subject-card .form-check-input {
            width: 1.4rem;
            height: 1.4rem;
            margin-right: 1rem;
            border: 2px solid #b6c8db;
            border-radius: 8px;
            cursor: pointer;
        }

        .subject-card .form-check-input:checked {
            background-color: #1b3a5e;
            border-color: #1b3a5e;
        }

        .subject-card label {
            font-weight: 600;
            color: #1e2f4a;
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            cursor: pointer;
            margin: 0;
        }

        .badge-stream {
            background: #f4b400;
            color: #0a1e32;
            font-weight: 700;
            font-size: 0.7rem;
            padding: 0.3rem 1rem;
            border-radius: 100px;
            letter-spacing: 0.3px;
        }

        /* action buttons */
        .btn-primary-app {
            background: linear-gradient(135deg, #1b3a5e, #0e2842);
            border: none;
            border-radius: 40px;
            padding: 1rem 1.8rem;
            font-weight: 700;
            font-size: 1.2rem;
            color: white;
            transition: all 0.25s;
            box-shadow: 0 20px 30px -12px #0e2842;
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.7rem;
        }

        .btn-primary-app:hover {
            transform: translateY(-3px);
            box-shadow: 0 28px 36px -12px #0e2842;
            background: linear-gradient(135deg, #1f446b, #123150);
        }

        .btn-outline-app {
            background: transparent;
            border: 2px solid #dde4ed;
            border-radius: 40px;
            padding: 0.9rem 1.5rem;
            font-weight: 600;
            color: #1b3a5e;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
        }

        .btn-outline-app:hover {
            background: #f0f6ff;
            border-color: #1b3a5e;
            color: #0e2842;
        }

        /* alert styles */
        .alert-app {
            border-radius: 30px;
            border: none;
            padding: 1.2rem 1.8rem;
            font-weight: 500;
            background: white;
            box-shadow: 0 8px 20px rgba(0,0,0,0.04);
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .alert-app i {
            font-size: 1.8rem;
        }

        .alert-success {
            background: #e1f7e3;
            color: #0f5722;
        }

        .alert-danger {
            background: #ffe7e5;
            color: #a4232a;
        }

        hr {
            opacity: 0.3;
            margin: 2rem 0;
        }

        /* mobile */
        @media (max-width: 700px) {
            .app-header {
                flex-direction: column;
                align-items: start;
                gap: 1rem;
                padding: 1.5rem;
            }
            .app-content {
                padding: 1.5rem;
            }
            .subjects-grid {
                grid-template-columns: 1fr;
            }
            .section-title {
                font-size: 1.2rem;
            }
        }

        /* small screen row spacing */
        .row.g-4 {
            --bs-gutter-y: 1.2rem;
        }
        /* ===== Minimal UI overrides (match Teacher Home) ===== */
        body {
            background: #fff;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
            min-height: auto;
            display: block;
            padding: 16px;
        }

        .register-container {
            max-width: 1100px;
        }

        .card-glass {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: none;
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
        }

        .app-header {
            background: #fff;
            color: #111827;
            border-bottom: 1px solid #e5e7eb;
            padding: 14px 16px;
        }

        .app-header h2 {
            font-size: 1.1rem;
            gap: 0.5rem;
        }

        .app-header h2 i {
            background: transparent;
            color: #111827;
            padding: 0;
            border-radius: 0;
            font-size: 1.1rem;
        }

        .btn-header {
            background: #fff;
            border: 1px solid #e5e7eb;
            color: #111827;
            border-radius: 10px;
            padding: 0.45rem 0.8rem;
            font-size: 0.9rem;
        }

        .btn-header:hover {
            background: #f3f4f6;
            border-color: #e5e7eb;
            color: #111827;
            transform: none;
        }

        .app-content {
            padding: 16px;
        }

        .section-title {
            font-size: 1rem;
            margin-bottom: 12px;
        }

        .section-title i {
            background: transparent;
            padding: 0;
            border-radius: 0;
            font-size: 1rem;
        }

        .form-card {
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            box-shadow: none;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
        }

        .btn-primary,
        .btn-success,
        .btn-secondary,
        .btn-outline-secondary,
        .btn-outline-primary,
        .btn-outline-danger {
            border-radius: 10px;
        }

        /* ===== Compact form overrides ===== */
        .form-control, .form-select {
            padding: 0.45rem 0.8rem !important;
            font-size: 0.9rem !important;
            border-radius: 10px !important;
            border-width: 1px !important;
        }
        .input-group-icon i {
            font-size: 0.9rem !important;
            left: 0.8rem !important;
        }
        .input-group-icon .form-control {
            padding-left: 2.2rem !important;
        }
        .password-wrapper .form-control {
            padding-right: 2.5rem !important;
        }
        .toggle-btn {
            width: 2rem !important;
            height: 2rem !important;
            font-size: 0.9rem !important;
            right: 0.2rem !important;
        }
        .row.g-4 {
            --bs-gutter-x: 0.8rem !important;
            --bs-gutter-y: 0.6rem !important;
        }
        .form-section {
            margin-bottom: 1.2rem !important;
        }
        .subjects-grid {
            gap: 0.6rem !important;
        }
        .subject-card {
            border-radius: 10px !important;
            border-width: 1px !important;
            padding: 0.5rem 0.8rem !important;
        }
        .btn-primary-app, .btn-outline-app {
            padding: 0.5rem !important;
            font-size: 0.9rem !important;
            border-radius: 10px !important;
        }
        hr {
            margin: 0.8rem 0 !important;
        }
        .alert-app {
            padding: 0.5rem 0.8rem !important;
            border-radius: 10px !important;
            margin-bottom: 0.8rem !important;
        }
    </style>
</head>
<body>
<div class="register-container">

    <!-- Main glass card -->
    <div class="card-glass">

        <!-- Header with app feel -->
        <div class="app-header">
            <h2>
                <i class="bi bi-person-plus-fill"></i> 
                Register Teacher
            </h2>
            <!-- navigation handled by dashboard sidebar -->
        </div>

        <!-- Main content -->
        <div class="app-content">

            <!-- Alerts -->
            <?php if(isset($success)){ ?>
                <div class="alert-app alert-success" style="position:relative;padding-right:2rem;">
                    <i class="bi bi-check-circle-fill"></i> <?= $success ?>
                    <button type="button" onclick="this.parentElement.style.display='none'" style="position:absolute;right:6px;top:50%;transform:translateY(-50%);background:none;border:none;font-size:1rem;cursor:pointer;">&times;</button>
                </div>
            <?php } ?>

            <?php if(isset($error)){ ?>
                <div class="alert-app alert-danger" style="position:relative;padding-right:2rem;">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?= $error ?>
                    <button type="button" onclick="this.parentElement.style.display='none'" style="position:absolute;right:6px;top:50%;transform:translateY(-50%);background:none;border:none;font-size:1rem;cursor:pointer;">&times;</button>
                </div>
            <?php } ?>

            <form method="POST">

                <!-- Personal Information -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="bi bi-person-badge"></i> Personal Information
                    </div>

                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="input-group-icon">
                                <i class="bi bi-person"></i>
                                <input type="text" name="first_name" class="form-control" placeholder="First name" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group-icon">
                                <i class="bi bi-person"></i>
                                <input type="text" name="second_name" class="form-control" placeholder="Second name" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group-icon">
                                <i class="bi bi-person"></i>
                                <input type="text" name="last_name" class="form-control" placeholder="Last name" required>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mt-2">
                        <div class="col-md-4">
                            <div class="input-group-icon">
                                <i class="bi bi-gender-ambiguous"></i>
                                <select name="sex" class="form-select" required>
                                    <option value="">Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group-icon">
                                <i class="bi bi-envelope"></i>
                                <input type="email" name="email" class="form-control" placeholder="Email address" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group-icon">
                                <i class="bi bi-phone"></i>
                                <input type="text" name="phone" class="form-control"
                                       placeholder="2557XXXXXXXX"
                                       pattern="255[0-9]{9}"
                                       title="Enter phone in format 2557XXXXXXXX"
                                       required>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mt-2">
                        <div class="col-md-6">
                            <div class="password-wrapper">
                                <input type="password" name="password" id="password"
                                       class="form-control" placeholder="Password" required>
                                <span class="toggle-btn" onclick="togglePassword('password')">
                                    <i class="bi bi-eye"></i>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="password-wrapper">
                                <input type="password" name="confirm_password" id="confirm_password"
                                       class="form-control" placeholder="Confirm password" required>
                                <span class="toggle-btn" onclick="togglePassword('confirm_password')">
                                    <i class="bi bi-eye"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Teaching Assignments -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="bi bi-book"></i> Teaching Assignments
                    </div>

                    <div class="subjects-grid">
                        <?php
                        $forms = ['Form One','Form Two','Form Three','Form Four'];
                        $short = ['F.1','F.2','F.3','F.4'];
                        $subjects = mysqli_query($conn,"SELECT * FROM subjects ORDER BY stream, subject_name");
                        while($row = mysqli_fetch_assoc($subjects)){
                        ?>
                        <div class="subject-card" style="flex-direction:column;align-items:stretch;">
                            <div style="display:flex;align-items:center;gap:8px;width:100%;">
                                <span style="font-weight:600;flex:1;font-size:0.9rem;"><?= $row['subject_name'] ?></span>
                                <span class="badge-stream" style="flex-shrink:0;"><?= $row['stream'] ?></span>
                            </div>
                            <div style="display:flex;gap:4px;margin-top:6px;">
                                <?php for ($fi=0;$fi<4;$fi++): ?>
                                <label style="display:flex;align-items:center;gap:3px;font-size:11px;padding:3px 6px;border:1px solid #e5e7eb;border-radius:6px;cursor:pointer;background:#f9fafb;">
                                    <input type="checkbox" name="assignments[]" value="<?=$row['id'].'|'.$forms[$fi]?>" style="width:12px;height:12px;margin:0;cursor:pointer;">
                                    <?=$short[$fi]?>
                                </label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>

                <hr>

                <!-- Action buttons -->
                <div class="d-grid gap-3">
                    <button type="submit" name="register" class="btn-primary-app">
                        <i class="bi bi-check-circle"></i> Register Teacher
                    </button>

                    <a href="manage_teachers.php" class="btn-outline-app" target="mainFrame">
                        <i class="bi bi-list"></i> Manage Teachers
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>

<!-- Password toggle script (identical logic, just visual) -->
<script>
function togglePassword(id){
    var input = document.getElementById(id);
    var icon = event.currentTarget.querySelector('i');
    if(input.type === "password"){
        input.type = "text";
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = "password";
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}
</script>

<!-- Bootstrap JS (optional) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.alert-app').forEach(function(a){setTimeout(function(){a.style.display='none';},5000);});
</script>
<script src="../assets/js/forms.js"></script>
<script src="../assets/js/loader.js"></script>
</body>
</html>
