<?php
require(__DIR__ . "/../../engine/engine.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

OnlyPOST();

// Sanitize and validate inputs
$name     = trim(filter_var($_POST['name'] ?? '', FILTER_SANITIZE_STRING));
$email    = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
$subject  = trim(filter_var($_POST['subject'] ?? 'General Inquiry', FILTER_SANITIZE_STRING));
$service  = trim(filter_var($_POST['service'] ?? 'Unspecified', FILTER_SANITIZE_STRING));
$message  = trim(filter_var($_POST['message'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$source   = trim(filter_var($_POST['source'] ?? 'Unknown', FILTER_SANITIZE_URL));

// Header injection protection
$name  = str_replace(["\r", "\n"], '', $name);
$email = str_replace(["\r", "\n"], '', $email);
$subject = str_replace(["\r", "\n"], '', $subject);

// Validation
if (!$name || !$email || !$message) {
    return new Response(false, "Missing required fields.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return new Response(false, "Invalid email address.");
}

if (strlen($message) > 5000 || strlen($subject) > 255 || strlen($source) > 255) {
    return new Response(false, "Input too long.");
}

// Optional: Add reCAPTCHA check here

$mail = new PHPMailer(true);

try {
    $kk_credentials = \Kickback\Backend\Config\ServiceCredentials::instance();

    $mail->isSMTP();
    $mail->SMTPAuth   = filter_var($kk_credentials["smtp_auth"], FILTER_VALIDATE_BOOLEAN);
    $mail->SMTPSecure = $kk_credentials["smtp_secure"];
    $mail->Host       = $kk_credentials["smtp_host"];
    $mail->Port       = intval($kk_credentials["smtp_port"]);
    $mail->Username   = $kk_credentials["smtp_username"];
    $mail->Password   = $kk_credentials["smtp_password"];

    $mail->setFrom($kk_credentials["smtp_from_email"], $kk_credentials["smtp_from_name"]);
    $mail->addAddress("horsemen@kickback-kingdom.com", "Craftsmen's Guild Inquiry");
    $mail->addReplyTo($email, $name);

    $mail->isHTML(true);
    $mail->Subject = "[Website Inquiry] " . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');

    $mail->Body = "
        <h2>New Contact Inquiry</h2>
        <p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
        <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
        <p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
        <p><strong>Service Interested In:</strong> " . htmlspecialchars($service) . "</p>
        <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>
        <hr>
        <p><small><strong>Source:</strong> " . htmlspecialchars($source) . "</small></p>
    ";

    $mail->AltBody = "Name: {$name}\nEmail: {$email}\nSubject: {$subject}\nService: {$service}\nMessage: {$message}\nSource: {$source}";

    $mail->send();

    return new Response(true, "Message sent successfully.");
} catch (Exception $e) {
    error_log("Contact form error: " . $mail->ErrorInfo);
    return new Response(false, "Something went wrong. Please try again later.");
}

?>