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

    // Update profile method
    public function updateProfile($data) {
        if (!isset($data['user_id'])) {
            return ['status' => 'error', 'message' => 'User ID is required.'];
        }
        $userId = $data['user_id'];
        $email = isset($data['email']) ? strtolower(trim($data['email'])) : '';
        $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $nic = isset($data['nic']) ? trim($data['nic']) : '';
        $nic = htmlspecialchars($nic, ENT_QUOTES, 'UTF-8');
        // Check for duplicate email (exclude current user)
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            return ['status' => 'error', 'message' => 'Email already exists'];
        }
        // Check for duplicate NIC (exclude current user)
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE NIC = ? AND user_id != ?");
        $stmt->execute([$nic, $userId]);
        if ($stmt->fetch()) {
            return ['status' => 'error', 'message' => 'NIC already exists'];
        }
        $name = isset($data['name']) ? trim($data['name']) : '';
        $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $this->setName($name);
        $this->setEmail($email);
        $phone = isset($data['phone_number']) ? trim($data['phone_number']) : '';
        $phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
        $this->setPhoneNumber($phone);
        $address = isset($data['address']) ? trim($data['address']) : '';
        $address = htmlspecialchars($address, ENT_QUOTES, 'UTF-8');
        $this->setAddress($address);
        $this->setNIC($nic);
        $disable_status = isset($data['disable_status']) ? (int)$data['disable_status'] : 0;
        $stmt = $this->conn->prepare("UPDATE users SET name = ?, email = ?, phone_number = ?, address = ?, NIC = ?, disable_status = ? WHERE user_id = ? AND user_type = 'provider'");
        $result = $stmt->execute([
            $this->getName(),
            $this->getEmail(),
            $this->getPhoneNumber(),
            $this->getAddress(),
            $this->getNIC(),
            $disable_status,
            $data['user_id']
        ]);
        // Update provider table fields if present
        $providerResult = true;
        if (isset($data['provider_id'])) {
            $description = isset($data['description']) ? trim($data['description']) : '';
            $description = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
            $qualifications = isset($data['qualifications']) ? trim($data['qualifications']) : '';
            $qualifications = htmlspecialchars($qualifications, ENT_QUOTES, 'UTF-8');
            $status = $data['status'] ?? 'inactive';
            $stmt2 = $this->conn->prepare("UPDATE provider SET description = ?, qualifications = ?, status = ? WHERE provider_id = ?");
            $providerResult = $stmt2->execute([
                $description,
                $qualifications,
                $status,
                $data['provider_id']
            ]);
        }
        if ($result && $providerResult) {
            return ['status' => 'success', 'message' => 'Profile updated successfully.'];
        } else {
            return ['status' => 'error', 'message' => 'Failed to update profile.'];
        }
    }
} 