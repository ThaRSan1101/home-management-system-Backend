<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/../class/Message.php';

$db = (new DBConnector())->connect();
$messageObj = new Message($db);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $messages = $messageObj->getAllMessages($page, $limit);
    echo json_encode(['messages' => $messages]);
    exit();
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed.']);
