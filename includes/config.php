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
define("SMS_USERNAME", "kizenga");
define("SMS_API_KEY",  "atsk_f2fc87e307be94d77b944f1fca9eb583e2f268d7d089195b5b01cde5c54fd4cc2549520f");
define("SMS_SENDER",   "AMALI12");

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