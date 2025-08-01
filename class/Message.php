<?php
/**
 * Message.php
 *
 * Defines the Message class for handling Contact Us messages in the Home Management System backend.
 *
 * Responsibilities:
 * - Save contact form submissions to the database (message table)
 * - Retrieve messages with pagination for the admin panel
 * - Provide error feedback for database operations
 *
 * Used by API endpoints such as contact_us.php and admin_messages.php.
 *
 * Depends on AbstractMessage for base structure and PDO connection.
 */
require_once __DIR__ . '/AbstractMessage.php';

/**
 * Class Message
 *
 * Implements message saving and retrieval logic for contact messages.
 *
 * Properties:
 * - protected $lastPdoError: Stores last PDO error for diagnostics
 *
 * Methods:
 * - __construct($db)
 * - saveMessage($name, $email, $phone_number, $subject, $message)
 * - getLastPdoError()
 * - getAllMessages($page = 1, $limit = 10)
 */
class Message extends AbstractMessage {
    /**
     * @var mixed $lastPdoError Last PDO error info for diagnostics
     */
    protected $lastPdoError = null;

    /**
     * Message constructor.
     *
     * @param PDO $db PDO database connection
     *
     * Sets up the database connection for message operations.
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Save a new contact message to the database.
     *
     * @param string $name Sender's name
     * @param string $email Sender's email
     * @param string $phone_number Sender's phone number
     * @param string $subject Message subject
     * @param string $message Message body
     * @return bool True on success, false on failure
     *
     * Used by contact_us.php API endpoint. Sanitizes all input before saving.
     */
    public function saveMessage($name, $email, $phone_number, $subject, $message) {
        // Sanitize all input to prevent XSS and ensure data integrity
        $name = htmlspecialchars(trim($name), ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars(strtolower(trim($email)), ENT_QUOTES, 'UTF-8');
        $phone_number = htmlspecialchars(trim($phone_number), ENT_QUOTES, 'UTF-8');
        $subject = htmlspecialchars(trim($subject), ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars(trim($message), ENT_QUOTES, 'UTF-8');

        // Prepare and execute insert query
        $query = "INSERT INTO {$this->table} (name, email, phone_number, subject, message, date) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([$name, $email, $phone_number, $subject, $message]);

        // Store error info for diagnostics
        if (!$result) {
            $this->lastPdoError = $stmt->errorInfo();
        } else {
            $this->lastPdoError = null;
        }

        return $result;
    }

    /**
     * Retrieve the last PDO error information.
     *
     * @return mixed|null Error info array or null if no error
     *
     * Useful for debugging failed database operations.
     */
    public function getLastPdoError() {
        return $this->lastPdoError;
    }

    /**
     * Retrieve all messages with pagination for admin panel.
     *
     * @param int $page Page number (default 1)
     * @param int $limit Number of messages per page (default 10)
     * @return array List of messages
     *
     * Used by admin_messages.php API endpoint.
     */
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
