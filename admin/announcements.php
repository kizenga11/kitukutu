<?php
session_start();
include "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

$result = mysqli_query($conn,"SELECT * FROM announcements ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Announcements</title>

<style>

body{
    *{
box-sizing:border-box;
}

    
font-family:'Segoe UI', sans-serif;
background:#f4f6f9;
margin:0;
padding:20px;
}
    

.header{
display:flex;
justify-content:space-between;
align-items:center;
flex-wrap:wrap;
gap:10px;
margin-bottom:25px;
}

.header h2{
margin:0;
color:#0f2b4b;
}

.back-btn{
background:#1e4a6d;
color:white;
padding:8px 16px;
text-decoration:none;
border-radius:6px;
font-size:14px;
}

.container{
max-width:1000px;
margin:auto;
}

/* FORM CARD */

.form-card{
background:white;
padding:20px;
border-radius:10px;
box-shadow:0 5px 20px rgba(0,0,0,0.05);
margin-bottom:30px;
}

.form-card h3{
margin-top:0;
color:#1e4a6d;
}

input, textarea, select{
width:100%;
padding:10px;
margin-bottom:12px;
border:1px solid #ddd;
border-radius:6px;
font-size:14px;
}

textarea{
resize:vertical;
}

button{
padding:10px 15px;
border:none;
border-radius:6px;
cursor:pointer;
font-weight:600;
}

.publish{
background:#1e4a6d;
color:white;
}

.delete{
background:#e53935;
color:white;
margin-top:10px;
}

/* ANNOUNCEMENT CARDS */

.announcement-card{
background:white;
padding:18px;
border-radius:10px;
box-shadow:0 5px 20px rgba(0,0,0,0.05);
margin-bottom:20px;
border-left:5px solid #1e4a6d;
}

.announcement-card.event{
border-left:5px solid #f4b400;
}

.announcement-card h4{
margin:0 0 8px 0;
color:#0f2b4b;
}

.type-badge{
display:inline-block;
padding:4px 10px;
font-size:12px;
border-radius:20px;
background:#e3f2fd;
color:#0f2b4b;
margin-bottom:8px;
}

.event .type-badge{
background:#fff3cd;
color:#856404;
}

.attachment-link{
display:inline-block;
margin-top:8px;
color:#1e4a6d;
font-weight:600;
text-decoration:none;
font-size:13px;
}

.meta{
font-size:12px;
color:#888;
margin-top:5px;
}

/* RESPONSIVE */

@media(max-width:600px){
body{ padding:10px; }
.form-card, .announcement-card{ padding:15px; }
.header{ flex-direction:column; align-items:flex-start; }
}

/* ===== Minimal overrides ===== */
body{ background:#fff; padding:12px; }
.container{ max-width:100%; }
.header{ margin-bottom:12px; }
.header h2{ font-size:1.1rem; }
.form-card{ border:1px solid #e5e7eb; box-shadow:none; padding:14px; margin-bottom:16px; }
.form-card h3{ font-size:1rem; margin-bottom:10px; }
input, textarea, select{ padding:0.4rem 0.6rem; font-size:0.85rem; border-radius:8px; margin-bottom:8px; }
button{ padding:0.45rem 0.8rem; border-radius:8px; font-size:0.85rem; }
.announcement-card{ border:1px solid #e5e7eb; box-shadow:none; padding:14px; border-left:4px solid #1e4a6d; margin-bottom:14px; }
.announcement-card h4{ font-size:1rem; }
</style>
</head>
<?php if(isset($_GET['success'])){ ?>
<div id="autoAlert" style="background:#d4edda;color:#155724;padding:8px 32px 8px 12px;border-radius:8px;margin-bottom:10px;position:relative;font-size:0.85rem;">
Status updated successfully.
<button type="button" onclick="this.parentElement.style.display='none'" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;font-size:1.1rem;cursor:pointer;">&times;</button>
</div>
<?php } ?>

<body>

<div class="container">

<div class="header">
<h2>Manage Announcements & Events</h2>
</div>

<!-- ADD FORM -->

<div class="form-card">

<h3>Add New Announcement / Event</h3>

<form action="save_announcement.php" method="POST" enctype="multipart/form-data">

<input type="text" name="title" placeholder="Title" required>

<select name="type">
<option value="Announcement">Announcement</option>
<option value="Event">Event</option>
</select>

<textarea name="content" rows="4" placeholder="Write details..." required></textarea>

<label>Attach Image or PDF (optional)</label>
<input type="file" name="attachment">

<button type="submit" class="publish">Publish</button>

</form>

</div>

<!-- LISTING -->

<?php while($row=mysqli_fetch_assoc($result)){ ?>

<div class="announcement-card <?php echo strtolower($row['type']); ?>">

<span class="type-badge"><?php echo $row['type']; ?></span>

<h4><?php echo $row['title']; ?></h4>

<p><?php echo nl2br($row['content']); ?></p>

<?php if($row['attachment']){ ?>
<a class="attachment-link"
href="../uploads/announcements/<?php echo $row['attachment']; ?>"
target="_blank">
📎 View Attachment
</a>
<?php } ?>

<div class="meta">
Status: <?php echo ucfirst($row['status']); ?> |
Posted on: <?php echo date("d M Y", strtotime($row['created_at'])); ?>
</div>

<!-- STATUS FORM -->
<form action="update_status.php" method="POST" style="margin-top:10px;">

<input type="hidden" name="id" value="<?php echo $row['id']; ?>">

<select name="status">
<option value="published" <?php if($row['status']=='published') echo 'selected'; ?>>
Publish
</option>
<option value="draft" <?php if($row['status']=='draft') echo 'selected'; ?>>
Draft
</option>
</select>

<button type="submit" class="publish">Update</button>

</form>

<!-- DELETE FORM -->
<form action="delete_announcement.php" method="POST"
onsubmit="return confirm('Delete this item?');"
style="margin-top:10px;">

<input type="hidden" name="id" value="<?php echo $row['id']; ?>">

<button class="delete">Delete</button>

</form>

</div>

<?php } ?>



</div>

    <script>
setTimeout(function(){
    let alertBox = document.getElementById('autoAlert');
    if(alertBox){ alertBox.style.display = "none"; }
},5000);
</script>

</body>
</html>
