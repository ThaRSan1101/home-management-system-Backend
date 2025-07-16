<?php
require 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Read and decode JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Sanitize input
$email = isset($data['email']) ? strtolower(trim($data['email'])) : '';
$otp = isset($data['code']) ? trim($data['code']) : '';

if (!$email || !$otp) {
    echo json_encode(['status' => 'error', 'message' => 'Email and OTP are required.']);
    exit;
}

// Prepare and execute query to fetch the OTP from otp table for password reset
$stmt = $conn->prepare("SELECT otp_code FROM otp WHERE email = ? AND purpose = 'password_reset' ");
$stmt->execute([$email]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired OTP.']);
    exit;
}

$storedOtp = $row['otp_code'];

// Compare the OTPs
if ($otp !== $storedOtp) {
    echo json_encode(['status' => 'error', 'message' => 'Incorrect OTP.']);
    exit;
}

echo json_encode(['status' => 'success', 'message' => 'OTP verified.']);
