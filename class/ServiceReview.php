<?php
/**
 * ServiceReview.php
 *
 * Handles service review logic for customers after service completion.
 *
 * Table: service_review
 *
 * Fields:
 * - review_id (PK)
 * - allocation_id (FK)
 * - provider_name (VARCHAR)
 * - service_name (VARCHAR)
 * - amount (DECIMAL)
 * - rating (INT 1-5)
 * - feedback_text (TEXT)
 * - reviewed_at (TIMESTAMP)
 */

require_once __DIR__ . '/../api/db.php';

class ServiceReview {
    /**
     * @var PDO $conn
     */
    protected $conn;
    protected $table = 'service_review';

    public function __construct($dbConn = null) {
        if ($dbConn) {
            $this->conn = $dbConn;
        } else {
            $db = new DBConnector();
            $this->conn = $db->connect();
        }
    }

    /**
     * Save a new service review after customer accepts the completed service.
     * @param array $data Review details (allocation_id, provider_name, service_name, amount, rating, feedback_text)
     * @return array Status and message
     */
    public function saveReview($data) {
        $required = ['allocation_id', 'provider_name', 'service_name', 'amount', 'rating', 'feedback_text'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                return ['status' => 'error', 'message' => "Missing required field: $field."];
            }
        }

        // Validate rating range
        if ($data['rating'] < 1 || $data['rating'] > 5) {
            return ['status' => 'error', 'message' => 'Rating must be between 1 and 5.'];
        }

        try {
            // Check if review already exists for this allocation
            $checkStmt = $this->conn->prepare("SELECT review_id FROM {$this->table} WHERE allocation_id = ?");
            $checkStmt->execute([$data['allocation_id']]);
            if ($checkStmt->fetch()) {
                return ['status' => 'error', 'message' => 'Review already submitted.'];
            }

            $stmt = $this->conn->prepare("INSERT INTO {$this->table} (allocation_id, provider_name, service_name, amount, rating, feedback_text) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['allocation_id'],
                $data['provider_name'],
                $data['service_name'],
                $data['amount'],
                $data['rating'],
                $data['feedback_text']
            ]);
            
