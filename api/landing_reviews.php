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

try {
    $db = new DBConnector();
    $conn = $db->connect();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get reviews for landing page display
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 6; // Default to 6 reviews
        
        $sql = "SELECT 
            sr.review_id,
            sr.provider_name,
            sr.service_name,
            sr.amount,
            sr.rating,
            sr.feedback_text as message,
            sr.reviewed_at,
            cu.name as customer_name
        FROM service_review sr
        JOIN service_provider_allocation spa ON sr.allocation_id = spa.allocation_id
        JOIN service_booking sb ON spa.service_book_id = sb.service_book_id
        JOIN users cu ON sb.user_id = cu.user_id
        ORDER BY sr.reviewed_at DESC 
        LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$limit]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no reviews found, return sample data
        if (empty($reviews)) {
            $sampleReviews = [
                [
                    'id' => 1,
                    'name' => 'Sarah Johnson',
                    'rating' => 5,
                    'comment' => 'Absolutely excellent! The team was professional, friendly, and the results were perfect. Highly recommended.',
                    'service' => 'Plumbing Services',
                    'amount' => '$120.00',
                    'avatar' => '👩‍🔧',
                    'provider_name' => 'John Smith',
                    'reviewed_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 2,
                    'name' => 'Michael Chen',
                    'rating' => 4,
                    'comment' => 'Great service! The electrician was knowledgeable and fixed the issue quickly. Very satisfied with the work.',
                    'service' => 'Electrical Services',
                    'amount' => '$90.00',
                    'avatar' => '👨‍🔧',
                    'provider_name' => 'Maria Garcia',
                    'reviewed_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 3,
                    'name' => 'Emily Rodriguez',
                    'rating' => 5,
                    'comment' => 'Outstanding cleaning service! My house has never looked better. The team was thorough and professional.',
                    'service' => 'Cleaning Services',
                    'amount' => '$60.00',
                    'avatar' => '👩‍🦰',
                    'provider_name' => 'David Johnson',
                    'reviewed_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 4,
                    'name' => 'David Lee',
                    'rating' => 4,
                    'comment' => 'Great job on the painting! The colors look perfect and the finish is smooth. Will definitely use again.',
                    'service' => 'Painting Services',
                    'amount' => '$150.00',
                    'avatar' => '👨‍🎨',
                    'provider_name' => 'Sarah Wilson',
                    'reviewed_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 5,
                    'name' => 'Priya Patel',
                    'rating' => 5,
                    'comment' => 'Excellent HVAC service! The technician was professional and fixed our AC unit efficiently. Highly recommend!',
                    'service' => 'HVAC Services',
                    'amount' => '$200.00',
                    'avatar' => '👩‍🔬',
                    'provider_name' => 'Michael Brown',
                    'reviewed_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 6,
                    'name' => 'Ahmed Hassan',
                    'rating' => 3,
                    'comment' => 'Good work overall, but it took longer than expected. The final result was satisfactory though.',
                    'service' => 'Carpentry Services',
                    'amount' => '$80.00',
                    'avatar' => '👨‍🔧',
                    'provider_name' => 'Lisa Davis',
                    'reviewed_at' => date('Y-m-d H:i:s')
                ]
            ];
            
            echo json_encode([
                'status' => 'success',
                'data' => $sampleReviews
            ]);
            exit();
        }
        
        // Transform the data to match frontend expectations
        $transformedReviews = [];
        foreach ($reviews as $review) {
            $transformedReviews[] = [
                'id' => $review['review_id'],
                'name' => $review['customer_name'],
                'rating' => intval($review['rating']),
                'comment' => $review['message'],
                'service' => $review['service_name'],
                'amount' => '$' . number_format($review['amount'], 2),
                'avatar' => getAvatarForName($review['customer_name']),
                'provider_name' => $review['provider_name'],
                'reviewed_at' => $review['reviewed_at']
            ];
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => $transformedReviews
        ]);
        
    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}

/**
 * Generate avatar emoji based on customer name
 */
function getAvatarForName($name) {
    $avatars = ['👩‍🔧', '👨‍🔧', '👩‍🦰', '👨‍🎨', '👩‍🔬', '👨‍💼', '👩‍💻', '👨‍🚀', '👩‍🎓', '👨‍🏫'];
    $hash = crc32($name);
    return $avatars[abs($hash) % count($avatars)];
}
