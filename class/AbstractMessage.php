<?php
/**
 * AbstractMessage.php
 *
 * Defines the abstract base class for message handling in the Home Management System backend.
 *
 * Responsibilities:
 * - Provide a base structure for message operations (save, retrieve, error handling)
 * - Enforce implementation of core message methods in subclasses
 *
 * This class is extended by Message.php, which implements the actual logic.
 */

/**
 * Class AbstractMessage (abstract)
 *
 * Provides base properties and enforces required methods for message handling.
 *
 * Properties:
 * - protected $conn: PDO database connection
 * - protected $table: Table name (default 'message')
 *
 * Methods:
 * - __construct($db)
 * - saveMessage($name, $email, $phone_number, $subject, $message) [abstract]
 * - getAllMessages($page = 1, $limit = 10) [abstract]
 * - getLastPdoError()
 */
abstract class AbstractMessage {
    /**
     * @var PDO $conn Database connection
     */
    protected $conn;
    /**
     * @var string $table Table name for messages
     */
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
