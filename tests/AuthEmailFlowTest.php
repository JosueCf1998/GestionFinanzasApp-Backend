<?php

declare(strict_types=1);

require_once __DIR__ . '/../exceptions/AuthFlowException.php';
require_once __DIR__ . '/../services/EmailCodeService.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../services/EmailService.php';

class FakeEmailService extends EmailService
{
    public array $messages = [];
    public function sendVerificationCode(string $email, string $name, string $code): void { $this->messages[] = ['verification', $email]; }
    public function sendPasswordResetCode(string $email, string $name, string $code): void { $this->messages[] = ['reset', $email]; }
    public function sendPasswordChangedNotice(string $email, string $name): void { $this->messages[] = ['changed', $email]; }
}

$fakeEmail = new FakeEmailService();
$fakeEmail->sendVerificationCode('test@example.com', 'Test', '123456');
$fakeEmail->sendPasswordResetCode('test@example.com', 'Test', '123456');
$fakeEmail->sendPasswordChangedNotice('test@example.com', 'Test');

$controller = file_get_contents(__DIR__ . '/../controllers/UsersController.php');
$codes = file_get_contents(__DIR__ . '/../services/EmailCodeService.php');
$email = file_get_contents(__DIR__ . '/../services/EmailService.php');
$up = file_get_contents(__DIR__ . '/../db/sql/migrations/20260811_email_codes_up.sql');

$tests = [
    'registro crea usuario pendiente' => str_contains($controller, "set('email_verified_at', null)"),
    'login rechaza usuario pendiente' => str_contains($controller, 'EMAIL_NOT_VERIFIED'),
    'codigo correcto verifica cuenta en transaccion' => str_contains($controller, 'UPDATE usuarios SET email_verified_at') && str_contains($controller, '$db->begin()'),
    'codigo incorrecto incrementa intentos' => str_contains($codes, 'SET attempts = ?'),
    'codigo vencido se rechaza' => str_contains($codes, 'VERIFICATION_CODE_EXPIRED'),
    'codigo usado no se reutiliza' => str_contains($codes, 'VERIFICATION_CODE_USED'),
    'reenvio anticipado se rechaza' => str_contains($codes, 'VERIFICATION_RESEND_TOO_SOON'),
    'solicitud no cambia password' => preg_match('/function requestPasswordReset.*?function verifyPasswordReset/s', $controller, $requestMethod) === 1
        && !str_contains($requestMethod[0], 'UPDATE usuarios SET password'),
    'otp produce token y solo guarda sha256' => str_contains($codes, 'bin2hex(random_bytes(32))') && str_contains($codes, "hash('sha256', \$token)"),
    'confirmacion cambia password y revoca sesiones' => str_contains($controller, 'UPDATE usuarios SET password = ?') && str_contains($controller, 'DELETE FROM sesiones WHERE user_id = ?'),
    'token usado o vencido se rechaza' => str_contains($codes, 'PASSWORD_RESET_TOKEN_INVALID') && str_contains($codes, 'PASSWORD_RESET_TOKEN_EXPIRED'),
    'no se guardan codigos en texto plano' => str_contains($codes, 'password_hash($code, PASSWORD_DEFAULT)') && str_contains($up, 'code_hash'),
    'servicio correo admite sustitucion sin enviar correo real' => str_contains($controller, 'setEmailService') && count($fakeEmail->messages) === 3,
    'errores smtp no revelan detalles' => str_contains($email, 'EMAIL_DELIVERY_FAILED') && !str_contains($email, 'ErrorInfo')
];

$failed = 0;
foreach ($tests as $name => $passed) {
    echo ($passed ? '[OK] ' : '[FAIL] ') . $name . PHP_EOL;
    $failed += $passed ? 0 : 1;
}
exit($failed === 0 ? 0 : 1);
