<?php

abstract class AbstractMessage {
    protected $conn;
    protected $table = 'message';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Save a new message.
     * @param string $name
     * @param string $email
     * @param string $phone_number
     * @param string $subject
     * @param string $message
     * @return bool
     */
    abstract public function saveMessage($name, $email, $phone_number, $subject, $message);

    /**
     * Get all messages with pagination.
     * @param int $page
     * @param int $limit
     * @return array
     */
    abstract public function getAllMessages($page = 1, $limit = 10);

    /**
     * Optionally, get last PDO error (can be concrete, as it's generic).
     */
    public function getLastPdoError() {
        return $this->lastPdoError ?? null;
    }
}
