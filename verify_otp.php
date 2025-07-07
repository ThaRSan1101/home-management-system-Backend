<?php
require 'db.php';

header("Access-Control-Allow-Origin: *");
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
$otp = $data['otp'] ?? '';
$nic = $data['nic'] ?? '';
$userType = $data['userType'] ?? 'customer';

if (!$email || !$fullName || !$phone || !$address || !$password || !$otp) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit;
}

// First, check if there's any OTP for this email
$checkEmailStmt = $conn->prepare("SELECT * FROM otp WHERE email = ? ORDER BY created_at DESC LIMIT 1");
$checkEmailStmt->bind_param("s", $email);
$checkEmailStmt->execute();
$result = $checkEmailStmt->get_result();
$latestOtp = $result->fetch_assoc();
$checkEmailStmt->close();

if (!$latestOtp) {
    echo json_encode(['status' => 'error', 'message' => 'No OTP found for this email. Please request a new OTP.']);
    exit;
}

// Get current time in the same format as the database
$currentTime = date('Y-m-d H:i:s');

// Verify OTP - check the latest OTP for this email with proper time comparison
$stmt = $conn->prepare("SELECT * FROM otp WHERE email = ? AND otp_code = ? AND purpose = 'registration' AND expired_at > ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("sss", $email, $otp, $currentTime);
$stmt->execute();
$result = $stmt->get_result();
$otpRecord = $result->fetch_assoc();
$stmt->close();

if (!$otpRecord) {
    // Check if OTP exists but is expired
    $expiredStmt = $conn->prepare("SELECT * FROM otp WHERE email = ? AND otp_code = ? AND purpose = 'registration' AND expired_at <= ?");
    $expiredStmt->bind_param("sss", $email, $otp, $currentTime);
    $expiredStmt->execute();
    $result = $expiredStmt->get_result();
    $expiredOtp = $result->fetch_assoc();
    $expiredStmt->close();
    
    if ($expiredOtp) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'OTP has expired. Please request a new OTP.',
            'debug' => [
                'current_time' => $currentTime,
                'expired_at' => $expiredOtp['expired_at'],
                'otp_code' => $expiredOtp['otp_code']
            ]
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid OTP. Please check and try again.']);
    }
    exit;
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert user into database
$insertStmt = $conn->prepare("INSERT INTO users (name, email, password, phone_number, address, NIC, user_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
$insertStmt->bind_param("sssssss", $fullName, $email, $hashedPassword, $phone, $address, $nic, $userType);

if ($insertStmt->execute()) {
    // Delete all OTPs for this email (clean up)
    $deleteStmt = $conn->prepare("DELETE FROM otp WHERE email = ?");
    $deleteStmt->bind_param("s", $email);
    $deleteStmt->execute();
    $deleteStmt->close();
    
    echo json_encode(['status' => 'success', 'message' => 'Registration successful!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Registration failed: ' . $insertStmt->error]);
}

$insertStmt->close();
?>
