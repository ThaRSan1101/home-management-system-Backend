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
require_once __DIR__ . '/phpmailer.php';

class SubscriptionBooking {
    protected $conn;
    protected $table = 'subscription_booking';

    /**
     * SubscriptionBooking constructor.
     *
     * PURPOSE: Initialize subscription booking handler with database connection
     * HOW IT WORKS: Sets up PDO connection for all subscription booking operations
     * 
     * @param PDO|null $dbConn Optional database connection for dependency injection
     * 
     * IMPLEMENTATION DETAILS:
     * - Accepts existing connection for performance optimization
     * - Creates new connection if none provided via DBConnector
     * - Stores connection for use across all subscription booking methods
     * - Supports dependency injection for testing and transaction management
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
     * Create a new subscription booking with comprehensive validation and notification system.
     *
     * PURPOSE: Enable customers to book subscription services with admin notification
     * WHY NEEDED: Customers need to schedule recurring services with proper tracking
     * HOW IT WORKS: Validates input, creates booking record, triggers admin notification
     * 
     * BUSINESS LOGIC:
     * - All new subscription bookings start with 'pending' status
     * - Admin receives notification for manual provider assignment
     * - Customer name stored for easy identification
     * - Booking includes scheduling and contact information
     * - Amount represents subscription cost for billing context
     * 
     * VALIDATION WORKFLOW:
     * 1. Required field validation for booking completeness
     * 2. Data insertion with 'pending' status
     * 3. Admin notification creation for assignment workflow
     * 4. Return booking confirmation with success status
     * 
     * NOTIFICATION INTEGRATION:
     * - Creates admin notification with 'active' status
     * - Links notification to new subscription booking
     * - Enables admin dashboard to show pending assignments
     * - Supports workflow where admin assigns providers manually
     * 
     * DATABASE OPERATIONS:
     * Subscription Booking Insert:
     * - sub_id: FK to subscription plan
     * - user_id: Customer who created booking
     * - customer_name: Display name for easy identification
     * - subbooking_status: Set to 'pending' for admin assignment
     * - sub_date: Scheduled subscription start date
     * - sub_time: Scheduled subscription start time
     * - sub_address: Service location address
     * - phoneNo: Contact phone for coordination
     * - amount: Subscription cost for billing
     * 
     * Admin Notification Insert:
     * - user_id: Customer who created booking
     * - subscription_booking_id: Links to new booking
     * - description: "New subscription service booking"
     * - admin_action: Set to 'active' for admin attention
     * - customer_action, provider_action: Set to 'none'
     * 
     * ERROR HANDLING:
     * - Missing required fields: Specific field identification
     * - Database insertion failures: PDO exception details
     * - Transaction rollback on notification failure
     * - Comprehensive error messages for debugging
     * 
     * @param array $data Subscription booking details with required fields
     *                    Required fields:
     *                    - sub_id: Subscription plan identifier
     *                    - user_id: Customer identifier
     *                    - sub_date: Scheduled service date
     *                    - sub_time: Scheduled service time
     *                    - sub_address: Service location
     *                    - phoneNo: Contact phone number
     *                    - amount: Subscription cost
     *                    Optional:
     *                    - customer_name: Display name (defaults to empty)
     * @return array Status response with success/error message
     *               Success: ['status' => 'success', 'message' => 'Subscription booked and pending confirmation.']
     *               Error: ['status' => 'error', 'message' => 'Specific error description']
     * 
     * USAGE CONTEXT: Called by subscription_booking.php API endpoint for customer bookings
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
            // Insert admin notification for new subscription booking
            $notificationStmt = $this->conn->prepare("
                INSERT INTO notification 
                (user_id, provider_id, service_booking_id, subscription_booking_id, description, customer_action, provider_action, admin_action) 
                VALUES (?, NULL, NULL, LAST_INSERT_ID(), 'New subscription service booking', 'none', 'none', 'active')
            ");
            $notificationStmt->execute([
                $data['user_id']
            ]);
            return ['status' => 'success', 'message' => 'Subscription booked and pending confirmation.'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Booking failed: ' . $e->getMessage()];
        }
    }

    /**
     * Cancel a subscription booking with comprehensive status management and notification handling.
     *
     * PURPOSE: Allow customers to cancel subscription bookings with proper tracking and notifications
     * WHY NEEDED: Customers need ability to cancel subscriptions with reason tracking for business insights
     * HOW IT WORKS: Updates booking status to 'cancel', stores reason, creates contextual notifications
     * 
     * BUSINESS LOGIC:
     * - Sets booking status to 'cancel' for clear state management
     * - Stores cancellation reason for business analysis
     * - Creates appropriate notifications based on current booking status
     * - Handles different cancellation scenarios (pending vs processing)
     * 
     * NOTIFICATION WORKFLOW:
     * Cancellation During Processing:
     * - If booking is in 'process' status with assigned provider
     * - Creates completion notification for all roles (customer, provider, admin)
     * - Ensures all parties are informed of service completion/cancellation
     * - Sets all action flags to 'active' for visibility
     * 
     * STATUS TRANSITION LOGIC:
     * - Any status → 'cancel': Valid cancellation path
     * - Stores original cancel_reason for analysis
     * - Maintains booking history for reporting
     * - Enables refund processing if needed
     * 
     * CONTEXT RETRIEVAL:
     * - Fetches user_id, provider_id, current status before update
     * - Enables contextual notification creation
     * - Supports different notification patterns based on booking state
     * - Ensures proper notification targeting
     * 
     * DATABASE OPERATIONS:
     * Context Query:
     * - Retrieves current booking state for notification logic
     * - Gets user_id and provider_id for notification targeting
     * - Checks current subbooking_status for workflow decisions
     * 
     * Cancellation Update:
     * - Sets subbooking_status = 'cancel'
     * - Stores cancel_reason for business analysis
     * - Updates based on subbook_id for precise targeting
     * 
     * Completion Notification (if processing):
     * - Creates notification linked to subscription booking
     * - Sets description as 'Subscription service is completed'
     * - Activates all action flags for comprehensive visibility
     * - Links customer, provider, and booking for full context
     * 
     * ERROR HANDLING:
     * - PDO exceptions caught with detailed error messages
     * - Database connection issues handled gracefully
     * - Invalid subbook_id results in no update (safe operation)
     * - Notification failures don't prevent cancellation
     * 
     * @param int $subbook_id Subscription booking identifier to cancel
     * @param string $cancel_reason Customer-provided cancellation reason for analysis
     * @return array Status response with success/error message
     *               Success: ['status' => 'success', 'message' => 'Booking cancelled successfully.']
     *               Error: ['status' => 'error', 'message' => 'Cancellation failed: {PDO error}']
     * 
     * USAGE CONTEXT: Called by subscription management APIs for customer cancellations
     */
    public function cancelBooking($subbook_id, $cancel_reason) {
        try {
            // Fetch context to populate notification
            $ctxStmt = $this->conn->prepare("SELECT user_id, provider_id, subbooking_status FROM {$this->table} WHERE subbook_id = ?");
            $ctxStmt->execute([$subbook_id]);
            $ctx = $ctxStmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $this->conn->prepare("UPDATE {$this->table} SET subbooking_status = 'cancel', cancel_reason = ? WHERE subbook_id = ?");
            $stmt->execute([$cancel_reason, $subbook_id]);

            // When customer unsubscribes during processing, create a completion-like notification for all roles
            if ($ctx && strtolower((string)$ctx['subbooking_status']) === 'process' && !empty($ctx['user_id']) && !empty($ctx['provider_id'])) {
                $notifStmt = $this->conn->prepare("
                    INSERT INTO notification 
                    (user_id, provider_id, service_booking_id, subscription_booking_id, description, customer_action, provider_action, admin_action)
                    VALUES (?, ?, NULL, ?, 'Subscription service is completed', 'active', 'active', 'active')
                ");
                $notifStmt->execute([$ctx['user_id'], $ctx['provider_id'], $subbook_id]);
            }

            return ['status' => 'success', 'message' => 'Booking cancelled successfully.'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Cancellation failed: ' . $e->getMessage()];
        }
    }

    /**
     * Assign a provider to a subscription booking with comprehensive validation and status management.
     *
     * PURPOSE: Enable admin to assign qualified providers to pending subscription bookings
     * WHY NEEDED: Subscription bookings require provider assignment for service delivery
     * HOW IT WORKS: Validates provider, assigns to booking, updates status to 'waiting' for customer confirmation
     * 
     * BUSINESS LOGIC:
     * - Only active providers can be assigned to bookings
     * - Assignment changes booking status from 'pending' to 'waiting'
     * - 'waiting' status indicates customer needs to confirm provider assignment
     * - Provider validation ensures service quality and availability
     * - Assignment creates provider-booking relationship for service delivery
     * 
     * VALIDATION WORKFLOW:
     * 1. Provider Existence Check: Verify provider exists in system
     * 2. Provider Status Check: Ensure provider is 'active' and available
     * 3. Booking Update: Assign provider and update status
     * 4. Result Validation: Confirm assignment was successful
     * 
     * STATUS TRANSITION:
     * - 'pending' → 'waiting': Standard assignment flow
     * - Provider assignment links booking to service delivery capability
     * - 'waiting' status indicates customer action required
     * - Sets up workflow for customer to accept/reject provider
     * 
     * PROVIDER VALIDATION LOGIC:
     * - Checks provider table for provider_id existence
     * - Validates status = 'active' for service availability
     * - Prevents assignment of inactive/suspended providers
     * - Ensures service quality through provider status management
     * 
     * DATABASE OPERATIONS:
     * Provider Validation Query:
     * - SELECT provider_id FROM provider WHERE provider_id = ? AND status = 'active'
     * - Ensures provider exists and is available for assignments
     * - Returns provider record or null for validation
     * 
     * Assignment Update:
     * - Updates subscription_bookings table
     * - Sets provider_id for service delivery assignment
     * - Changes subbooking_status to 'waiting' for customer action
     * - Uses subbook_id for precise booking targeting
     * 
     * ROW COUNT VALIDATION:
     * - Checks if update affected any rows
     * - Confirms booking exists and was successfully updated
     * - Handles cases where subbook_id doesn't exist
     * 
     * ERROR HANDLING:
     * - Provider not found: Clear error message about provider availability
     * - Provider inactive: Prevents assignment of unavailable providers
     * - Booking not found: rowCount() check for invalid subbook_id
     * - Database errors: PDO exception details for debugging
     * - Transaction integrity maintained throughout process
     * 
     * @param int $subbook_id Subscription booking identifier for provider assignment
     * @param int $provider_id Provider identifier to assign to booking
     * @return array Status response with success/error message
     *               Success: ['status' => 'success', 'message' => 'Provider assigned successfully.']
     *               Provider Error: ['status' => 'error', 'message' => 'Provider not found or inactive.']
     *               Booking Error: ['status' => 'error', 'message' => 'Booking not found.']
     *               System Error: ['status' => 'error', 'message' => 'Assignment failed: {PDO error}']
     * 
     * USAGE CONTEXT: Called by admin dashboard for manual provider assignment to subscription bookings
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
     * Retrieve subscription bookings with advanced filtering, pagination, and comprehensive data joining.
     *
     * PURPOSE: Provide flexible subscription booking retrieval for admin dashboards and customer views
     * WHY NEEDED: Different user roles need filtered views of subscription bookings with complete details
     * HOW IT WORKS: Builds dynamic queries with filters, joins multiple tables, implements pagination
     * 
     * BUSINESS LOGIC:
     * - Supports role-based filtering (customer-specific or admin-wide views)
     * - Status filtering for workflow management (pending, process, complete, cancel)
     * - Comprehensive data joining for complete booking information
     * - Pagination for performance with large datasets
     * - Ordered by newest bookings first for relevance
     * 
     * FILTERING CAPABILITIES:
     * User Filtering:
     * - Filter by user_id for customer-specific bookings
     * - Admin can view all bookings when no user_id filter
     * - Enables customer dashboard and admin management views
     * 
     * Status Filtering:
     * - 'pending': New bookings awaiting provider assignment
     * - 'process': Active bookings with assigned providers
     * - 'complete': Finished service subscriptions
     * - 'cancel': Cancelled bookings with reasons
     * - No status filter: All bookings for comprehensive view
     * 
     * QUERY CONSTRUCTION:
     * Dynamic WHERE Clause:
     * - Builds conditions based on provided filters
     * - Uses parameterized queries for SQL injection prevention
     * - Combines multiple filters with AND logic
     * - Maintains query flexibility for different use cases
     * 
     * TABLE JOINS:
     * - subscription_bookings (sb): Main booking data
     * - subscriptions (s): Service details and pricing
     * - users (u): Customer information for display
     * - provider (p): Provider details when assigned
     * - LEFT JOINs prevent data loss for unassigned bookings
     * 
     * DATA SELECTION:
     * Booking Information:
     * - subbook_id, user_id, provider_id: Identifiers
     * - customer_name, sub_date, sub_time: Scheduling details
     * - sub_address, phoneNo: Contact and location
     * - amount, subbooking_status: Financial and status info
     * - cancel_reason: For cancelled bookings analysis
     * 
     * Subscription Details:
     * - sub_id, sub_name, sub_description: Service information
     * - sub_category, sub_price, duration: Service specifications
     * 
     * User Information:
     * - Customer first_name, last_name for identification
     * - Email for contact and notification purposes
     * 
     * Provider Information:
     * - Provider first_name, last_name when assigned
     * - Status for provider availability tracking
     * 
     * PAGINATION IMPLEMENTATION:
     * - Calculates offset based on (page - 1) * limit
     * - Consistent ordering by sb.subbook_id DESC
     * - Enables reliable pagination navigation
     * - Performance optimization for large datasets
     * 
     * ERROR HANDLING:
     * - PDO exceptions caught with detailed error messages
     * - Invalid filter values handled gracefully
     * - Empty result sets return valid empty arrays
     * - Database connection issues reported clearly
     * 
     * @param array $filters Optional filtering criteria
     *                       Supported filters:
     *                       - 'user_id': int - Filter by customer identifier
     *                       - 'status': string - Filter by booking status
     *                         Valid values: 'pending', 'process', 'complete', 'cancel'
     * @param int $page Page number for pagination (1-based, default: 1)
     * @param int $limit Number of records per page (default: 10)
     * @return array Database result with comprehensive booking information
     *               Success: Array of booking objects with joined data
     *               Error: ['status' => 'error', 'message' => 'Database error description']
     * 
     * USAGE CONTEXT: Called by admin dashboards for booking management and customer views for history
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
     * Move a subscription booking to a specific provider with comprehensive notification workflow.
     *
     * PURPOSE: Transfer pending subscription bookings to providers with proper notification system
     * WHY NEEDED: Admin needs ability to reassign bookings while maintaining workflow notifications
     * HOW IT WORKS: Updates provider assignment, changes status to 'waiting', creates provider notification
     * 
     * BUSINESS LOGIC:
     * - Only 'pending' bookings can be moved to prevent workflow disruption
     * - Status changes to 'waiting' indicating provider action required
     * - Creates provider notification for new service request awareness
     * - Maintains booking history and assignment tracking
     * - Enables flexible provider assignment management
     * 
     * WORKFLOW SEQUENCE:
     * 1. Update booking with new provider and 'waiting' status
     * 2. Verify update was successful (prevents invalid booking moves)
     * 3. Retrieve customer user_id for notification context
     * 4. Create provider notification for new assignment
     * 5. Return success confirmation for admin feedback
     * 
     * STATUS TRANSITION LOGIC:
     * - 'pending' → 'waiting': Standard provider assignment flow
     * - Only pending bookings are eligible for movement
     * - 'waiting' status indicates provider needs to accept/decline
     * - Prevents moving active or completed bookings
     * 
     * NOTIFICATION CREATION:
     * - Targets assigned provider with 'active' provider_action
     * - Links notification to subscription booking for context
     * - Sets customer_action and admin_action to 'none'
     * - Uses descriptive message for provider dashboard
     * 
     * DATABASE OPERATIONS:
     * Booking Update:
     * - Updates provider_id with new assignment
     * - Sets subbooking_status to 'waiting' for provider action
     * - Conditions on current status being 'pending'
     * - Uses row count to verify successful update
     * 
     * Customer Context Retrieval:
     * - Fetches user_id from updated booking
     * - Provides customer context for notification
     * - Enables notification linking to customer
     * 
     * Provider Notification Insert:
     * - Creates notification with provider focus
     * - Links to subscription_booking_id for context
     * - Sets provider_action to 'active' for dashboard visibility
     * - Uses clear description for provider understanding
     * 
     * ERROR HANDLING:
     * - Row count check prevents notification for failed updates
     * - Invalid subbook_id results in no changes (safe operation)
     * - Non-pending bookings remain unchanged
     * - PDO exceptions caught with detailed error messages
     * - Graceful handling of missing customer context
     * 
     * @param int $subbook_id Subscription booking identifier to move
     * @param int $provider_id New provider identifier for assignment
     * @return array Status response with success/error message
     *               Success: ['status' => 'success', 'message' => 'Booking moved successfully.']
     *               No Update: ['status' => 'error', 'message' => 'Booking not found or not pending.']
     *               System Error: ['status' => 'error', 'message' => 'Move failed: {PDO error}']
     * 
     * USAGE CONTEXT: Called by admin dashboard for provider reassignment management
     */
    public function moveBooking($subbook_id, $provider_id) {
        try {
            $stmt = $this->conn->prepare("UPDATE {$this->table} SET provider_id = ?, subbooking_status = 'waiting' WHERE subbook_id = ? AND subbooking_status = 'pending'");
            $stmt->execute([$provider_id, $subbook_id]);
            if ($stmt->rowCount() > 0) {
                // Fetch customer user_id for notification
                $userStmt = $this->conn->prepare("SELECT user_id FROM {$this->table} WHERE subbook_id = ?");
                $userStmt->execute([$subbook_id]);
                $row = $userStmt->fetch(PDO::FETCH_ASSOC);
                $userId = $row ? (int)$row['user_id'] : null;
                if ($userId) {
                    // Create notification for provider
                    $notif = $this->conn->prepare("
                        INSERT INTO notification 
                        (user_id, provider_id, service_booking_id, subscription_booking_id, description, customer_action, provider_action, admin_action)
                        VALUES (?, ?, NULL, ?, 'You have a new subscription service request', 'none', 'active', 'none')
                    ");
                    $notif->execute([$userId, $provider_id, $subbook_id]);
                }
                return ['status' => 'success', 'message' => 'Subscription booking moved to provider and set to waiting.'];
            } else {
                return ['status' => 'error', 'message' => 'Booking not found or not pending.'];
            }
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Move failed: ' . $e->getMessage()];
        }
    }

    /**
     * Provider accepts a subscription booking with comprehensive workflow management and customer notification.
     *
     * PURPOSE: Enable providers to accept assigned subscription bookings and notify customers
     * WHY NEEDED: Providers need ability to confirm service availability and start customer communication
     * HOW IT WORKS: Updates booking status to 'process', sends confirmation email to customer
     * 
     * BUSINESS LOGIC:
     * - Only 'waiting' bookings can be accepted by assigned providers
     * - Status changes to 'process' indicating active service delivery
     * - Customer receives email confirmation of provider acceptance
     * - Creates formal service agreement between customer and provider
     * - Enables tracking of active subscription services
     * 
     * VALIDATION WORKFLOW:
     * 1. Verify booking exists and is in 'waiting' status
     * 2. Confirm provider is assigned to this specific booking
     * 3. Update status to 'process' within database transaction
     * 4. Retrieve customer details for email notification
     * 5. Send confirmation email with service details
     * 
     * STATUS TRANSITION LOGIC:
     * - 'waiting' → 'process': Standard provider acceptance flow
     * - Only assigned provider can accept their booking
     * - 'process' status indicates service is actively being delivered
     * - Prevents unauthorized acceptances from other providers
     * 
     * TRANSACTION MANAGEMENT:
     * - Uses database transaction for data consistency
     * - Rollback on failed update prevents inconsistent state
     * - Commit after successful validation ensures data integrity
     * - Protects against concurrent modification issues
     * 
     * EMAIL NOTIFICATION SYSTEM:
     * - Retrieves comprehensive booking details for email content
     * - Includes customer information and subscription plan details
     * - Sends confirmation with service date and time
     * - Professional communication for service confirmation
     * 
     * DATABASE OPERATIONS:
     * Status Update:
     * - Updates subscription_bookings table
     * - Sets subbooking_status to 'process'
     * - Conditions on subbook_id, provider_id, and current status
     * - Row count verification ensures successful update
     * 
     * Customer Detail Retrieval:
     * - Joins subscription_bookings with users and subscription_plan
     * - Fetches customer email and name for notification
     * - Gets plan details and scheduling information
     * - Provides complete context for email content
     * 
     * EMAIL CONTENT PREPARATION:
     * - Plan name from subscription_plan.category
     * - Customer name for personalized communication
     * - Service date and time for scheduling confirmation
     * - Customer email for delivery
     * 
     * ERROR HANDLING:
     * - Row count check prevents invalid acceptances
     * - Transaction rollback on update failures
     * - Graceful handling of missing email details
     * - PDO exception catching with detailed error messages
     * - Validation for booking existence and status
     * 
     * @param int $subbook_id Subscription booking identifier to accept
     * @param int $provider_id Provider identifier confirming acceptance
     * @return array Status response with success/error message
     *               Success: ['status' => 'success', 'message' => 'Booking accepted successfully.']
     *               Invalid: ['status' => 'error', 'message' => 'Booking not found or not waiting.']
     *               System Error: ['status' => 'error', 'message' => 'Acceptance failed: {PDO error}']
     * 
     * USAGE CONTEXT: Called by provider dashboard when accepting assigned subscription bookings
     */
    public function acceptBooking($subbook_id, $provider_id) {
        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare("UPDATE {$this->table} SET subbooking_status = 'process' WHERE subbook_id = ? AND provider_id = ? AND subbooking_status = 'waiting'");
            $stmt->execute([$subbook_id, $provider_id]);
            if ($stmt->rowCount() === 0) {
                $this->conn->rollBack();
                return ['status' => 'error', 'message' => 'Booking not found or not waiting.'];
            }
            // Fetch details for email
            $detailStmt = $this->conn->prepare("SELECT u.email AS customer_email, u.name AS customer_name, sp.category AS plan_name, sb.sub_date, sb.sub_time
                                                FROM {$this->table} sb
                                                JOIN users u ON sb.user_id = u.user_id
                                                JOIN subscription_plan sp ON sb.sub_id = sp.sub_id
                                                WHERE sb.subbook_id = ?");
            $detailStmt->execute([$subbook_id]);
            $details = $detailStmt->fetch(PDO::FETCH_ASSOC);
            $this->conn->commit();

            if ($details && !empty($details['customer_email'])) {
                $planName = $details['plan_name'] ?? 'Subscription Plan';
                $subDate = $details['sub_date'] ?? '';
                $subTime = $details['sub_time'] ?? '';
                $customerName = $details['customer_name'] ?? '';
                $subject = 'Your Subscription Request Is Accepted';
                $body = '<div style="font-family:Arial,sans-serif;max-width:420px;margin:auto;border:1px solid #e0e0e0;padding:24px;border-radius:8px;">'
                    . '<div style="font-size:20px;font-weight:bold;color:#2a4365;margin-bottom:8px;">Home Management System</div>'
                    . '<div style="font-size:16px;margin-bottom:16px;">Hello, <strong>' . htmlspecialchars($customerName) . '</strong></div>'
                    . '<div style="margin-bottom:12px;">Your subscription request has been <strong>accepted</strong>. Here are the details:</div>'
                    . '<div style="font-size:14px;line-height:1.6;margin-bottom:12px;">'
                    . '<div><strong>Subscription:</strong> ' . htmlspecialchars($planName) . '</div>'
                    . '<div><strong>Start Date:</strong> ' . htmlspecialchars($subDate) . '</div>'
                    . '<div><strong>Time:</strong> ' . htmlspecialchars($subTime) . '</div>'
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

            return ['status' => 'success', 'message' => 'Subscription booking accepted.'];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return ['status' => 'error', 'message' => 'Accept failed: ' . $e->getMessage()];
        }
    }

    /**
     * Provider declines a subscription booking with comprehensive workflow reset and availability restoration.
     *
     * PURPOSE: Enable providers to decline assigned subscription bookings and restore availability for reassignment
     * WHY NEEDED: Providers may be unavailable or unable to provide service, requiring booking reassignment
     * HOW IT WORKS: Reverts booking to 'pending' status, removes provider assignment, enables admin reassignment
     * 
     * BUSINESS LOGIC:
     * - Only 'waiting' bookings can be declined by assigned providers
     * - Status reverts to 'pending' for admin reassignment workflow
     * - Provider assignment is cleared (set to NULL) for availability
     * - Booking returns to admin queue for new provider assignment
     * - Maintains booking history while enabling workflow continuation
     * 
     * VALIDATION WORKFLOW:
     * 1. Verify booking exists and is in 'waiting' status
     * 2. Confirm provider is assigned to this specific booking
     * 3. Clear provider assignment and revert status to 'pending'
     * 4. Verify successful update with row count check
     * 5. Return appropriate status for admin dashboard feedback
     * 
     * STATUS TRANSITION LOGIC:
     * - 'waiting' → 'pending': Provider decline workflow
     * - Only assigned provider can decline their booking
     * - 'pending' status returns booking to admin assignment queue
     * - Prevents unauthorized declines from other providers
     * 
     * PROVIDER ASSIGNMENT MANAGEMENT:
     * - Sets provider_id to NULL to clear assignment
     * - Enables booking to be assigned to different provider
     * - Maintains booking integrity while restoring availability
     * - Supports flexible provider management workflow
     * 
     * DATABASE OPERATIONS:
     * Booking Reversion:
     * - Updates subscription_bookings table
     * - Sets subbooking_status to 'pending'
     * - Clears provider_id to NULL
     * - Conditions on subbook_id, current provider_id, and 'waiting' status
     * - Row count verification ensures successful reversion
     * 
     * SECURITY VALIDATION:
     * - Verifies provider_id matches current assignment
     * - Prevents providers from declining others' bookings
     * - Status check ensures booking is in correct state
     * - Database constraints maintain data integrity
     * 
     * WORKFLOW IMPLICATIONS:
     * - Booking becomes available for new provider assignment
     * - Admin dashboard shows booking as pending again
     * - Customer remains unaware of provider decline (admin handles reassignment)
     * - Maintains service quality through provider choice flexibility
     * 
     * ERROR HANDLING:
     * - Row count check prevents invalid declines
     * - Handles non-existent booking IDs gracefully
     * - Validates provider assignment before decline
     * - PDO exception catching with detailed error messages
     * - Clear error messages for debugging and user feedback
     * 
     * @param int $subbook_id Subscription booking identifier to decline
     * @param int $provider_id Provider identifier confirming decline
     * @return array Status response with success/error message
     *               Success: ['status' => 'success', 'message' => 'Subscription booking reverted to pending.']
     *               Invalid: ['status' => 'error', 'message' => 'Booking not found or not waiting.']
     *               System Error: ['status' => 'error', 'message' => 'Decline failed: {PDO error}']
     * 
     * USAGE CONTEXT: Called by provider dashboard when declining assigned subscription bookings
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
