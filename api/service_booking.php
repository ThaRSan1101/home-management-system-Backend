<?php
// service_booking.php
// --- CORS HEADERS START ---
$allowed_origins = [
    'http://localhost:5173',
    'http://localhost:3000',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:3000',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
// --- CORS HEADERS END ---
// API endpoint for service booking creation, retrieval, and cancellation
// Accepts customer_name in POST data and passes to ServiceBooking::serviceBook

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../class/ServiceBooking.php';

// Require authentication for all requests
$user = require_auth();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$serviceBooking = new ServiceBooking();

if ($method === 'PATCH') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['service_book_id']) || !isset($input['cancel_reason'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing booking ID or cancel reason.']);
        exit;
    }
    $result = $serviceBooking->cancelBooking($input['service_book_id'], $input['cancel_reason']);
    echo json_encode($result);
    exit;
}

if ($method === 'POST') {
    // Create a new booking
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or missing JSON payload.']);
        exit;
    }
    // Use user_id from JWT if not provided in input
    if (!isset($input['user_id'])) {
        $input['user_id'] = $user['user_id'];
    }
    $result = $serviceBooking->serviceBook($input);
    echo json_encode($result);
    exit;
}

if ($method === 'GET') {
    // Retrieve bookings (optionally filtered by user_id and/or status)
    $filters = [];
    // Use user_id from JWT if not provided in query
    if (isset($_GET['user_id'])) {
        $filters['user_id'] = (int)$_GET['user_id'];
    } else if ($user && $user['user_type'] === 'customer') {
        $filters['user_id'] = $user['user_id'];
    }
    if (isset($_GET['status'])) {
        $filters['status'] = $_GET['status'];
    }
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $result = $serviceBooking->getServiceBooking($filters, $page, $limit);
    echo json_encode($result);
    exit;
}

// If not POST or GET
http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
