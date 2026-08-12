<?php

namespace Controllers;

require_once __DIR__ . '/BaseController.php';

class UsersController extends BaseController
{
    protected $userModel;
    protected $emailService;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new \m_usuarios();
        $this->emailService = new \EmailService();
    }

    public function setEmailService(\EmailService $emailService): void
    {
        $this->emailService = $emailService;
    }

    public function register($f3)
    {
        $this->validateRequestMethod('POST');
        
        try {
            // Support both raw JSON and encrypted-body { "data": "..." }
            $body = $this->parseJsonOrEncryptedBody($f3);
            if (empty($body)) {
                return;
            }

            $requestData = \SecurityHelper::sanitizeInput($body);

            if (!isset($requestData['email']) || !isset($requestData['password'])) {
                throw new \InvalidArgumentException('Email y/o password no proporcionados', 400);
            }

            // Asumir que el cliente envía email/password en texto plano (igual que login)
            $emailPlain = $this->normalizeEmail((string)$body['email']);
            // No transformar la contraseña como HTML antes de hashearla.
            $passwordPlain = $body['password'];

            // Mantener consistencia con otras funciones: colocar password en requestData
            $requestData['password'] = $passwordPlain;

            $this->validateUserData($requestData, $emailPlain);
            $db = $f3->get('DB');
            $db->begin();
            try {
                $this->prepareNewUser($requestData, $emailPlain, $passwordPlain);
                if (!$this->userModel->save()) throw new \RuntimeException('Error al guardar el usuario');
                $code = (new \EmailCodeService($db))->create((int)$this->userModel->id, \EmailCodeService::EMAIL_VERIFICATION);
                $db->commit();
            } catch (\Throwable $e) {
                $db->rollback();
                throw $e;
            }
            $this->emailService->sendVerificationCode($emailPlain, (string)$this->userModel->nombre, $code);
            $this->successResponse([
                'userId' => (int)$this->userModel->id,
                'maskedEmail' => $this->maskEmail($emailPlain),
                'expiresIn' => \EmailCodeService::EXPIRES_IN,
                'resendAfter' => \EmailCodeService::RESEND_AFTER
            ], 'Te enviamos un código de verificación.');
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

            $emailPlain = $this->normalizeEmail((string)$body['email']);
            $passwordPlain = $body['password'];

            $emailEncrypted = \SecurityHelper::encryptData($emailPlain, $this->encryptionKey, $this->iv);

            $this->userModel->load(['email = ?', $emailEncrypted]);

            $usuarioEncontrado = null;
            if ($this->userModel->loaded()) {
                $usuarioEncontrado = $this->userModel;
            } else {
                $usuarios = $this->userModel->find();
                foreach ($usuarios as $usuario) {
                    try {
                        $emailBD = \SecurityHelper::decryptData($usuario->email, $this->encryptionKey, $this->iv);
                        if ($emailBD === $emailPlain) {
                            $usuarioEncontrado = $usuario;
                            break;
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }

            if (!$usuarioEncontrado) {
                throw new \RuntimeException('Credenciales inválidas.', 401);
            }

            if (!password_verify($passwordPlain, $usuarioEncontrado->password)) {
                throw new \RuntimeException('Credenciales inválidas.', 401);
            }

            if (empty($usuarioEncontrado->email_verified_at)) {
                $this->codedErrorResponse('Debes verificar tu correo antes de iniciar sesión.', 403, 'EMAIL_NOT_VERIFIED', [
                    'userId' => (int)$usuarioEncontrado->id,
                    'maskedEmail' => $this->maskEmail($emailPlain)
                ]);
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
            ], 'Login exitoso');
        
    } catch (\Exception $e) {
        $this->handleError($e);
    }
}


    public function profile($f3)
    {
        $this->validateRequestMethod('GET');
        $authenticatedUserId = $this->validateToken($f3);
        $this->userModel->load(['id = ?', $authenticatedUserId]);
        
        if ($this->userModel->loaded()) {
            $this->successResponse([
                'user' => $this->getUserResponseData()
            ], 'Perfil de usuario');
        } else {
            $this->codedErrorResponse('Usuario no encontrado', 404, 'USER_NOT_FOUND');
        }
    }

    public function update($f3)
    {
        $this->validateRequestMethod('POST');
        $authenticatedUserId = $this->validateToken($f3);

        try {
            $requestData = $this->parseAndValidateRequest($f3->get('BODY'));
            $requestedUserId = $f3->get('PARAMS.user_id') ?: ($requestData['user_id'] ?? ($requestData['id'] ?? null));

            if ($requestedUserId !== null && (int)$requestedUserId !== $authenticatedUserId) {
                $this->codedErrorResponse('No tiene permiso para modificar otro usuario', 403, 'FORBIDDEN');
            }

            $allowedFields = ['nombre', 'apellidos'];
            $compatibilityFields = ['id', 'user_id'];
            $unexpectedFields = array_diff(array_keys($requestData), array_merge($allowedFields, $compatibilityFields));
            if (!empty($unexpectedFields)) {
                $this->codedErrorResponse('La solicitud contiene campos no permitidos', 400, 'UNEXPECTED_FIELD');
            }

            $profileData = array_intersect_key($requestData, array_flip($allowedFields));
            if (empty($profileData)) {
                $this->codedErrorResponse('No se proporcionaron campos de perfil válidos', 400, 'UNEXPECTED_FIELD');
            }

            $this->userModel->load(['id = ?', $authenticatedUserId]);
            
            if (!$this->userModel->loaded()) {
                $this->codedErrorResponse('Usuario no encontrado', 404, 'USER_NOT_FOUND');
            }

            $this->updateUserData($profileData);
            
            $this->successResponse([
                'user' => $this->getUserResponseData()
            ], 'Usuario actualizado correctamente');
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    public function forgotPassword($f3)
    {
        $this->validateRequestMethod('POST');
        $this->codedErrorResponse(
            'La recuperación segura de contraseña requiere verificación por correo.',
            501,
            'PASSWORD_RESET_VERIFICATION_REQUIRED'
        );
    }

    public function verifyEmail($f3)
    {
        $this->validateRequestMethod('POST');
        try {
            $body = $this->requiredBody($f3, ['userId', 'code']);
            $user = $this->loadUser((int)$body['userId']);
            if (!empty($user->email_verified_at)) throw new \AuthFlowException('El correo ya fue verificado.', 'EMAIL_ALREADY_VERIFIED', 409);
            $db = $f3->get('DB');
            $db->begin();
            try {
                (new \EmailCodeService($db))->verify((int)$user->id, \EmailCodeService::EMAIL_VERIFICATION, (string)$body['code']);
                $db->exec('UPDATE usuarios SET email_verified_at = ? WHERE id = ?', [date('Y-m-d H:i:s'), (int)$user->id]);
                $db->commit();
            } catch (\AuthFlowException $e) {
                if (in_array($e->errorCode, ['VERIFICATION_CODE_INVALID', 'VERIFICATION_ATTEMPTS_EXCEEDED'], true)) $db->commit();
                else $db->rollback();
                throw $e;
            } catch (\Throwable $e) { $db->rollback(); throw $e; }
            $this->successResponse([], 'Correo verificado correctamente.');
        } catch (\Exception $e) { $this->handleError($e); }
    }

    public function resendVerificationCode($f3)
    {
        $this->validateRequestMethod('POST');
        try {
            $body = $this->requiredBody($f3, ['userId']);
            $user = $this->loadUser((int)$body['userId']);
            if (!empty($user->email_verified_at)) throw new \AuthFlowException('El correo ya fue verificado.', 'EMAIL_ALREADY_VERIFIED', 409);
            $code = (new \EmailCodeService($f3->get('DB')))->create((int)$user->id, \EmailCodeService::EMAIL_VERIFICATION, true);
            $email = $this->decryptEmail((string)$user->email);
            $this->emailService->sendVerificationCode($email, (string)$user->nombre, $code);
            $this->successResponse(['maskedEmail' => $this->maskEmail($email), 'expiresIn' => 600, 'resendAfter' => 60], 'Te enviamos un nuevo código.');
        } catch (\Exception $e) { $this->handleError($e); }
    }

    public function requestPasswordReset($f3)
    {
        $this->validateRequestMethod('POST');
        try {
            $body = $this->requiredBody($f3, ['email']);
            $email = $this->normalizeEmail((string)$body['email']);
            $user = $this->findUserByEmail($email);
            if ($user) {
                try {
                    $code = (new \EmailCodeService($f3->get('DB')))->create((int)$user->id, \EmailCodeService::PASSWORD_RESET, true);
                    $this->emailService->sendPasswordResetCode($email, (string)$user->nombre, $code);
                } catch (\AuthFlowException $ignored) {
                    // Mantener respuesta indistinguible para evitar enumeración.
                }
            }
            $this->successResponse(['expiresIn' => 600, 'resendAfter' => 60], 'Si existe una cuenta asociada, enviaremos un código.');
        } catch (\Exception $e) { $this->handleError($e); }
    }

    public function verifyPasswordReset($f3)
    {
        $this->validateRequestMethod('POST');
        try {
            $body = $this->requiredBody($f3, ['email', 'code']);
            $user = $this->findUserByEmail($this->normalizeEmail((string)$body['email']));
            if (!$user) throw new \AuthFlowException('Código inválido.', 'VERIFICATION_CODE_INVALID');
            $db = $f3->get('DB'); $db->begin();
            try {
                $service = new \EmailCodeService($db);
                $codeRow = $service->verify((int)$user->id, \EmailCodeService::PASSWORD_RESET, (string)$body['code'], false);
                $token = $service->attachResetToken((int)$codeRow['id']);
                $db->commit();
            } catch (\AuthFlowException $e) {
                if (in_array($e->errorCode, ['VERIFICATION_CODE_INVALID', 'VERIFICATION_ATTEMPTS_EXCEEDED'], true)) $db->commit();
                else $db->rollback();
                throw $e;
            } catch (\Throwable $e) { $db->rollback(); throw $e; }
            $this->successResponse(['resetToken' => $token, 'expiresIn' => 600], 'Código verificado.');
        } catch (\Exception $e) { $this->handleError($e); }
    }

    public function confirmPasswordReset($f3)
    {
        $this->validateRequestMethod('POST');
        try {
            $body = $this->requiredBody($f3, ['resetToken', 'newPassword']);
            $password = (string)$body['newPassword'];
            $this->validatePassword($password);
            $db = $f3->get('DB'); $db->begin();
            try {
                $service = new \EmailCodeService($db);
                $reset = $service->findResetToken((string)$body['resetToken']);
                $user = $this->loadUser((int)$reset['user_id']);
                if (password_verify($password, (string)$user->password)) throw new \InvalidArgumentException('La nueva contraseña debe ser diferente.', 400);
                $db->exec('UPDATE usuarios SET password = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), (int)$user->id]);
                $db->exec('DELETE FROM sesiones WHERE user_id = ?', [(int)$user->id]);
                $service->invalidateResetToken((int)$reset['id']);
                $db->commit();
            } catch (\Throwable $e) { $db->rollback(); throw $e; }
            try { $this->emailService->sendPasswordChangedNotice($this->decryptEmail((string)$user->email), (string)$user->nombre); }
            catch (\AuthFlowException $ignored) { error_log('Password change notice delivery failed'); }
            $this->successResponse([], 'Contraseña actualizada. Inicia sesión nuevamente.');
        } catch (\Exception $e) { $this->handleError($e); }
    }

    public function delete($f3)
    {
        $this->validateRequestMethod('POST');
        $authenticatedUserId = $this->validateToken($f3);
        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return;
        }

        $requestedUserId = $body['user_id'] ?? ($body['id'] ?? $authenticatedUserId);
        if ((int)$requestedUserId !== $authenticatedUserId) {
            $this->codedErrorResponse('No tiene permiso para eliminar otro usuario', 403, 'FORBIDDEN');
        }
        if (!empty(array_diff(array_keys($body), ['id', 'user_id']))) {
            $this->codedErrorResponse('La solicitud contiene campos no permitidos', 400, 'UNEXPECTED_FIELD');
        }

        try {
            $this->userModel->load(['id = ?', $authenticatedUserId]);

            if (!$this->userModel->loaded()) {
                $this->codedErrorResponse('Usuario no encontrado', 404, 'USER_NOT_FOUND');
            }

            $db = $f3->get('DB');
            foreach (['cuentas', 'categorias', 'transacciones', 'transferencias'] as $table) {
                if (!empty($db->exec("SELECT 1 FROM {$table} WHERE usuario_id = ? LIMIT 1", [$authenticatedUserId]))) {
                    $this->codedErrorResponse(
                        'La cuenta tiene datos financieros asociados y requiere eliminación lógica',
                        409,
                        'ACCOUNT_DELETION_CONFLICT'
                    );
                }
            }

            $db->begin();
            try {
                $db->exec('DELETE FROM sesiones WHERE user_id = ?', [$authenticatedUserId]);
                $db->exec('DELETE FROM usuarios WHERE id = ?', [$authenticatedUserId]);
                $db->commit();
            } catch (\Throwable $e) {
                $db->rollback();
                throw $e;
            }

            $this->successResponse(['id' => $authenticatedUserId], 'Usuario eliminado correctamente');
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }


public function listAll($f3)
{
    $this->validateRequestMethod('GET');
    $this->validateToken($f3);

    // No existe un sistema de roles verificable; no se inventan privilegios.
    $this->codedErrorResponse('No tiene permiso para listar usuarios', 403, 'FORBIDDEN');
}

    // Wrapper to match routes.ini (GET /users/list)
    public function list($f3)
    {
        return $this->listAll($f3);
    }


    protected function validateToken($f3): int
    {
        try {
            $token = \JwtHelper::getBearerToken($f3);
            $decoded = \JwtHelper::validateToken($token, $this->jwtKey);
        } catch (\Exception $e) {
            $this->codedErrorResponse('Autenticación requerida', 401, 'AUTHENTICATION_REQUIRED');
        }

        if (!isset($decoded->data->user_id) || !is_numeric($decoded->data->user_id)) {
            $this->codedErrorResponse('Autenticación requerida', 401, 'AUTHENTICATION_REQUIRED');
        }

        $authenticatedUserId = (int)$decoded->data->user_id;
        try {
            \SessionHelper::verifySession($f3, $authenticatedUserId, $token);
        } catch (\Exception $e) {
            $this->codedErrorResponse('La sesión no es válida', 401, 'INVALID_SESSION');
        }

        $f3->set('user_id', $authenticatedUserId);
        return $authenticatedUserId;
    }


    protected function validateUserData(array $data, string $email): void
    {   
        $this->validatePassword((string)$data['password']);
        
        $this->userModel->load(['email = ?', \SecurityHelper::encryptData($email, $this->encryptionKey, $this->iv)]);
        if ($this->userModel->loaded()) {
            throw new \RuntimeException('El email ya está registrado', 409);
        }
    }

    protected function prepareNewUser(array $data, string $email, string $password): void
    {
        $this->userModel->reset();
        $this->userModel->set('nombre', \SecurityHelper::sanitizeInput($data['nombre']));
        $this->userModel->set('apellidos', \SecurityHelper::sanitizeInput($data['apellidos']));
        $this->userModel->set('email', \SecurityHelper::encryptData($email, $this->encryptionKey, $this->iv));
        $this->userModel->set('password', password_hash($password, PASSWORD_BCRYPT));
        $this->userModel->set('fecha_registro', date('Y-m-d H:i:s'));
        $this->userModel->set('email_verified_at', null);
    }

    protected function updateUserData(array $data): void
    {
        if (!empty($data['nombre'])) {
            $this->userModel->set('nombre', \SecurityHelper::sanitizeInput($data['nombre']));
        }
        
        if (!empty($data['apellidos'])) {
            $this->userModel->set('apellidos', \SecurityHelper::sanitizeInput($data['apellidos']));
        }
        
        if (!$this->userModel->save()) {
            throw new \RuntimeException('Error al actualizar el usuario');
        }
    }

    protected function getUserResponseData(): array
{
    $userData = $this->userModel->cast();

    try {
        // Los emails en BD se cifran con SecurityHelper::encryptData
        $userData['email'] = \SecurityHelper::decryptData(
            $userData['email'],
            $this->encryptionKey,
            $this->iv
        );
    } catch (\Exception $e) {
        $userData['email'] = 'Error al desencriptar';
    }

    unset($userData['password']);
    return array_intersect_key($userData, array_flip(['id', 'nombre', 'apellidos', 'email']));
}

    protected function requiredBody($f3, array $fields): array
    {
        $body = $this->parseJsonOrEncryptedBody($f3);
        foreach ($fields as $field) {
            if (!array_key_exists($field, $body) || $body[$field] === '') throw new \InvalidArgumentException('Falta un campo requerido.', 400);
        }
        if (array_diff(array_keys($body), $fields)) $this->codedErrorResponse('La solicitud contiene campos no permitidos', 400, 'UNEXPECTED_FIELD');
        return $body;
    }

    protected function normalizeEmail(string $email): string
    {
        $email = strtolower(trim($email));
        if (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new \InvalidArgumentException('Correo electrónico inválido.', 400);
        return $email;
    }

    protected function validatePassword(string $password): void
    {
        if (strlen($password) < 8 || strlen($password) > 128) throw new \InvalidArgumentException('La contraseña debe tener entre 8 y 128 caracteres.', 400);
    }

    protected function loadUser(int $id)
    {
        $this->userModel->reset(); $this->userModel->load(['id = ?', $id]);
        if (!$this->userModel->loaded()) $this->codedErrorResponse('Usuario no encontrado', 404, 'USER_NOT_FOUND');
        return $this->userModel;
    }

    protected function findUserByEmail(string $email)
    {
        $this->userModel->reset();
        $this->userModel->load(['email = ?', \SecurityHelper::encryptData($email, $this->encryptionKey, $this->iv)]);
        if ($this->userModel->loaded()) return $this->userModel;
        foreach ($this->userModel->find() as $user) {
            try { if (hash_equals($email, strtolower(trim($this->decryptEmail((string)$user->email))))) return $user; }
            catch (\Throwable $ignored) {}
        }
        return null;
    }

    protected function decryptEmail(string $encrypted): string
    {
        $email = \SecurityHelper::decryptData($encrypted, $this->encryptionKey, $this->iv);
        if (!is_string($email) || $email === '') throw new \RuntimeException('No se pudo procesar el correo.');
        return $email;
    }

    protected function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        return substr($local, 0, 1) . '***@' . $domain;
    }

}
