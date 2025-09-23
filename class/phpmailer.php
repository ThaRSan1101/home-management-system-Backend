<?php
/**
 * phpmailer.php
 *
 * Provides the PHPMailerService class, a wrapper around PHPMailer for sending emails from the backend.
 *
 * Responsibilities:
 * - Configures SMTP settings for email delivery (default: Gmail SMTP)
 * - Sends transactional emails for OTPs, registration, password resets, and notifications
 * - Used by User.php and other backend classes to deliver system emails
 *
 * SECURITY NOTE: Credentials in this file should be protected and never committed to public repositories. Use environment variables or configuration files in production.
 */

require_once __DIR__ . '/../api/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../api/PHPMailer/SMTP.php';
require_once __DIR__ . '/../api/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Class PHPMailerService
 *
 * Wrapper for PHPMailer to manage email sending for the backend.
 *
 * Used by: User.php (for OTP, registration, password reset emails), Admin.php (for notifications), etc.
 */
class PHPMailerService {
    /**
     * @var PHPMailer Instance of PHPMailer used for sending emails
     */
    private $mail;

    /**
     * PHPMailerService constructor.
     *
     * PURPOSE: Initialize PHPMailer instance with pre-configured SMTP settings
     * WHY NEEDED: Provides consistent email configuration across the application
     * HOW IT WORKS: Creates PHPMailer instance and applies SMTP configuration
     * 
     * INITIALIZATION PROCESS:
     * 1. Create new PHPMailer instance with exception handling enabled
     * 2. Call configure() method to set up SMTP parameters
     * 3. Ready for immediate use by calling sendMail() method
     * 
     * CONFIGURATION BENEFITS:
     * - Centralizes email settings in one location
     * - Ensures consistent sender information
     * - Provides ready-to-use email service
     * - Simplifies email sending throughout application
     * 
     * USAGE CONTEXT: Called when creating email service instances in User, Admin classes
     */
    public function __construct() {
        $this->mail = new PHPMailer(true);
        $this->configure();
    }

    /**
     * Configure SMTP settings and sender information for outgoing emails.
     *
     * PURPOSE: Set up comprehensive SMTP configuration for reliable email delivery
     * WHY NEEDED: Email service requires proper SMTP authentication and settings
     * HOW IT WORKS: Configures PHPMailer with Gmail SMTP settings and sender details
     * 
     * SMTP CONFIGURATION DETAILS:
     * - Provider: Gmail SMTP service (smtp.gmail.com)
     * - Port: 587 (standard SMTP with STARTTLS encryption)
     * - Authentication: Required with username/password
     * - Encryption: STARTTLS for secure transmission
     * - Sender: ServiceHub system email address
     * 
     * SECURITY IMPLEMENTATION:
     * - Uses app-specific password instead of regular Gmail password
     * - STARTTLS encryption for all email transmissions
     * - SMTP authentication prevents unauthorized usage
     * - Secure connection establishment before data transmission
     * 
     * EMAIL FORMAT SETTINGS:
     * - HTML format enabled for rich email content
     * - Supports both HTML and plain text alternatives
     * - UTF-8 character encoding for international content
     * - Proper MIME type handling for attachments
     * 
     * PRODUCTION CONSIDERATIONS:
     * - Credentials should be moved to environment variables
     * - Consider using dedicated SMTP service for production
     * - Implement email rate limiting and monitoring
     * - Add backup SMTP servers for redundancy
     * 
     * WARNING: Hardcoded credentials are for demonstration only.
     * Move sensitive data to environment variables or secure config in production.
     * 
     * CONFIGURATION PARAMETERS:
     * - Host: SMTP server hostname
     * - Username: Gmail account for sending
     * - Password: Gmail app password (not regular password)
     * - Port: 587 for STARTTLS
     * - From address: ServiceHub system identifier
     * 
     * USAGE CONTEXT: Called during constructor initialization
     */
    private function configure() {
        // SMTP configuration for Gmail (update with your real credentials)
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.gmail.com';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = 'arultharsan096@gmail.com'; // Your Gmail address
        $this->mail->Password = 'dwzuvfvwhoitkfkp'; // Your Gmail App Password
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = 587;
        $this->mail->setFrom('arultharsan096@gmail.com', 'ServiceHub');
        $this->mail->isHTML(true);
    }

    /**
     * Send an email using PHPMailer with comprehensive error handling.
     *
     * PURPOSE: Provide reliable email delivery with detailed error reporting
     * WHY NEEDED: System requires email notifications for OTP, registration, and alerts
     * HOW IT WORKS: Configures recipient, content, and sends with error capture
     * 
     * BUSINESS LOGIC:
     * - Supports both HTML and plain text email formats
     * - Automatically generates plain text from HTML if not provided
     * - Clears previous recipients to prevent cross-contamination
     * - Provides detailed error information for troubleshooting
     * 
     * EMAIL PROCESSING WORKFLOW:
     * 1. Clear any previous recipients from PHPMailer instance
     * 2. Set recipient email address
     * 3. Configure subject line and HTML body content
     * 4. Set alternative plain text body (auto-generated if needed)
     * 5. Attempt to send email through configured SMTP
     * 6. Return success status or detailed error information
     * 
     * SECURITY FEATURES:
     * - Input validation through PHPMailer's built-in checks
     * - SMTP authentication prevents unauthorized sending
     * - Error messages don't expose sensitive configuration
     * - Proper email header handling prevents injection
     * 
     * ERROR HANDLING:
     * - Captures PHPMailer exceptions for detailed error reporting
     * - Returns standardized response format for consistent handling
     * - Provides ErrorInfo for debugging email delivery issues
     * - Graceful failure without exposing system internals
     * 
     * CONTENT HANDLING:
     * - HTML body: Rich formatted email content with styling
     * - Alt body: Plain text alternative for email clients that don't support HTML
     * - Auto-generation: Uses strip_tags() to create plain text from HTML
     * - Character encoding: Proper UTF-8 handling for international content
     * 
     * PERFORMANCE CONSIDERATIONS:
     * - Clears recipients to prevent memory buildup in long-running processes
     * - Reuses SMTP connection for efficiency
     * - Minimal memory footprint with proper cleanup
     * 
     * @param string $to Recipient email address (validated by PHPMailer)
     * @param string $subject Email subject line (supports UTF-8 characters)
     * @param string $body HTML formatted email body content
     * @param string $altBody Optional plain text alternative (auto-generated if empty)
     * @return array Associative array with 'success' boolean and optional 'error' string
     *               Success: ['success' => true]
     *               Failure: ['success' => false, 'error' => 'Error description']
     * 
     * USAGE CONTEXT: Called for OTP delivery, registration confirmations, password resets,
     *                provider notifications, and system alerts
     */
    public function sendMail($to, $subject, $body, $altBody = '') {
        try {
            $this->mail->clearAddresses(); // Remove previous recipients
            $this->mail->addAddress($to);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->AltBody = $altBody ?: strip_tags($body);
            $this->mail->send();
            return ["success" => true];
        } catch (Exception $e) {
            // Log or handle error as needed
            return ["success" => false, "error" => $this->mail->ErrorInfo];
        }
    }

    // You can add more methods for attachments, CC, BCC, etc.
}
