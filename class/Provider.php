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
        $this->setName($data['name'] ?? '');
        $this->setEmail($data['email'] ?? '');
        $this->setPhoneNumber($data['phone_number'] ?? '');
        $this->setAddress($data['address'] ?? '');
        $this->setNIC($data['nic'] ?? '');
        $stmt = $this->conn->prepare("UPDATE users SET name = ?, email = ?, phone_number = ?, address = ?, NIC = ? WHERE user_id = ? AND user_type = 'provider'");
        $result = $stmt->execute([
            $this->getName(),
            $this->getEmail(),
            $this->getPhoneNumber(),
            $this->getAddress(),
            $this->getNIC(),
            $data['user_id']
        ]);
        if ($result) {
            return ['status' => 'success', 'message' => 'Profile updated successfully.'];
        } else {
            return ['status' => 'error', 'message' => 'Failed to update profile.'];
        }
    }
} 