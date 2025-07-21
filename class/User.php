<?php
require_once __DIR__ . '/../api/db.php';
// User.php - OOP class for user authentication and management

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class User {
    /**
     * @var PDO Database connection
     */
    protected $conn;
    private $jwtKey = 'f8d3c2e1b4a7d6e5f9c8b7a6e3d2c1f0a9b8c7d6e5f4a3b2c1d0e9f8a7b6c5d4';

    // User table columns as private properties
    /** @var int|null */
    protected $user_id;
    /** @var string|null */
    protected $name;
    /** @var string|null */
    protected $email;
    /** @var string|null */
    protected $password;
    /** @var string|null */
    protected $phone_number;
    /** @var string|null */
    protected $address;
    /** @var string|null */
    protected $NIC;
    /** @var string|null */
    protected $user_type;
    /** @var bool|null */
    protected $disable_status;
    /** @var string|null */
    protected $registered_date;

    /**
     * User constructor.
     * @param PDO|null $dbConn
     */
    public function __construct($dbConn = null) {
        if ($dbConn) {
            $this->conn = $dbConn;
        } else {
            $db = new DBConnector();
            $this->conn = $db->connect();
        }
    }

    public function login($email, $password) {
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
            'user_type' => $user['user_type'],
            'exp' => time() + (60 * 60 * 24)
        ];
        $jwt = JWT::encode($payload, $this->jwtKey, 'HS256');
        return [
            'status' => 'success',
            'message' => 'Login successful.',
            'jwt' => $jwt,
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
        if ($nic && !preg_match('/^(\d{12}|\d{9}[Vv])$/', $nic)) {
            return ['status' => 'error', 'message' => 'NIC must be 12 digits or 9 digits followed by V.'];
        }
        if (!$email || !$fullName || !$phone || !$address || !$password) {
            return ['status' => 'error', 'message' => 'All fields are required.'];
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
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'arultharsan096@gmail.com';
            $mail->Password = 'dwzuvfvwhoitkfkp';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->setFrom('arultharsan096@gmail.com', 'ServiceHub');
            $mail->addAddress($email, $fullName);
            $mail->isHTML(true);
            $mail->Subject = 'Your OTP for Registration';
            $mail->Body    = "<h3>Hello $fullName,</h3><p>Your OTP is: <strong>$otp</strong></p><p>This OTP will expire in 15 minutes.</p>";
            $mail->send();
            return [
                'status' => 'success',
                'message' => 'OTP sent',
                'debug' => [
                    'otp' => $otp,
                    'expires_at' => $expires_at,
                    'current_time' => date('Y-m-d H:i:s')
                ]
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Mail Error: ' . $mail->ErrorInfo];
        }
    }

    public function verifyOtp($data) {
        $email = $data['email'] ?? '';
        $fullName = $data['fullName'] ?? '';
        $phone = $data['phone'] ?? '';
        $address = $data['address'] ?? '';
        $password = $data['password'] ?? '';
        $otp = $data['otp'] ?? '';
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

    public function forgotPassword($email) {
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            return ['status' => 'error', 'message' => 'No account found with that email.'];
        }
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $stmt = $this->conn->prepare("INSERT INTO otp (email, otp_code, purpose, expired_at) VALUES (?, ?, 'password_reset', ?) ON DUPLICATE KEY UPDATE otp_code=VALUES(otp_code), expired_at=VALUES(expired_at)");
        $stmt->execute([$email, $otp, $expires_at]);
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'arultharsan096@gmail.com';
            $mail->Password = 'dwzuvfvwhoitkfkp';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->setFrom('arultharsan096@gmail.com', 'Your App Name');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Your Password Reset Code';
            $mail->Body    = "<h3>Password Reset</h3><p>Your OTP is: <strong>$otp</strong></p><p>This OTP will expire in 10 minutes.</p>";
            $mail->send();
            return ['status' => 'success', 'message' => 'OTP sent to your email.'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Mail Error: ' . $mail->ErrorInfo];
        }
    }

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
} 