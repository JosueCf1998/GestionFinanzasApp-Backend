<?php

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

require_once 'BaseController.php';

class usuario_controllers extends BaseController
{
    public $m_user = null;
    private $jwt_key = "$#Gre1410#$"; 
    private $secret_key = '$#Gre1410'; // Cambia esto por una segura
    private $cipher = 'AES-256-CBC'; // Método de cifrado
    private $iv = '1234567890123456'; // Vector de inicialización (16 caracteres)


    public function __construct()
    {
        $this->m_user = new m_usuarios();
    }

    public function crear($f3)
    {
        
        $body = json_decode($f3->get('BODY'), true);
        $email_encriptado = openssl_encrypt($body['email'], $this->cipher, $this->secret_key, 0, $this->iv);

        $this->m_user->load(['nombre = ? OR email = ?', $body['nombre'], $body['email']]);
    
        if ($this->m_user->loaded() > 0) {
            $this->errorResponse(
                'Ya existe un Usuario con el nombre o correo que intenta registrar',
                409,
                ['id' => 0]
            );
            return;
        }
    
        
        $this->m_user->set('nombre', $body['nombre']);
        $this->m_user->set('apellidos', $body['apellidos']);
        $this->m_user->set('email', $email_encriptado);

            
        $password_hash = password_hash($body['password'], PASSWORD_DEFAULT);
        $this->m_user->set('password', $password_hash);
        
        date_default_timezone_set('America/Lima');
        $this->m_user->set('fecha_registro', date('Y-m-d H:i:s'));

    
        
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
          $body = json_decode($f3->get('BODY'), true);
      
          $email = $body['email'] ?? null;
          $password = $body['password'] ?? null;
      
          $email_encriptado = openssl_encrypt($email, $this->cipher, $this->secret_key, 0, $this->iv);
      
          $this->m_user->load(['email = ?', $email_encriptado]);
      
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
      
              $info = $this->m_user->cast();
      
              $info['email'] = openssl_decrypt($info['email'], $this->cipher, $this->secret_key, 0, $this->iv);
      
              $this->successResponse([
                  'mensaje' => 'Login exitoso',
                  // 'token' => $token, // descomenta si usas JWT
                  'info' => $info
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
          $user_id = $f3->get('PARAMS.user_id');
          $this->m_user->load(['id = ?', $user_id]);
      
          if ($this->m_user->loaded()) {
              $data = $this->m_user->cast();
      
              
              $data['email'] = openssl_decrypt($data['email'], $this->cipher, $this->secret_key, 0, $this->iv);
      
      
              $this->successResponse([
                  'mensaje' => 'Usuario encontrado',
                  'info' => ['items' => $data]
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
         // Leer cuerpo JSON
         $body = json_decode($f3->get('BODY'), true);
         $user_id = $body['user_id'];
     
         // Cargar el usuario por ID
         $this->m_user->load(['id = ?', $user_id]);
     
         if ($this->m_user->loaded()) {
             $this->m_user->erase();
     
             $this->successResponse([
                 'mensaje' => 'Usuario eliminado',
                 'info' => ['id' => $user_id]
             ]);
         } else {
             $this->errorResponse('Usuario no encontrado', 404);
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
      
          // Actualizar nombre y apellidos si se envían
          if (!empty($body['nombre'])) {
              $this->m_user->set('nombre', $body['nombre']);
          }
      
          if (!empty($body['apellidos'])) {
              $this->m_user->set('apellidos', $body['apellidos']);
          }
      
          // Encriptar y guardar email si se envía
          if (!empty($body['email'])) {
              $email_encriptado = openssl_encrypt($body['email'], $this->cipher, $this->secret_key, 0, $this->iv);
              $this->m_user->set('email', $email_encriptado);
          }
      
          // Verificar si se quiere actualizar la contraseña
          if (!empty($body['password'])) {
              // Verificar que no sea igual a la actual
              if (password_verify($body['password'], $this->m_user->password)) {
                  $this->errorResponse('La nueva contraseña no puede ser igual a la anterior', 400);
                  return;
              }
      
              // Hashear y guardar
              $password_hash = password_hash($body['password'], PASSWORD_DEFAULT);
              $this->m_user->set('password', $password_hash);
          }
      
          // Guardar cambios
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
