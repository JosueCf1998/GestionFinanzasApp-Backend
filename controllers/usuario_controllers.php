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

    public function testEncryptDecrypt($f3)
{

    try {
        $body = $this->parseAndValidateRequest($f3->get('BODY'));
        //$body = json_decode($f3->get('BODY'), true);
        $email = $body['email'];
        $encrypted2 = $body['encrypted'];

        if (!$email) {
            return $this->errorResponse('Email no proporcionado', 400);
        }

        try {
            $encrypted = SecurityHelper::encryptData($email, $this->encryptionKey, $this->iv);
            $decrypted = SecurityHelper::decryptData($encrypted, $this->encryptionKey, $this->iv);
            $decrypted2= SecurityHelper::decryptData($encrypted2, $this->encryptionKey, $this->iv);

            $this->successResponse([
                'original' => $email,
                'encrypted' => $encrypted,
                'decrypted' => $decrypted,
                'decrypted2' => $decrypted2
            ], 'Prueba de encriptación/desencriptación exitosa');
        } catch (Exception $e) {
            $this->errorResponse('Error: ' . $e->getMessage(), 500);
        }
    } catch (Exception $e) {
        $this->handleError($e);
    }
}

    /**
     * Registra un nuevo usuario
     */
    public function register($f3)
    {
        $this->validateRequestMethod('POST');
        
        try {
            $requestData = $this->parseAndValidateRequest($f3->get('BODY'));
            $emailDecrypt = AesDecryptor::decrypt(
                $requestData['email'], 
                getenv('ENCRYPTION_PASSWORD') ?: "TuClaveSuperSecreta@2024"
            );
            $passwordDecrypt = AesDecryptor::decrypt(
            $requestData['password'],
            getenv('ENCRYPTION_PASSWORD') ?: "TuClaveSuperSecreta@2024"
        );
            
            $this->validateUserData($requestData, $emailDecrypt);
            $this->prepareNewUser($requestData, $emailDecrypt, $passwordDecrypt);
            
            if ($this->userModel->save()) {
                $this->successResponse([
                    'id' => $this->userModel->get('id'),
                ], 'Usuario registrado exitosamente');
            } else {
                throw new RuntimeException('Error al guardar el usuario');
            }
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }


    public function login($f3)
{
    $this->validateRequestMethod('POST');

    try {
        $requestData = $this->parseAndValidateRequest($f3->get('BODY'));

        $emailDecrypt = AesDecryptor::decrypt(
            $requestData['email'],
            getenv('ENCRYPTION_PASSWORD') ?: "TuClaveSuperSecreta@2024"
        );

         $passwordDecrypt = AesDecryptor::decrypt(
            $requestData['password'],
            getenv('ENCRYPTION_PASSWORD') ?: "TuClaveSuperSecreta@2024"
        );
        
         $emailEncrypted = SecurityHelper::encryptData($emailDecrypt, $this->encryptionKey, $this->iv);

        // Buscar usuario
        $this->userModel->load(['email = ?', $emailEncrypted]);

        if (!$this->userModel->loaded()) {
            throw new RuntimeException('Credenciales inválidas.', 401);
        }

        // Validar Usuario
        $usuarios = $this->userModel->find();
        $usuarioEncontrado = null;

        foreach ($usuarios as $usuario) {
            try {
                $emailBD = SecurityHelper::decryptData($usuario->email, $this->encryptionKey, $this->iv);
                if ($emailBD === $emailDecrypt) {
                    $usuarioEncontrado = $usuario;
                    break;
                }
            } catch (Exception $e) {
                continue;
            }
        }

        if (!$usuarioEncontrado) {
            throw new RuntimeException('Credenciales inválidas.', 401);
        }
        // Validar contraseña
        if (!password_verify($passwordDecrypt, $usuarioEncontrado->password)) {
            throw new RuntimeException('Credenciales inválidas.', 401);
        }
        
        $this->userModel->reset();
        $this->userModel->set('email', $emailEncrypted);
        $this->userModel->set('password', password_hash($requestData['password'], PASSWORD_BCRYPT));
        
         // Generar token
        $token = JwtHelper::generateToken([
        'data' => [
        'user_id' => $usuarioEncontrado->id,  // 👈 usar $usuarioEncontrado
        'email' => $emailDecrypt
        ]
    ], $this->jwtKey);

        // Guardar sesión
        SessionHelper::createSession($f3, (int)$usuarioEncontrado->id, $token);

        // Respuesta
        $this->successResponse([
            'token' => $token,
            'id' => $usuarioEncontrado->id,        // 👈 también aquí
            'name' => $usuarioEncontrado->nombre,  // 👈 usar nombre del encontrado
            'email' => $emailDecrypt
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
    
    $body = json_decode($f3->get('BODY'), true);
    $userId = $body['id'] ?? null;
    
    try {
        if (!$userId) {
            throw new RuntimeException('ID no proporcionado', 400);
        }

        $this->userModel->load(['id = ?', $userId]);

        if (!$this->userModel->loaded()) {
            throw new RuntimeException('Usuario no encontrado', 404);
        }

        $db = $f3->get('DB');
        $db->exec("DELETE FROM sesiones WHERE user_id = ?", [$userId]);

        $this->userModel->erase();

        $this->successResponse(['id' => $userId], 'Usuario eliminado correctamente');
    } catch (Exception $e) {
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
                
                $userData['email'] = SecurityHelper::decryptData(
                    $userData['email'],
                    $this->encryptionKey,
                    $this->iv
                );
            } catch (Exception $e) {
                $userData['email'] = 'Error al desencriptar';
            }
           
            $userData['password'];
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



    
    protected function validateToken($f3)
        {
            try {
                $token = JwtHelper::getBearerToken();
                $decoded = JwtHelper::validateToken($token, $this->jwtKey);
            
                if (!isset($decoded->data->user_id)) {
                    throw new RuntimeException('Token inválido: user_id no presente', 401);
                }
            
                SessionHelper::verifySession($f3, $decoded->data->user_id, $token);
                $f3->set('user_id', $decoded->data->user_id);
            
            } catch (Exception $e) {
                $this->handleError($e);
            }
        }


    
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
    protected function prepareNewUser(array $data, string $email, string $password): void
    {
        $this->userModel->reset();
        $this->userModel->set('nombre', SecurityHelper::sanitizeInput($data['nombre']));
        $this->userModel->set('apellidos', SecurityHelper::sanitizeInput($data['apellidos']));
        $this->userModel->set('email', SecurityHelper::encryptData($email, $this->encryptionKey, $this->iv));
        $this->userModel->set('password', password_hash($password, PASSWORD_BCRYPT));
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

    try {
        $userData['email'] = AesDecryptor::decrypt(
            $userData['email'],
            getenv('ENCRYPTION_PASSWORD') ?: "TuClaveSuperSecreta@2024"
        );
    } catch (Exception $e) {
        $userData['email'] = 'Error al desencriptar';
    }

    unset($userData['password']); // Nunca devolver la contraseña
    return $userData;
}

}