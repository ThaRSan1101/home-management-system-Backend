<?php
/**
 * SubscriptionReview.php
 *
 * Handles subscription review logic for customers after cancellation.
 *
 * Table: subscription_review
 *
 * Fields from database schema:
 * - review_id (PK)
 * - allocation_id (FK to subscription_provider_allocation)
 * - provider_name (VARCHAR)
 * - service_name (VARCHAR) 
 * - amount (DECIMAL)
 * - rating (INT 1-5)
 * - feedback_text (TEXT)
 * - reviewed_at (TIMESTAMP)
 */

require_once __DIR__ . '/../api/db.php';

class SubscriptionReview {
    /**
     * @var PDO $conn
     */
    protected $conn;
    protected $table = 'subscription_review';

    public function __construct($dbConn = null) {
        if ($dbConn) {
            $this->conn = $dbConn;
        } else {
            $db = new DBConnector();
            $this->conn = $db->connect();
        }
    }

    /**
     * Save a new subscription review after cancellation.
     * @param array $data Review details (subbook_id, provider_name, service_name, amount, rating, feedback_text)
     * @return array Status and message
     */
    public function submitReview($data) {
        $required = ['subbook_id', 'provider_name', 'service_name', 'amount', 'rating', 'feedback_text'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                return ['status' => 'error', 'message' => "Missing required field: $field."];
            }
        }

        // Validate rating
        if ($data['rating'] < 1 || $data['rating'] > 5) {
            return ['status' => 'error', 'message' => 'Rating must be between 1 and 5.'];
        }

        try {
            // First, check if there's a subscription_provider_allocation for this booking
            $allocStmt = $this->conn->prepare("SELECT allocation_id FROM subscription_provider_allocation WHERE subbook_id = ?");
            $allocStmt->execute([$data['subbook_id']]);
            $allocation = $allocStmt->fetch(PDO::FETCH_ASSOC);

            $allocation_id = null;
            if ($allocation) {
                $allocation_id = $allocation['allocation_id'];
            } else {
                // Get provider_id from subscription_booking
                $providerStmt = $this->conn->prepare("SELECT provider_id FROM subscription_booking WHERE subbook_id = ?");
                $providerStmt->execute([$data['subbook_id']]);
                $booking = $providerStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($booking && $booking['provider_id']) {
                    // Create a temporary allocation record if it doesn't exist
                    $insertAllocStmt = $this->conn->prepare("INSERT INTO subscription_provider_allocation (subbook_id, provider_id, allocated_at) VALUES (?, ?, NOW())");
                    $insertAllocStmt->execute([$data['subbook_id'], $booking['provider_id']]);
                    $allocation_id = $this->conn->lastInsertId();
                } else {
                    // If no provider assigned, create allocation with provider_id = 1 (default)
                    $insertAllocStmt = $this->conn->prepare("INSERT INTO subscription_provider_allocation (subbook_id, provider_id, allocated_at) VALUES (?, 1, NOW())");
                    $insertAllocStmt->execute([$data['subbook_id']]);
                    $allocation_id = $this->conn->lastInsertId();
                }
            }

            // Insert the review
            $stmt = $this->conn->prepare("INSERT INTO {$this->table} (allocation_id, provider_name, service_name, amount, rating, feedback_text, reviewed_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $allocation_id,
                $data['provider_name'],
                $data['service_name'],
                $data['amount'],
                $data['rating'],
                $data['feedback_text']
            ]);

            return ['status' => 'success', 'message' => 'Review submitted successfully.', 'review_id' => $this->conn->lastInsertId()];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Failed to submit review: ' . $e->getMessage()];
        }
    }

    /**
     * Get all reviews for a specific provider.
     * @param int $provider_id
     * @param int $page
     * @param int $limit
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
            JOIN subscription_provider_allocation spa ON sr.allocation_id = spa.allocation_id
            JOIN subscription_booking sb ON spa.subbook_id = sb.subbook_id
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
     * Get all subscription reviews (for admin).
     * @param int $page
     * @param int $limit
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
            JOIN subscription_provider_allocation spa ON sr.allocation_id = spa.allocation_id
            JOIN subscription_booking sb ON spa.subbook_id = sb.subbook_id
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
            return ['status' => 'error', 'message' => 'Failed to fetch all reviews: ' . $e->getMessage()];
        }
    }
}
