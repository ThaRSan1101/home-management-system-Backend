<?php
/**
 * ServiceBooking.php
 *
 * Comprehensive service booking management system for the Home Management System backend.
 * Handles the complete lifecycle of service bookings from creation to completion.
 *
 * PURPOSE:
 * ========
 * This class manages all aspects of service bookings including:
 * - Customer service booking creation and validation
 * - Provider assignment and allocation management
 * - Booking status transitions (pending → waiting → process → complete/cancel)
 * - Notification creation for all stakeholders
 * - Provider acceptance/decline workflows
 * - Cancellation handling for customers and providers
 * - Payment and completion confirmation
 *
 * HOW IT WORKS:
 * =============
 * SERVICE BOOKING LIFECYCLE:
 * 1. Customer creates booking (status: pending)
 * 2. Admin assigns provider (status: waiting, creates allocation record)
 * 3. Provider accepts/declines (status: process/pending)
 * 4. Service is performed
 * 5. Provider marks complete (status: complete)
 * 6. Customer accepts/reviews (final completion)
 *
 * PROVIDER ALLOCATION SYSTEM:
 * - service_provider_allocation table tracks provider assignments
 * - Supports provider reassignment if initial provider declines
 * - Maintains history of all provider interactions
 *
 * NOTIFICATION INTEGRATION:
 * - Creates notifications for customers, providers, and admins
 * - Updates notification states based on booking status changes
 * - Supports role-specific notification visibility
 *
 * IMPLEMENTATION STRATEGY:
 * ========================
 * - Uses prepared statements for all database operations
 * - Implements transaction support for data consistency
 * - Provides comprehensive error handling and logging
 * - Supports pagination for large result sets
 * - Maintains referential integrity with related tables
 *
 * DATABASE SCHEMA:
 * ================
 * service_booking table fields:
 * - service_book_id: Primary key
 * - service_category_id: FK to service categories
 * - user_id: FK to customer
 * - customer_name: Customer display name
 * - serbooking_status: ENUM (pending, waiting, process, complete, cancel)
 * - serbooking_date: Booking creation timestamp
 * - service_date: Scheduled service date
 * - service_time: Scheduled service time
 * - service_address: Service location
 * - phoneNo: Contact phone number
 * - amount: Service cost
 * - service_amount: Final amount (after completion)
 * - cancel_reason: Reason for cancellation (if applicable)
 *
 * service_provider_allocation table:
 * - allocation_id: Primary key
 * - service_book_id: FK to service booking
 * - provider_id: FK to assigned provider
 * - allocation_status: Current assignment status
 * - allocated_at: Assignment timestamp
 */

require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/phpmailer.php';

/**
 * Class ServiceBooking
 *
 * Manages service booking operations for customers, providers, and administrators.
 *
 * CORE RESPONSIBILITIES:
 * ======================
 * - Service booking creation and validation
 * - Provider assignment and allocation management
 * - Booking status lifecycle management
 * - Notification system integration
 * - Customer and provider workflow support
 * - Administrative booking management
 * - Payment and completion processing
 */
class ServiceBooking {
    /**
     * @var PDO $conn Database connection for booking operations
     */
    protected $conn;
    
    /**
     * @var string $table Primary table name for service bookings
     */
    protected $table = 'service_booking';

    /**
     * ServiceBooking constructor.
     *
     * PURPOSE: Initialize service booking handler with database connection
     * HOW IT WORKS: Sets up PDO connection for all booking operations
     * 
     * @param PDO|null $dbConn Optional database connection
     * 
     * IMPLEMENTATION DETAILS:
     * - Accepts existing connection for performance optimization
     * - Creates new connection if none provided
     * - Stores connection for use across all booking methods
     * - Supports dependency injection for testing
     */
    public function __construct($dbConn = null) {
        if ($dbConn) {
            $this->conn = $dbConn;
        } else {
            $db = new DBConnector();
            $this->conn = $db->connect();
        }
    }

