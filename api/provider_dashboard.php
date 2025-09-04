<?php
/**
 * provider_dashboard.php
 *
 * API endpoint to fetch provider dashboard statistics.
 * Requires JWT auth and provider role.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../class/Provider.php';
require_once __DIR__ . '/db.php';

header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit();
}

try {
	$user = require_auth();
	if ($user['user_type'] !== 'provider') {
		http_response_code(403);
		echo json_encode(['status' => 'error', 'message' => 'Access denied. Providers only.']);
		exit();
	}

	$db = (new DBConnector())->connect();
	$provider = new Provider($db);

	// Resolve provider_id from provider table using authenticated user_id
	$stmt = $db->prepare('SELECT provider_id FROM provider WHERE user_id = ?');
	$stmt->execute([$user['user_id']]);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	if (!$row) {
		http_response_code(404);
		echo json_encode(['status' => 'error', 'message' => 'Provider not found.']);
		exit();
	}

	$providerId = (int)$row['provider_id'];
	$result = $provider->getDashboardStats($providerId);
	if (($result['status'] ?? '') !== 'success') {
		http_response_code(500);
		echo json_encode($result);
		exit();
	}

	echo json_encode($result);
} catch (Exception $e) {
	http_response_code(500);
	echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}


