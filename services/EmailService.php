<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;

class EmailService
{
    public function sendVerificationCode(string $email, string $name, string $code): void
    {
        $this->sendCode($email, $name, $code, 'Verifica tu correo electrónico');
    }

    public function sendPasswordResetCode(string $email, string $name, string $code): void
    {
        $this->sendCode($email, $name, $code, 'Recuperación de contraseña');
    }

    public function sendPasswordChangedNotice(string $email, string $name): void
    {
        $safeName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $this->send(
            $email,
            $name,
            'Tu contraseña fue modificada',
            '<p>Hola ' . $safeName . ',</p><p>La contraseña de tu cuenta fue modificada.</p><p>Si no realizaste este cambio, contacta al soporte.</p>',
            "Hola {$name},\n\nLa contraseña de tu cuenta fue modificada. Si no realizaste este cambio, contacta al soporte."
        );
    }

    private function sendCode(string $email, string $name, string $code, string $subject): void
    {
        $safeName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeCode = htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $this->send(
            $email,
            $name,
            $subject,
            '<p>Hola ' . $safeName . ',</p><p>Tu código es:</p><p style="font-size:28px;font-weight:bold;letter-spacing:5px">' . $safeCode . '</p><p>Vence en 10 minutos.</p><p>Si no solicitaste esta operación, ignora este correo.</p>',
            "Hola {$name},\n\nTu código es: {$code}\nVence en 10 minutos.\nSi no solicitaste esta operación, ignora este correo."
        );
    }

    private function send(string $email, string $name, string $subject, string $html, string $text): void
    {
        $config = $this->config();
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->Port = (int)$config['port'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->SMTPSecure = $config['encryption'] === 'tls'
                ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($config['fromAddress'], $config['fromName']);
            $mail->addAddress($email, $name);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = $text;
            $mail->send();
        } catch (MailerException $e) {
            throw new AuthFlowException('No fue posible enviar el correo.', 'EMAIL_DELIVERY_FAILED', 502);
        }
    }

    private function config(): array
    {
        $config = [
            'host' => getenv('MAIL_HOST') ?: '', 'port' => getenv('MAIL_PORT') ?: '',
            'username' => getenv('MAIL_USERNAME') ?: '', 'password' => getenv('MAIL_PASSWORD') ?: '',
            'encryption' => strtolower(getenv('MAIL_ENCRYPTION') ?: ''),
            'fromAddress' => getenv('MAIL_FROM_ADDRESS') ?: '', 'fromName' => getenv('MAIL_FROM_NAME') ?: ''
        ];
        foreach ($config as $value) {
            if ($value === '') {
                throw new AuthFlowException('El servicio de correo no está disponible.', 'EMAIL_DELIVERY_FAILED', 503);
            }
        }
        if (!filter_var($config['fromAddress'], FILTER_VALIDATE_EMAIL)
            || !in_array($config['encryption'], ['tls', 'ssl', 'smtps'], true)) {
            throw new AuthFlowException('El servicio de correo no está disponible.', 'EMAIL_DELIVERY_FAILED', 503);
        }
        return $config;
    }
}
