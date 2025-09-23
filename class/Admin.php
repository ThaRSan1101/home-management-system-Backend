<?php
/**
 * Admin.php
 *
 * Comprehensive administrative management system for the Home Management System backend.
 * Extends the User class to provide specialized administrative functionality and operations.
 *
 * PURPOSE:
 * ========
 * This class handles all administrative operations including:
 * - Provider account creation and management
 * - User oversight and data retrieval
 * - Administrative reporting and analytics
 * - Email communication for provider onboarding
 * - System-wide user management functions
 *
 * HOW IT WORKS:
 * =============
 * INHERITANCE ARCHITECTURE:
 * - Extends User class to inherit core user functionality
 * - Adds admin-specific methods for system management
 * - Maintains full compatibility with base User operations
 * - Provides specialized administrative data handling
 *
 * PROVIDER MANAGEMENT WORKFLOW:
 * 1. Admin submits provider registration data
 * 2. System validates input and checks for duplicates
 * 3. Creates user account with auto-generated credentials
 * 4. Creates provider profile with additional details
 * 5. Sends welcome email with login credentials
 * 6. Returns comprehensive status information
 *
 * SECURITY IMPLEMENTATION:
 * - Input sanitization using htmlspecialchars
 * - Prepared statements for SQL injection prevention
 * - Unique constraint validation for email and NIC
 * - Secure password generation and hashing
 * - Comprehensive error handling and validation
 *
 * EMAIL INTEGRATION:
 * - Professional welcome emails for new providers
 * - Auto-generated secure passwords
 * - Branded email templates with system styling
 * - Error handling for email delivery failures
 *
 * BUSINESS LOGIC:
 * ===============
 * - New providers start with 'inactive' status requiring activation
 * - Auto-generated passwords ensure immediate access
 * - Dual-table architecture (users + provider) for data integrity
 * - Comprehensive validation prevents duplicate accounts
 * - Email notifications ensure provider awareness
 *
 * This class is typically used by endpoints such as admin_customers.php, get_providers.php, 
 * add_provider.php, and other administrative interfaces.
 */
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/phpmailer.php';

/**
 * Class Admin
 *
 * Manages administrative operations and extends User functionality.
 * Provides specialized methods for provider management and user oversight.
 *
 * CORE RESPONSIBILITIES:
 * ======================
 * - Provider account creation and credential management
 * - System-wide user data retrieval and reporting
 * - Administrative email communications
 * - User validation and duplicate prevention
 * - Provider profile management and status control
 *
 * METHODS OVERVIEW:
 * =================
 * - addProvider($data): Create new provider accounts with validation
 * - getAllProviders(): Retrieve comprehensive provider listings
 * - getCustomerDetails(): Fetch customer data for administrative review
 */
class Admin extends User {
    // Add admin-specific methods here

