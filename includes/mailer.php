<?php
/**
 * Zoho SMTP mailer — thin wrapper around PHPMailer.
 * Credentials are defined as constants in includes/config.php.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;

require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

/**
 * Send an HTML email via Zoho SMTP.
 *
 * @param  string $toEmail   Recipient address
 * @param  string $toName    Recipient display name
 * @param  string $subject
 * @param  string $htmlBody  Full HTML content
 * @return bool              true on success
 */
function sendMailZoho(string $toEmail, string $toName, string $subject, string $htmlBody): bool
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = ZOHO_FROM_EMAIL;
        $mail->Password   = ZOHO_SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(ZOHO_FROM_EMAIL, ZOHO_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        $mail->send();
        return true;
    } catch (MailerException $e) {
        error_log('Mailer error: ' . $mail->ErrorInfo);
        return false;
    }
}
