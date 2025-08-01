<?php
/**
 * Admin.php
 *
 * Defines the Admin class, representing an admin user in the Home Management System backend.
 *
 * Responsibilities:
 * - Encapsulates admin-specific logic (add provider, get providers, get customers)
 * - Inherits all properties and methods from User.php
 * - Used by admin panel APIs to manage users and providers
 *
 * This class is typically used by endpoints such as admin_customers.php, get_providers.php, and others.
 */
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/phpmailer.php';

/**
 * Class Admin
 *
 * Extends the User class to handle admin-specific actions.
 *
 * Methods:
 * - addProvider($data)
 * - getAllProviders()
 * - getCustomerDetails()
 */
class Admin extends User {
    // Add admin-specific methods here

    /**
     * Add a new provider (admin action).
     *
     * @param array $data Provider registration fields
     * @return array Status and message
     *
     * This method is called by admin panel endpoints to add a new provider.
     * It validates input, checks for duplicates, inserts into users and provider tables,
     * and sends a welcome email with credentials.
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
     * Fetch all service providers (admin action).
     *
     * Used by get_providers.php API endpoint to provide a list of all providers for the admin dashboard.
     * Joins user info with provider-specific details.
     *
     * @return array List of providers with details
     */
    public function getAllProviders() {
        $stmt = $this->conn->prepare("SELECT u.*, p.description, p.qualifications, p.status, p.provider_id FROM users u JOIN provider p ON u.user_id = p.user_id WHERE u.user_type = 'provider'");
        $stmt->execute();
        $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $providers;
    }

    /**
     * Fetch all customer details (admin action).
     *
     * Used by admin_customers.php API endpoint to let the admin panel display/manage all customers.
     * Returns status and customer data or error info.
     *
     * @return array Status and customer data or error
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
