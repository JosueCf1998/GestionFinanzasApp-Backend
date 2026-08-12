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
            $emailPlain = $requestData['email'];
            // La contraseña se valida y hashea sin transformaciones HTML.
            $passwordPlain = $body['password'];

            // Mantener consistencia con otras funciones: colocar password en requestData
            $requestData['password'] = $passwordPlain;

            $this->validateUserData($requestData, $emailPlain);
            $this->prepareNewUser($requestData, $emailPlain, $passwordPlain);
            
            if ($this->userModel->save()) {
                $this->successResponse([], 'Usuario registrado exitosamente');
            } else {
                throw new \RuntimeException('Error al guardar el usuario');
            }
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

            $emailPlain = $body['email'];
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
        $userId = $this->validateToken($f3);
        $this->userModel->load(['id = ?', $userId]);
        
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
        $this->validateRequestMethod('POST');
        $userId = $this->validateToken($f3);
        try {
            $requestData = $this->parseAndValidateRequest($f3->get('BODY'));
            $requestedId = $f3->get('PARAMS.user_id') ?: ($requestData['user_id'] ?? ($requestData['id'] ?? null));
            if ($requestedId !== null && (int)$requestedId !== $userId) {
                $this->codedErrorResponse('No tiene permiso para modificar otro usuario', 403, 'FORBIDDEN');
            }
            $allowed = ['nombre', 'apellidos'];
            if (array_diff(array_keys($requestData), array_merge($allowed, ['id', 'user_id']))) {
                $this->codedErrorResponse('La solicitud contiene campos no permitidos', 400, 'UNEXPECTED_FIELD');
            }
            $profileData = array_intersect_key($requestData, array_flip($allowed));
            if (!$profileData) {
                $this->codedErrorResponse('No se proporcionaron campos válidos', 400, 'UNEXPECTED_FIELD');
            }
            $this->userModel->load(['id = ?', $userId]);
            
            if (!$this->userModel->loaded()) {
                throw new \RuntimeException('Usuario no encontrado', 404);
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

    public function delete($f3)
    {
        $this->validateRequestMethod('POST');
        $userId = $this->validateToken($f3);
        $body = $this->parseJsonOrEncryptedBody($f3);
        $requestedId = $body['user_id'] ?? ($body['id'] ?? $userId);
        if ((int)$requestedId !== $userId) {
            $this->codedErrorResponse('No tiene permiso para eliminar otro usuario', 403, 'FORBIDDEN');
        }
        if (array_diff(array_keys($body), ['id', 'user_id'])) {
            $this->codedErrorResponse('La solicitud contiene campos no permitidos', 400, 'UNEXPECTED_FIELD');
        }
        try {
            $this->userModel->load(['id = ?', $userId]);
            if (!$this->userModel->loaded()) {
                $this->codedErrorResponse('Usuario no encontrado', 404, 'USER_NOT_FOUND');
            }
            $db = $f3->get('DB');
            foreach (['cuentas', 'categorias', 'transacciones', 'transferencias'] as $table) {
                if ($db->exec("SELECT 1 FROM {$table} WHERE usuario_id = ? LIMIT 1", [$userId])) {
                    $this->codedErrorResponse('La cuenta tiene datos financieros asociados', 409, 'ACCOUNT_DELETION_CONFLICT');
                }
            }
            $db->begin();
            try {
                $db->exec('DELETE FROM sesiones WHERE user_id = ?', [$userId]);
                $db->exec('DELETE FROM usuarios WHERE id = ?', [$userId]);
                $db->commit();
            } catch (\Throwable $e) {
                $db->rollback();
                throw $e;
            }
            $this->successResponse(['id' => $userId], 'Usuario eliminado correctamente');
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }


public function listAll($f3)
{
    $this->validateRequestMethod('GET');
    $this->validateToken($f3);
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
        $userId = (int)$decoded->data->user_id;
        try {
            \SessionHelper::verifySession($f3, $userId, $token);
        } catch (\Exception $e) {
            $this->codedErrorResponse('La sesión no es válida', 401, 'INVALID_SESSION');
        }
        $f3->set('user_id', $userId);
        return $userId;
    }


    protected function validateUserData(array $data, string $email): void
    {   
        if (strlen($data['password']) < 8) {
            throw new \InvalidArgumentException('La contraseña debe tener al menos 8 caracteres', 400);
        }
        
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

}
