<?php
require_once __DIR__ . '/config.php';

// Minimal PHPMailer wrapper. Requires phpmailer/phpmailer via Composer in /vendor
require_once dirname(__DIR__) . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_email($to, $subject, $body){
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $_ENV['MAIL_HOST'] ?? 'smtp.example.com';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['MAIL_USER'] ?? '';
        $mail->Password = $_ENV['MAIL_PASS'] ?? '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int)($_ENV['MAIL_PORT'] ?? 587);

        $mail->setFrom($_ENV['MAIL_FROM'] ?? 'no-reply@example.com', $_ENV['MAIL_FROM_NAME'] ?? 'Student Time Advisor');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mail error: ' . $mail->ErrorInfo);
        return false;
    }
}