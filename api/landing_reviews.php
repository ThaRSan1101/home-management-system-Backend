<?php
/**
 * landing_reviews.php
 *
 * API endpoint for fetching reviews for the landing page
 * Returns reviews in a format suitable for the frontend display
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

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../class/ServiceReview.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
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
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}

// Avatar generation moved into ServiceReview
