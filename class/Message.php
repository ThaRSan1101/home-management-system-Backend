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
     * Save a new contact message to the database with comprehensive data processing.
     *
     * PURPOSE: Securely store contact form submissions from website visitors
     * WHY NEEDED: Provides communication channel between users and administrators
     * HOW IT WORKS: Sanitizes input, validates data, and stores with timestamp
     * 
     * BUSINESS LOGIC:
     * - Accepts messages from both logged-in users and anonymous visitors
     * - Automatically timestamps all messages with server time
     * - Sanitizes all input to prevent security vulnerabilities
     * - Stores comprehensive contact information for follow-up
     * 
     * SECURITY IMPLEMENTATION:
     * - HTML special character encoding prevents XSS attacks
     * - Input trimming removes accidental whitespace
     * - Email normalization with lowercase conversion
     * - Prepared statements prevent SQL injection
     * - Error tracking for debugging and monitoring
     * 
     * DATA PROCESSING:
     * - name: Trimmed and HTML-encoded sender name
     * - email: Lowercased, trimmed, and HTML-encoded for consistency
     * - phone_number: Trimmed and HTML-encoded contact number
     * - subject: Trimmed and HTML-encoded message subject
     * - message: Trimmed and HTML-encoded message body
     * - date: Automatically set to current timestamp (NOW())
     * 
     * ERROR HANDLING:
     * - Captures PDO error information for debugging
     * - Stores error details in lastPdoError property
     * - Returns boolean for simple success/failure checking
     * - Enables detailed error analysis through getLastPdoError()
     * 
     * DATABASE INTEGRATION:
     * - Uses inherited table property for flexibility
     * - Prepared statement execution for security
     * - Automatic timestamp generation
     * - Error information preservation
     * 
     * @param string $name Sender's full name
     * @param string $email Sender's email address for response
     * @param string $phone_number Sender's contact phone number
     * @param string $subject Brief subject line for the message
     * @param string $message Detailed message content
     * @return bool True if message saved successfully, false on failure
     * 
     * USAGE CONTEXT: Called by contact_us.php API endpoint for form submissions
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
     * Retrieve all contact messages with pagination support for admin panel.
     *
     * PURPOSE: Provide paginated access to contact messages for administrative review
     * WHY NEEDED: Admin panel requires organized message display with navigation
     * HOW IT WORKS: Uses LIMIT/OFFSET SQL pattern for efficient pagination
     * 
     * BUSINESS LOGIC:
     * - Returns messages in reverse chronological order (newest first)
     * - Supports configurable page size for different display needs
     * - Uses OFFSET calculation for proper page navigation
     * - Retrieves all message fields for complete display
     * 
     * PAGINATION IMPLEMENTATION:
     * - Page parameter: 1-based page numbering (user-friendly)
     * - Limit parameter: Number of records per page
     * - Offset calculation: (page - 1) * limit for 0-based database indexing
     * - ORDER BY date DESC: Newest messages appear first
     * 
     * PERFORMANCE CONSIDERATIONS:
     * - Uses LIMIT clause to prevent large result sets
     * - Prepared statement with bound parameters for efficiency
     * - Optimized query structure for fast execution
     * - Supports indexes on date column for sorting
     * 
     * DATABASE INTEGRATION:
     * - Uses inherited table property for flexibility
     * - PDO::PARAM_INT binding for proper integer handling
     * - FETCH_ASSOC for associative array results
     * - Error handling inherits from parent database connection
     * 
     * PARAMETER BINDING:
     * - limit: Bound as integer to prevent type issues
     * - offset: Bound as integer for calculation accuracy
     * - Prevents potential SQL injection through proper binding
     * 
     * @param int $page Page number starting from 1 (default: 1)
     * @param int $limit Number of messages per page (default: 10)
     * @return array Array of message records with all fields
     * 
     * USAGE CONTEXT: Called by admin_messages.php API endpoint for paginated display
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
