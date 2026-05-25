<?php
/**
 * ZOHO SMTP TEST — Futa faili hili baada ya kutatua tatizo!
 * Fikia: http://amalikitukutu.unaux.com/test_mail.php
 */

// Weka credentials hapa moja kwa moja (kwa test tu)
$zoho_email = "kizengagodlove5@gmail.com";
$zoho_pass  = "phpojppssedahydx";
$send_to    = "kizengagodlove5@gmail.com";

// -----------------------------------------------
require_once __DIR__ . '/includes/phpmailer/Exception.php';
require_once __DIR__ . '/includes/phpmailer/PHPMailer.php';
require_once __DIR__ . '/includes/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;

echo "<h2>🔧 Zoho SMTP Test</h2>";
echo "<pre style='background:#f4f4f4;padding:16px;border-radius:8px;font-size:13px;'>";

$mail = new PHPMailer(true);

try {
    // Debug level 2 = inaonyesha mazungumzo yote na SMTP server
    $mail->SMTPDebug  = 2;
    $mail->Debugoutput = function($str, $level) {
        echo htmlspecialchars($str) . "\n";
    };

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $zoho_email;
    $mail->Password   = $zoho_pass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom($zoho_email, 'Kitukutu Test');
    $mail->addAddress($send_to);

    $mail->isHTML(true);
    $mail->Subject = 'Test Email - Zoho SMTP';
    $mail->Body    = '<h3>✅ SMTP inafanya kazi!</h3><p>Email imetumwa kwa mafanikio.</p>';

    $mail->send();
    echo "</pre>";
    echo "<div style='background:#d4edda;color:#155724;padding:16px;border-radius:8px;margin-top:12px;font-size:15px;'>
            ✅ <strong>Email imetumwa!</strong> Angalia inbox yako.
          </div>";

} catch (MailerException $e) {
    echo "</pre>";
    echo "<div style='background:#f8d7da;color:#721c24;padding:16px;border-radius:8px;margin-top:12px;'>
            ❌ <strong>Error:</strong> " . htmlspecialchars($mail->ErrorInfo) . "
          </div>";
}

echo "<br><p style='color:#888;font-size:12px;'>⚠️ Futa faili hili <strong>test_mail.php</strong> baada ya kumaliza test!</p>";
?>