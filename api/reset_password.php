<?php
require 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Get and sanitize input
$data = json_decode(file_get_contents("php://input"), true);
$email = isset($data['email']) ? strtolower(trim($data['email'])) : '';
$otp = isset($data['otp']) ? trim($data['otp']) : '';
$newPassword = isset($data['newPassword']) ? trim($data['newPassword']) : '';

// Check required fields
if (!$email || !$otp || !$newPassword) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit;
}

// Optional: Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
    exit;
}

// Verify OTP from otp table for password reset
$stmt = $conn->prepare("SELECT * FROM otp WHERE email = ? AND otp_code = ? AND purpose = 'password_reset'");
$stmt->execute([$email, $otp]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid or expired OTP.'
    ]);
    exit;
}

// Hash and update password
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt->execute([$hashedPassword, $email]);

// Optional: check if any row was updated
if ($stmt->rowCount() === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Password reset failed. User may not exist.']);
    exit;
}

// Delete OTP after successful password reset
$stmt = $conn->prepare("DELETE FROM otp WHERE email = ? AND purpose = 'password_reset'");
$stmt->execute([$email]);

echo json_encode(['status' => 'success', 'message' => 'Password changed successfully.']);
exit;
