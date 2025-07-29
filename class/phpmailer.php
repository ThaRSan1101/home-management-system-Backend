<?php
// phpmailer.php: Email handling class using PHPMailer

require_once __DIR__ . '/../api/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../api/PHPMailer/SMTP.php';
require_once __DIR__ . '/../api/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class PHPMailerService {
    private $mail;

    public function __construct() {
        $this->mail = new PHPMailer(true);
        $this->configure();
    }

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

    public function sendMail($to, $subject, $body, $altBody = '') {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->AltBody = $altBody ?: strip_tags($body);
            $this->mail->send();
            return ["success" => true];
        } catch (Exception $e) {
            return ["success" => false, "error" => $this->mail->ErrorInfo];
        }
    }

    // You can add more methods for attachments, CC, BCC, etc.
}
