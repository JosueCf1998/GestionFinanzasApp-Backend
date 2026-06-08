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
            $passwordPlain = $requestData['password'];

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

        $token = \JwtHelper::generateToken([
            'data' => [
                'user_id' => $usuarioEncontrado->id,
                'email' => $emailPlain
            ]
        ], $this->jwtKey);

        \SessionHelper::createSession($f3, (int)$usuarioEncontrado->id, $token);

        $this->successResponse([
            'token' => $token,
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

    public function forgotPassword($f3)
{
    $this->validateRequestMethod('POST');

        try {
            $requestData = $this->parseAndValidateRequest($f3->get('BODY'));

            // Asumir email y new_password en texto plano (igual que login)
            $emailDecrypt = $requestData['email'];
            $newPasswordDecrypt = $requestData['new_password'];

        $emailEncrypted = \SecurityHelper::encryptData($emailDecrypt, $this->encryptionKey, $this->iv);
        $this->userModel->load(['email = ?', $emailEncrypted]);

        if (!$this->userModel->loaded()) {
            throw new \RuntimeException('Usuario no encontrado con ese email', 404);
        }

        if (password_verify($newPasswordDecrypt, $this->userModel->password)) {
            throw new \RuntimeException('La nueva contraseña no puede ser igual a la anterior', 400);
        }

        $this->userModel->set('password', password_hash($newPasswordDecrypt, PASSWORD_BCRYPT));

        if (!$this->userModel->save()) {
            throw new \RuntimeException('Error al actualizar la contraseña', 500);
        }

        $this->successResponse([], 'Contraseña actualizada correctamente');

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
           
            $userData['password'];
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

    // Wrapper to match routes.ini (GET /users/list)
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
                    throw new \RuntimeException('Token inválido: user_id no presente', 401);
                }
            
                \SessionHelper::verifySession($f3, $decoded->data->user_id, $token);
                $f3->set('user_id', $decoded->data->user_id);
            
            } catch (\Exception $e) {
                $this->handleError($e);
            }
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
        
        if (!empty($data['email'])) {
            $this->userModel->set('email', \SecurityHelper::encryptData($data['email'], $this->encryptionKey, $this->iv));
        }
        
        if (!empty($data['password'])) {
            if (password_verify($data['password'], $this->userModel->password)) {
                throw new \RuntimeException('La nueva contraseña no puede ser igual a la anterior', 400);
            }
            $this->userModel->set('password', password_hash($data['password'], PASSWORD_BCRYPT));
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
    return $userData;
}

}
