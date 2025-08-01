<?php
// provider_status.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../class/Provider.php';
require_once __DIR__ . '/auth.php'; // Add authentication/session validation

$method = $_SERVER['REQUEST_METHOD'];
$providerId = null;

if ($method === 'GET') {
    // Get provider status
    if (isset($_GET['provider_id'])) {
        $providerId = intval($_GET['provider_id']);
        $provider = new Provider();
        $result = $provider->getProviderStatus($providerId);
        echo json_encode($result);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'provider_id required']);
        exit;
    }
} elseif ($method === 'POST') {
    // Change provider status
    // Support both JSON and form-data POST
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;
    $providerId = isset($input['provider_id']) ? intval($input['provider_id']) : null;
    $newStatus = isset($input['new_status']) ? $input['new_status'] : null;
    if ($providerId && $newStatus) {
        $provider = new Provider();
        $result = $provider->changeProviderStatus($providerId, $newStatus);
        echo json_encode($result);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'provider_id and new_status required']);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Unsupported request method']);
    exit;
}
