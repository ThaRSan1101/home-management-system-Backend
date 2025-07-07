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

// Verify OTP
$stmt = $conn->prepare("SELECT * FROM password_reset WHERE email = ? AND code = ?");
$stmt->bind_param("ss", $email, $otp);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

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
$stmt->bind_param("ss", $hashedPassword, $email);
$stmt->execute();

// Optional: check if any row was updated
if ($stmt->affected_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Password reset failed. User may not exist.']);
    $stmt->close();
    exit;
}

$stmt->close();

// Delete OTP after successful password reset
$stmt = $conn->prepare("DELETE FROM password_reset WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->close();

echo json_encode(['status' => 'success', 'message' => 'Password changed successfully.']);
exit;
