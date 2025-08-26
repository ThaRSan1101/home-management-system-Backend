<?php
/**
 * User.php
 *
 * This file defines the User class, which encapsulates all core user-related operations for the Home Management System backend.
 *
 * Responsibilities:
 * - User authentication (login)
 * - Registration with OTP verification
 * - Password reset and OTP validation
 * - Profile update with OTP flow (for customer/provider)
 * - User data retrieval
 *
 * This class is used by API endpoints such as login.php, register.php, forgot_password.php, admin_update_customer.php, etc.
 * All database interactions are handled securely using prepared statements.
 *
 * Dependencies:
 * - db.php: Database connection
 * - phpmailer.php: For sending OTP and notification emails
 */
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/phpmailer.php';

/**
 * Class User
 *
 * Represents a user in the system (customer, provider, or admin).
 * Handles authentication, registration, OTP, password reset, and profile updates.
 *
 * Properties:
 * - protected $conn: PDO database connection
 *
 * Methods:
 * - __construct($dbConn = null)
 * - getUserById($userId)
 * - login($email, $password)
 * - register($data)
 * - verifyOtp($data)
 * - forgotPassword($email)
 * - verifyResetOtp($email, $otp)
 * - resetPassword($email, $otp, $newPassword)
 * - requestProfileUpdateOtp($data, $purpose)
 * - verifyProfileUpdateOtp($userId, $otp, $purpose)
 */
