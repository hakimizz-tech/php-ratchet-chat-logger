<?php

namespace ChatApp\Email;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configureMailer();
    }

    private function configureMailer()
    {
        try {
            //Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host       = $_ENV['SMTP_HOST'];
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = $_ENV['SMTP_USER'];
            $this->mailer->Password   = $_ENV['SMTP_PASSWORD'];
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port       = $_ENV['SMTP_PORT'];

            //Recipients
            $this->mailer->setFrom($_ENV['SMTP_FROM_EMAIL'], $_ENV['SMTP_FROM_NAME']);
        } catch (Exception $e) {
            // Handle exception
        }
    }

    public function sendOtpEmail(string $toEmail, string $otp, bool $isNewUser)
    {
        try {
            $this->mailer->addAddress($toEmail);

            // Content
            $this->mailer->isHTML(true);
            if ($isNewUser) {
                $this->mailer->Subject = 'Welcome to ChatApp! Your Login Code';
                $this->mailer->Body    = "Welcome to ChatApp! <br>Your login code is: <b>{$otp}</b><br>This code will expire in 15 minutes.<br><br><b>Warning:</b> You are receiving this email because of a login attempt on our development server. Since we are not using HTTPS yet, please be cautious. If you did not request this code, you can safely ignore it. <b>Please check your spam/junk folder if you don't see our emails.</b>";
            } else {
                $this->mailer->Subject = 'Your ChatApp Login Code';
                $this->mailer->Body    = "Your login code is: <b>{$otp}</b><br>This code will expire in 15 minutes.<br><br><b>Warning:</b> You are receiving this email because of a login attempt on our development server. Since we are not using HTTPS yet, please be cautious. If you did not request this code, you can safely ignore it. <b>Please check your spam/junk folder if you don't see our emails.</b>";
            }

            $this->mailer->send();
        } catch (Exception $e) {
            // Handle exception
        }
    }
}
