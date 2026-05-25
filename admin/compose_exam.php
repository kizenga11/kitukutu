<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include '../includes/config.php';

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

$parent_exam_id = (int) $_GET['exam_id'];

// GET PARENT EXAM
$parent = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT * FROM exams WHERE id = $parent_exam_id"
));

// SAVE COMPOSITION
if(isset($_POST['save_composition'])){

    mysqli_query($conn, 
        "DELETE FROM exam_composition WHERE parent_exam_id = $parent_exam_id"
    );

    if(isset($_POST['child_exams'])){
        foreach($_POST['child_exams'] as $child){
            $child = (int)$child;

            mysqli_query($conn, "
                INSERT INTO exam_composition (parent_exam_id, child_exam_id)
                VALUES ($parent_exam_id, $child)
            ");
        }
    }

    $success = "Composition updated successfully.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Compose Exam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body{ background:#fff; padding:16px; }
        .card{ border:1px solid #e5e7eb; border-radius:10px; box-shadow:none; }
    </style>
</head>

<body>

<div class="container-fluid p-0">

    <h4 style="font-size:1rem; font-weight:700; margin-bottom:12px;">Compose Exam: <?= $parent['exam_name']; ?></h4>

    <?php if(isset($success)){ ?>
        <div class="alert alert-success alert-dismissible fade show" style="border-radius:10px; padding:0.5rem 2rem 0.5rem 0.8rem; font-size:0.85rem;"><?= $success; ?>
<button type="button" class="btn-close" data-bs-dismiss="alert" style="padding:0.6rem;font-size:0.7rem;"></button>
</div>
    <?php } ?>

    <div class="card">
        <div class="card-body" style="padding:14px;">

            <form method="POST">

                <?php
                $exams = mysqli_query($conn, 
                    "SELECT * FROM exams 
                     WHERE id != $parent_exam_id 
                     ORDER BY id DESC"
                );

                while($exam = mysqli_fetch_assoc($exams)){

                    // Check kama tayari imechaguliwa
                    $check = mysqli_query($conn, "
                        SELECT * FROM exam_composition 
                        WHERE parent_exam_id = $parent_exam_id 
                        AND child_exam_id = {$exam['id']}
                    ");

                    $checked = mysqli_num_rows($check) ? "checked" : "";
                ?>

                    <div class="form-check">
                        <input class="form-check-input" 
                               type="checkbox" 
                               name="child_exams[]" 
                               value="<?= $exam['id']; ?>"
                               <?= $checked; ?>>

                        <label class="form-check-label">
                            <?= $exam['exam_name']; ?>
                        </label>
                    </div>

                <?php } ?>

                <button type="submit" name="save_composition" 
                        class="btn btn-primary mt-3">
                    Save Composition
                </button>

                <a href="exam_management.php" 
                   class="btn btn-secondary mt-3" target="mainFrame">
                   Back
                </a>

            </form>

        </div>
    </div>

</div>

<script>
document.querySelectorAll('.alert-dismissible').forEach(function(a){setTimeout(function(){a.classList.remove('show');a.style.display='none';},5000);});
</script>
<script src="../assets/js/forms.js"></script>
</body>
</html>
