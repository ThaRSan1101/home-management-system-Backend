<?php
// subscription_booking.php
// Set CORS headers
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include required files
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../class/SubscriptionBooking.php';

// Require authentication for all requests
$user = require_auth();
header('Content-Type: application/json');

// Create database connection
$dbConnector = new DBConnector();
$db = $dbConnector->connect();

// Create SubscriptionBooking object
$subscriptionBooking = new SubscriptionBooking($db);

// Get HTTP method
$method = $_SERVER['REQUEST_METHOD'];

// Handle different HTTP methods
switch ($method) {
    case 'GET':
        // Get subscription bookings based on status and user_id
        $filters = [];
        if (isset($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        if (isset($_GET['user_id'])) {
            $filters['user_id'] = $_GET['user_id'];
        }
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        
        $result = $subscriptionBooking->getSubscriptionBooking($filters, $page, $limit);
        
        echo json_encode($result);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        break;
}
