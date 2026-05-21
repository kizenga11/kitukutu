<?php
include "../includes/config.php";
date_default_timezone_set("Africa/Dar_es_Salaam");

$current_day = date("l");
$target_time = date("H:i", strtotime("+10 minutes"));

$query = mysqli_query($conn,"
    SELECT periods.*, teachers.phone, teachers.first_name, subjects.subject_name
    FROM periods
    JOIN teachers ON periods.teacher_id = teachers.id
    JOIN subjects ON periods.subject_id = subjects.id
    WHERE periods.day='$current_day'
    AND periods.start_time='$target_time'
    AND periods.notification_sent=0
");

while($row = mysqli_fetch_assoc($query)){

    $phone = $row['phone'];
    $teacher = $row['first_name'];
    $subject = $row['subject_name'];
    $time = $row['start_time'];

    if(empty($phone)) continue;

    $message = "Reminder: You have $subject at $time.";

    $api_key = "1b8769fe572c471c";
    $secret = "Nzg3NDNiMDE3MTEyYzAwZWY2OTI4OTQyM2Q1YzlkOWM4ZDhmYWNiZjRmMDEyODYxMGE4NTFkN2I5NTBmZjVkZg==";

    $data = [
        "source_addr" => "INFO",
        "encoding" => 0,
        "message" => $message,
        "recipients" => [
            [
                "recipient_id" => 1,
                "dest_addr" => $phone
            ]
        ]
    ];

    $ch = curl_init("https://apisms.beem.africa/v1/send");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type:application/json",
        "Authorization:Basic ". base64_encode("$api_key:$secret")
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    // HIZI NDIZO MUHIMU
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $response = curl_exec($ch);
    curl_close($ch);

    mysqli_query($conn,"UPDATE periods SET notification_sent=1 WHERE id=".$row['id']);
}
?>