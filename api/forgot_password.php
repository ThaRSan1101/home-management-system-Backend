<?php
require 'db.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/Exception.php';
require 'PHPMailer/SMTP.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$email = isset($data['email']) ? strtolower(trim($data['email'])) : '';

if (!$email) {
    echo json_encode(['status' => 'error', 'message' => 'Email is required.']);
    exit;
}

// Check if email exists in users
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->execute([$email]);
if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode(['status' => 'error', 'message' => 'No account found with that email.']);
    exit;
}

// Generate OTP
$otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
$expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Store OTP in otp table for password reset
$stmt = $conn->prepare("INSERT INTO otp (email, otp_code, purpose, expired_at) VALUES (?, ?, 'password_reset', ?) ON DUPLICATE KEY UPDATE otp_code=VALUES(otp_code), expired_at=VALUES(expired_at)");
$stmt->execute([$email, $otp, $expires_at]);

// Send OTP email
$mail = new PHPMailer\PHPMailer\PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'arultharsan096@gmail.com';
    $mail->Password = 'dwzuvfvwhoitkfkp';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('arultharsan096@gmail.com', 'Your App Name');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Your Password Reset Code';
    $mail->Body    = "<h3>Password Reset</h3><p>Your OTP is: <strong>$otp</strong></p><p>This OTP will expire in 10 minutes.</p>";
    $mail->send();
    echo json_encode(['status' => 'success', 'message' => 'OTP sent to your email.']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Mail Error: ' . $mail->ErrorInfo]);
} 