<?php
/**
 * Customer.php
 *
 * Comprehensive customer management system for the Home Management System backend.
 * Extends the User class to provide customer-specific functionality and operations.
 *
 * PURPOSE:
 * ========
 * This class handles all customer-specific operations including:
 * - Customer profile management and updates
 * - Administrative customer data modifications
 * - Customer account status management
 * - Validation and security for customer data
 *
 * HOW IT WORKS:
 * =============
 * INHERITANCE STRUCTURE:
 * - Extends User class to inherit core user functionality
 * - Adds customer-specific methods and validation logic
 * - Maintains compatibility with base User operations
 * - Provides specialized customer data handling
 *
 * PROFILE UPDATE WORKFLOW:
 * 1. Validate required user_id parameter
 * 2. Process and sanitize input fields
 * 3. Check for unique constraints (email, NIC)
 * 4. Execute database update with prepared statements
 * 5. Return comprehensive status response
 *
 * SECURITY FEATURES:
 * - Input sanitization using htmlspecialchars
 * - Prepared statements for SQL injection prevention
 * - Unique constraint validation for email and NIC
 * - User existence verification before updates
 * - Admin-only access control for profile modifications
 *
 * IMPLEMENTATION STRATEGY:
 * ========================
 * - Dynamic field building based on provided data
 * - Comprehensive error handling and validation
 * - Support for partial updates (only provided fields)
 * - Graceful handling of database errors
 * - Detailed response messages for debugging
 *
 * Used by: admin_update_customer.php API endpoint, customer management interfaces
 */
require_once __DIR__ . '/User.php';

/**
 * Class Customer
 *
 * Manages customer-specific operations and profile management.
 * Extends User class to provide specialized customer functionality.
 *
 * CORE RESPONSIBILITIES:
 * ======================
 * - Customer profile updates and modifications
 * - Administrative customer account management
 * - Customer data validation and sanitization
 * - Unique constraint enforcement (email, NIC)
 * - Customer account status management
 *
 * METHODS OVERVIEW:
 * =================
 * - __construct($dbConn = null): Initialize with database connection
 * - updateProfile($data): Update customer profile with validation
 */
class Customer extends User {
    /**
     * Customer constructor.
     *
     * @param PDO|null $dbConn Optional PDO connection. If not provided, inherited constructor creates one.
     */
    public function __construct($dbConn = null) {
        parent::__construct($dbConn);
    }

    /**
     * Update customer profile with comprehensive validation and security measures.
     *
     * PURPOSE: Allow administrators to update customer profile information safely
     * WHY NEEDED: Admins need ability to modify customer data for support and management
     * HOW IT WORKS: Validates input, checks constraints, builds dynamic SQL, executes update
     * 
     * BUSINESS LOGIC:
     * - Only updates fields that are provided and non-empty
     * - Maintains data integrity with unique constraint validation
     * - Supports partial updates without affecting other fields
     * - Provides detailed feedback for success and error cases
     * 
     * SECURITY IMPLEMENTATION:
     * - HTML special character encoding to prevent XSS
     * - Prepared statements to prevent SQL injection
     * - Unique constraint validation for email and NIC
     * - User existence verification before update
     * - Input sanitization and validation
     * 
     * FIELD PROCESSING:
     * - name: Trimmed and HTML-encoded full name
     * - email: Lowercased, trimmed, and HTML-encoded
     * - phone_number: Trimmed and HTML-encoded contact number
     * - address: Trimmed and HTML-encoded physical address
     * - NIC/nic: Trimmed and HTML-encoded national ID (supports both cases)
     * - disable_status: Integer flag for account status
     * 
     * VALIDATION CHECKS:
     * 1. user_id is required and must be provided
     * 2. At least one field must be provided for update
     * 3. Email uniqueness check (if email being changed)
     * 4. NIC uniqueness check (if NIC being changed)
     * 5. User existence verification
     * 
     * ERROR HANDLING:
     * - Missing user_id: Returns error immediately
     * - No fields to update: Returns appropriate error message
     * - Duplicate email: Returns specific error about email conflict
     * - Duplicate NIC: Returns specific error about NIC conflict
     * - User not found: Returns user not found error
     * - Database errors: Returns database error with exception message
     * 
     * @param array $data Customer data fields (must include user_id)
     *                    Supported fields: name, email, phone_number, address, NIC/nic, disable_status
     * @return array Status response with success/error and descriptive message
     * 
     * USAGE CONTEXT: Called by admin_update_customer.php API endpoint
     */
    public function updateProfile($data) {
        // Extract and validate user_id
        $userId = $data['user_id'] ?? null;
        if (!$userId) {
            return ['status' => 'error', 'message' => 'User ID is required.'];
        }

        // Build list of allowed fields to update
        $fields = [];
        $params = [];
        // Only update fields that are present and non-empty in the input
        if (!empty($data['name'])) {
            $fields[] = 'name = ?';
            $params[] = htmlspecialchars(trim($data['name']), ENT_QUOTES, 'UTF-8');
        }
        if (!empty($data['email'])) {
            $fields[] = 'email = ?';
            $params[] = htmlspecialchars(strtolower(trim($data['email'])), ENT_QUOTES, 'UTF-8');
        }
        if (!empty($data['phone_number'])) {
            $fields[] = 'phone_number = ?';
            $params[] = htmlspecialchars(trim($data['phone_number']), ENT_QUOTES, 'UTF-8');
        }
        if (!empty($data['address'])) {
            $fields[] = 'address = ?';
            $params[] = htmlspecialchars(trim($data['address']), ENT_QUOTES, 'UTF-8');
        }
        // Support both 'NIC' or 'nic' as input
        $nicValue = $data['NIC'] ?? $data['nic'] ?? null;
        if (!empty($nicValue)) {
            $fields[] = 'NIC = ?';
            $params[] = htmlspecialchars(trim($nicValue), ENT_QUOTES, 'UTF-8');
        }
        // Allow admin to set disable_status
        if (isset($data['disable_status'])) {
            $fields[] = 'disable_status = ?';
            $params[] = (int)$data['disable_status'];
        }
        if (empty($fields)) {
            return ['status' => 'error', 'message' => 'No valid fields to update.'];
        }
        $params[] = $userId;

        // Check for unique email (if changed)
        if (!empty($data['email'])) {
            $checkEmail = $this->conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $checkEmail->execute([strtolower(trim($data['email'])), $userId]);
            if ($checkEmail->fetchColumn()) {
                return ['status' => 'error', 'message' => 'Email already exists.'];
            }
        }
        // Check for unique NIC (if changed)
        if (!empty($nicValue)) {
            $checkNIC = $this->conn->prepare("SELECT user_id FROM users WHERE NIC = ? AND user_id != ?");
            $checkNIC->execute([trim($nicValue), $userId]);
            if ($checkNIC->fetchColumn()) {
                return ['status' => 'error', 'message' => 'NIC already exists.'];
            }
        }

        try {
            // Ensure user exists before update
            $checkStmt = $this->conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
            $checkStmt->execute([$userId]);
            if (!$checkStmt->fetchColumn()) {
                return ['status' => 'error', 'message' => 'User not found.'];
            }
            // Prepare and execute the update statement
            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE user_id = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt->execute($params)) {
                return ['status' => 'error', 'message' => 'Failed to update customer profile.'];
            }
            // Always return success if user exists and query ran, even if no changes
            return ['status' => 'success', 'message' => 'Customer profile updated successfully.'];
        } catch (PDOException $e) {
            // Handle database errors gracefully
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}
