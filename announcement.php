<?php
include "includes/config.php";

if(!isset($_GET['id'])){
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id']);

$result = mysqli_query($conn,"
SELECT * FROM announcements
WHERE id='$id' AND status='published'
");

if(mysqli_num_rows($result) == 0){
    echo "Announcement not found.";
    exit();
}

$row = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($row['title']); ?> | Amali Kitukutu</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>

body{
    margin:0;
    font-family:'Segoe UI', sans-serif;
    background:#f4f6f9;
}

.container{
    max-width:800px;
    margin:40px auto;
    padding:20px;
}

.card{
    background:white;
    padding:30px;
    border-radius:15px;
    box-shadow:0 10px 30px rgba(0,0,0,0.08);
}

.title{
    margin-top:0;
    color:#0f2b4b;
}

.date{
    color:#777;
    font-size:14px;
    margin-bottom:20px;
}

.content{
    line-height:1.8;
    font-size:16px;
    color:#333;
}

.attachment{
    margin-top:25px;
}

.attachment a{
    background:#1e4a6d;
    color:white;
    padding:10px 20px;
    border-radius:25px;
    text-decoration:none;
    display:inline-block;
}

.attachment a:hover{
    background:#f4b400;
    color:#0f2b4b;
}

.back-btn{
    display:inline-block;
    margin-top:30px;
    text-decoration:none;
    color:#1e4a6d;
    font-weight:600;
}

.back-btn:hover{
    color:#f4b400;
}

/* Mobile */
@media(max-width:600px){

    .container{
        margin:20px;
        padding:10px;
    }

    .card{
        padding:20px;
    }

    .content{
        font-size:15px;
    }

}

</style>
</head>

<body>

<div class="container">

<div class="card">

<h2 class="title">
<?php echo htmlspecialchars($row['title']); ?>
</h2>

<div class="date">
📅 <?php echo date("d F Y", strtotime($row['created_at'])); ?>
</div>

<hr>

<div class="content">
<?php echo nl2br(htmlspecialchars($row['content'])); ?>
</div>

<?php if(!empty($row['attachment'])){ ?>
<div class="attachment">
<br>
<a href="uploads/announcements/<?php echo $row['attachment']; ?>" target="_blank">
📎 View Attachment
</a>
</div>
<?php } ?>

<a href="index.php" class="back-btn">
← Back to Home
</a>

</div>

</div>

</body>
</html>