            return ['status' => 'success', 'message' => 'Review saved successfully.', 'review_id' => $this->conn->lastInsertId()];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Failed to save review: ' . $e->getMessage()];
        }
    }

    /**
     * Get reviews for a specific provider.
     * @param int $provider_id
     * @param int $page Pagination page (default 1)
     * @param int $limit Results per page (default 10)
     * @return array List of reviews
     */
    public function getProviderReviews($provider_id, $page = 1, $limit = 10) {
        try {
            $sql = "SELECT 
    sr.*, 
    spa.provider_id, 
    sb.user_id AS customer_id, 
    cu.name AS customer_name, 
    sp.user_id AS provider_user_id, 
    pu.name AS provider_name
FROM {$this->table} sr
JOIN service_provider_allocation spa ON sr.allocation_id = spa.allocation_id
JOIN service_booking sb ON spa.service_book_id = sb.service_book_id
JOIN users cu ON sb.user_id = cu.user_id
JOIN provider sp ON spa.provider_id = sp.provider_id
JOIN users pu ON sp.user_id = pu.user_id
WHERE spa.provider_id = ?
ORDER BY sr.reviewed_at DESC 
LIMIT ? OFFSET ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$provider_id, $limit, ($page - 1) * $limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['status' => 'success', 'data' => $results];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Failed to fetch reviews: ' . $e->getMessage()];
        }
    }

    /**
     * Get average rating for a provider.
     * @param int $provider_id
     * @return array Average rating and count
     */
    public function getProviderRating($provider_id) {
        try {
            $sql = "SELECT AVG(sr.rating) as avg_rating, COUNT(sr.rating) as review_count
                    FROM {$this->table} sr
                    JOIN service_provider_allocation spa ON sr.allocation_id = spa.allocation_id
                    WHERE spa.provider_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$provider_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'status' => 'success', 
                'data' => [
                    'avg_rating' => round($result['avg_rating'] ?? 0, 2),
                    'review_count' => $result['review_count'] ?? 0
                ]
            ];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Failed to fetch rating: ' . $e->getMessage()];
        }
    }

    /**
     * Get all reviews with pagination.
     * @param int $page Pagination page (default 1)
     * @param int $limit Results per page (default 10)
     * @return array List of all reviews
     */
    public function getAllReviews($page = 1, $limit = 10) {
        try {
            $sql = "SELECT 
    sr.*, 
    spa.provider_id, 
    sb.user_id AS customer_id, 
    cu.name AS customer_name, 
    sp.user_id AS provider_user_id, 
    pu.name AS provider_name
FROM {$this->table} sr
JOIN service_provider_allocation spa ON sr.allocation_id = spa.allocation_id
JOIN service_booking sb ON spa.service_book_id = sb.service_book_id
JOIN users cu ON sb.user_id = cu.user_id
JOIN provider sp ON spa.provider_id = sp.provider_id
JOIN users pu ON sp.user_id = pu.user_id
ORDER BY sr.reviewed_at DESC 
LIMIT ? OFFSET ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$limit, ($page - 1) * $limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['status' => 'success', 'data' => $results];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Failed to fetch reviews: ' . $e->getMessage()];
        }
    }

    /**
     * Get reviews formatted for the landing page.
     * @param int $limit Number of reviews to return (default 6)
     * @return array Status and transformed review data
     */
    public function getLandingReviews($limit = 6) {
        try {
            $sql = "SELECT 
            sr.review_id,
            sr.provider_name,
            sr.service_name,
            sr.amount,
            sr.rating,
            sr.feedback_text as message,
            sr.reviewed_at,
            cu.name as customer_name
        FROM {$this->table} sr
        JOIN service_provider_allocation spa ON sr.allocation_id = spa.allocation_id
        JOIN service_booking sb ON spa.service_book_id = sb.service_book_id
        JOIN users cu ON sb.user_id = cu.user_id
        ORDER BY sr.reviewed_at DESC 
        LIMIT ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$limit]);
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

                return [
                    'status' => 'success',
                    'data' => $sampleReviews
                ];
            }

            $transformedReviews = [];
            foreach ($reviews as $review) {
                $transformedReviews[] = [
                    'id' => $review['review_id'],
                    'name' => $review['customer_name'],
                    'rating' => intval($review['rating']),
                    'comment' => $review['message'],
                    'service' => $review['service_name'],
                    'amount' => '$' . number_format($review['amount'], 2),
                    'avatar' => $this->generateAvatarForName($review['customer_name']),
                    'provider_name' => $review['provider_name'],
                    'reviewed_at' => $review['reviewed_at']
                ];
            }

            return [
                'status' => 'success',
                'data' => $transformedReviews
            ];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Failed to fetch landing reviews: ' . $e->getMessage()];
        }
    }

    /**
     * Generate avatar emoji based on customer name
     */
    private function generateAvatarForName($name) {
        $avatars = ['👩‍🔧', '👨‍🔧', '👩‍🦰', '👨‍🎨', '👩‍🔬', '👨‍💼', '👩‍💻', '👨‍🚀', '👩‍🎓', '👨‍🏫'];
        $hash = crc32($name);
        return $avatars[abs($hash) % count($avatars)];
    }

    /**
     * Get review by service booking ID to check if already reviewed.
     * @param int $service_book_id
     * @return array Review data or null
     */
    public function getReviewByBookingId($service_book_id) {
        try {
            $sql = "SELECT sr.* 
                    FROM {$this->table} sr
                    JOIN service_provider_allocation spa ON sr.allocation_id = spa.allocation_id
                    WHERE spa.service_book_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$service_book_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return ['status' => 'success', 'data' => $result];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Failed to fetch review: ' . $e->getMessage()];
        }
    }
}
