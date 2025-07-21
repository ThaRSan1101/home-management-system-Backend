<?php
require_once __DIR__ . '/../api/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../api/PHPMailer/SMTP.php';
require_once __DIR__ . '/../api/PHPMailer/Exception.php';
require_once __DIR__ . '/../class/Admin.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'No data received.']);
    exit;
}
$admin = new Admin();
$result = $admin->addProvider($data);
echo json_encode($result); 