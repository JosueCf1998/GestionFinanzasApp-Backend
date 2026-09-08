<?php

namespace Controllers;

require_once __DIR__ . '/BaseController.php';

class UsersController extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new \m_usuarios();
    }

    // Mantiene compatibilidad con la ruta historica /users/register
    public function register($f3)
    {
        return $this->registerRequest($f3);
    }

    public function registerRequest($f3)
    {
        $this->validateRequestMethod('POST');

        try {
            $body = $this->parseJsonOrEncryptedBody($f3);
            if (empty($body)) {
                return;
            }

            $requestData = \SecurityHelper::sanitizeInput($body);

            if (
                empty($requestData['nombre']) ||
                empty($requestData['apellidos']) ||
                empty($requestData['email']) ||
                empty($requestData['password'])
            ) {
                throw new \InvalidArgumentException('Nombre, apellidos, email y password son obligatorios', 400);
            }

            $emailPlain = $requestData['email'];
            $passwordPlain = $requestData['password'];

            $this->validateUserData($requestData, $emailPlain);

            $emailEncrypted = \SecurityHelper::encryptData($emailPlain, $this->encryptionKey, $this->iv);
            $code = $this->generateNumericCode(6);

            $db = $f3->get('DB');
            $now = date('Y-m-d H:i:s');
            $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            $db->exec(
                "UPDATE pending_user_registrations SET consumed_at = ?, updated_at = ? WHERE email_enc = ? AND consumed_at IS NULL",
                [$now, $now, $emailEncrypted]
            );

            $db->exec(
                "INSERT INTO pending_user_registrations (nombre, apellidos, email_enc, password_hash, verification_code_hash, code_expires_at, attempts, consumed_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 0, NULL, ?, ?)",
                [
                    $requestData['nombre'],
                    $requestData['apellidos'],
                    $emailEncrypted,
                    password_hash($passwordPlain, PASSWORD_BCRYPT),
                    password_hash($code, PASSWORD_BCRYPT),
                    $expiresAt,
                    $now,
                    $now
                ]
            );

            \MailHelper::sendRegistrationCode($emailPlain, $code);

            $this->successResponse([], 'Se envio un codigo de verificacion al correo');
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    public function registerVerify($f3)
    {
        $this->validateRequestMethod('POST');

        try {
            $body = $this->parseJsonOrEncryptedBody($f3);
            if (empty($body)) {
                return;
            }

            $requestData = \SecurityHelper::sanitizeInput($body);

            if (empty($requestData['email']) || empty($requestData['code'])) {
                throw new \InvalidArgumentException('Email y codigo son obligatorios', 400);
            }

            $emailPlain = $requestData['email'];
            $emailEncrypted = \SecurityHelper::encryptData($emailPlain, $this->encryptionKey, $this->iv);
            $db = $f3->get('DB');

            $rows = $db->exec(
                "SELECT * FROM pending_user_registrations WHERE email_enc = ? AND consumed_at IS NULL ORDER BY id DESC LIMIT 1",
                [$emailEncrypted]
            );

            if (empty($rows)) {
                throw new \RuntimeException('No existe solicitud de registro pendiente', 404);
            }

            $pending = $rows[0];

            if ((int)$pending['attempts'] >= 5) {
                throw new \RuntimeException('Se excedio el numero maximo de intentos', 429);
            }

            if (strtotime($pending['code_expires_at']) < time()) {
                throw new \RuntimeException('El codigo de verificacion expiro', 400);
            }

            if (!password_verify((string)$requestData['code'], $pending['verification_code_hash'])) {
                $db->exec(
                    "UPDATE pending_user_registrations SET attempts = attempts + 1, updated_at = ? WHERE id = ?",
                    [date('Y-m-d H:i:s'), (int)$pending['id']]
                );
                throw new \RuntimeException('Codigo de verificacion invalido', 400);
            }

            $existingUser = $this->resolveUserByEmail($emailPlain);
            if ($existingUser !== null) {
                throw new \RuntimeException('El email ya esta registrado', 409);
            }

            $this->userModel->reset();
            $this->userModel->set('nombre', $pending['nombre']);
            $this->userModel->set('apellidos', $pending['apellidos']);
            $this->userModel->set('email', $pending['email_enc']);
            $this->userModel->set('password', $pending['password_hash']);
            $this->userModel->set('email_verificado', 1);
            $this->userModel->set('fecha_registro', date('Y-m-d H:i:s'));
            $this->userModel->set('updated_at', date('Y-m-d H:i:s'));

            if (!$this->userModel->save()) {
                throw new \RuntimeException('Error al crear el usuario verificado', 500);
            }

            $db->exec(
                "UPDATE pending_user_registrations SET consumed_at = ?, updated_at = ? WHERE id = ?",
                [date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), (int)$pending['id']]
            );

            $this->successResponse([], 'Usuario registrado y verificado exitosamente');
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    public function login($f3)
    {
        $this->validateRequestMethod('POST');

        try {
            $body = $this->parseJsonOrEncryptedBody($f3);
            if (empty($body)) {
                return;
            }

            if (empty($body['email']) || empty($body['password'])) {
                throw new \InvalidArgumentException('Email y/o password no proporcionados', 400);
            }

            $emailPlain = \SecurityHelper::sanitizeInput($body['email']);
            $passwordPlain = $body['password'];

            $usuarioEncontrado = $this->resolveUserByEmail($emailPlain);

            if (!$usuarioEncontrado) {
                throw new \RuntimeException('Credenciales invalidas.', 401);
            }

            if ((int)($usuarioEncontrado->email_verificado ?? 0) !== 1) {
                throw new \RuntimeException('Debes verificar tu correo antes de iniciar sesion', 403);
            }

            if (!password_verify($passwordPlain, $usuarioEncontrado->password)) {
                throw new \RuntimeException('Credenciales invalidas.', 401);
            }

            $accountModel = new \m_cuentas();
            $accountsCount = $accountModel->count(['usuario_id = ?', (int)$usuarioEncontrado->id]);
            $isFirstTime = $accountsCount === 0;

            $token = \JwtHelper::generateToken([
                'data' => [
                    'user_id' => $usuarioEncontrado->id,
                    'email' => $emailPlain
                ]
            ], $this->jwtKey);

            \SessionHelper::createSession($f3, (int)$usuarioEncontrado->id, $token);

            $this->successResponse([
                'token' => $token,
                'isFirstTime' => $isFirstTime,
                'name' => $usuarioEncontrado->nombre,
                'otpEnabled' => (int)($usuarioEncontrado->otp_enabled ?? 0) === 1
            ], 'Login exitoso');
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    public function profile($f3)
    {
        $this->validateToken($f3);
        $this->userModel->load(['id = ?', $f3->get('user_id')]);

        if ($this->userModel->loaded()) {
            $this->successResponse([
                'user' => $this->getUserResponseData()
            ], 'Perfil de usuario');
        } else {
            $this->errorResponse('Usuario no encontrado', 404);
        }
    }

    public function update($f3)
    {
        $userId = $f3->get('PARAMS.user_id');

        try {
            $this->userModel->load(['id = ?', $userId]);

            if (!$this->userModel->loaded()) {
                throw new \RuntimeException('Usuario no encontrado', 404);
            }

            $requestData = $this->parseAndValidateRequest($f3->get('BODY'));
            $this->updateUserData($requestData);

            $this->successResponse([
                'id' => $this->userModel->get('id')
            ], 'Usuario actualizado correctamente');
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    // Mantiene compatibilidad con ruta historica, ahora dispara el flujo seguro.
    public function forgotPassword($f3)
    {
        return $this->passwordResetRequest($f3);
    }

    public function passwordResetRequest($f3)
    {
        $this->validateRequestMethod('POST');

        try {
            $body = $this->parseJsonOrEncryptedBody($f3);
            if (empty($body)) {
                return;
            }

            $requestData = \SecurityHelper::sanitizeInput($body);

            if (empty($requestData['email'])) {
                throw new \InvalidArgumentException('Email requerido', 400);
            }

            $emailPlain = $requestData['email'];
            $user = $this->resolveUserByEmail($emailPlain);

            if ($user !== null && (int)($user->otp_enabled ?? 0) === 1) {
                $token = $this->generateResetToken();
                $now = date('Y-m-d H:i:s');
                $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                $db = $f3->get('DB');

                $db->exec(
                    "UPDATE password_reset_tokens SET consumed_at = ?, updated_at = ? WHERE user_id = ? AND consumed_at IS NULL",
                    [$now, $now, (int)$user->id]
                );

                $db->exec(
                    "INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, attempts, consumed_at, created_at, updated_at) VALUES (?, ?, ?, 0, NULL, ?, ?)",
                    [
                        (int)$user->id,
                        password_hash($token, PASSWORD_BCRYPT),
                        $expiresAt,
                        $now,
                        $now
                    ]
                );

                \MailHelper::sendPasswordResetToken($emailPlain, $token);
            }

            $this->successResponse([], 'Si el correo existe y cumple requisitos, se envio un token de reseteo');
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    public function passwordResetConfirm($f3)
    {
        $this->validateRequestMethod('POST');

        try {
            $body = $this->parseJsonOrEncryptedBody($f3);
            if (empty($body)) {
                return;
            }

            $requestData = \SecurityHelper::sanitizeInput($body);

            if (
                empty($requestData['email']) ||
                empty($requestData['reset_token']) ||
                empty($requestData['otp_code']) ||
                empty($requestData['new_password'])
            ) {
                throw new \InvalidArgumentException('Email, reset_token, otp_code y new_password son obligatorios', 400);
            }

            $user = $this->resolveUserByEmail($requestData['email']);
            if ($user === null) {
                throw new \RuntimeException('Solicitud invalida', 400);
            }

            if ((int)($user->otp_enabled ?? 0) !== 1 || empty($user->otp_secret_enc)) {
                throw new \RuntimeException('OTP no habilitado para este usuario', 403);
            }

            if (strlen($requestData['new_password']) < 8) {
                throw new \RuntimeException('La nueva contrasena debe tener al menos 8 caracteres', 400);
            }

            if (password_verify($requestData['new_password'], $user->password)) {
                throw new \RuntimeException('La nueva contrasena no puede ser igual a la anterior', 400);
            }

            $db = $f3->get('DB');
            $tokenRows = $db->exec(
                "SELECT * FROM password_reset_tokens WHERE user_id = ? AND consumed_at IS NULL ORDER BY id DESC LIMIT 10",
                [(int)$user->id]
            );

            if (empty($tokenRows)) {
                throw new \RuntimeException('Token de reseteo invalido o expirado', 400);
            }

            $matchingToken = null;
            foreach ($tokenRows as $row) {
                if (strtotime($row['expires_at']) < time()) {
                    continue;
                }

                if ((int)$row['attempts'] >= 5) {
                    continue;
                }

                if (password_verify($requestData['reset_token'], $row['token_hash'])) {
                    $matchingToken = $row;
                    break;
                }
            }

            if ($matchingToken === null) {
                $db->exec(
                    "UPDATE password_reset_tokens SET attempts = attempts + 1, updated_at = ? WHERE id = ?",
                    [date('Y-m-d H:i:s'), (int)$tokenRows[0]['id']]
                );
                throw new \RuntimeException('Token de reseteo invalido o expirado', 400);
            }

            $otpSecret = \SecurityHelper::decryptData($user->otp_secret_enc, $this->encryptionKey, $this->iv);
            if (!$otpSecret || !\OtpHelper::verifyCode($otpSecret, (string)$requestData['otp_code'], 1)) {
                $db->exec(
                    "UPDATE password_reset_tokens SET attempts = attempts + 1, updated_at = ? WHERE id = ?",
                    [date('Y-m-d H:i:s'), (int)$matchingToken['id']]
                );
                throw new \RuntimeException('OTP invalido', 400);
            }

            $user->set('password', password_hash($requestData['new_password'], PASSWORD_BCRYPT));
            $user->set('updated_at', date('Y-m-d H:i:s'));

            if (!$user->save()) {
                throw new \RuntimeException('Error al actualizar la contrasena', 500);
            }

            $db->exec(
                "UPDATE password_reset_tokens SET consumed_at = ?, updated_at = ? WHERE id = ?",
                [date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), (int)$matchingToken['id']]
            );

            $this->successResponse([], 'Contrasena actualizada correctamente');
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    public function otpSetupInit($f3)
    {
        $this->validateToken($f3);

        try {
            $userId = (int)$f3->get('user_id');
            $this->userModel->load(['id = ?', $userId]);

            if (!$this->userModel->loaded()) {
                throw new \RuntimeException('Usuario no encontrado', 404);
            }

            $emailPlain = \SecurityHelper::decryptData($this->userModel->email, $this->encryptionKey, $this->iv);
            $secret = \OtpHelper::generateSecret();

            $this->userModel->set('otp_secret_enc', \SecurityHelper::encryptData($secret, $this->encryptionKey, $this->iv));
            $this->userModel->set('otp_enabled', 0);
            $this->userModel->set('updated_at', date('Y-m-d H:i:s'));

            if (!$this->userModel->save()) {
                throw new \RuntimeException('No se pudo iniciar configuracion OTP', 500);
            }

            $issuer = getenv('APP_NAME') ?: 'Gestion Finanzas';
            $otpauthUrl = \OtpHelper::getOtpAuthUrl($issuer, $emailPlain ?: ('user-' . $userId), $secret);

            $this->successResponse([
                'secret' => $secret,
                'otpauth_url' => $otpauthUrl
            ], 'OTP generado. Escanea el QR o usa el secret manualmente');
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    public function otpSetupConfirm($f3)
    {
        $this->validateToken($f3);

        try {
            $body = $this->parseJsonOrEncryptedBody($f3);
            if (empty($body)) {
                return;
            }

            $requestData = \SecurityHelper::sanitizeInput($body);

            if (empty($requestData['otp_code'])) {
                throw new \InvalidArgumentException('otp_code es obligatorio', 400);
            }

            $userId = (int)$f3->get('user_id');
            $this->userModel->load(['id = ?', $userId]);

            if (!$this->userModel->loaded()) {
                throw new \RuntimeException('Usuario no encontrado', 404);
            }

            if (empty($this->userModel->otp_secret_enc)) {
                throw new \RuntimeException('Debes iniciar la configuracion OTP primero', 400);
            }

            $otpSecret = \SecurityHelper::decryptData($this->userModel->otp_secret_enc, $this->encryptionKey, $this->iv);
            if (!$otpSecret || !\OtpHelper::verifyCode($otpSecret, (string)$requestData['otp_code'], 1)) {
                throw new \RuntimeException('OTP invalido', 400);
            }

            $this->userModel->set('otp_enabled', 1);
            $this->userModel->set('updated_at', date('Y-m-d H:i:s'));

            if (!$this->userModel->save()) {
                throw new \RuntimeException('No se pudo confirmar OTP', 500);
            }

            $this->successResponse([], 'OTP habilitado correctamente');
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    public function delete($f3)
    {
        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return;
        }
        $userId = $body['id'] ?? null;

        try {
            if (!$userId) {
                throw new \RuntimeException('ID no proporcionado', 400);
            }

            $this->userModel->load(['id = ?', $userId]);

            if (!$this->userModel->loaded()) {
                throw new \RuntimeException('Usuario no encontrado', 404);
            }

            $db = $f3->get('DB');
            $db->exec("DELETE FROM sesiones WHERE user_id = ?", [$userId]);

            $this->userModel->erase();

            $this->successResponse(['id' => $userId], 'Usuario eliminado correctamente');
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    public function listAll($f3)
    {
        try {
            $users = $this->userModel->find();
            $userList = [];

            foreach ($users as $user) {
                $userData = $user->cast();

                try {
                    $userData['email'] = \SecurityHelper::decryptData(
                        $userData['email'],
                        $this->encryptionKey,
                        $this->iv
                    );
                } catch (\Exception $e) {
                    $userData['email'] = 'Error al desencriptar';
                }

                unset($userData['password']);
                unset($userData['otp_secret_enc']);
                $userList[] = $userData;
            }

            $this->successResponse([
                'users' => $userList,
                'count' => count($userList)
            ], 'Lista de usuarios obtenida');
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    // Wrapper para mantener ruta existente GET /users/list
    public function list($f3)
    {
        return $this->listAll($f3);
    }

    protected function validateToken($f3)
    {
        try {
            $token = \JwtHelper::getBearerToken($f3);
            $decoded = \JwtHelper::validateToken($token, $this->jwtKey);

            if (!isset($decoded->data->user_id)) {
                throw new \RuntimeException('Token invalido: user_id no presente', 401);
            }

            \SessionHelper::verifySession($f3, $decoded->data->user_id, $token);
            $f3->set('user_id', $decoded->data->user_id);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    protected function validateUserData(array $data, string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Formato de email invalido', 400);
        }

        if (strlen((string)$data['password']) < 8) {
            throw new \InvalidArgumentException('La contrasena debe tener al menos 8 caracteres', 400);
        }

        $existing = $this->resolveUserByEmail($email);
        if ($existing !== null) {
            throw new \RuntimeException('El email ya esta registrado', 409);
        }
    }

    protected function updateUserData(array $data): void
    {
        if (!empty($data['nombre'])) {
            $this->userModel->set('nombre', \SecurityHelper::sanitizeInput($data['nombre']));
        }

        if (!empty($data['apellidos'])) {
            $this->userModel->set('apellidos', \SecurityHelper::sanitizeInput($data['apellidos']));
        }

        if (!empty($data['email'])) {
            $this->userModel->set('email', \SecurityHelper::encryptData($data['email'], $this->encryptionKey, $this->iv));
            $this->userModel->set('email_verificado', 0);
        }

        if (!empty($data['password'])) {
            if (password_verify($data['password'], $this->userModel->password)) {
                throw new \RuntimeException('La nueva contrasena no puede ser igual a la anterior', 400);
            }
            $this->userModel->set('password', password_hash($data['password'], PASSWORD_BCRYPT));
        }

        $this->userModel->set('updated_at', date('Y-m-d H:i:s'));

        if (!$this->userModel->save()) {
            throw new \RuntimeException('Error al actualizar el usuario');
        }
    }

    protected function getUserResponseData(): array
    {
        $userData = $this->userModel->cast();

        try {
            $userData['email'] = \SecurityHelper::decryptData(
                $userData['email'],
                $this->encryptionKey,
                $this->iv
            );
        } catch (\Exception $e) {
            $userData['email'] = 'Error al desencriptar';
        }

        unset($userData['password']);
        unset($userData['otp_secret_enc']);
        return $userData;
    }

    protected function resolveUserByEmail(string $emailPlain)
    {
        $emailEncrypted = \SecurityHelper::encryptData($emailPlain, $this->encryptionKey, $this->iv);

        $directModel = new \m_usuarios();
        $directModel->load(['email = ?', $emailEncrypted]);
        if ($directModel->loaded()) {
            return $directModel;
        }

        $scanModel = new \m_usuarios();
        $users = $scanModel->find();
        foreach ($users as $user) {
            try {
                $emailBD = \SecurityHelper::decryptData($user->email, $this->encryptionKey, $this->iv);
                if ($emailBD === $emailPlain) {
                    return $user;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    protected function generateNumericCode(int $digits = 6): string
    {
        $max = (10 ** $digits) - 1;
        $code = (string)random_int(0, $max);
        return str_pad($code, $digits, '0', STR_PAD_LEFT);
    }

    protected function generateResetToken(): string
    {
        return strtoupper(bin2hex(random_bytes(16)));
    }
}
