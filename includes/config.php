<?php

// ============================================================
//  DATABASE CONNECTION
// ============================================================
// Local (XAMPP): create DB `kitukutu_db` in phpMyAdmin.
// Live (InfinityFree): fill in the credentials below when ready.

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'kitukutu_db';

// Switch to InfinityFree credentials when deployed.
// InfinityFree subdomains typically end with `.epizy.com`.
$httpHost = strtolower($_SERVER['HTTP_HOST'] ?? '');
if ($httpHost !== '' && preg_match('/\.epizy\.com$/', $httpHost)) {
    // TODO: Replace with InfinityFree MySQL credentials.
    // Example values (placeholders):
    // $host = 'sqlXXX.epizy.com';
    // $user = 'epiz_XXXXXXX';
    // $pass = '...';
    // $db   = 'epiz_XXXXXXX_kitukutu';
}

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}

// ============================================================
//  SMS CONFIG
// ============================================================
define("SMS_USERNAME", "kizenga");
define("SMS_API_KEY",  "atsk_f2fc87e307be94d77b944f1fca9eb583e2f268d7d089195b5b01cde5c54fd4cc2549520f"); // <-- badilisha pia!
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
