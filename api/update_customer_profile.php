<?php
require_once __DIR__ . '/../class/Customer.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'No data received.']);
    exit;
}
$customer = new Customer();
$result = $customer->updateProfile($data);
echo json_encode($result); 