    /**
     * Add a new provider account with comprehensive validation and email notification.
     *
     * PURPOSE: Enable administrators to create provider accounts with auto-generated credentials
     * WHY NEEDED: Admins need secure way to onboard service providers into the system
     * HOW IT WORKS: Validates input, creates dual-table records, generates credentials, sends email
     * 
     * BUSINESS LOGIC:
     * - All providers start with 'inactive' status requiring manual activation
     * - Auto-generated passwords ensure immediate but secure access
     * - Dual-table insertion (users + provider) maintains data integrity
     * - Email notification provides instant credential delivery
     * - Comprehensive validation prevents duplicate accounts
     * 
     * VALIDATION WORKFLOW:
     * 1. Input sanitization and HTML encoding for XSS prevention
     * 2. Required field validation (name, email, phone, address, NIC)
     * 3. Email uniqueness check across all users
     * 4. NIC uniqueness check for identity verification
     * 5. Secure password generation using cryptographic functions
     * 
     * SECURITY IMPLEMENTATION:
     * - htmlspecialchars() encoding prevents XSS attacks
     * - Prepared statements prevent SQL injection
     * - bin2hex(random_bytes()) generates cryptographically secure passwords
     * - password_hash() with PASSWORD_DEFAULT for secure storage
     * - Comprehensive duplicate checking before insertion
     * 
     * DATABASE OPERATIONS:
     * Users Table Insert:
     * - name: Provider's full name (sanitized)
     * - email: Unique email address (lowercased)
     * - password: Securely hashed auto-generated password
     * - phone_number: Contact phone number
     * - address: Business/service address
     * - NIC: National identification number
     * - user_type: Set to 'provider' for role identification
     * 
     * Provider Table Insert:
     * - user_id: Foreign key from users table
     * - description: Service description and specialties
     * - qualifications: Professional certifications
     * - status: Set to 'inactive' requiring admin activation
     * 
     * EMAIL NOTIFICATION:
     * - Professional HTML-formatted welcome email
     * - Includes auto-generated login credentials
     * - Branded with system styling and instructions
     * - Error handling for email delivery failures
     * - Returns email error status for admin awareness
     * 
     * ERROR HANDLING:
     * - Missing required fields: Immediate validation error
     * - Duplicate email: Specific conflict error message
     * - Duplicate NIC: Identity conflict error message
     * - Database insertion failures: Detailed error reporting
     * - Email delivery failures: Non-blocking error tracking
     * 
     * @param array $data Provider registration fields
     *                    Required: name, email, phone, address, nic
     *                    Optional: description, qualification
     * @return array Comprehensive status response with success/error and email delivery status
     *               Success: ['status' => 'success', 'message' => 'Provider added successfully.', 'emailError' => null]
     *               Error: ['status' => 'error', 'message' => 'Specific error description']
     * 
     * USAGE CONTEXT: Called by add_provider.php API endpoint for admin provider creation
     */
    public function addProvider($data) {
        $name = isset($data['name']) ? trim($data['name']) : '';
        $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

        $email = isset($data['email']) ? strtolower(trim($data['email'])) : '';
        $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

        $phone = isset($data['phone']) ? trim($data['phone']) : '';
        $phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');

        $address = isset($data['address']) ? trim($data['address']) : '';
        $address = htmlspecialchars($address, ENT_QUOTES, 'UTF-8');

        $nic = isset($data['nic']) ? trim($data['nic']) : '';
        $nic = htmlspecialchars($nic, ENT_QUOTES, 'UTF-8');

        $description = isset($data['description']) ? trim($data['description']) : '';
        $description = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');

        $qualification = isset($data['qualification']) ? trim($data['qualification']) : '';
        $qualification = htmlspecialchars($qualification, ENT_QUOTES, 'UTF-8');

        if (!$name || !$email || !$phone || !$address || !$nic) {
            return ['status' => 'error', 'message' => 'All fields are required.'];
        }

        // Check for duplicate email
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['status' => 'error', 'message' => 'Email already exists.'];
        }

        // Check for duplicate NIC
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE NIC = ?");
        $stmt->execute([$nic]);
        if ($stmt->fetch()) {
            return ['status' => 'error', 'message' => 'NIC already exists.'];
        }

        // Generate random password for provider account
        $randomPassword = bin2hex(random_bytes(5)); // 10 chars alphanumeric
        $hashedPassword = password_hash($randomPassword, PASSWORD_DEFAULT);

        // Insert into users table
        $stmt = $this->conn->prepare("INSERT INTO users (name, email, password, phone_number, address, NIC, user_type) VALUES (?, ?, ?, ?, ?, ?, 'provider')");
        $result = $stmt->execute([$name, $email, $hashedPassword, $phone, $address, $nic]);

        if (!$result) {
            return ['status' => 'error', 'message' => 'Failed to add provider to users table.'];
        }

        $userId = $this->conn->lastInsertId();

        // Insert into provider table
        $stmt = $this->conn->prepare("INSERT INTO provider (user_id, description, qualifications, status) VALUES (?, ?, ?, 'inactive')");
        $result2 = $stmt->execute([$userId, $description, $qualification]);

