<?php

$api_key = "1b8769fe572c471c";
$secret = "Nzg3NDNiMDE3MTEyYzAwZWY2OTI4OTQyM2Q1YzlkOWM4ZDhmYWNiZjRmMDEyODYxMGE4NTFkN2I5NTBmZjVkZg==";

$phone = "255712978722"; // WEKA NAMBA YAKO HAPA
$message = "Test SMS from School System.";

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
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);

$response = curl_exec($ch);

if(curl_errno($ch)){
    echo "cURL Error: " . curl_error($ch);
} else {
    echo "Response:<br><br>";
    echo $response;
}

curl_close($ch);
?>