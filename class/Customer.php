<?php
require_once __DIR__ . '/User.php';

class Customer extends User {
    public function __construct($dbConn = null) {
        parent::__construct($dbConn);
    }

    /**
     * Update customer profile (admin action)
     * @param array $data
     * @return array
     */
    public function updateProfile($data) {
        $userId = $data['user_id'] ?? null;
        if (!$userId) {
            return ['status' => 'error', 'message' => 'User ID is required.'];
        }

        // Allowed fields to update
        $fields = [];
        $params = [];
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
        $nicValue = $data['NIC'] ?? $data['nic'] ?? null;
        if (!empty($nicValue)) {
            $fields[] = 'NIC = ?';
            $params[] = htmlspecialchars(trim($nicValue), ENT_QUOTES, 'UTF-8');
        }
        if (isset($data['disable_status'])) {
            $fields[] = 'disable_status = ?';
            $params[] = (int)$data['disable_status'];
        }
        if (empty($fields)) {
            return ['status' => 'error', 'message' => 'No valid fields to update.'];
        }
        $params[] = $userId;

        // Uniqueness checks for email and NIC
        if (!empty($data['email'])) {
            $checkEmail = $this->conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $checkEmail->execute([strtolower(trim($data['email'])), $userId]);
            if ($checkEmail->fetchColumn()) {
                return ['status' => 'error', 'message' => 'Email already exists.'];
            }
        }
        if (!empty($nicValue)) {
            $checkNIC = $this->conn->prepare("SELECT user_id FROM users WHERE NIC = ? AND user_id != ?");
            $checkNIC->execute([trim($nicValue), $userId]);
            if ($checkNIC->fetchColumn()) {
                return ['status' => 'error', 'message' => 'NIC already exists.'];
            }
        }

        try {
            // Check if user exists before update
            $checkStmt = $this->conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
            $checkStmt->execute([$userId]);
            if (!$checkStmt->fetchColumn()) {
                return ['status' => 'error', 'message' => 'User not found.'];
            }
            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE user_id = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt->execute($params)) {
                return ['status' => 'error', 'message' => 'Failed to update customer profile.'];
            }
            // Always return success if user exists and query ran, even if no changes
            return ['status' => 'success', 'message' => 'Customer profile updated successfully.'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}
