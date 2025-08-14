<?php
/**
 * service_review.php
 *
 * API endpoint for handling service reviews
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
require_once __DIR__ . '/../class/ServiceReview.php';

try {
    $db = new DBConnector();
    $conn = $db->connect();
    $serviceReview = new ServiceReview($conn);

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            // Create a new review
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input.']);
                exit();
            }

            // Get allocation_id from service_book_id
            if (isset($input['service_book_id'])) {
                $stmt = $conn->prepare("SELECT allocation_id FROM service_provider_allocation WHERE service_book_id = ?");
                $stmt->execute([$input['service_book_id']]);
                $allocation = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$allocation) {
                    echo json_encode(['status' => 'error', 'message' => 'Service booking allocation not found.']);
                    exit();
                }
                
                $input['allocation_id'] = $allocation['allocation_id'];
            }

            $required = ['allocation_id', 'provider_name', 'service_name', 'amount', 'rating', 'feedback_text'];
            foreach ($required as $field) {
                if (!isset($input[$field]) || $input[$field] === '') {
                    echo json_encode(['status' => 'error', 'message' => "Missing required field: $field"]);
                    exit();
                }
            }

            $result = $serviceReview->saveReview($input);
            echo json_encode($result);
            break;

        case 'GET':
            if (isset($_GET['provider_id'])) {
                // Get reviews for a specific provider
                $provider_id = intval($_GET['provider_id']);
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
                
                $result = $serviceReview->getProviderReviews($provider_id, $page, $limit);
                echo json_encode($result);
                
            } elseif (isset($_GET['provider_rating'])) {
                // Get average rating for a provider
                $provider_id = intval($_GET['provider_rating']);
                $result = $serviceReview->getProviderRating($provider_id);
                echo json_encode($result);
                
            } elseif (isset($_GET['booking_id'])) {
                // Check if booking already has a review
                $booking_id = intval($_GET['booking_id']);
                $result = $serviceReview->getReviewByBookingId($booking_id);
                echo json_encode($result);
                
            } else {
                // Get all reviews
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
                
                $result = $serviceReview->getAllReviews($page, $limit);
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
