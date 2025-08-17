<?php
/**
 * SubscriptionBooking.php
 *
 * Handles subscription booking logic for customers, including saving and retrieving bookings.
 *
 * Table: subscription_booking
 *
 * Fields:
 * - subbook_id (PK)
 * - sub_id (FK)
 * - user_id (FK)
 * - subbooking_status (ENUM: pending, process, complete, cancel)
 * - subbooking_date (TIMESTAMP)
 * - sub_date (DATE)
 * - sub_time (TIME)
 * - sub_address (TEXT)
 * - phoneNo (VARCHAR)
 * - amount (DECIMAL)
 * - cancel_reason (TEXT)
 */

require_once __DIR__ . '/../api/db.php';

class SubscriptionBooking {
    protected $conn;
    protected $table = 'subscription_booking';

    public function __construct($dbConn = null) {
        if ($dbConn) {
            $this->conn = $dbConn;
        } else {
            $db = new DBConnector();
            $this->conn = $db->connect();
        }
    }

    /**
     * Save a new subscription booking.
     * @param array $data Booking details (sub_id, user_id, sub_date, sub_time, sub_address, phoneNo, amount)
     * @return array Status and message
     */
    public function subscriptionBook($data) {
        $required = ['sub_id', 'user_id', 'sub_date', 'sub_time', 'sub_address', 'phoneNo', 'amount'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['status' => 'error', 'message' => "Missing required field: $field."];
            }
        }
        try {
            // Insert subscription booking without provider_id (will be assigned later by admin)
            $stmt = $this->conn->prepare("INSERT INTO {$this->table} (sub_id, user_id, customer_name, subbooking_status, sub_date, sub_time, sub_address, phoneNo, amount) VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['sub_id'],
                $data['user_id'],
                $data['customer_name'] ?? '',
                $data['sub_date'],
                $data['sub_time'],
                $data['sub_address'],
                $data['phoneNo'],
                $data['amount']
            ]);
            return ['status' => 'success', 'message' => 'Subscription booked and pending confirmation.'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Booking failed: ' . $e->getMessage()];
        }
    }

    /**
     * Cancel a subscription booking by updating status and cancel_reason.
     */
    public function cancelBooking($subbook_id, $cancel_reason) {
        try {
            $stmt = $this->conn->prepare("UPDATE {$this->table} SET subbooking_status = 'cancel', cancel_reason = ? WHERE subbook_id = ?");
            $stmt->execute([$cancel_reason, $subbook_id]);
            return ['status' => 'success', 'message' => 'Booking cancelled successfully.'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Cancellation failed: ' . $e->getMessage()];
        }
    }

    /**
     * Assign a provider to a subscription booking.
     */
    public function assignProvider($subbook_id, $provider_id) {
        try {
            // First check if the provider exists and is active
            $providerStmt = $this->conn->prepare("SELECT provider_id FROM provider WHERE provider_id = ? AND status = 'active'");
            $providerStmt->execute([$provider_id]);
            $provider = $providerStmt->fetch();
            
            if (!$provider) {
                return ['status' => 'error', 'message' => 'Provider not found or inactive.'];
            }
            
            // Update the subscription booking with the provider
            $stmt = $this->conn->prepare("UPDATE {$this->table} SET provider_id = ?, subbooking_status = 'waiting' WHERE subbook_id = ?");
            $stmt->execute([$provider_id, $subbook_id]);
            
            if ($stmt->rowCount() > 0) {
                return ['status' => 'success', 'message' => 'Provider assigned successfully.'];
            } else {
                return ['status' => 'error', 'message' => 'Booking not found.'];
            }
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Assignment failed: ' . $e->getMessage()];
        }
    }

    /**
     * Retrieve subscription bookings (pending or all) for customer or admin.
     *
     * @param array $filters Optional: ['user_id'=>int, 'status'=>'pending'|'process'|'complete'|'cancel']
     * @param int $page Pagination page (default 1)
     * @param int $limit Results per page (default 10)
     * @return array List of bookings
     */
    public function getSubscriptionBooking($filters = [], $page = 1, $limit = 10) {
        $where = [];
        $params = [];
        if (!empty($filters['user_id'])) {
            $where[] = 'sb.user_id = ?';
            $params[] = $filters['user_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'sb.subbooking_status = ?';
            $params[] = $filters['status'];
        }
        $sql = "SELECT sb.*, sp.category AS plan_name, sb.customer_name, u.name AS user_name, u.phone_number, u.address,
                       p.user_id as provider_user_id, pu.name as provider_name
                FROM {$this->table} sb
                JOIN subscription_plan sp ON sb.sub_id = sp.sub_id
                JOIN users u ON sb.user_id = u.user_id
                LEFT JOIN provider p ON sb.provider_id = p.provider_id
                LEFT JOIN users pu ON p.user_id = pu.user_id";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY subbooking_date DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = ($page - 1) * $limit;
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($results as &$row) {
                if (isset($row['subbooking_status'])) {
                    $row['subbooking_status'] = strtolower($row['subbooking_status']);
                }
            }
            return ['status' => 'success', 'data' => $results];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Failed to fetch bookings: ' . $e->getMessage()];
        }
    }

    /**
     * Move a booking to a provider and set status to 'waiting'.
     */
    public function moveBooking($subbook_id, $provider_id) {
        try {
            $stmt = $this->conn->prepare("UPDATE {$this->table} SET provider_id = ?, subbooking_status = 'waiting' WHERE subbook_id = ? AND subbooking_status = 'pending'");
            $stmt->execute([$provider_id, $subbook_id]);
            if ($stmt->rowCount() > 0) {
                return ['status' => 'success', 'message' => 'Subscription booking moved to provider and set to waiting.'];
            } else {
                return ['status' => 'error', 'message' => 'Booking not found or not pending.'];
            }
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Move failed: ' . $e->getMessage()];
        }
    }

    /**
     * Provider accepts the booking: set status to 'process'.
     */
    public function acceptBooking($subbook_id, $provider_id) {
        try {
            $stmt = $this->conn->prepare("UPDATE {$this->table} SET subbooking_status = 'process' WHERE subbook_id = ? AND provider_id = ? AND subbooking_status = 'waiting'");
            $stmt->execute([$subbook_id, $provider_id]);
            if ($stmt->rowCount() > 0) {
                return ['status' => 'success', 'message' => 'Subscription booking accepted.'];
            } else {
                return ['status' => 'error', 'message' => 'Booking not found or not waiting.'];
            }
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Accept failed: ' . $e->getMessage()];
        }
    }

    /**
     * Provider declines the booking: revert status to 'pending'.
     */
    public function declineBooking($subbook_id, $provider_id) {
        try {
            $stmt = $this->conn->prepare("UPDATE {$this->table} SET subbooking_status = 'pending', provider_id = NULL WHERE subbook_id = ? AND provider_id = ? AND subbooking_status = 'waiting'");
            $stmt->execute([$subbook_id, $provider_id]);
            if ($stmt->rowCount() > 0) {
                return ['status' => 'success', 'message' => 'Subscription booking reverted to pending.'];
            } else {
                return ['status' => 'error', 'message' => 'Booking not found or not waiting.'];
            }
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Decline failed: ' . $e->getMessage()];
        }
    }

    /**
     * Get subscription bookings for a provider, filtered by status if provided.
     * @param int $provider_id
     * @param string|array|null $status
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getProviderRequests($provider_id, $status = null, $page = 1, $limit = 10) {
        $sql = "SELECT sb.*, sp.category, sb.customer_name AS customer_name, cu.name AS user_name, cu.phone_number AS customer_phone
                FROM {$this->table} sb
                JOIN subscription_plan sp ON sb.sub_id = sp.sub_id
                JOIN users cu ON sb.user_id = cu.user_id
                WHERE sb.provider_id = ?";
        $params = [$provider_id];
        
        if (is_array($status) && count($status) > 0) {
            $in = str_repeat('?,', count($status) - 1) . '?';
            $sql .= " AND sb.subbooking_status IN ($in)";
            $params = array_merge($params, $status);
        } elseif (!empty($status)) {
            $sql .= " AND sb.subbooking_status = ?";
            $params[] = $status;
        } else {
            $sql .= " AND sb.subbooking_status = 'waiting'";
        }
        
        $sql .= " ORDER BY sb.subbooking_date DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = ($page - 1) * $limit;
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as &$row) {
                if (isset($row['subbooking_status'])) {
                    $row['subbooking_status'] = strtolower($row['subbooking_status']);
                }
            }
            
            return ['status' => 'success', 'data' => $results];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Failed to fetch provider subscription requests: ' . $e->getMessage()];
        }
    }
}
