<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

$result = mysqli_query($conn,"SELECT * FROM messages ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Messages</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>

body{
font-family:'Segoe UI',sans-serif;
background:#f4f6f9;
margin:0;
padding:20px;
}

.container{
max-width:900px;
margin:auto;
}

h2{
color:#0f2b4b;
}

.card{
background:white;
padding:20px;
margin-bottom:20px;
border-radius:12px;
box-shadow:0 8px 25px rgba(0,0,0,0.06);
border-left:6px solid #1e4a6d;
}

.card.unread{
border-left:6px solid #f4b400;
background:#fffdf5;
}

.name{
font-weight:700;
font-size:16px;
}

.email{
color:#1e4a6d;
font-weight:600;
font-size:14px;
}

.date{
font-size:13px;
color:#777;
margin-top:10px;
}

.actions{
margin-top:15px;
display:flex;
gap:10px;
flex-wrap:wrap;
}

.btn{
padding:8px 15px;
border:none;
border-radius:20px;
cursor:pointer;
font-size:13px;
}

.delete{
background:#e53935;
color:white;
}

.mark{
background:#1e4a6d;
color:white;
}
    
    .card {
    word-wrap: break-word;
    overflow-wrap: break-word;
    word-break: break-word;
}
    
    .back-btn{
display:inline-block;
margin-bottom:15px;
text-decoration:none;
background:#1e4a6d;
color:white;
padding:8px 16px;
border-radius:20px;
font-size:14px;
}

.back-btn:hover{
background:#163754;
}



@media(max-width:600px){
.card{
padding:15px;
}
}

</style>

</head>
<body>

<div class="container">
<a href="dashboard.php" class="back-btn">← Back to Dashboard</a>

<h2>Contact Messages</h2>

<?php while($row=mysqli_fetch_assoc($result)){ ?>

<div class="card <?php echo $row['status']=='unread' ? 'unread' : ''; ?>">

<div class="name"><?php echo htmlspecialchars($row['name']); ?></div>
<div class="email"><?php echo htmlspecialchars($row['email']); ?></div>

<br>
<?php echo nl2br(htmlspecialchars($row['message'])); ?>

<div class="date">
<?php echo date("d M Y H:i", strtotime($row['created_at'])); ?>
</div>

<div class="actions">

<!-- MARK AS READ -->
<?php if($row['status']=='unread'){ ?>
<form action="mark_read.php" method="POST">
<input type="hidden" name="id" value="<?php echo $row['id']; ?>">
<button class="btn mark">Mark as Read</button>
</form>
<?php } ?>

<!-- DELETE -->
<form action="delete_message.php" method="POST" id="del-msg-<?php echo $row['id']; ?>">
<input type="hidden" name="id" value="<?php echo $row['id']; ?>">
<button type="button" class="btn delete"
  data-bs-toggle="modal" data-bs-target="#deleteConfirmModal"
  data-delete-form="del-msg-<?php echo $row['id']; ?>"
  data-delete-msg="Delete this message? This cannot be undone.">Delete</button>
</form>

</div>

</div>

<?php } ?>

</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center pt-1 px-4">
        <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size:2.4rem;"></i>
        <h6 class="fw-bold mt-2 mb-1">Confirm Delete</h6>
        <p class="text-muted small mb-0" id="deleteConfirmMsg">This action cannot be undone.</p>
      </div>
      <div class="modal-footer border-0 justify-content-center gap-2 pt-2">
        <button type="button" class="btn btn-light btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger btn-sm px-4" id="deleteConfirmBtn">
          <i class="bi bi-trash me-1"></i>Delete
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
  var modal = document.getElementById('deleteConfirmModal');
  var confirmBtn = document.getElementById('deleteConfirmBtn');
  var pendingForm = null;

  modal.addEventListener('show.bs.modal', function (e) {
    var t = e.relatedTarget;
    if (!t) return;
    document.getElementById('deleteConfirmMsg').textContent =
      t.getAttribute('data-delete-msg') || 'This action cannot be undone.';
    pendingForm = document.getElementById(t.getAttribute('data-delete-form'));
  });

  confirmBtn.addEventListener('click', function () {
    if (pendingForm) pendingForm.submit();
    bootstrap.Modal.getInstance(modal).hide();
  });
})();
</script>

</body>
</html>
