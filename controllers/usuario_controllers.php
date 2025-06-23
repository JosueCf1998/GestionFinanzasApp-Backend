<?php

require_once 'BaseController.php';
require_once 'helpers/ResponseHelper.php';
require_once 'helpers/SecurityHelper.php';
require_once 'helpers/JwtHelper.php';
require_once 'helpers/SessionHelper.php';
require_once 'helpers/AesDecryptor.php';

class usuario_controllers extends BaseController
{
    protected $userModel;
    private $jwtKey;
    private $encryptionKey;
    private $iv;

    public function __construct()
    {
        $this->userModel = new m_usuarios();
        $this->jwtKey = getenv('JWT_SECRET') ?: '$#Gre1410#$';
        $this->encryptionKey = getenv('ENCRYPTION_KEY') ?: '$#Gre1410';
        $this->iv = SecurityHelper::generateIV($this->encryptionKey);
    }

    /**
     * Registra un nuevo usuario
     */
    public function register($f3)
    {
        $this->validateRequestMethod('POST');
        
        try {
            $requestData = $this->parseAndValidateRequest($f3->get('BODY'));
            $email = AesDecryptor::decrypt(
                $requestData['email'], 
                getenv('ENCRYPTION_PASSWORD') ?: "TuClaveSuperSecreta@2024"
            );
            
            $this->validateUserData($requestData, $email);
            $this->prepareNewUser($requestData, $email);
            
            if ($this->userModel->save()) {
                $this->successResponse([
                    'id' => $this->userModel->get('id'),
                    'email' => $email
                ], 'Usuario registrado exitosamente');
            } else {
                throw new RuntimeException('Error al guardar el usuario');
            }
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Autentica un usuario y genera JWT
     */
    public function login($f3)
    {
        $this->validateRequestMethod('POST');
        
        try {
            $requestData = $this->parseAndValidateRequest($f3->get('BODY'));
            $encryptedEmail = SecurityHelper::encryptData($requestData['email'], $this->encryptionKey, $this->iv);
            
            $this->userModel->load(['email = ?', $encryptedEmail]);
            
            if (!$this->userModel->loaded()) {
                throw new RuntimeException('Credenciales incorrectas', 401);
            }
            
            if (!password_verify($requestData['password'], $this->userModel->password)) {
                throw new RuntimeException('Credenciales incorrectas', 401);
            }
            
            $token = JwtHelper::generateToken([
                'user_id' => $this->userModel->id,
                'email' => $requestData['email']
            ], $this->jwtKey);
            
            SessionHelper::createSession($f3, $this->userModel->id, $token);
            
            $this->successResponse([
                'token' => $token,
                'user' => $this->getUserResponseData()
            ], 'Login exitoso');
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Obtiene el perfil del usuario autenticado
     */
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

    /**
     * Actualiza los datos del usuario
     */
    public function update($f3)
    {
        $this->validateToken($f3);
        $userId = $f3->get('PARAMS.user_id');
        
        try {
            $this->userModel->load(['id = ?', $userId]);
            
            if (!$this->userModel->loaded()) {
                throw new RuntimeException('Usuario no encontrado', 404);
            }
            
            $requestData = $this->parseAndValidateRequest($f3->get('BODY'));
            $this->updateUserData($requestData);
            
            $this->successResponse([
                'id' => $this->userModel->get('id')
            ], 'Usuario actualizado correctamente');
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Elimina un usuario
     */
    public function delete($f3)
    {
        $this->validateToken($f3);
        $userId = $f3->get('PARAMS.user_id');
        
        try {
            $this->userModel->load(['id = ?', $userId]);
            
            if (!$this->userModel->loaded()) {
                throw new RuntimeException('Usuario no encontrado', 404);
            }
            
            // Eliminar sesiones activas primero
            $db = $f3->get('DB');
            $db->exec("DELETE FROM sesiones WHERE user_id = ?", [$userId]);
            
            $this->userModel->erase();
            
            $this->successResponse([
                'id' => $userId
            ], 'Usuario eliminado correctamente');
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Lista todos los usuarios (solo para administradores)
     */
    public function listAll($f3)
    {
        $this->validateToken($f3);
        
        try {
            $users = $this->userModel->find();
            $userList = [];
            
            foreach ($users as $user) {
                $userData = $user->cast();
                $userData['email'] = SecurityHelper::decryptData($userData['email'], $this->encryptionKey, $this->iv);
                $userList[] = $userData;
            }
            
            $this->successResponse([
                'users' => $userList,
                'count' => count($userList)
            ], 'Lista de usuarios obtenida');
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    /***********************
     * Métodos protegidos *
     ***********************/

    /**
     * Valida el token JWT y establece el user_id en el framework
     */
    protected function validateToken($f3)
    {
        try {
            $token = JwtHelper::getBearerToken();
            $decoded = JwtHelper::validateToken($token, $this->jwtKey);
            SessionHelper::verifySession($f3, $decoded->data->user_id, $token);
            $f3->set('user_id', $decoded->data->user_id);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Valida los datos del usuario antes de registrarlo
     */
    protected function validateUserData(array $data, string $email): void
    {   
        if (strlen($data['password']) < 8) {
            throw new InvalidArgumentException('La contraseña debe tener al menos 8 caracteres', 400);
        }
        
        $this->userModel->load(['email = ?', SecurityHelper::encryptData($email, $this->encryptionKey, $this->iv)]);
        if ($this->userModel->loaded()) {
            throw new RuntimeException('El email ya está registrado', 409);
        }
    }

    /**
     * Prepara un nuevo usuario para registro
     */
    protected function prepareNewUser(array $data, string $email): void
    {
        $this->userModel->reset();
        $this->userModel->set('nombre', SecurityHelper::sanitizeInput($data['nombre']));
        $this->userModel->set('apellidos', SecurityHelper::sanitizeInput($data['apellidos']));
        $this->userModel->set('email', SecurityHelper::encryptData($email, $this->encryptionKey, $this->iv));
        $this->userModel->set('password', password_hash($data['password'], PASSWORD_BCRYPT));
        $this->userModel->set('fecha_registro', date('Y-m-d H:i:s'));
    }

    /**
     * Actualiza los datos del usuario
     */
    protected function updateUserData(array $data): void
    {
        if (!empty($data['nombre'])) {
            $this->userModel->set('nombre', SecurityHelper::sanitizeInput($data['nombre']));
        }
        
        if (!empty($data['apellidos'])) {
            $this->userModel->set('apellidos', SecurityHelper::sanitizeInput($data['apellidos']));
        }
        
        if (!empty($data['email'])) {
            $this->userModel->set('email', SecurityHelper::encryptData($data['email'], $this->encryptionKey, $this->iv));
        }
        
        if (!empty($data['password'])) {
            if (password_verify($data['password'], $this->userModel->password)) {
                throw new RuntimeException('La nueva contraseña no puede ser igual a la anterior', 400);
            }
            $this->userModel->set('password', password_hash($data['password'], PASSWORD_BCRYPT));
        }
        
        if (!$this->userModel->save()) {
            throw new RuntimeException('Error al actualizar el usuario');
        }
    }

    /**
     * Obtiene los datos del usuario para la respuesta
     */
    protected function getUserResponseData(): array
    {
        $userData = $this->userModel->cast();
        $userData['email'] = SecurityHelper::decryptData($userData['email'], $this->encryptionKey, $this->iv);
        unset($userData['password']); // Nunca devolver la contraseña
        return $userData;
    }
}