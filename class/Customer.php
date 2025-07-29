<?php
require_once __DIR__ . '/User.php';

class Customer extends User {
    public function __construct($dbConn = null) {
        parent::__construct($dbConn);
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
} 