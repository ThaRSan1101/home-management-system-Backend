<?php
class Message {
    private $conn;
    private $table = 'message';
    private $lastPdoError = null;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Save a new message
    public function saveMessage($name, $email, $phone_number, $subject, $message) {
        $name = htmlspecialchars(trim($name), ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars(strtolower(trim($email)), ENT_QUOTES, 'UTF-8');
        $phone_number = htmlspecialchars(trim($phone_number), ENT_QUOTES, 'UTF-8');
        $subject = htmlspecialchars(trim($subject), ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars(trim($message), ENT_QUOTES, 'UTF-8');

        $query = "INSERT INTO {$this->table} (name, email, phone_number, subject, message, date) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([$name, $email, $phone_number, $subject, $message]);

        if (!$result) {
            $this->lastPdoError = $stmt->errorInfo();
        } else {
            $this->lastPdoError = null;
        }

        return $result;
    }

    public function getLastPdoError() {
        return $this->lastPdoError;
    }

    // Get all messages with pagination
    public function getAllMessages($page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $query = "SELECT * FROM {$this->table} ORDER BY date DESC LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
