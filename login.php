<?php
require 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(['status' => 'error', 'message' => 'Email and password are required.']);
    exit;
}

$stmt = $conn->prepare("SELECT user_id, name, email, password, user_type, disable_status FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
    exit;
}

// Check if user is disabled
if ($user['disable_status']) {
    echo json_encode(['status' => 'error', 'message' => 'Your account has been disabled. Please contact support.']);
    exit;
}

// Success: return user_type for frontend to redirect
echo json_encode([
    'status' => 'success',
    'message' => 'Login successful.',
    'user_type' => $user['user_type'],
    'name' => $user['name'],
    'email' => $user['email'],
    'user_id' => $user['user_id']
]);
?> 