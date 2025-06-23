<?php

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

require_once 'BaseController.php';

class usuario_controllers extends BaseController
{
    public $m_user = null;
    private $jwt_key = "$#Gre1410#$"; 
    private $secret_key = '$#Gre1410'; 
    private $cipher = 'AES-256-CBC'; 
    private $iv = '1234567890123456'; 


    public function __construct()
    {
        $this->m_user = new m_usuarios();
    }

    public function crear($f3)
    {
        
        $body = json_decode($f3->get('BODY'), true);
        $email_encriptado = openssl_encrypt($body['email'], $this->cipher, $this->secret_key, 0, $this->iv);

        $this->m_user->load(['nombre = ? OR email = ?', $body['nombre'], $email_encriptado]);
    
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
              
               
                $email_desencriptado = openssl_decrypt($this->m_user->email, $this->cipher, $this->secret_key, 0, $this->iv);
                
                
                $payload = [
                    'iat' => time(),
                    'exp' => time() + (5 * 60), // Vida máxima
                    'data' => [
                        'user_id' => $this->m_user->id,
                        'email' => $email_desencriptado
                    ]
                ];
                $token = JWT::encode($payload, $this->jwt_key, 'HS256');
                
                $db = \Base::instance()->get('DB');
                $db->exec("SET time_zone = '-05:00'");
                date_default_timezone_set('America/Lima');
                $ahora = date('Y-m-d H:i:s');
                            
                $db->exec(
                    "INSERT INTO sesiones (user_id, token, ultimo_uso, creado_en) VALUES (?, ?, ?, ?)",
                    [$this->m_user->id, $token, $ahora, $ahora]
                );

            
              $info = $this->m_user->cast();      
              $info['email'] = openssl_decrypt($info['email'], $this->cipher, $this->secret_key, 0, $this->iv);
      
              $this->successResponse([
                  'mensaje' => 'Login exitoso',
                   'token' => $token, 
                  'info' => $info
              ]);
          } else {
              $this->errorResponse('Credenciales incorrectas', 401, ['info' => []]);
          }
      }




    
    private function validarToken($f3)
     {
         $headers = getallheaders();
         $token = null;
     
         
         if (isset($headers['Authorization']) && preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
             $token = $matches[1];
         }
     
        
         if (!$token) {
             $body = json_decode($f3->get('BODY'), true);
             $token = $body['token'] ?? null;
         }
     
         if (!$token) {
             echo json_encode(['mensaje' => 'Token no proporcionado']);
             http_response_code(401);
             exit;
         }
     
         try {
             
             $decoded = JWT::decode($token, new Key($this->jwt_key, 'HS256'));
             $user_id = $decoded->data->user_id;
     
            
             $db = \Base::instance()->get('DB');
             $db->exec("SET time_zone = '-05:00'");
             $sesion = $db->exec("SELECT * FROM sesiones WHERE user_id = ? AND token = ?", [$user_id, $token]);
     
             if (count($sesion) === 0) {
                 echo json_encode(['mensaje' => 'Sesión no encontrada']);
                 http_response_code(401);
                 exit;
             }
                  
             date_default_timezone_set('America/Lima');
             $ultimoUso = strtotime($sesion[0]['ultimo_uso']);
             $ahora = time();
             date_default_timezone_set('America/Lima');

     
             if (($ahora - $ultimoUso) > (5 * 60)) {
                 
                 $db->exec("DELETE FROM sesiones WHERE user_id = ? AND token = ?", [$user_id, $token]);
                 echo json_encode(['mensaje' => 'Sesión expirada por inactividad']);
                 http_response_code(401);
                 exit;
             }
     
             
             $ahora = date('Y-m-d H:i:s');
             $db->exec("UPDATE sesiones SET ultimo_uso = ? WHERE user_id = ? AND token = ?", [$ahora, $user_id, $token]);

     
            
             $f3->set('user_id', $user_id);
     
         } catch (Exception $e) {
             echo json_encode(['mensaje' => 'Token inválido o expirado']);
             http_response_code(403);
             exit;
         }
     }


      public function perfilProtegido($f3)
       {
           $this->validarToken($f3); 
       
           $user_id = $f3->get('user_id'); 
           $this->m_user->load(['id = ?', $user_id]);
       
           if ($this->m_user->loaded()) {
               $info = $this->m_user->cast();
               $info['email'] = openssl_decrypt($info['email'], $this->cipher, $this->secret_key, 0, $this->iv);
       
               $this->successResponse([
                   'mensaje' => 'Acceso autorizado al perfil',
                   'info' => $info
               ]);
           } else {
               $this->errorResponse('Usuario no encontrado', 404);
           }
       }


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
         $this->validarToken($f3);
         $body = json_decode($f3->get('BODY'), true);
         $user_id = $body['user_id'];
     
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
          $this->validarToken($f3);

          $user_id = $f3->get('PARAMS.user_id');
          $this->m_user->load(['id = ?', $user_id]);
      
          if (!$this->m_user->loaded()) {
              $this->errorResponse('Usuario no encontrado', 404);
              return;
          }
      
          $body = json_decode($f3->get('BODY'), true);
      
          if (!empty($body['nombre'])) {
              $this->m_user->set('nombre', $body['nombre']);
          }
      
          if (!empty($body['apellidos'])) {
              $this->m_user->set('apellidos', $body['apellidos']);
          }
      
          if (!empty($body['email'])) {
              $email_encriptado = openssl_encrypt($body['email'], $this->cipher, $this->secret_key, 0, $this->iv);
              $this->m_user->set('email', $email_encriptado);
          }
          
          if (!empty($body['password'])) {
              if (password_verify($body['password'], $this->m_user->password)) {
                  $this->errorResponse('La nueva contraseña no puede ser igual a la anterior', 400);
                  return;
              }
      
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
        $this->validarToken($f3);
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
