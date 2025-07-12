<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'db.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/Exception.php';
require 'PHPMailer/SMTP.php';

header("Access-Control-Allow-Origin: *");  // For development
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'No data received.']);
    exit;
}

$email = $data['email'] ?? '';
$fullName = $data['fullName'] ?? '';
$phone = $data['phone'] ?? '';
$address = $data['address'] ?? '';
$password = $data['password'] ?? '';
$nic = $data['nic'] ?? '';
$userType = $data['userType'] ?? 'customer';

// NIC validation: must be 12 digits or 9 digits followed by 'V' or 'v'
if ($nic && !preg_match('/^(\d{12}|\d{9}[Vv])$/', $nic)) {
    echo json_encode(['status' => 'error', 'message' => 'NIC must be 12 digits or 9 digits followed by V.']);
    exit;
}

if (!$email || !$fullName || !$phone || !$address || !$password) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit;
}

// Check if email already exists in users table
$checkStmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
$checkStmt->execute([$email]);
if ($checkStmt->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode(['status' => 'error', 'message' => 'An account with this email already exists.']);
    exit;
}

// Check if NIC already exists (if provided)
if ($nic) {
    $checkNicStmt = $conn->prepare("SELECT user_id FROM users WHERE NIC = ?");
    $checkNicStmt->execute([$nic]);
    if ($checkNicStmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(['status' => 'error', 'message' => 'An account with this NIC already exists.']);
        exit;
    }
}

// Generate 6-digit OTP
$otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
$expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

// First, delete any existing OTPs for this email
$deleteStmt = $conn->prepare("DELETE FROM otp WHERE email = ?");
$deleteStmt->execute([$email]);

// Save new OTP to database
$stmt = $conn->prepare("INSERT INTO otp (email, otp_code, purpose, expired_at) VALUES (?, ?, 'registration', ?)");
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->errorInfo()[2]]);
    exit;
}
if (!$stmt->execute([$email, $otp, $expires_at])) {
    echo json_encode(['status' => 'error', 'message' => 'Execute failed: ' . $stmt->errorInfo()[2]]);
    exit;
}

// Send OTP email
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'arultharsan096@gmail.com';     // Your Gmail
    $mail->Password = 'dwzuvfvwhoitkfkp';        // App password (not Gmail password)
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('arultharsan096@gmail.com', 'ServiceHub');
    $mail->addAddress($email, $fullName);
    $mail->isHTML(true);
    $mail->Subject = 'Your OTP for Registration';
    $mail->Body    = "<h3>Hello $fullName,</h3><p>Your OTP is: <strong>$otp</strong></p><p>This OTP will expire in 15 minutes.</p>";

    $mail->send();
    echo json_encode([
        'status' => 'success', 
        'message' => 'OTP sent',
        'debug' => [
            'otp' => $otp,
            'expires_at' => $expires_at,
            'current_time' => date('Y-m-d H:i:s')
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Mail Error: ' . $mail->ErrorInfo]);
} 