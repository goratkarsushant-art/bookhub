<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/*
 * Gmail SMTP configuration
 * Use a Gmail App Password (16 characters), NOT your normal Gmail password.
 */
const SMTP_EMAIL = 'goratkarsushant@gmail.com';
const SMTP_PASSWORD = 'oyyq zqpm wiao lwui';

/*
 * Works even when Composer is not installed.
 * The project already contains PHPMailer in PHPMailer-FE_v4.11/src.
 */
$phpmailerBase = __DIR__ . '/PHPMailer-FE_v4.11/src/';
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} elseif (file_exists($phpmailerBase . 'Exception.php')) {
    require_once $phpmailerBase . 'Exception.php';
    require_once $phpmailerBase . 'PHPMailer.php';
    require_once $phpmailerBase . 'SMTP.php';
}

function send_mail($to, $name, $subject, $body) {
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_EMAIL;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(SMTP_EMAIL, 'BookHub Library');
        $mail->addAddress($to, $name ?: $to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        return $mail->send();
    } catch (Exception $e) {
        error_log('BookHub mail error: ' . $mail->ErrorInfo);
        return false;
    }
}

function send_otp($to, $name, $otp) {
    return send_mail(
        $to,
        $name,
        'BookHub - Password Reset OTP',
        '<div style="font-family:Arial,sans-serif;padding:20px">
            <h2>Password Reset</h2>
            <p>Hello ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>
            <p>Your OTP is:</p>
            <h1 style="letter-spacing:5px">' . htmlspecialchars($otp, ENT_QUOTES, 'UTF-8') . '</h1>
            <p>This OTP is valid for 5 minutes.</p>
        </div>'
    );
}

function send_login_alert($to, $name) {
    return send_mail(
        $to,
        $name,
        'BookHub - Login Successful',
        '<div style="font-family:Arial,sans-serif;padding:20px">
            <h2>Login Successful</h2>
            <p>Hello <b>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</b>,</p>
            <p>Your BookHub account login was successful.</p>
            <p><b>Date & Time:</b> ' . date('d M Y, h:i A') . '</p>
            <p>If this login was not made by you, please change your password.</p>
        </div>'
    );
}
?>