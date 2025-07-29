<?php
require_once __DIR__ . '/../class/Admin.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

try {
    $admin = new Admin();
    $result = $admin->getCustomerDetails();
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
}