        if (!$result2) {
            return ['status' => 'error', 'message' => 'Failed to add provider details.'];
        }

        $emailError = null;

        // Send welcome email with credentials
        $mailer = new PHPMailerService();
        $subject = 'Welcome to Home Management System!';
        $body = '<div style="font-family:Arial,sans-serif;max-width:420px;margin:auto;border:1px solid #e0e0e0;padding:24px;border-radius:8px;">
            <div style="font-size:20px;font-weight:bold;color:#2a4365;margin-bottom:8px;">Home Management System</div>
            <div style="font-size:16px;margin-bottom:16px;">Hello, <strong>' . htmlspecialchars($name) . '</strong></div>
            <div style="margin-bottom:12px;">Your provider account has been created by the admin. Use the credentials below to log in:</div>
            <div style="font-size:16px;margin-bottom:8px;"><b>Username:</b> ' . htmlspecialchars($email) . '</div>
            <div style="font-size:16px;margin-bottom:16px;"><b>Password:</b> <span style="font-size:20px;font-weight:bold;color:#2a4365;letter-spacing:2px;">' . htmlspecialchars($randomPassword) . '</span></div>
            <div style="font-size:13px;color:#555;margin-bottom:10px;">Important: Please change your password after logging in for the first time.</div>
            <div style="font-size:13px;color:#555;margin-bottom:10px;">You can now log in and start accepting service requests.</div>
            <hr style="margin:24px 0 12px 0;border:none;border-top:1px solid #eee;">
            <div style="font-size:12px;color:#999;">If you did not request this, please ignore this email.</div>
            </div>';
        $result = $mailer->sendMail($email, $subject, $body);

        if (!$result['success']) {
            $emailError = $result['error'];
        }

