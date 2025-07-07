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
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'No account found with that email.']);
    $stmt->close();
    exit;
}
$stmt->close();

// Generate OTP
$otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
$expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Store OTP in password_reset table
$stmt = $conn->prepare("INSERT INTO password_reset (email, code, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE code=VALUES(code), expires_at=VALUES(expires_at)");
$stmt->bind_param("sss", $email, $otp, $expires_at);
$stmt->execute();
$stmt->close();

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