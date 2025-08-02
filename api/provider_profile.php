<?php
/**
 * provider_profile.php
 *
 * API endpoint to fetch the current authenticated provider's profile information, including provider_id.
 *
 * - Requires JWT authentication (provider)
 * - Returns provider_id and basic info
 *
 * Used by: Provider dashboard/profile page.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../class/Provider.php';
require_once __DIR__ . '/../api/db.php';

header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$user = require_auth();
if ($user['user_type'] !== 'provider') {
    echo json_encode(['status' => 'error', 'message' => 'Not a provider']);
    exit;
}

$db = (new DBConnector())->connect();
$stmt = $db->prepare('SELECT provider_id FROM provider WHERE user_id = ?');
$stmt->execute([$user['user_id']]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    echo json_encode([
        'status' => 'success',
        'provider_id' => $row['provider_id'],
        'user_id' => $user['user_id'],
        'user_type' => $user['user_type']
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Provider not found']);
}
