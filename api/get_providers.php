<?php
require_once __DIR__ . '/../class/Admin.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');
$admin = new Admin();
$providers = $admin->getAllProviders();
echo json_encode(['status' => 'success', 'providers' => $providers]); 