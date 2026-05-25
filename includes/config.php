<?php

// ============================================================
//  DATABASE CONNECTION
// ============================================================
// Local (XAMPP): create DB `kitukutu_db` in phpMyAdmin.
// Railway (Production): credentials loaded from environment.

$isRailway = getenv('MYSQLHOST') !== false;

if ($isRailway) {
    // Railway (Production)
    $host = getenv('MYSQLHOST');
    $port = (int) getenv('MYSQLPORT');
    $user = getenv('MYSQLUSER');
    $pass = getenv('MYSQLPASSWORD');
    $db   = getenv('MYSQLDATABASE');
} else {
    // Local (XAMPP)
    $host = 'localhost';
    $port = 3306;
    $user = 'root';
    $pass = '';
    $db   = 'kitukutu_db';
}

$conn = mysqli_connect($host, $user, $pass, $db, $port);
if (!$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}

// ============================================================
//  SMS CONFIG
// ============================================================
define("SMS_USERNAME",  "kizenga");
define("SMS_API_KEY",   "atsk_26c261778f6c0deb889a5297a06656f86f5a6d0f3d86c36a8a57815b106d59ebd88ee06f");
define("SMS_SENDER",    "AMALI12");

// ============================================================
//  ZOHO SMTP — Password reset emails
//  Replace the three values below with your Zoho credentials.
// ============================================================
define("ZOHO_FROM_EMAIL", getenv('ZOHO_FROM_EMAIL') ?: "kizengagodlove5@gmail.com");
define("ZOHO_FROM_NAME",  getenv('ZOHO_FROM_NAME')  ?: "Kitukutu School System");
define("ZOHO_SMTP_PASS",  getenv('ZOHO_SMTP_PASS')  ?: "phpojppssedahydx");
 

// ============================================================
//  SYSTEM LOCK
//  Weka $site_locked = true  → mfumo umezuiwa
//  Weka $site_locked = false → mfumo unafanya kazi kawaida
// ============================================================
$site_locked = false;

if ($site_locked) {
    $current_page = basename($_SERVER['PHP_SELF']);
    if ($current_page !== 'maintenance.php') {
        header('Location: /maintenance.php');
        exit();
    }
}