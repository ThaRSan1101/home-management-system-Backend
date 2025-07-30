<?php
if (isset($_SERVER['HTTP_ORIGIN']) && preg_match('/^http:\/\/localhost(:[0-9]+)?$/', $_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
require_once __DIR__ . '/auth_middleware.php';     // ✅ JWT auth
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/../class/Message.php';

$user = require_auth(); // ✅ Validate JWT from cookie

// ✅ Restrict to admin users
if ($user['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied: Admins only.']);
    exit;
}

$db = (new DBConnector())->connect();
$messageObj = new Message($db);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

    $messages = $messageObj->getAllMessages($page, $limit);
    echo json_encode(['status' => 'success', 'data' => $messages]);
    exit();
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
