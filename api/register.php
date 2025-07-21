<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../class/User.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/Exception.php';
require_once 'PHPMailer/SMTP.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'No data received.']);
    exit;
}
$userObj = new User();
$result = $userObj->register($data);
echo json_encode($result); 