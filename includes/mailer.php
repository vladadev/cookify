<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once ROOT . '/vendor/autoload.php';

function send_email(string $to, string $to_name, string $subject, string $body): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to, $to_name);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer error: ' . $mail->ErrorInfo);
        return false;
    }
}

function send_activation_email(string $email, string $name, string $token): bool {
    $link    = BASE_URL . '/pages/activate.php?token=' . urlencode($token);
    $subject = 'Activate your Cookify account';
    $body    = "
        <h2>Welcome to Cookify, {$name}!</h2>
        <p>Please click the link below to activate your account:</p>
        <p><a href='{$link}'>{$link}</a></p>
        <p>If you did not register, please ignore this email.</p>
    ";
    return send_email($email, $name, $subject, $body);
}

function send_lock_warning_email(string $email, string $name): bool {
    $subject = 'Cookify — Account Locked';
    $body    = "
        <h2>Account Security Alert</h2>
        <p>Hello {$name},</p>
        <p>Your Cookify account has been locked due to 3 failed login attempts within 5 minutes.</p>
        <p>If this was you, please contact the administrator to unlock your account.</p>
        <p>If this was not you, your account may be under attack — please change your password immediately after unlocking.</p>
    ";
    return send_email($email, $name, $subject, $body);
}
