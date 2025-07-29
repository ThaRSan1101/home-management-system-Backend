<?php
require_once __DIR__ . '/User.php';

class Provider extends User {
    public function __construct($dbConn = null) {
        parent::__construct($dbConn);
    }
    // Add provider-specific methods here
    public function getProviderServices() {
        // Example: return services offered by this provider
    }

    // Getter and Setter methods
    public function getName() { return $this->name; }
    public function setName($name) { $this->name = $name; }
    public function getEmail() { return $this->email; }
    public function setEmail($email) { $this->email = $email; }
    public function getPhoneNumber() { return $this->phone_number; }
    public function setPhoneNumber($phone) { $this->phone_number = $phone; }
    public function getAddress() { return $this->address; }
    public function setAddress($address) { $this->address = $address; }
    public function getNIC() { return $this->NIC; }
    public function setNIC($nic) { $this->NIC = $nic; }
    /**
     * Update provider profile (admin action)
     * @param array $data
     * @return array
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
}