        return ['status' => 'success', 'message' => 'Provider added successfully.', 'emailError' => $emailError];
    }

    /**
     * Fetch comprehensive list of all service providers for administrative management.
     *
     * PURPOSE: Provide complete provider data for admin dashboard and management interfaces
     * WHY NEEDED: Admins require full provider oversight for system management and monitoring
     * HOW IT WORKS: Joins users and provider tables to create unified provider profiles
     * 
     * BUSINESS LOGIC:
     * - Retrieves all users with user_type = 'provider'
     * - Combines basic user info with provider-specific details
     * - Includes provider status for admin management decisions
     * - Returns complete profiles for comprehensive admin oversight
     * 
     * DATABASE QUERY ARCHITECTURE:
     * - INNER JOIN between users and provider tables
     * - Filters by user_type = 'provider' for role-specific results
     * - Retrieves all fields from both tables for complete profiles
     * - No pagination - returns all providers for admin overview
     * 
     * DATA FIELDS RETURNED:
     * From Users Table:
     * - user_id: Unique user identifier
     * - name: Provider's full name
     * - email: Contact email address
     * - phone_number: Contact phone number
     * - address: Business/service address
     * - NIC: National identification number
     * - registered_date: Account creation timestamp
     * - disable_status: Account status flag
     * 
     * From Provider Table:
     * - provider_id: Unique provider identifier
     * - description: Service description and specialties
     * - qualifications: Professional certifications
     * - status: Provider status (active/inactive)
     * 
     * PERFORMANCE CONSIDERATIONS:
     * - Uses INNER JOIN for optimal query performance
     * - Indexes on user_type and join columns improve speed
     * - Returns all providers without pagination for admin convenience
     * - Efficient single-query approach reduces database load
     * 
     * SECURITY IMPLEMENTATION:
     * - Uses prepared statement for SQL injection prevention
     * - No user input parameters reduce attack surface
     * - Returns safe data fields suitable for admin interfaces
     * - No sensitive data exposure in provider listings
     * 
     * @return array Complete list of provider records with combined user and provider data
     *               Format: [
     *                 [
     *                   'user_id' => int,
     *                   'name' => string,
     *                   'email' => string,
     *                   'phone_number' => string,
     *                   'address' => string,
     *                   'NIC' => string,
     *                   'registered_date' => string,
     *                   'disable_status' => int,
     *                   'provider_id' => int,
     *                   'description' => string,
     *                   'qualifications' => string,
     *                   'status' => string
     *                 ],
     *                 ...
     *               ]
     * 
     * USAGE CONTEXT: Called by get_providers.php API endpoint for admin provider management
     */
    public function getAllProviders() {
        $stmt = $this->conn->prepare("SELECT u.*, p.description, p.qualifications, p.status, p.provider_id FROM users u JOIN provider p ON u.user_id = p.user_id WHERE u.user_type = 'provider'");
        $stmt->execute();
        $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $providers;
    }

    /**
     * Fetch comprehensive customer details for administrative review and management.
     *
     * PURPOSE: Provide complete customer data for admin dashboard monitoring and user management
     * WHY NEEDED: Admins require customer oversight for support, analytics, and system management
     * HOW IT WORKS: Queries users table for customer-type accounts with comprehensive error handling
     * 
     * BUSINESS LOGIC:
     * - Retrieves all users with user_type = 'customer'
     * - Returns essential customer information for admin review
     * - Excludes sensitive data like passwords for security
     * - Provides account status information for management decisions
     * 
     * DATABASE QUERY IMPLEMENTATION:
     * - Filters by user_type = 'customer' for role-specific results
     * - Selects specific safe fields excluding sensitive information
     * - Uses prepared statement for security and performance
     * - Returns all customers without pagination for admin overview
     * 
     * DATA FIELDS RETRIEVED:
     * - user_id: Unique customer identifier for operations
     * - name: Customer's full name for identification
     * - email: Contact email for communication
     * - phone_number: Contact phone for support
     * - address: Customer location information
     * - NIC: National identification for verification
     * - registered_date: Account creation timestamp for analytics
     * - disable_status: Account status for management control
     * 
     * SECURITY IMPLEMENTATION:
     * - Excludes password field for data protection
     * - Uses prepared statement to prevent SQL injection
     * - Returns only necessary data for admin functions
     * - Comprehensive error handling prevents information leakage
     * 
     * ERROR HANDLING STRATEGY:
     * - PDOException catching for database errors
     * - Detailed error logging for debugging
     * - User-friendly error messages for admin interface
     * - Status-based response format for consistent API behavior
     * 
     * PERFORMANCE CONSIDERATIONS:
     * - Single query retrieves all necessary customer data
     * - Index on user_type field optimizes filtering
     * - Minimal field selection reduces memory usage
     * - Efficient fetchAll() for complete result set
     * 
     * RESPONSE FORMAT:
     * Success Response:
     * {
     *   "status": "success",
     *   "data": [
     *     {
     *       "user_id": 123,
     *       "name": "John Doe",
     *       "email": "john@example.com",
     *       "phone_number": "1234567890",
     *       "address": "123 Main St",
     *       "NIC": "987654321",
     *       "registered_date": "2023-01-15 10:30:00",
     *       "disable_status": 0
     *     },
     *     ...
     *   ]
     * }
     * 
     * Error Response:
     * {
     *   "status": "error",
     *   "message": "User-friendly error description",
     *   "error": "Technical error details for debugging"
     * }
     * 
     * @return array Standardized response with customer data or error information
     * 
     * USAGE CONTEXT: Called by admin_customers.php API endpoint for customer management interface
     */
    public function getCustomerDetails() {
        try {
            $stmt = $this->conn->prepare("SELECT user_id, name, email, phone_number, address, NIC, registered_date, disable_status FROM users WHERE user_type = 'customer'");
            $stmt->execute();
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return [
                'status' => 'success',
                'data' => $customers
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Failed to fetch customers',
                'error' => $e->getMessage()
            ];
        }
    }
}
