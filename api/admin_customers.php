<?php
require_once __DIR__ . '/../class/Customer.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

try {
    $customer = new Customer();
    $result = $customer->getCustomerDetails();
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
}
