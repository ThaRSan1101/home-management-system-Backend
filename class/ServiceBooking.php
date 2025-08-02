<?php
/**
 * ServiceBooking.php
 *
 * Handles service booking logic for customers, including saving and retrieving bookings.
 *
 * Table: service_booking
 *
 * Fields:
 * - service_book_id (PK)
 * - service_category_id (FK)
 * - user_id (FK)
 * - serbooking_status (ENUM: pending, process, complete, cancel)
 * - serbooking_date (TIMESTAMP)
 * - service_date (DATE)
 * - service_time (TIME)
 * - service_address (TEXT)
 * - phoneNo (VARCHAR)
 * - amount (DECIMAL)
 * - cancel_reason (TEXT)
 */

require_once __DIR__ . '/../api/db.php';

class ServiceBooking {
    /**
     * @var PDO $conn
     */
    protected $conn;
    protected $table = 'service_booking';

    public function __construct($dbConn = null) {
        if ($dbConn) {
            $this->conn = $dbConn;
        } else {
            $db = new DBConnector();
            $this->conn = $db->connect();
        }
    }

    /**
     * Save a new service booking (after payment confirmation).
     * @param array $data Booking details (service_category_id, user_id, service_date, service_time, service_address, phoneNo, amount)
     * @return array Status and message
     */
    public function serviceBook($data) {
        $required = ['service_category_id', 'user_id', 'customer_name', 'service_date', 'service_time', 'service_address', 'phoneNo', 'amount'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['status' => 'error', 'message' => "Missing required field: $field."];
            }
        }
        try {
            $stmt = $this->conn->prepare("INSERT INTO {$this->table} (service_category_id, user_id, customer_name, serbooking_status, service_date, service_time, service_address, phoneNo, amount) VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['service_category_id'],
                $data['user_id'],
                $data['customer_name'],
                $data['service_date'],
                $data['service_time'],
                $data['service_address'],
                $data['phoneNo'],
                $data['amount']
            ]);
            return ['status' => 'success', 'message' => 'Service booked and pending confirmation.'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Booking failed: ' . $e->getMessage()];
        }
    }

    /**
     * Cancel a booking by updating status and cancel_reason.
     */
    public function cancelBooking($service_book_id, $cancel_reason) {
        try {
            $stmt = $this->conn->prepare("UPDATE {$this->table} SET serbooking_status = 'cancel', cancel_reason = ? WHERE service_book_id = ?");
            $stmt->execute([$cancel_reason, $service_book_id]);
            return ['status' => 'success', 'message' => 'Booking cancelled successfully.'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Cancellation failed: ' . $e->getMessage()];
        }
    }

    /**
     * Retrieve bookings (pending or all) for customer or admin.
     *
     * @param array $filters Optional: ['user_id'=>int, 'status'=>'pending'|'process'|'complete'|'cancel']
     * @param int $page Pagination page (default 1)
     * @param int $limit Results per page (default 10)
     * @return array List of bookings
     */
    public function getServiceBooking($filters = [], $page = 1, $limit = 10) {
        $where = [];
        $params = [];
        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = ?';
            $params[] = $filters['user_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'serbooking_status = ?';
            $params[] = $filters['status'];
        }
        $sql = "SELECT sb.*, sc.service_name FROM {$this->table} sb JOIN service_category sc ON sb.service_category_id = sc.service_category_id";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY serbooking_date DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = ($page - 1) * $limit;
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Ensure all status fields are lowercase for frontend compatibility
            foreach ($results as &$row) {
                if (isset($row['serbooking_status'])) {
                    $row['serbooking_status'] = strtolower($row['serbooking_status']);
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
    public function moveBooking($service_book_id, $provider_id) {
        try {
            $stmt = $this->conn->prepare("UPDATE {$this->table} SET provider_id = ?, serbooking_status = 'waiting' WHERE service_book_id = ? AND serbooking_status = 'pending'");
            $stmt->execute([$provider_id, $service_book_id]);
            if ($stmt->rowCount() > 0) {
                return ['status' => 'success', 'message' => 'Booking moved to provider and set to waiting.'];
            } else {
                return ['status' => 'error', 'message' => 'Booking not found or not pending.'];
            }
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Move failed: ' . $e->getMessage()];
        }
    }

    /**
     * Provider accepts the booking: set status to 'process' and allocate.
     */
    public function acceptBooking($service_book_id, $provider_id) {
        try {
            $this->conn->beginTransaction();
            // Set to processing
            $stmt = $this->conn->prepare("UPDATE {$this->table} SET serbooking_status = 'process' WHERE service_book_id = ? AND provider_id = ? AND serbooking_status = 'waiting'");
            $stmt->execute([$service_book_id, $provider_id]);
            if ($stmt->rowCount() === 0) {
                $this->conn->rollBack();
                return ['status' => 'error', 'message' => 'Booking not found or not waiting.'];
            }
            // Insert allocation
            $allocStmt = $this->conn->prepare("INSERT INTO service_provider_allocation (service_book_id, provider_id, allocated_at) VALUES (?, ?, NOW())");
            $allocStmt->execute([$service_book_id, $provider_id]);
            $this->conn->commit();
            return ['status' => 'success', 'message' => 'Booking accepted and allocated.'];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return ['status' => 'error', 'message' => 'Accept failed: ' . $e->getMessage()];
        }
    }

    /**
     * Provider declines the booking: revert status to 'pending'.
     */
    public function declineBooking($service_book_id, $provider_id) {
        try {
            $stmt = $this->conn->prepare("UPDATE {$this->table} SET serbooking_status = 'pending', provider_id = NULL WHERE service_book_id = ? AND provider_id = ? AND serbooking_status = 'waiting'");
            $stmt->execute([$service_book_id, $provider_id]);
            if ($stmt->rowCount() > 0) {
                return ['status' => 'success', 'message' => 'Booking reverted to pending.'];
            } else {
                return ['status' => 'error', 'message' => 'Booking not found or not waiting.'];
            }
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Decline failed: ' . $e->getMessage()];
        }
    }

    /**
     * Get all bookings with status 'process' (processing), joined with service, customer, and provider details.
     *
     * @param array $filters Optional: ['user_id'=>int (for customer), 'provider_id'=>int (for provider), 'admin'=>bool]
     * @param int $page Pagination page (default 1)
     * @param int $limit Results per page (default 10)
     * @return array List of bookings with full details
     */
    public function getProcessingBookings($filters = [], $page = 1, $limit = 10) {
        $where = ["sb.serbooking_status = 'process'"];
        $params = [];
        // Filter for customer
        if (!empty($filters['user_id'])) {
            $where[] = 'sb.user_id = ?';
            $params[] = $filters['user_id'];
        }
        // Filter for provider
        if (!empty($filters['provider_id'])) {
            $where[] = 'spa.provider_id = ?';
            $params[] = $filters['provider_id'];
        }
        $sql = "SELECT sb.service_book_id, sc.service_name, sb.serbooking_date, sb.service_date, sb.service_time, sb.service_address, sb.phoneNo, sb.amount, sb.serbooking_status, sb.cancel_reason, sb.customer_name, 
                cu.name AS customer_name, cu.phone_number AS customer_phone, cu.address AS customer_address, 
                pu.name AS provider_name, pu.phone_number AS provider_phone, pu.address AS provider_address,
                spa.allocated_at
            FROM service_booking sb
            JOIN service_category sc ON sb.service_category_id = sc.service_category_id
            JOIN service_provider_allocation spa ON sb.service_book_id = spa.service_book_id
            JOIN users cu ON sb.user_id = cu.user_id
            JOIN users pu ON spa.provider_id = pu.user_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY sb.serbooking_date DESC
            LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = ($page - 1) * $limit;
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($results as &$row) {
                if (isset($row['serbooking_status'])) {
                    $row['serbooking_status'] = strtolower($row['serbooking_status']);
                }
            }
            return ['status' => 'success', 'data' => $results];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Failed to fetch processing bookings: ' . $e->getMessage()];
        }
    }

    /**
     * Get all bookings in 'waiting' status for a provider (new requests).
     */
    public function getProviderRequests($provider_id, $page = 1, $limit = 10) {
        $sql = "SELECT sb.*, sc.service_name, cu.name AS customer_name, cu.phone_number AS customer_phone
                FROM {$this->table} sb
                JOIN service_category sc ON sb.service_category_id = sc.service_category_id
                JOIN users cu ON sb.user_id = cu.user_id
                WHERE sb.provider_id = ? AND sb.serbooking_status = 'waiting'
                ORDER BY sb.serbooking_date DESC
                LIMIT ? OFFSET ?";
        $params = [$provider_id, $limit, ($page - 1) * $limit];
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($results as &$row) {
                if (isset($row['serbooking_status'])) {
                    $row['serbooking_status'] = strtolower($row['serbooking_status']);
                }
            }
            return ['status' => 'success', 'data' => $results];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Failed to fetch provider requests: ' . $e->getMessage()];
        }
    }
}

