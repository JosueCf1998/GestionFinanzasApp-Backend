<?php

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

require_once 'BaseController.php';

class usuario_controllers extends BaseController
{
    public $m_user = null;
    private $jwt_key = "$#Gre1410#$"; // Cámbiala por algo seguro

    public function __construct()
    {
        $this->m_user = new m_usuarios();
    }

    public function crear($f3)
    {
        
        $body = json_decode($f3->get('BODY'), true);
    
        // Validar existencia por nombre o email
        $this->m_user->load(['nombre = ? OR email = ?', $body['nombre'], $body['email']]);
    
        if ($this->m_user->loaded() > 0) {
            $this->errorResponse(
                'Ya existe un Usuario con el nombre o correo que intenta registrar',
                409, // Código HTTP para conflicto
                ['id' => 0]
            );
            return;
        }
    
        // Asignar valores del JSON al modelo
        $this->m_user->set('nombre', $body['nombre']);
        $this->m_user->set('apellidos', $body['apellidos']);
        $this->m_user->set('email', $body['email']);
    
        // Encriptar contraseña
        $password_hash = password_hash($body['password'], PASSWORD_DEFAULT);
        $this->m_user->set('password', $password_hash);
    
        // Establecer fecha actual
        $this->m_user->set('fecha_registro', date('Y-m-d H:i:s'));
    
        // Guardar usuario
        if ($this->m_user->save()) {
            $this->successResponse([
                'mensaje' => 'Usuario creado correctamente',
                'info' => ['id' => $this->m_user->get('id')]
            ]);
        } else {
            $this->errorResponse('No se pudo crear el usuario', 500);
        }
    }



    public function login($f3)
     {
         // Leer el cuerpo JSON
         $body = json_decode($f3->get('BODY'), true);
     
         $email = $body['email'] ?? null;
         $password = $body['password'] ?? null;
     
         // Buscar usuario por email
         $this->m_user->load(['email = ?', $email]);
     
         if ($this->m_user->loaded() && password_verify($password, $this->m_user->password)) {
             // Aquí podrías generar el token JWT si deseas usarlo
             // $payload = [
             //     'iat' => time(),
             //     'exp' => time() + (60 * 60), // 1 hora
             //     'data' => [
             //         'user_id' => $this->m_user->id,
             //         'email' => $this->m_user->email
             //     ]
             // ];
             // $token = JWT::encode($payload, $this->jwt_key, 'HS256');
     
             $this->successResponse([
                 'mensaje' => 'Login exitoso',
                 // 'token' => $token,
                 'info' => $this->m_user->cast()
             ]);
         } else {
             $this->errorResponse('Credenciales incorrectas', 401, ['info' => []]);
         }
     }



    // private function validarToken($f3)
    // {
    //     $headers = getallheaders();
    //     if (!isset($headers['Authorization'])) {
    //         echo json_encode(['mensaje' => 'Token no proporcionado']);
    //         http_response_code(401);
    //         exit;
    //     }

    //     if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
    //         $token = $matches[1];
    //         try {
    //             $decoded = JWT::decode($token, new Key($this->jwt_key, 'HS256'));
    //             // Puedes pasar el usuario al $f3 si lo deseas
    //             $f3->set('user_id', $decoded->data->user_id);
    //         } catch (Exception $e) {
    //             echo json_encode(['mensaje' => 'Token inválido o expirado']);
    //             http_response_code(403);
    //             exit;
    //         }
    //     } else {
    //         echo json_encode(['mensaje' => 'Formato de token inválido']);
    //         http_response_code(400);
    //         exit;
    //     }
    // }

    public function consultar($f3)
     {
         // $this->validarToken($f3); // Descomenta si usas autenticación
     
         $user_id = $f3->get('PARAMS.user_id');
         $this->m_user->load(['id = ?', $user_id]);
     
         if ($this->m_user->loaded() > 0) {
             $this->successResponse([
                 'mensaje' => 'Usuario encontrado',
                 'info' => ['items' => $this->m_user->cast()]
             ]);
         } else {
             $this->errorResponse(
                 'Usuario no encontrado',
                 404,
                 ['items' => []]
             );
         }
     }


    public function eliminar($f3)
     {
         $user_id = $f3->get('POST.user_id');
         $this->m_user->load(['id = ?', $user_id]);
     
         if ($this->m_user->loaded() > 0) {
             $this->m_user->erase();
             $this->successResponse([
                 'mensaje' => 'Usuario eliminado',
                 'info' => ['id' => $user_id]
             ]);
         } else {
             $this->errorResponse(
                 'Usuario no encontrado',
                 404
             );
         }
     }


    public function actualizar($f3)
     {
         $user_id = $f3->get('PARAMS.user_id');
         $this->m_user->load(['id = ?', $user_id]);
     
         if (!$this->m_user->loaded()) {
             $this->errorResponse('Usuario no encontrado', 404);
             return;
         }
     
         // Leer cuerpo como JSON
         $body = json_decode($f3->get('BODY'), true);
     
         // Verificar si otro usuario tiene el mismo nombre o email
         $_user = new m_usuarios();
         $_user->load(['(nombre = ? OR email = ?) AND id != ?', $body['nombre'], $body['email'], $user_id]);
     
         if ($_user->loaded()) {
             $this->errorResponse('El correo o nombre ya está en uso por otro usuario', 409);
             return;
         }
     
         // Asignar datos nuevos
         $this->m_user->set('nombre', $body['nombre']);
         $this->m_user->set('apellidos', $body['apellidos']);
         $this->m_user->set('email', $body['email']);
     
         // Si viene contraseña, actualizarla
         if (!empty($body['password'])) {
             $password_hash = password_hash($body['password'], PASSWORD_DEFAULT);
             $this->m_user->set('password', $password_hash);
         }
     
         $this->m_user->save();
     
         $this->successResponse([
             'mensaje' => 'Usuario actualizado',
             'info' => ['id' => $this->m_user->get('id')]
         ]);
     }



    public function listado($f3)
    {
        // $this->validarToken($f3);
        $result = $this->m_user->find();
        $items = [];
        foreach ($result as $user) {
            $items[] = $user->cast();
        }
      
        if (count($items) > 0) {
         $this->successResponse(['items' => $items, 'Total' => count($items)]);
     } else {
         $this->errorResponse('Aún no hay registros que mostrar', 404, ['items' => [], 'Total' => 0]);
     }
    }
}
