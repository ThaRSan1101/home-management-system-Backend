<?php
require_once 'db.php';
require_once __DIR__ . '/../class/User.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'No data received.']);
    exit;
}
$conn = getDbConnection();
$userObj = new User($conn);
$result = $userObj->verifyOtp($data);
echo json_encode($result);

?>
