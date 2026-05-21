<?php
include "includes/config.php";

$ann = mysqli_query($conn,"
SELECT * FROM announcements
WHERE status='published'
ORDER BY id DESC
LIMIT 6
");

echo "<h2>TEST ANNOUNCEMENTS PAGE (PUBLISHED ONLY)</h2>";

if(mysqli_num_rows($ann) > 0){

    while($row = mysqli_fetch_assoc($ann)){

        echo "<hr>";
        echo "<strong>ID:</strong> ".$row['id']."<br>";
        echo "<strong>Title:</strong> ".$row['title']."<br>";
        echo "<strong>Type:</strong> ".$row['type']."<br>";
        echo "<strong>Status:</strong> ".$row['status']."<br>";
        echo "<strong>Created:</strong> ".$row['created_at']."<br>";
        echo "<strong>Content:</strong> ".$row['content']."<br>";

        if(!empty($row['attachment'])){
            echo "<strong>Attachment:</strong> 
            <a href='uploads/announcements/".$row['attachment']."' target='_blank'>
            View Document
            </a><br>";
        }

    }

} else {

    echo "<p style='color:red;'>NO PUBLISHED ANNOUNCEMENTS FOUND</p>";

}
?>
