<?php
/**
 * subscription_review.php
 *
 * API endpoint for handling subscription reviews
 * Supports POST (create review), GET (fetch reviews)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../class/SubscriptionReview.php';

try {
    $db = new DBConnector();
    $conn = $db->connect();
    $subscriptionReview = new SubscriptionReview($conn);

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            // Create a new review
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input.']);
                exit();
            }

            $result = $subscriptionReview->submitReview($input);
            echo json_encode($result);
            break;

        case 'GET':
            if (isset($_GET['provider_id'])) {
                // Get reviews for a specific provider
                $provider_id = intval($_GET['provider_id']);
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
                
                $result = $subscriptionReview->getProviderReviews($provider_id, $page, $limit);
                echo json_encode($result);
                
            } elseif (isset($_GET['subbook_id'])) {
                // Check if a review exists for a given subscription booking id
                $subbook_id = intval($_GET['subbook_id']);
                $stmt = $conn->prepare("SELECT sr.* FROM subscription_review sr JOIN subscription_provider_allocation spa ON sr.allocation_id = spa.allocation_id WHERE spa.subbook_id = ? LIMIT 1");
                $stmt->execute([$subbook_id]);
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode(['status' => 'success', 'data' => $data]);
                
            } else {
                // Get all reviews
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
                
                $result = $subscriptionReview->getAllReviews($page, $limit);
                echo json_encode($result);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
