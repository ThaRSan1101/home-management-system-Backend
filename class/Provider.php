<?php
/**
 * Provider.php
 *
 * Defines the Provider class, representing a service provider user in the Home Management System backend.
 *
 * Responsibilities:
 * - Encapsulates provider-specific logic and data updates
 * - Inherits all core user logic from User.php
 * - Used by provider/admin APIs to update provider profiles securely
 *
 * This class is typically used by endpoints such as update_provider_profile.php, admin_update_provider.php, etc.
 */
require_once __DIR__ . '/User.php';

/**
 * Class Provider
 *
 * Extends the User class to handle provider-specific actions.
 *
 * Methods:
 * - __construct($dbConn = null)
 * - getProviderServices()
 * - updateProfile($data)
 */
class Provider extends User {
    /**
     * Provider constructor.
     *
     * @param PDO|null $dbConn Optional PDO connection. If not provided, inherited constructor creates one.
     */
    public function __construct($dbConn = null) {
        parent::__construct($dbConn);
    }

    // Add provider-specific methods here
    /**
     * Get services offered by this provider (stub/example).
     *
     * @return array Services (not implemented)
     */
    public function getProviderServices() {
        // Example: return services offered by this provider
    }

    /**
     * Update provider profile with dual-table management and comprehensive validation.
     *
     * PURPOSE: Enable provider and admin users to update provider profile information
     * WHY NEEDED: Providers need to maintain current profile info, admins need management capability
     * HOW IT WORKS: Splits updates between users and provider tables with transaction support
     * 
     * BUSINESS LOGIC:
     * - Updates span two tables: users (basic info) and provider (specialized info)
     * - Uses database transactions to ensure data consistency
     * - Validates unique constraints before any updates
     * - Supports partial updates (only provided fields are changed)
     * - Maintains referential integrity between tables
     * 
     * DUAL-TABLE ARCHITECTURE:
     * Users Table Updates:
     * - name: Provider's full name
     * - email: Contact email address
     * - phone_number: Contact phone number
     * - address: Business/service address
     * - NIC: National identification number
     * - disable_status: Account status flag
     * 
     * Provider Table Updates:
     * - description: Service description and specialties
     * - qualifications: Professional qualifications and certifications
     * 
     * SECURITY IMPLEMENTATION:
     * - HTML encoding for all text inputs to prevent XSS
     * - Prepared statements for SQL injection prevention
     * - Unique constraint validation for email and NIC
     * - Transaction rollback on any failure
     * - Input sanitization and validation
     * 
     * VALIDATION WORKFLOW:
     * 1. Verify user_id is provided
     * 2. Separate fields for users vs provider tables
     * 3. Check email uniqueness if email being changed
     * 4. Check NIC uniqueness if NIC being changed
     * 5. Begin database transaction
     * 6. Update users table if fields present
     * 7. Update provider table if fields present
     * 8. Commit transaction on success
     * 9. Rollback on any error
     * 
     * TRANSACTION HANDLING:
     * - Ensures atomicity across both table updates
     * - Prevents partial updates that could cause data inconsistency
     * - Automatic rollback on any database error
     * - Comprehensive error reporting for debugging
     * 
     * @param array $data Provider data fields (must include user_id)
     *                    Users table: name, email, phone_number, address, NIC/nic, disable_status
     *                    Provider table: description, qualifications
     * @return array Status response with success/error and descriptive message
     * 
     * USAGE CONTEXT: Called by update_provider_profile.php and admin_update_provider.php
     */
    public function updateProfile($data) {
        $userId = $data['user_id'] ?? null;
        if (!$userId) {
            return ['status' => 'error', 'message' => 'User ID is required.'];
        }

        // Split fields for users and provider tables
        $userFields = [];
        $userParams = [];
        $providerFields = [];
        $providerParams = [];

        if (!empty($data['name'])) {
            $userFields[] = 'name = ?';
            $userParams[] = htmlspecialchars(trim($data['name']), ENT_QUOTES, 'UTF-8');
        }
        if (!empty($data['email'])) {
            $userFields[] = 'email = ?';
            $userParams[] = htmlspecialchars(strtolower(trim($data['email'])), ENT_QUOTES, 'UTF-8');
        }
        if (!empty($data['phone_number'])) {
            $userFields[] = 'phone_number = ?';
            $userParams[] = htmlspecialchars(trim($data['phone_number']), ENT_QUOTES, 'UTF-8');
        }
        if (!empty($data['address'])) {
            $userFields[] = 'address = ?';
            $userParams[] = htmlspecialchars(trim($data['address']), ENT_QUOTES, 'UTF-8');
        }

        $nicValue = $data['NIC'] ?? $data['nic'] ?? null;
        if (!empty($nicValue)) {
            $userFields[] = 'NIC = ?';
            $userParams[] = htmlspecialchars(trim($nicValue), ENT_QUOTES, 'UTF-8');
        }

        if (isset($data['disable_status'])) {
            $userFields[] = 'disable_status = ?';
            $userParams[] = (int)$data['disable_status'];
        }

        // Provider table fields
        if (isset($data['description'])) {
            $providerFields[] = 'description = ?';
            $providerParams[] = htmlspecialchars(trim($data['description']), ENT_QUOTES, 'UTF-8');
        }
        if (isset($data['qualifications'])) {
            $providerFields[] = 'qualifications = ?';
            $providerParams[] = htmlspecialchars(trim($data['qualifications']), ENT_QUOTES, 'UTF-8');
        }

        if (empty($userFields) && empty($providerFields)) {
            return ['status' => 'error', 'message' => 'No valid fields to update.'];
        }

        // Uniqueness checks for email and NIC
        if (!empty($data['email'])) {
            $checkEmail = $this->conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $checkEmail->execute([strtolower(trim($data['email'])), $userId]);
            if ($checkEmail->fetchColumn()) {
                return ['status' => 'error', 'message' => 'Email already exists.'];
            }
        }

        $nicValue = $data['NIC'] ?? $data['nic'] ?? null;
        if (!empty($nicValue)) {
            $checkNIC = $this->conn->prepare("SELECT user_id FROM users WHERE NIC = ? AND user_id != ?");
            $checkNIC->execute([trim($nicValue), $userId]);
            if ($checkNIC->fetchColumn()) {
                return ['status' => 'error', 'message' => 'NIC already exists.'];
            }
        }

        try {
            $this->conn->beginTransaction();

            // Check if user exists before update
            $checkStmt = $this->conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
            $checkStmt->execute([$userId]);
            if (!$checkStmt->fetchColumn()) {
                $this->conn->rollBack();
                return ['status' => 'error', 'message' => 'User not found.'];
            }

            // Update users table
            if (!empty($userFields)) {
                $userParams[] = $userId;
                $sql = "UPDATE users SET " . implode(', ', $userFields) . " WHERE user_id = ?";
                $stmt = $this->conn->prepare($sql);
                if (!$stmt->execute($userParams)) {
                    $this->conn->rollBack();
                    return ['status' => 'error', 'message' => 'Failed to update user profile.'];
                }
            }

            // Update provider table
            if (!empty($providerFields)) {
                $providerParams[] = $userId;
                $sql2 = "UPDATE provider SET " . implode(', ', $providerFields) . " WHERE user_id = ?";
                $stmt2 = $this->conn->prepare($sql2);
                if (!$stmt2->execute($providerParams)) {
                    $this->conn->rollBack();
                    return ['status' => 'error', 'message' => 'Failed to update provider details.'];
                }
            }

            $this->conn->commit();
            return ['status' => 'success', 'message' => 'Provider profile updated successfully.'];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Change provider status (active/inactive).
     *
     * @param int $providerId
     * @param string $newStatus ('active' or 'inactive')
     * @return array
     */
    public function changeProviderStatus($providerId, $newStatus) {
        $allowed = ['active', 'inactive'];
        $newStatus = strtolower(trim($newStatus));
        if (!in_array($newStatus, $allowed)) {
            return ['status' => 'error', 'message' => 'Invalid status value.'];
        }
        try {
            $stmt = $this->conn->prepare("UPDATE provider SET status = ? WHERE user_id = ?");
            if ($stmt->execute([$newStatus, $providerId])) {
                return ['status' => 'success', 'message' => 'Status updated successfully.'];
            } else {
                return ['status' => 'error', 'message' => 'Failed to update status.'];
            }
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get provider status (active/inactive).
     *
     * @param int $providerId
     * @return array ['status' => ..., 'provider_status' => ...]
     */
    public function getProviderStatus($providerId) {
        try {
            $stmt = $this->conn->prepare("SELECT status FROM provider WHERE user_id = ?");
            $stmt->execute([$providerId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return ['status' => 'success', 'provider_status' => $row['status']];
            } else {
                return ['status' => 'error', 'message' => 'Provider not found.'];
            }
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get dashboard statistics for a provider.
     *
     * Returns counts for:
     * - bookings: service + subscription bookings with status 'waiting'
     * - subscriptions: subscription bookings with status in ('process','cancel')
     * - services: service bookings with status in ('process','cancel','request','complete')
     * - feedback: total feedback from service_review and subscription_review linked to provider
     *
     * @param int $providerId
     * @return array
     */
    public function getDashboardStats($providerId) {
        try {
            // Booking Requests (waiting)
            $stmt1 = $this->conn->prepare("SELECT COUNT(*) AS c FROM service_booking WHERE provider_id = ? AND serbooking_status = 'waiting'");
            $stmt1->execute([$providerId]);
            $serviceWaiting = (int)$stmt1->fetchColumn();

            $stmt2 = $this->conn->prepare("SELECT COUNT(*) AS c FROM subscription_booking WHERE provider_id = ? AND subbooking_status = 'waiting'");
            $stmt2->execute([$providerId]);
            $subscriptionWaiting = (int)$stmt2->fetchColumn();

            $bookings = $serviceWaiting + $subscriptionWaiting;

            // Total Subscriptions (process, cancel)
            $stmt3 = $this->conn->prepare("SELECT COUNT(*) AS c FROM subscription_booking WHERE provider_id = ? AND subbooking_status IN ('process','cancel')");
            $stmt3->execute([$providerId]);
            $subscriptions = (int)$stmt3->fetchColumn();

            // Total Services (process, cancel, request, complete)
            $stmt4 = $this->conn->prepare("SELECT COUNT(*) AS c FROM service_booking WHERE provider_id = ? AND serbooking_status IN ('process','cancel','request','complete')");
            $stmt4->execute([$providerId]);
            $services = (int)$stmt4->fetchColumn();

            // Total Feedback from both review tables via allocations
            $serviceFeedback = 0;
            try {
                $sf = $this->conn->prepare("SELECT COUNT(*) FROM service_review sr JOIN service_provider_allocation spa ON sr.allocation_id = spa.allocation_id WHERE spa.provider_id = ?");
                $sf->execute([$providerId]);
                $serviceFeedback = (int)$sf->fetchColumn();
            } catch (PDOException $e) {
                $serviceFeedback = 0;
            }

            $subscriptionFeedback = 0;
            try {
                $ss = $this->conn->prepare("SELECT COUNT(*) FROM subscription_review sr JOIN subscription_provider_allocation spa ON sr.allocation_id = spa.allocation_id WHERE spa.provider_id = ?");
                $ss->execute([$providerId]);
                $subscriptionFeedback = (int)$ss->fetchColumn();
            } catch (PDOException $e) {
                $subscriptionFeedback = 0;
            }

            $feedback = $serviceFeedback + $subscriptionFeedback;

            return [
                'status' => 'success',
                'data' => [
                    'bookings' => $bookings,
                    'subscriptions' => $subscriptions,
                    'services' => $services,
                    'feedback' => $feedback,
                ]
            ];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Failed to fetch stats: ' . $e->getMessage()];
        }
    }
}
