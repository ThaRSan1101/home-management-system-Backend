<?php
/**
 * Customer.php
 *
 * Defines the Customer class, which represents a customer user in the Home Management System backend.
 *
 * Responsibilities:
 * - Encapsulates all customer-specific logic and data updates
 * - Inherits all properties and methods from User.php
 * - Used by admin APIs to update customer profiles securely
 *
 * This class is typically used by endpoints such as admin_update_customer.php.
 */
require_once __DIR__ . '/User.php';

/**
 * Class Customer
 *
 * Extends the User class to handle customer-specific actions.
 *
 * Methods:
 * - __construct($dbConn = null)
 * - updateProfile($data)
 */
class Customer extends User {
    /**
     * Customer constructor.
     *
     * @param PDO|null $dbConn Optional PDO connection. If not provided, inherited constructor creates one.
     */
    public function __construct($dbConn = null) {
        parent::__construct($dbConn);
    }

    /**
     * Update customer profile (admin action).
     *
     * @param array $data Customer data fields (must include user_id)
     * @return array Status and message
     *
     * This method is called by admin_update_customer.php to update a customer's profile.
     * Performs field validation, uniqueness checks, and updates the database.
     */
    public function updateProfile($data) {
        // Extract and validate user_id
        $userId = $data['user_id'] ?? null;
        if (!$userId) {
            return ['status' => 'error', 'message' => 'User ID is required.'];
        }

        // Build list of allowed fields to update
        $fields = [];
        $params = [];
        // Only update fields that are present and non-empty in the input
        if (!empty($data['name'])) {
            $fields[] = 'name = ?';
            $params[] = htmlspecialchars(trim($data['name']), ENT_QUOTES, 'UTF-8');
        }
        if (!empty($data['email'])) {
            $fields[] = 'email = ?';
            $params[] = htmlspecialchars(strtolower(trim($data['email'])), ENT_QUOTES, 'UTF-8');
        }
        if (!empty($data['phone_number'])) {
            $fields[] = 'phone_number = ?';
            $params[] = htmlspecialchars(trim($data['phone_number']), ENT_QUOTES, 'UTF-8');
        }
        if (!empty($data['address'])) {
            $fields[] = 'address = ?';
            $params[] = htmlspecialchars(trim($data['address']), ENT_QUOTES, 'UTF-8');
        }
        // Support both 'NIC' or 'nic' as input
        $nicValue = $data['NIC'] ?? $data['nic'] ?? null;
        if (!empty($nicValue)) {
            $fields[] = 'NIC = ?';
            $params[] = htmlspecialchars(trim($nicValue), ENT_QUOTES, 'UTF-8');
        }
        // Allow admin to set disable_status
        if (isset($data['disable_status'])) {
            $fields[] = 'disable_status = ?';
            $params[] = (int)$data['disable_status'];
        }
        if (empty($fields)) {
            return ['status' => 'error', 'message' => 'No valid fields to update.'];
        }
        $params[] = $userId;

        // Check for unique email (if changed)
        if (!empty($data['email'])) {
            $checkEmail = $this->conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $checkEmail->execute([strtolower(trim($data['email'])), $userId]);
            if ($checkEmail->fetchColumn()) {
                return ['status' => 'error', 'message' => 'Email already exists.'];
            }
        }
        // Check for unique NIC (if changed)
        if (!empty($nicValue)) {
            $checkNIC = $this->conn->prepare("SELECT user_id FROM users WHERE NIC = ? AND user_id != ?");
            $checkNIC->execute([trim($nicValue), $userId]);
            if ($checkNIC->fetchColumn()) {
                return ['status' => 'error', 'message' => 'NIC already exists.'];
            }
        }

        try {
            // Ensure user exists before update
            $checkStmt = $this->conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
            $checkStmt->execute([$userId]);
            if (!$checkStmt->fetchColumn()) {
                return ['status' => 'error', 'message' => 'User not found.'];
            }
            // Prepare and execute the update statement
            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE user_id = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt->execute($params)) {
                return ['status' => 'error', 'message' => 'Failed to update customer profile.'];
            }
            // Always return success if user exists and query ran, even if no changes
            return ['status' => 'success', 'message' => 'Customer profile updated successfully.'];
        } catch (PDOException $e) {
            // Handle database errors gracefully
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}
