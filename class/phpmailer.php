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
     * Initializes PHPMailer and configures SMTP settings.
     */
    public function __construct() {
        $this->mail = new PHPMailer(true);
        $this->configure();
    }

    /**
     * Configure SMTP and sender settings for outgoing emails.
     *
     * WARNING: Hardcoded credentials are for demonstration only.
     * Move sensitive data to environment variables or secure config in production.
     */
    private function configure() {
        // SMTP configuration for Gmail (update with your real credentials)
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.gmail.com';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = 'servicehub2509@gmail.com'; // Your Gmail address
        $this->mail->Password = 'ylwckdezikztneop'; // Your Gmail App Password
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = 587;
        $this->mail->setFrom('servicehub2509@gmail.com', 'ServiceHub');
        $this->mail->isHTML(true);
    }

    /**
     * Send an email using PHPMailer.
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body HTML body content
     * @param string $altBody Optional plain-text alternative body
     * @return array [success => bool, error => string|null]
     *
     * Used for sending OTPs, registration confirmations, password resets, and notifications.
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
