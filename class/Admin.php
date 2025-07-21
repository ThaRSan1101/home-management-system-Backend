<?php
require_once __DIR__ . '/User.php';

class Admin extends User {
    // Add admin-specific methods here

    public function addProvider($data) {
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $phone = $data['phone'] ?? '';
        $address = $data['address'] ?? '';
        $nic = $data['nic'] ?? '';
        $description = $data['description'] ?? '';
        $qualification = $data['qualification'] ?? '';

        if (!$name || !$email || !$password || !$phone || !$address || !$nic) {
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

        // Insert into users table
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
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
        // Send welcome email
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'arultharsan096@gmail.com';
            $mail->Password = 'dwzuvfvwhoitkfkp';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->setFrom('arultharsan096@gmail.com', 'ServiceHub');
            $mail->addAddress($email, $name);
            $mail->isHTML(true);
            $mail->Subject = 'Welcome to ServiceHub!';
            $mail->Body = "<h3>Welcome to ServiceHub, $name!</h3><p>Your provider account has been created by the admin.</p><p><b>Username:</b> $email<br><b>Password:</b> $password</p><p>You can now log in and start accepting service requests.</p><p>Thank you,<br>ServiceHub Team</p>";
            $mail->send();
        } catch (\Exception $e) {
            $emailError = $mail->ErrorInfo;
        }

        return ['status' => 'success', 'message' => 'Provider added successfully.', 'emailError' => $emailError];
    }

    public function getAllProviders() {
        $stmt = $this->conn->prepare("SELECT u.*, p.description, p.qualifications, p.status, p.provider_id FROM users u JOIN provider p ON u.user_id = p.user_id WHERE u.user_type = 'provider'");
        $stmt->execute();
        $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $providers;
    }
} 