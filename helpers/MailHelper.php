<?php

require_once __DIR__ . '/../smtp.php';

class MailHelper
{
    public static function sendEmail(string $to, string $subject, string $message): void
    {
        $host = getenv('SMTP_HOST') ?: '';
        $port = (int)(getenv('SMTP_PORT') ?: 587);
        $scheme = getenv('SMTP_SCHEME') ?: 'tls';
        $user = getenv('SMTP_USER') ?: '';
        $pass = getenv('SMTP_PASS') ?: '';
        $from = getenv('SMTP_FROM') ?: $user;

        if ($host === '' || $user === '' || $pass === '' || $from === '') {
            throw new \RuntimeException('Configuracion SMTP incompleta');
        }

        $smtp = new \SMTP($host, $port, $scheme, $user, $pass);
        $smtp->set('From', $from);
        $smtp->set('To', $to);
        $smtp->set('Subject', $subject);

        if (!$smtp->send($message)) {
            throw new \RuntimeException('No se pudo enviar el correo');
        }
    }

    public static function sendRegistrationCode(string $to, string $code): void
    {
        $appName = getenv('APP_NAME') ?: 'Gestion Finanzas';
        $subject = $appName . ' - Codigo de verificacion de registro';
        $message = "Tu codigo de verificacion es: {$code}\n\n";
        $message .= "Este codigo vence en 10 minutos.\n";
        $message .= "Si no solicitaste este registro, ignora este mensaje.";

        self::sendEmail($to, $subject, $message);
    }

    public static function sendPasswordResetToken(string $to, string $token): void
    {
        $appName = getenv('APP_NAME') ?: 'Gestion Finanzas';
        $appUrl = rtrim(getenv('APP_URL') ?: '', '/');

        $subject = $appName . ' - Solicitud de reseteo de contrasena';
        $message = "Usa este token para resetear tu contrasena: {$token}\n\n";
        $message .= "Este token vence en 10 minutos.\n";
        $message .= "Si no solicitaste este cambio, ignora este mensaje.";

        self::sendEmail($to, $subject, $message);
    }
}
