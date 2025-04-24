<?php
// --- CORS HEADERS ---
$allowed_domains = [
    'https://craftsmens-guild.com',
    'https://www.craftsmens-guild.com'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_domains)) {
    header("Access-Control-Allow-Origin: $origin");
}

header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Max-Age: 86400"); // Cache preflight for 1 day

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}


require(__DIR__ . "/../../engine/engine.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use Kickback\Backend\Models\Response;
OnlyPOST();

// Sanitize and validate inputs
$name    = isset($_POST['name']) ? trim(strip_tags($_POST['name'])) : '';
$email   = isset($_POST['email']) ? trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL)) : '';
$subject = isset($_POST['subject']) ? trim(strip_tags($_POST['subject'])) : 'General Inquiry';
$service = isset($_POST['service']) ? trim(strip_tags($_POST['service'])) : 'Unspecified';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$source  = isset($_POST['source']) ? trim(filter_var($_POST['source'], FILTER_SANITIZE_URL)) : 'Unknown';

// Header injection protection
$name    = str_replace(["\r", "\n"], '', $name);
$email   = str_replace(["\r", "\n"], '', $email);
$subject = str_replace(["\r", "\n"], '', $subject);

// Validation: required fields
if (empty($name) || empty($email) || empty($message)) {
    return new Response(false, "Missing required fields.");
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return new Response(false, "Invalid email address.");
}

// Validate lengths
if (strlen($message) > 5000) {
    return new Response(false, "Message is too long (max 5000 characters).");
}
if (strlen($subject) > 255 || strlen($source) > 255 || strlen($name) > 255 || strlen($service) > 255) {
    return new Response(false, "One or more fields exceed allowed length.");
}

// Optional: Validate name with regex (letters, spaces, dashes, apostrophes)
if (!preg_match("/^[\p{L} \-']+$/u", $name)) {
    return new Response(false, "Name contains invalid characters.");
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