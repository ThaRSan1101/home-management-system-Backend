<?php
require_once __DIR__ . '/jwt_utils.php';

function require_auth() {
    if (!isset($_COOKIE['token'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $payload = validate_jwt($_COOKIE['token']);
    if (!$payload) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid or expired token']);
        exit;
    }

    return $payload;
}
