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
define("SMS_API_KEY",   "atsk_f2fc87e307be94d77b944f1fca9eb583e2f268d7d089195b5b01cde5c54fd4cc2549520f");
define("SMS_SENDER",    "AMALI12");

// Beem.Africa (period reminder SMS via cron)
define("BEEM_API_KEY",  "1b8769fe572c471c");
define("BEEM_SECRET",   "Nzg3NDNiMDE3MTEyYzAwZWY2OTI4OTQyM2Q1YzlkOWM4ZDhmYWNiZjRmMDEyODYxMGE4NTFkN2I5NTBmZjVkZg==");

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