class User {
    // ...
    /**
     * Fetch user details by user ID.
     *
     * @param int $userId
     * @return array|false User data or false if not found
     *
     * Used by admin panels or profile endpoints to retrieve user info.
     */
    public function getUserById($userId) {
        $stmt = $this->conn->prepare("SELECT user_id, email, user_type, name, phone_number, address, registered_date, NIC FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    /**
     * @var PDO $conn Database connection
     */
    protected $conn;
    
    // ... your existing properties ...

    /**
     * User constructor.
     *
     * @param PDO|null $dbConn Optional PDO connection. If not provided, a new connection is created.
     */
    public function __construct($dbConn = null) {
        if ($dbConn) {
            $this->conn = $dbConn;
        } else {
            $db = new DBConnector();
            $this->conn = $db->connect();
        }
    }

    /**
     * Authenticate a user by email and password.
     *
     * @param string $email
     * @param string $password
     * @return array Status, user data, and messages
     *
     * Called by login API endpoint. Performs validation, password hash verification,
     * disables access for disabled users, and returns user details.
     */
    public function login($email, $password) {
        $email = strtolower(trim($email));
        $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $password = trim($password);
        $password = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');

        $stmt = $this->conn->prepare("SELECT user_id, name, email, password, user_type, disable_status, phone_number, address, registered_date, NIC FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || !password_verify($password, $user['password'])) {
            return ['status' => 'error', 'message' => 'Invalid email or password.'];
        }
        if ($user['disable_status']) {
            return ['status' => 'error', 'message' => 'Your account has been disabled. Please contact support.'];
        }

        
        $payload = [
            'user_id' => $user['user_id'],
            'email' => $user['email'],
            'user_type' => $user['user_type']
        ];
        generate_jwt($payload);

        return [
            'status' => 'success',
            'message' => 'Login successful.',
            
            'user_type' => $user['user_type'],
            'name' => $user['name'],
            'email' => $user['email'],
            'user_id' => $user['user_id'],
            'user_details' => $user['user_type'] === 'customer' ? [
                'fullName' => $user['name'],
                'address' => $user['address'],
                'phone' => $user['phone_number'],
                'email' => $user['email'],
                'joined' => isset($user['registered_date']) ? date('Y-m-d', strtotime($user['registered_date'])) : '',
                'nic' => $user['NIC'] ?? ''
            ] : ($user['user_type'] === 'provider' ? [
                'fullName' => $user['name'],
                'address' => $user['address'],
                'phone' => $user['phone_number'],
                'email' => $user['email'],
                'joined' => isset($user['registered_date']) ? date('Y-m-d', strtotime($user['registered_date'])) : '',
                'nic' => $user['NIC'] ?? ''
            ] : ($user['user_type'] === 'admin' ? [
                'fullName' => $user['name'],
                'email' => $user['email']
            ] : null))
        ];
    }

    /**
     * Register a new user and send OTP for verification.
     *
     * @param array $data Registration fields (email, fullName, phone, address, password, nic, userType)
     * @return array Status and message
     *
     * Called by register API endpoint. Cleans up expired OTPs, validates input, and saves pending registration.
     */
    public function register($data) {
        // Cleanup expired registration OTPs
        $cleanupStmt = $this->conn->prepare("DELETE FROM otp WHERE purpose = 'registration' AND expired_at < NOW()");
        $cleanupStmt->execute();
        $email = $data['email'] ?? '';
        $fullName = $data['fullName'] ?? '';
        $phone = $data['phone'] ?? '';
        $address = $data['address'] ?? '';
        $password = $data['password'] ?? '';
        $nic = $data['nic'] ?? '';
        $userType = $data['userType'] ?? 'customer';

        // Full name: only letters and spaces
        if (!preg_match('/^[A-Za-z ]+$/', $fullName)) {
            return ['status' => 'error', 'message' => 'Full name can only contain letters and spaces.'];
        }
        // Email: allow all domains, only valid format (letters, numbers, periods, underscores before @)
        if (!preg_match('/^[a-zA-Z0-9._]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
            return ['status' => 'error', 'message' => 'Enter a valid email address (only letters, numbers, periods, underscores before @, and a valid domain)'];
        }
        if ($nic && !preg_match('/^(\d{12}|\d{9}[Vv])$/', $nic)) {
            return ['status' => 'error', 'message' => 'NIC must be 12 digits or 9 digits followed by V.'];
        }
        if (!$email || !$fullName || !$phone || !$address || !$password) {
            return ['status' => 'error', 'message' => 'All fields are required.'];
        }
        // Phone: must be 10 digits
        if (!preg_match('/^\d{10}$/', $phone)) {
            return ['status' => 'error', 'message' => 'Phone number must be exactly 10 digits.'];
        }
        // Address: minimum 4 characters
        if (strlen(trim($address)) < 4) {
            return ['status' => 'error', 'message' => 'Address must be at least 4 characters.'];
        }
        // Password strength: min 8 chars, at least 1 letter, 1 number, 1 special char
        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/', $password)) {
            return ['status' => 'error', 'message' => 'Password must be at least 8 characters and include at least one letter, one number, and one special character.'];
        }
        $checkStmt = $this->conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch(PDO::FETCH_ASSOC)) {
            return ['status' => 'error', 'message' => 'An account with this email already exists.'];
        }
        if ($nic) {
            $checkNicStmt = $this->conn->prepare("SELECT user_id FROM users WHERE NIC = ?");
            $checkNicStmt->execute([$nic]);
            if ($checkNicStmt->fetch(PDO::FETCH_ASSOC)) {
                return ['status' => 'error', 'message' => 'An account with this NIC already exists.'];
            }
        }
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        $deleteStmt = $this->conn->prepare("DELETE FROM otp WHERE email = ?");
        $deleteStmt->execute([$email]);
        $stmt = $this->conn->prepare("INSERT INTO otp (email, otp_code, purpose, expired_at) VALUES (?, ?, 'registration', ?)");
        if (!$stmt->execute([$email, $otp, $expires_at])) {
            return ['status' => 'error', 'message' => 'Failed to save OTP.'];
        }
        // Send OTP email
        $mailer = new PHPMailerService();
            $subject = 'Your OTP for Registration';
            $body = '<div style="font-family:Arial,sans-serif;max-width:420px;margin:auto;border:1px solid #e0e0e0;padding:24px;border-radius:8px;">
            <div style="font-size:20px;font-weight:bold;color:#2a4365;margin-bottom:8px;">Home Management System</div>
            <div style="font-size:16px;margin-bottom:16px;">Hello, <strong>' . htmlspecialchars($fullName) . '</strong></div>
            <div style="margin-bottom:12px;">Thank you for registering. Use the code below to verify your email:</div>
            <div style="font-size:28px;font-weight:bold;color:#2a4365;margin-bottom:16px;letter-spacing:2px;">' . htmlspecialchars($otp) . '</div>
            <div style="font-size:13px;color:#555;">This code will expire in 15 minutes.</div>
            <hr style="margin:24px 0 12px 0;border:none;border-top:1px solid #eee;">
            <div style="font-size:12px;color:#999;">If you did not request this, please ignore this email.</div>
            </div>';
            $result = $mailer->sendMail($email, $subject, $body);
            if ($result['success']) {
                return [
                    'status' => 'success',
                    'message' => 'OTP sent',
                    'debug' => [
                        'otp' => $otp,
                        'expires_at' => $expires_at,
                        'current_time' => date('Y-m-d H:i:s')
                    ]
                ];
            } else {
                return ['status' => 'error', 'message' => 'Mail Error: ' . $result['error']];
            }
    }

    /**
     * Verify OTP for registration or other purposes.
     *
     * @param array $data (email, otp, purpose)
     * @return array Status and message
     *
     * Called by verify_otp API endpoint. Checks OTP validity and completes registration if valid.
     */
    public function verifyOtp($data) {
        $email = $data['email'] ?? '';
        $fullName = $data['fullName'] ?? '';
        $phone = $data['phone'] ?? '';
        $address = $data['address'] ?? '';
        $password = $data['password'] ?? '';
        $otp = isset($data['otp']) ? trim($data['otp']) : '';
        $otp = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');
        $nic = $data['nic'] ?? '';
        $userType = $data['userType'] ?? 'customer';
        if (!$email || !$fullName || !$phone || !$address || !$password || !$otp) {
            return ['status' => 'error', 'message' => 'All fields are required.'];
        }
        $checkEmailStmt = $this->conn->prepare("SELECT * FROM otp WHERE email = ? ORDER BY created_at DESC LIMIT 1");
        $checkEmailStmt->execute([$email]);
        $latestOtp = $checkEmailStmt->fetch(PDO::FETCH_ASSOC);
        if (!$latestOtp) {
            return ['status' => 'error', 'message' => 'No OTP found for this email. Please request a new OTP.'];
        }
        $currentTime = date('Y-m-d H:i:s');
        $stmt = $this->conn->prepare("SELECT * FROM otp WHERE email = ? AND otp_code = ? AND purpose = 'registration' AND expired_at > ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$email, $otp, $currentTime]);
        $otpRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$otpRecord) {
            $expiredStmt = $this->conn->prepare("SELECT * FROM otp WHERE email = ? AND otp_code = ? AND purpose = 'registration' AND expired_at <= ?");
            $expiredStmt->execute([$email, $otp, $currentTime]);
            $expiredOtp = $expiredStmt->fetch(PDO::FETCH_ASSOC);
            if ($expiredOtp) {
                return [
                    'status' => 'error',
                    'message' => 'OTP has expired. Please request a new OTP.',
                    'debug' => [
                        'current_time' => $currentTime,
                        'expired_at' => $expiredOtp['expired_at'],
                        'otp_code' => $expiredOtp['otp_code']
                    ]
                ];
            } else {
                return ['status' => 'error', 'message' => 'Invalid OTP. Please check and try again.'];
            }
        }
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $insertStmt = $this->conn->prepare("INSERT INTO users (name, email, password, phone_number, address, NIC, user_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insertStmt->execute([$fullName, $email, $hashedPassword, $phone, $address, $nic, $userType]);
        if ($insertStmt->rowCount() > 0) {
            $deleteStmt = $this->conn->prepare("DELETE FROM otp WHERE email = ?");
            $deleteStmt->execute([$email]);
            return ['status' => 'success', 'message' => 'Registration successful!'];
        } else {
            return ['status' => 'error', 'message' => 'Registration failed.'];
        }
    }

    /**
     * Initiate password reset process by sending OTP to user's email.
     *
     * @param string $email
     * @return array Status and message
     *
     * Called by forgot_password API endpoint. Generates OTP and sends email.
     */
    public function forgotPassword($email) {
        $cleanupStmt = $this->conn->prepare("DELETE FROM otp WHERE purpose = 'password_reset' AND expired_at < NOW()");
        $cleanupStmt->execute();
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            return ['status' => 'error', 'message' => 'No account found with that email.'];
        }
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $stmt = $this->conn->prepare("INSERT INTO otp (email, otp_code, purpose, expired_at) VALUES (?, ?, 'password_reset', ?) ON DUPLICATE KEY UPDATE otp_code=VALUES(otp_code), expired_at=VALUES(expired_at)");
        $stmt->execute([$email, $otp, $expires_at]);
        $mailer = new PHPMailerService();
            $subject = 'Your Password Reset Code';
            $body = '<div style="font-family:Arial,sans-serif;max-width:420px;margin:auto;border:1px solid #e0e0e0;padding:24px;border-radius:8px;">
            <div style="font-size:20px;font-weight:bold;color:#2a4365;margin-bottom:8px;">Home Management System</div>
            <div style="font-size:16px;margin-bottom:16px;">Hello,</div>
            <div style="margin-bottom:12px;">Use the code below to reset your password:</div>
            <div style="font-size:28px;font-weight:bold;color:#2a4365;margin-bottom:16px;letter-spacing:2px;">' . htmlspecialchars($otp) . '</div>
            <div style="font-size:13px;color:#555;">This code will expire in 10 minutes.</div>
            <hr style="margin:24px 0 12px 0;border:none;border-top:1px solid #eee;">
            <div style="font-size:12px;color:#999;">If you did not request this, please ignore this email.</div>
            </div>';
            $result = $mailer->sendMail($email, $subject, $body);
            if ($result['success']) {
                return ['status' => 'success', 'message' => 'OTP sent to your email.'];
            } else {
                return ['status' => 'error', 'message' => 'Mail Error: ' . $result['error']];
            }
    }

    /**
     * Verify OTP for password reset.
     *
     * @param string $email
     * @param string $otp
     * @return array Status and message
     *
     * Called by verify_reset_otp API endpoint. Checks OTP validity for password reset.
     */
    public function verifyResetOtp($email, $otp) {
        $stmt = $this->conn->prepare("SELECT otp_code FROM otp WHERE email = ? AND purpose = 'password_reset' ");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return ['status' => 'error', 'message' => 'Invalid or expired OTP.'];
        }
        $storedOtp = $row['otp_code'];
        if ($otp !== $storedOtp) {
            return ['status' => 'error', 'message' => 'Incorrect OTP.'];
        }
        return ['status' => 'success', 'message' => 'OTP verified.'];
    }