    /**
     * Resolve provider ID for a booking from multiple data sources.
     * 
     * PURPOSE: Find the provider assigned to a service booking across different tables
     * WHY NEEDED: Provider ID may be stored in service_booking table or allocation table
     * HOW IT WORKS: Checks service_booking table first, falls back to allocation table
     * 
     * IMPLEMENTATION STRATEGY:
     * 1. Try to get provider_id directly from service_booking table
     * 2. If not found or column doesn't exist, check allocation table
     * 3. Return most recent allocation if multiple exist
     * 4. Return null if no provider assigned
     * 
     * BUSINESS LOGIC:
     * - Supports different database schema versions
     * - Handles cases where provider_id column may not exist in service_booking
     * - Uses allocation table as authoritative source for provider assignments
     * - Orders by allocation timestamp to get most recent assignment
     * 
     * @param int $service_book_id The service booking ID to lookup
     * @return int|null Provider ID if found, null if not assigned
     * 
     * USAGE CONTEXT: Internal method used by other booking operations
     */
    private function resolveProviderIdForBooking($service_book_id) {
        // Try from service_booking table if column exists
        try {
            $stmt = $this->conn->prepare("SELECT provider_id FROM {$this->table} WHERE service_book_id = ?");
            $stmt->execute([$service_book_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['provider_id'])) {
                return (int)$row['provider_id'];
            }
        } catch (PDOException $e) {
            // Ignore if column does not exist; fallback to allocation
        }
        
        // Fallback: latest allocation record
        try {
            $allocStmt = $this->conn->prepare("SELECT provider_id FROM service_provider_allocation WHERE service_book_id = ? ORDER BY allocated_at DESC, allocation_id DESC LIMIT 1");
            $allocStmt->execute([$service_book_id]);
            $alloc = $allocStmt->fetch(PDO::FETCH_ASSOC);
            if ($alloc && !empty($alloc['provider_id'])) {
                return (int)$alloc['provider_id'];
            }
        } catch (PDOException $e) {
            // No allocation, return null
        }
        return null;
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
            
            // Get the newly created service booking ID
            $service_book_id = $this->conn->lastInsertId();
            
            // Insert notification for new service booking (admin only active)
            $notificationStmt = $this->conn->prepare("
                INSERT INTO notification 
                (user_id, provider_id, service_booking_id, subscription_booking_id, description, customer_action, provider_action, admin_action) 
                VALUES (?, NULL, ?, NULL, 'New service booking', 'none', 'none', 'active')
            ");
            $notificationStmt->execute([
                $data['user_id'],
                $service_book_id
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
            // Fetch existing booking to infer context (status, user, provider)
            $fetchStmt = $this->conn->prepare("SELECT user_id, provider_id, serbooking_status FROM {$this->table} WHERE service_book_id = ?");
            $fetchStmt->execute([$service_book_id]);
            $existing = $fetchStmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $this->conn->prepare("UPDATE {$this->table} SET serbooking_status = 'cancel', cancel_reason = ? WHERE service_book_id = ?");
            $stmt->execute([$cancel_reason, $service_book_id]);

            // If it was in process and a provider can be resolved, create the cancellation notification
            $resolvedProviderId = $existing ? ($existing['provider_id'] ?? null) : null;
            if (empty($resolvedProviderId)) {
                $resolvedProviderId = $this->resolveProviderIdForBooking($service_book_id);
            }
            if ($existing && strtolower((string)$existing['serbooking_status']) === 'process' && !empty($resolvedProviderId)) {
                $notificationStmt = $this->conn->prepare("
                    INSERT INTO notification 
                    (user_id, provider_id, service_booking_id, subscription_booking_id, description, customer_action, provider_action, admin_action) 
                    VALUES (?, ?, ?, NULL, 'Service booking is canceled', 'active', 'active', 'active')
                ");
                $notificationStmt->execute([
                    $existing['user_id'],
                    $resolvedProviderId,
                    $service_book_id
                ]);
            }

            return ['status' => 'success', 'message' => 'Booking cancelled successfully.'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Cancellation failed: ' . $e->getMessage()];
        }
    }

    /**
     * Provider cancels a processing booking. Marks booking as 'cancel' and creates notifications
     * for customer, provider, and admin with description "Service booking is canceled".
     *
     * @param int $service_book_id
     * @param int $provider_id
     * @param string $cancel_reason
     * @return array
     */
    public function cancelBookingByProvider($service_book_id, $provider_id, $cancel_reason) {
        try {
            $this->conn->beginTransaction();

            // Ensure the booking is currently in process and belongs to this provider
            $checkStmt = $this->conn->prepare("SELECT user_id, provider_id, serbooking_status FROM {$this->table} WHERE service_book_id = ? FOR UPDATE");
            $checkStmt->execute([$service_book_id]);
            $booking = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if (!$booking) {
                $this->conn->rollBack();
                return ['status' => 'error', 'message' => 'Booking not found.'];
            }
            if (strtolower($booking['serbooking_status']) !== 'process') {
                $this->conn->rollBack();
                return ['status' => 'error', 'message' => 'Only processing bookings can be cancelled by provider.'];
            }
            if ((int)$booking['provider_id'] !== (int)$provider_id) {
                $this->conn->rollBack();
                return ['status' => 'error', 'message' => 'This booking is not assigned to the provider.'];
            }

            // Update booking status and reason
            $updateStmt = $this->conn->prepare("UPDATE {$this->table} SET serbooking_status = 'cancel', cancel_reason = ? WHERE service_book_id = ?");
            $updateStmt->execute([$cancel_reason, $service_book_id]);

            // Insert notification for cancellation
            $notificationStmt = $this->conn->prepare("
                INSERT INTO notification 
                (user_id, provider_id, service_booking_id, subscription_booking_id, description, customer_action, provider_action, admin_action) 
                VALUES (?, ?, ?, NULL, 'Service booking is canceled', 'active', 'active', 'active')
            ");
            $notificationStmt->execute([
                $booking['user_id'],
                $provider_id,
                $service_book_id
            ]);

            $this->conn->commit();
            return ['status' => 'success', 'message' => 'Booking cancelled and notifications created.'];
        } catch (PDOException $e) {
            $this->conn->rollBack();
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
            $where[] = 'sb.user_id = ?';
            $params[] = $filters['user_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'sb.serbooking_status = ?';
            $params[] = $filters['status'];
        }
        $sql = "SELECT sb.*, sb.service_amount, sc.service_name, 
                spa.allocated_at, 
                pu.name AS provider_name, pu.phone_number AS provider_phone, pu.address AS provider_address,
                COALESCE(u_provider.name, 'Unassigned') AS provider_name,
                COALESCE(u_provider.phone_number, '') AS provider_phone,
                COALESCE(u_provider.address, '') AS provider_address
            FROM {$this->table} sb 
            JOIN service_category sc ON sb.service_category_id = sc.service_category_id
            LEFT JOIN service_provider_allocation spa ON sb.service_book_id = spa.service_book_id
            LEFT JOIN provider p ON sb.provider_id = p.provider_id
            LEFT JOIN users u_provider ON p.user_id = u_provider.user_id
            LEFT JOIN users pu ON spa.provider_id = pu.user_id";
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
            $this->conn->beginTransaction();
            
            // Update the booking
            $stmt = $this->conn->prepare("UPDATE {$this->table} SET provider_id = ?, serbooking_status = 'waiting' WHERE service_book_id = ? AND serbooking_status = 'pending'");
            $stmt->execute([$provider_id, $service_book_id]);
            
            if ($stmt->rowCount() > 0) {
                // Get customer user_id for the notification
                $customerStmt = $this->conn->prepare("SELECT user_id FROM {$this->table} WHERE service_book_id = ?");
                $customerStmt->execute([$service_book_id]);
                $booking = $customerStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($booking) {
                    // Create notification for provider
                    $notificationStmt = $this->conn->prepare("
                        INSERT INTO notification 
                        (user_id, provider_id, service_booking_id, subscription_booking_id, description, customer_action, provider_action, admin_action) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $notificationStmt->execute([
                        $booking['user_id'],           // user_id (customer)
                        $provider_id,                  // provider_id
                        $service_book_id,              // service_booking_id
                        null,                          // subscription_booking_id (NULL)
                        'You have a new service request', // description
                        'none',                        // customer_action
                        'active',                      // provider_action
                        'none'                         // admin_action
                    ]);
                }
                
                $this->conn->commit();
                return ['status' => 'success', 'message' => 'Booking moved to provider and set to waiting.'];
            } else {
                $this->conn->rollBack();
                return ['status' => 'error', 'message' => 'Booking not found or not pending.'];
            }
        } catch (PDOException $e) {
            $this->conn->rollBack();
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
            // Fetch details for email
            $detailStmt = $this->conn->prepare("SELECT u.email AS customer_email, u.name AS customer_name, sc.service_name, sb.service_date, sb.service_time
                                                FROM {$this->table} sb
                                                JOIN users u ON sb.user_id = u.user_id
                                                JOIN service_category sc ON sb.service_category_id = sc.service_category_id
                                                WHERE sb.service_book_id = ?");
            $detailStmt->execute([$service_book_id]);
            $details = $detailStmt->fetch(PDO::FETCH_ASSOC);
            $this->conn->commit();

            // Prepare acceptance email to customer using OTP email card style and defer sending
            if ($details && !empty($details['customer_email'])) {
                $serviceName = $details['service_name'] ?? 'Service';
                $serviceDate = $details['service_date'] ?? '';
                $serviceTime = $details['service_time'] ?? '';
                $customerName = $details['customer_name'] ?? '';
                $subject = 'Your Service Request Is Accepted';
                $body = '<div style="font-family:Arial,sans-serif;max-width:420px;margin:auto;border:1px solid #e0e0e0;padding:24px;border-radius:8px;">'
                    . '<div style="font-size:20px;font-weight:bold;color:#2a4365;margin-bottom:8px;">Home Management System</div>'
                    . '<div style="font-size:16px;margin-bottom:16px;">Hello, <strong>' . htmlspecialchars($customerName) . '</strong></div>'
                    . '<div style="margin-bottom:12px;">Your service request has been <strong>accepted</strong>. Here are the details:</div>'
                    . '<div style="font-size:14px;line-height:1.6;margin-bottom:12px;">'
                    . '<div><strong>Service:</strong> ' . htmlspecialchars($serviceName) . '</div>'
                    . '<div><strong>Date:</strong> ' . htmlspecialchars($serviceDate) . '</div>'
                    . '<div><strong>Time:</strong> ' . htmlspecialchars($serviceTime) . '</div>'
                    . '</div>'
                    . '<div style="font-size:12px;color:#555;">We will keep you updated with further progress.</div>'
                    . '<hr style="margin:24px 0 12px 0;border:none;border-top:1px solid #eee;">'
                    . '<div style="font-size:12px;color:#999;">If you did not request this, please ignore this email.</div>'
                    . '</div>';
                $emailTo = $details['customer_email'];
                register_shutdown_function(function() use ($emailTo, $subject, $body) {
                    $mailer = new PHPMailerService();
                    $mailer->sendMail($emailTo, $subject, $body);
                });
            }

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
     * Get all bookings with status 'process' (processing) or 'cancel', joined with service, customer, and provider details.
     *
     * @param array $filters Optional: ['user_id'=>int (for customer), 'provider_id'=>int (for provider), 'admin'=>bool, 'status'=>string]
     * @param int $page Pagination page (default 1)
     * @param int $limit Results per page (default 10)
     * @return array List of bookings with full details
     */
    public function getAdminBookings($filters = [], $page = 1, $limit = 10) {
        $where = [];
        $params = [];
        // Filter for status (pending/waiting/process/complete/cancel)
        if (!empty($filters['status'])) {
            $where[] = 'sb.serbooking_status = ?';
            $params[] = $filters['status'];
        }
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
        $sql = "SELECT sb.service_book_id, sc.service_name, sb.serbooking_date, sb.service_date, sb.service_time, sb.service_address, sb.phoneNo, sb.amount, sb.service_amount, sb.serbooking_status, sb.cancel_reason, sb.customer_name AS customer_name, 
                cu.name AS user_name, cu.phone_number AS customer_phone, cu.address AS customer_address, 
                COALESCE(u_provider.name, 'Unassigned') AS provider_name,
                COALESCE(u_provider.phone_number, '') AS provider_phone,
                COALESCE(u_provider.address, '') AS provider_address,
                spa.allocated_at
            FROM service_booking sb
            JOIN service_category sc ON sb.service_category_id = sc.service_category_id
            LEFT JOIN service_provider_allocation spa ON sb.service_book_id = spa.service_book_id
            JOIN users cu ON sb.user_id = cu.user_id
            LEFT JOIN provider p ON sb.provider_id = p.provider_id
            LEFT JOIN users u_provider ON p.user_id = u_provider.user_id
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
                if (!isset($row['provider_name']) || $row['provider_name'] === null || $row['provider_name'] === '') {
                    $row['provider_name'] = 'Unassigned';
                }
            }
            return ['status' => 'success', 'data' => $results];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Failed to fetch processing bookings: ' . $e->getMessage()];
        }
    }

    /**
     * Get bookings for a provider, filtered by status if provided.
     * @param int $provider_id
     * @param string|array|null $status
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getProviderRequests($provider_id, $status = null, $page = 1, $limit = 10) {
        $sql = "SELECT sb.*, sc.service_name, sb.customer_name AS customer_name, cu.name AS user_name, cu.phone_number AS customer_phone
                FROM {$this->table} sb
                JOIN service_category sc ON sb.service_category_id = sc.service_category_id
                JOIN users cu ON sb.user_id = cu.user_id
                WHERE sb.provider_id = ?";
        $params = [$provider_id];
        if (is_array($status) && count($status) > 0) {
            $in = str_repeat('?,', count($status) - 1) . '?';
            $sql .= " AND sb.serbooking_status IN ($in)";
            $params = array_merge($params, $status);
        } elseif (!empty($status)) {
            $sql .= " AND sb.serbooking_status = ?";
            $params[] = $status;
        } else {
            $sql .= " AND sb.serbooking_status = 'waiting'";
        }
        $sql .= " ORDER BY sb.serbooking_date DESC LIMIT ? OFFSET ?";
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
            return ['status' => 'error', 'message' => 'Failed to fetch provider requests: ' . $e->getMessage()];
        }
    }

    /**
     * Provider completes booking: set status to 'request' and update service_amount
     */
    public function providerCompleteBooking($service_book_id, $service_amount) {
        try {
            $stmt = $this->conn->prepare("UPDATE {$this->table} SET serbooking_status = 'request', service_amount = ? WHERE service_book_id = ? AND serbooking_status = 'process'");
            $stmt->execute([$service_amount, $service_book_id]);
            if ($stmt->rowCount() > 0) {
                return ['status' => 'success', 'message' => 'Booking set to request and service amount updated.'];
            } else {
                return ['status' => 'error', 'message' => 'Booking not found or not processing.'];
            }
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Failed to update booking: ' . $e->getMessage()];
        }
    }

    /**
     * Customer accepts booking: set status to 'complete'
     */
    public function customerAcceptBooking($service_book_id) {
        try {
            $stmt = $this->conn->prepare("UPDATE {$this->table} SET serbooking_status = 'complete' WHERE service_book_id = ? AND serbooking_status = 'request'");
            $stmt->execute([$service_book_id]);
            if ($stmt->rowCount() > 0) {
                // Fetch user and provider to generate notifications
                $fetchStmt = $this->conn->prepare("SELECT user_id FROM {$this->table} WHERE service_book_id = ?");
                $fetchStmt->execute([$service_book_id]);
                $row = $fetchStmt->fetch(PDO::FETCH_ASSOC);
                $userId = $row ? (int)$row['user_id'] : null;
                $providerId = $this->resolveProviderIdForBooking($service_book_id);
                if ($userId && $providerId) {
                    $notificationStmt = $this->conn->prepare("
                        INSERT INTO notification 
                        (user_id, provider_id, service_booking_id, subscription_booking_id, description, customer_action, provider_action, admin_action) 
                        VALUES (?, ?, ?, NULL, 'Service booking is completed', 'active', 'active', 'active')
                    ");
                    $notificationStmt->execute([$userId, $providerId, $service_book_id]);
                }
                return ['status' => 'success', 'message' => 'Booking marked as complete.'];
            } else {
                return ['status' => 'error', 'message' => 'Booking not found or not in request state.'];
            }
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Failed to update booking: ' . $e->getMessage()];
        }
    }
}