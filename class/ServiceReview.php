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
