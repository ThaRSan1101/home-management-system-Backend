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
        $sql = "SELECT * FROM {$this->table}";
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
            }
