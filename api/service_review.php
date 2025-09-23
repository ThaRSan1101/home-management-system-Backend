<?php
/**
 * service_review.php
 *
 * API endpoint for comprehensive service review management in the Home Management System.
 * Handles creation and retrieval of customer reviews for completed services.
 *
 * AUTHENTICATION:
 * ===============
 * Currently no authentication enforced - should be added for security.
 * TODO: Add JWT authentication to ensure only authorized users can create/view reviews.
 *
 * HTTP METHODS SUPPORTED:
 * =======================
 * 
 * POST: Create new service review
 * - Customer submits review after service completion
 * - Links review to service allocation for proper tracking
 * - Validates all required fields before saving
 * 
 * GET: Retrieve reviews with various filtering options
 * - Get reviews for specific provider
 * - Get average rating for provider
 * - Check if booking already has review
 * - Get all reviews with pagination
 *
 * POST REQUIREMENTS:
 * ==================
 * Required fields for creating a review:
 * - allocation_id OR service_book_id (converted to allocation_id)
 * - provider_name: Name of the service provider
 * - service_name: Name of the service provided
 * - amount: Final service amount paid
 * - rating: Rating from 1-5 stars
 * - feedback_text: Customer's written feedback
 *
 * GET QUERY PARAMETERS:
 * =====================
 * - provider_id: Get all reviews for specific provider (with pagination)
 * - provider_rating: Get average rating for specific provider
 * - booking_id: Check if specific booking has a review
 * - page: Page number for pagination (default: 1)
 * - limit: Results per page (default: 10)
 * - (no params): Get all reviews with pagination
 *
 * RESPONSE FORMATS:
 * ================
 * 
 * POST Success:
 * {
 *   "status": "success",
 *   "message": "Review saved successfully.",
 *   "review_id": 123
 * }
 *
 * GET Reviews List:
 * {
 *   "status": "success",
 *   "reviews": [...],
 *   "pagination": {
 *     "page": 1,
 *     "limit": 10,
 *     "total": 50
 *   }
 * }
 *
 * GET Provider Rating:
 * {
 *   "status": "success",
 *   "average_rating": 4.5,
 *   "total_reviews": 25
 * }
 *
 * Error Response:
 * {
 *   "status": "error",
 *   "message": "Error description"
 * }
 *
 * BUSINESS LOGIC:
 * ===============
 * - Reviews are linked to service allocations for proper tracking
 * - Automatic conversion from service_book_id to allocation_id when needed
 * - Duplicate review prevention (one review per allocation)
 * - Rating calculations for provider performance metrics
 * - Pagination support for large datasets
 *
 * CORS CONFIGURATION:
 * ==================
 * Configured for frontend integration with localhost:5173.
 * Supports credentials for future authentication implementation.
 *
 * DEPENDENCIES:
 * =============
 * - ServiceReview.php: Business logic for review operations
 * - db.php: Database connection management
 */

/**
 * CORS HEADERS AND PREFLIGHT HANDLING
 * ===================================
 * Configure cross-origin resource sharing for frontend integration.
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

/**
 * DEPENDENCIES AND INITIALIZATION
 * ===============================
 * Load required classes and establish database connection.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../class/ServiceReview.php';

/**
 * MAIN REQUEST PROCESSING
 * =======================
 * Handle different HTTP methods and route to appropriate logic.
 */
try {
    $db = new DBConnector();
    $conn = $db->connect();
    $serviceReview = new ServiceReview($conn);

    switch ($_SERVER['REQUEST_METHOD']) {
        /**
         * POST REQUEST HANDLER
         * ===================
         * Create new service review from customer feedback.
         */
        case 'POST':
            // Create a new review
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input.']);
                exit();
            }

            /**
             * ALLOCATION ID RESOLUTION
             * Convert service_book_id to allocation_id if needed.
             * Reviews are linked to allocations for proper provider tracking.
             */
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

            /**
             * INPUT VALIDATION
             * Ensure all required fields are present and non-empty.
             */
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

        /**
         * GET REQUEST HANDLER
         * ==================
         * Retrieve reviews based on various filtering criteria.
         */
        case 'GET':
            /**
             * PROVIDER-SPECIFIC REVIEWS
             * Get all reviews for a specific provider with pagination.
             */
            if (isset($_GET['provider_id'])) {
                // Get reviews for a specific provider
                $provider_id = intval($_GET['provider_id']);
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
                
                $result = $serviceReview->getProviderReviews($provider_id, $page, $limit);
                echo json_encode($result);
                
            } 
            /**
             * PROVIDER RATING CALCULATION
             * Get average rating and total review count for a provider.
             */
            elseif (isset($_GET['provider_rating'])) {
                // Get average rating for a provider
                $provider_id = intval($_GET['provider_rating']);
                $result = $serviceReview->getProviderRating($provider_id);
                echo json_encode($result);
                
            } 
            /**
             * BOOKING REVIEW CHECK
             * Check if a specific booking already has a review (prevent duplicates).
             */
            elseif (isset($_GET['booking_id'])) {
                // Check if booking already has a review
                $booking_id = intval($_GET['booking_id']);
                $result = $serviceReview->getReviewByBookingId($booking_id);
                echo json_encode($result);
                
            } 
            /**
             * ALL REVIEWS WITH PAGINATION
             * Get all reviews in the system with pagination support.
             */
            else {
                // Get all reviews
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
                
                $result = $serviceReview->getAllReviews($page, $limit);
                echo json_encode($result);
            }
            break;

        /**
         * UNSUPPORTED METHOD HANDLER
         * ==========================
         * Return error for unsupported HTTP methods.
         */
        default:
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
