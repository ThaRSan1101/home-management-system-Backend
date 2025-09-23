<?php
/**
 * AbstractMessage.php
 *
 * Defines the abstract base class for message handling in the Home Management System backend.
 * Implements the Template Method pattern to enforce consistent message operations.
 *
 * PURPOSE:
 * ========
 * This abstract class provides:
 * - Standardized interface for message operations across different implementations
 * - Common properties and base functionality for message handling
 * - Enforcement of required methods through abstract declarations
 * - Foundation for extensible message system architecture
 *
 * HOW IT WORKS:
 * =============
 * TEMPLATE METHOD PATTERN:
 * - Defines common structure and properties for all message classes
 * - Forces implementation of core methods in concrete subclasses
 * - Provides default implementations for shared functionality
 * - Ensures consistent behavior across different message types
 *
 * INHERITANCE STRUCTURE:
 * - Abstract base class with core properties and method signatures
 * - Concrete subclasses implement specific message handling logic
 * - Shared database connection and table management
 * - Common error handling patterns
 *
 * ARCHITECTURAL BENEFITS:
 * - Code reuse through shared properties and methods
 * - Consistent interface for all message operations
 * - Enforced implementation of critical methods
 * - Easy extension for new message types
 * - Centralized configuration management
 *
 * EXTENSIBILITY:
 * - New message types can extend this class
 * - Abstract methods ensure required functionality
 * - Shared properties reduce code duplication
 * - Common error handling approach
 *
 * This class is extended by Message.php, which implements the actual contact message logic.
 */

/**
 * Class AbstractMessage (abstract)
 *
 * Provides base properties and enforces required methods for message handling.
 * Implements Template Method pattern for consistent message operations.
 *
 * CORE RESPONSIBILITIES:
 * ======================
 * - Database connection management for message operations
 * - Table name configuration for different message types
 * - Method signature enforcement through abstract declarations
 * - Common error handling patterns and utilities
 * - Base constructor for database initialization
 *
 * ABSTRACT METHOD CONTRACTS:
 * ==========================
 * - saveMessage(): Must implement message storage logic
 * - getAllMessages(): Must implement message retrieval with pagination
 * - getLastPdoError(): Provides error information for debugging
 *
 * PROPERTIES:
 * ===========
 * - protected $conn: PDO database connection shared by all message operations
 * - protected $table: Table name for message storage (configurable in subclasses)
 *
 * DESIGN PATTERNS:
 * ================
 * - Template Method: Defines algorithm structure, subclasses implement details
 * - Dependency Injection: Accepts database connection through constructor
 * - Strategy Pattern: Different message types can implement different strategies
 */
abstract class AbstractMessage {
    /**
     * @var PDO $conn Database connection for all message operations
     *                 Shared across all methods to maintain connection efficiency
     */
    protected $conn;
    
    /**
     * @var string $table Default table name for message storage
     *                    Can be overridden in subclasses for different message types
     */
    protected $table = 'message';

    /**
     * AbstractMessage constructor.
     *
     * PURPOSE: Initialize the abstract message handler with database connection
     * WHY NEEDED: All message operations require database access for storage and retrieval
     * HOW IT WORKS: Stores provided database connection for use by all message methods
     * 
     * DEPENDENCY INJECTION:
     * - Accepts PDO connection as parameter for flexibility
     * - Enables testing with mock database connections
     * - Supports connection reuse across multiple message operations
     * - Facilitates transaction management across message operations
     * 
     * @param PDO $db Active PDO database connection for message operations
     * 
     * USAGE CONTEXT: Called by concrete message class constructors
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Save a new message to the database.
     * 
     * PURPOSE: Enforce implementation of message storage functionality in subclasses
     * WHY ABSTRACT: Different message types may require different validation and processing
     * HOW TO IMPLEMENT: Subclasses must provide complete message saving logic
     * 
     * IMPLEMENTATION REQUIREMENTS:
     * - Validate all input parameters for completeness and security
     * - Sanitize input data to prevent XSS and injection attacks
     * - Execute database insert with proper error handling
     * - Return boolean success/failure status
     * 
     * @param string $name Sender's full name
     * @param string $email Sender's email address
     * @param string $phone_number Sender's contact phone number
     * @param string $subject Message subject line
     * @param string $message Detailed message content
     * @return bool True if message saved successfully, false on failure
     * 
     * SUBCLASS RESPONSIBILITY: Implement complete message storage logic with validation
     */
    abstract public function saveMessage($name, $email, $phone_number, $subject, $message);

    /**
     * Retrieve all messages with pagination support.
     * 
     * PURPOSE: Enforce implementation of message retrieval functionality in subclasses
     * WHY ABSTRACT: Different message types may require different sorting and filtering
     * HOW TO IMPLEMENT: Subclasses must provide paginated message retrieval
     * 
     * IMPLEMENTATION REQUIREMENTS:
     * - Support pagination with page and limit parameters
     * - Return messages in appropriate order (usually newest first)
     * - Handle database errors gracefully
     * - Return array of message records
     * 
     * @param int $page Page number for pagination (1-based)
     * @param int $limit Number of messages per page
     * @return array Array of message records from database
     * 
     * SUBCLASS RESPONSIBILITY: Implement efficient paginated message retrieval
     */
    abstract public function getAllMessages($page = 1, $limit = 10);

    /**
     * Retrieve the last PDO error information for debugging purposes.
     * 
     * PURPOSE: Provide consistent error reporting across all message implementations
     * WHY CONCRETE: Error handling pattern is consistent across message types
     * HOW IT WORKS: Returns stored error information or null if no error occurred
     * 
     * ERROR INFORMATION:
     * - PDO error codes and messages for database operations
     * - Useful for debugging failed message operations
     * - Can be null if no error occurred or not implemented
     * - Enables detailed error logging and monitoring
     * 
     * @return mixed|null PDO error information array or null if no error
     * 
     * USAGE CONTEXT: Called after message operations for error checking and logging
     */
    public function getLastPdoError() {
        return $this->lastPdoError ?? null;
    }
}