    /**
     * Reset user's password after OTP verification.
     *
     * @param string $email
     * @param string $otp
     * @param string $newPassword
     * @return array Status and message
     *
     * Called by reset_password API endpoint. Updates password if OTP is valid.
     */
    public function resetPassword($email, $otp, $newPassword) {
        $stmt = $this->conn->prepare("SELECT * FROM otp WHERE email = ? AND otp_code = ? AND purpose = 'password_reset'");
        $stmt->execute([$email, $otp]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return [
                'status' => 'error',
                'message' => 'Invalid or expired OTP.'
            ];
        }
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $email]);
        if ($stmt->rowCount() === 0) {
            return ['status' => 'error', 'message' => 'Password reset failed. User may not exist.'];
        }
        $stmt = $this->conn->prepare("DELETE FROM otp WHERE email = ? AND purpose = 'password_reset'");
        $stmt->execute([$email]);
        return ['status' => 'success', 'message' => 'Password changed successfully.'];
    }

    /**
     * Request OTP for profile update (customer/provider)
     * @param array $data - profile fields, must include user_id
     * @param string $purpose - 'updateCustomerProfile' or 'updateProviderProfile'
     */
    /**
     * Request OTP for profile update (customer/provider).
     *
     * @param array $data Profile fields (must include user_id)
     * @param string $purpose 'updateCustomerProfile' or 'updateProviderProfile'
     * @return array Status and message
     *
     * Called by profile update API endpoints. Sends OTP for profile update verification.
     */
    public function requestProfileUpdateOtp($data, $purpose) {
        $userId = $data['user_id'] ?? null;
        if (!$userId) {
            return ['status' => 'error', 'message' => 'User ID is required.'];
        }
        // Fetch current email from users table
        $stmt = $this->conn->prepare("SELECT email, name FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            return ['status' => 'error', 'message' => 'User not found.'];
        }
        $email = $user['email'];
        $fullName = $user['name'];
        // Generate OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $pendingData = json_encode($data);
        // Remove any previous pending OTP for this user/purpose
        $del = $this->conn->prepare("DELETE FROM otp WHERE email = ? AND purpose = ?");
        $del->execute([$email, $purpose]);
        // Insert new OTP with pending data in pending_data column
        $stmt = $this->conn->prepare("INSERT INTO otp (email, otp_code, purpose, expired_at, pending_data) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt->execute([$email, $otp, $purpose, $expires_at, $pendingData])) {
            return ['status' => 'error', 'message' => 'Failed to save OTP.'];
        }
        // Send OTP email
        $mailer = new PHPMailerService();
        $subject = 'Profile Update OTP';
        $body = '<div style="font-family:Arial,sans-serif;max-width:420px;margin:auto;border:1px solid #e0e0e0;padding:24px;border-radius:8px;">
            <div style="font-size:20px;font-weight:bold;color:#2a4365;margin-bottom:8px;">Home Management System</div>
            <div style="font-size:16px;margin-bottom:16px;">Hello, <strong>' . htmlspecialchars($fullName) . '</strong></div>
            <div style="margin-bottom:12px;">You requested to update your profile. Use the code below to verify:</div>
            <div style="font-size:28px;font-weight:bold;color:#2a4365;margin-bottom:16px;letter-spacing:2px;">' . htmlspecialchars($otp) . '</div>
            <div style="font-size:13px;color:#555;">This code will expire in 10 minutes.</div>
            <hr style="margin:24px 0 12px 0;border:none;border-top:1px solid #eee;">
            <div style="font-size:12px;color:#999;">If you did not request this, please ignore this email.</div>
            </div>';
        $result = $mailer->sendMail($email, $subject, $body);
        if ($result['success']) {
            return [
                'status' => 'success',
                'message' => 'OTP sent to your email. Please enter it to confirm profile update.'
            ];
        } else {
            return ['status' => 'error', 'message' => 'Mail Error: ' . $result['error']];
        }
    }

    /**
     * Verify OTP and update profile (customer/provider)
     * @param int $userId
     * @param string $otp
     * @param string $purpose
     */
    /**
     * Verify OTP and update profile (customer/provider).
     *
     * @param int $userId
     * @param string $otp
     * @param string $purpose
     * @return array Status and message
     *
     * Called by profile update API endpoints after OTP is entered by user.
     * Updates user data if OTP is valid.
     */
    public function verifyProfileUpdateOtp($userId, $otp, $purpose) {
        // Fetch current email
        $stmt = $this->conn->prepare("SELECT email FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            return ['status' => 'error', 'message' => 'User not found.'];
        }
        $email = $user['email'];
        // Find OTP
        $now = date('Y-m-d H:i:s');
        $stmt = $this->conn->prepare("SELECT * FROM otp WHERE email = ? AND otp_code = ? AND purpose = ? AND expired_at > ?");
        $stmt->execute([$email, $otp, $purpose, $now]);
        $otpRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$otpRow) {
            return ['status' => 'error', 'message' => 'Invalid or expired OTP.'];
        }
        // Get pending data from pending_data column
        $pendingData = json_decode($otpRow['pending_data'] ?? '', true);
        if (!$pendingData || !isset($pendingData['user_id'])) {
            return ['status' => 'error', 'message' => 'Pending profile data is invalid.'];
        }
        // Update users table
        $fields = [];
        $params = [];
        foreach (['name', 'email', 'phone_number', 'address'] as $field) {
            if (isset($pendingData[$field]) || isset($pendingData[$field === 'name' ? 'fullName' : $field])) {
                $fields[] = "$field = ?";
                $params[] = $pendingData[$field] ?? $pendingData[$field === 'name' ? 'fullName' : $field];
            }
        }
        if (empty($fields)) {
            return ['status' => 'error', 'message' => 'No valid profile fields to update.'];
        }
        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt->execute($params)) {
            return ['status' => 'error', 'message' => 'Failed to update profile.'];
        }
        // Clean up OTP
        $del = $this->conn->prepare("DELETE FROM otp WHERE email = ? AND purpose = ?");
        $del->execute([$email, $purpose]);
        return ['status' => 'success', 'message' => 'Profile updated successfully.'];
    }
} 