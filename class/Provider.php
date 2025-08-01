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
     * Update provider profile (admin or provider action).
     *
     * @param array $data Provider data fields (must include user_id)
     * @return array Status and message
     *
     * This method is called by update_provider_profile.php, admin_update_provider.php, etc.
     * Splits updates between users and provider tables, validates uniqueness, and uses transactions for consistency.
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
}
