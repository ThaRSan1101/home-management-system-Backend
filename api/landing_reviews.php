<?php
/**
 * landing_reviews.php
 *
 * API endpoint for fetching customer reviews for the public landing page display.
 * Provides a curated selection of reviews to showcase service quality to potential customers.
 *
 * AUTHENTICATION:
 * ===============
 * No authentication required - this is a public endpoint for the landing page.
 * Reviews are filtered and sanitized for public display.
 *
 * HTTP METHODS SUPPORTED:
 * =======================
 * 
 * GET: Retrieve reviews for landing page display
 * - Returns a limited number of reviews suitable for public viewing
 * - Formatted specifically for landing page presentation
 * - Includes customer names, ratings, and feedback text
 *
 * GET QUERY PARAMETERS:
 * =====================
 * - limit: Number of reviews to return (default: 6)
 *   Example: ?limit=10
 *
 * RESPONSE FORMAT:
 * ===============
 * Success Response:
 * {
 *   "status": "success",
 *   "reviews": [
 *     {
 *       "customer_name": "John D.",
 *       "rating": 5,
 *       "feedback_text": "Excellent service!",
 *       "service_name": "Home Cleaning",
 *       "provider_name": "ABC Services",
 *       "avatar": "generated_avatar_url",
 *       "reviewed_at": "2024-01-15"
 *     }
 *   ],
 *   "total": 50
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
 * - Fetches high-quality reviews for marketing purposes
 * - May include automated avatar generation for customer privacy
 * - Reviews are curated to show positive customer experiences
 * - Used to build trust with potential customers visiting the landing page
 * - No sensitive customer information is exposed
 *
 * CORS CONFIGURATION:
 * ==================
 * Configured for frontend integration with localhost:5173.
 * Since this is a public endpoint, CORS is more permissive.
 *
 * DEPENDENCIES:
 * =============
 * - ServiceReview.php: Business logic for review retrieval and formatting
 * - db.php: Database connection management
 *
 * USAGE:
 * ======
 * GET /api/landing_reviews.php?limit=6
 * Used by the frontend landing page to display customer testimonials.
 */

/**
 * CORS HEADERS AND PREFLIGHT HANDLING
 * ===================================
 * Configure cross-origin resource sharing for public access.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
 * Load required classes for review processing.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../class/ServiceReview.php';

/**
 * MAIN REQUEST PROCESSING
 * =======================
 * Handle GET requests for landing page reviews.
 */
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        /**
         * LANDING PAGE REVIEWS RETRIEVAL
         * ==============================
         * Fetch curated reviews for public display on landing page.
         */
        // Get reviews for landing page display
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 6; // Default to 6 reviews
        $serviceReview = new ServiceReview();
        $result = $serviceReview->getLandingReviews($limit);

        if ($result['status'] === 'success') {
            echo json_encode($result);
        } else {
            http_response_code(500);
            echo json_encode($result);
        }
        
    } else {
        /**
         * UNSUPPORTED METHOD HANDLER
         * ==========================
         * Return error for unsupported HTTP methods.
         */
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    }

} catch (Exception $e) {
    /**
     * ERROR HANDLING
     * ==============
     * Catch any unexpected errors and return structured error response.
     */
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}

/**
 * TECHNICAL NOTES:
 * ===============
 * - Avatar generation logic is handled within the ServiceReview class
 * - Reviews may be cached for better performance on high-traffic landing pages
 * - Consider implementing rate limiting for public endpoints in production
 */
