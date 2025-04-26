<?php

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class usuario_controllers
{
    public $m_user = null;
    private $jwt_key = "$#Gre1410#$"; // Cámbiala por algo seguro

    public function __construct()
    {
        $this->m_user = new m_usuarios();
    }

    public function crear($f3)
    {
        $this->m_user->load(['nombre = ? or email = ?', $f3->get('POST.nombre'), $f3->get('POST.email')]);
        if ($this->m_user->loaded() > 0) {
            echo json_encode([
                'mensaje' => 'Ya existe un Usuario con el nombre o correo que intenta registrar',
                'info' => ['id' => 0]
            ]);
            return;
        }

        $this->m_user->set('nombre', $f3->get('POST.nombre'));
        $this->m_user->set('apellidos', $f3->get('POST.apellidos'));
        $this->m_user->set('email', $f3->get('POST.email'));
        $password_hash = password_hash($f3->get('POST.password'), PASSWORD_DEFAULT);
        $this->m_user->set('password', $password_hash);
        $this->m_user->set('fecha_registro', date('Y-m-d H:i:s'));
        $this->m_user->save();

        echo json_encode([
            'mensaje' => 'Usuario creado',
            'info' => ['id' => $this->m_user->get('id')]
        ]);
    }

    public function login($f3)
    {
        $email = $f3->get('POST.email');
        $password = $f3->get('POST.password');

        $this->m_user->load(['email = ?', $email]);

        if ($this->m_user->loaded() > 0 && password_verify($password, $this->m_user->password)) {
            // Creamos el token JWT
            $payload = [
                'iat' => time(),
                'exp' => time() + (60 * 60), // 1 hora
                'data' => [
                    'user_id' => $this->m_user->id,
                    'email' => $this->m_user->email
                ]
            ];
            $token = JWT::encode($payload, $this->jwt_key, 'HS256');

            echo json_encode([
                'mensaje' => 'Login exitoso',
                'token' => $token,
                'info' => $this->m_user->cast()
            ]);
        } else {
            echo json_encode([
                'mensaje' => 'Credenciales incorrectas',
                'info' => []
            ]);
        }
    }

    private function validarToken($f3)
    {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            echo json_encode(['mensaje' => 'Token no proporcionado']);
            http_response_code(401);
            exit;
        }

        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            $token = $matches[1];
            try {
                $decoded = JWT::decode($token, new Key($this->jwt_key, 'HS256'));
                // Puedes pasar el usuario al $f3 si lo deseas
                $f3->set('user_id', $decoded->data->user_id);
            } catch (Exception $e) {
                echo json_encode(['mensaje' => 'Token inválido o expirado']);
                http_response_code(403);
                exit;
            }
        } else {
            echo json_encode(['mensaje' => 'Formato de token inválido']);
            http_response_code(400);
            exit;
        }
    }

    public function consultar($f3)
    {
        $this->validarToken($f3); // Proteger con token
        $user_id = $f3->get('PARAMS.user_id');
        $this->m_user->load(['id = ?', $user_id]);
        $msg = '';
        $items = array();
        if ($this->m_user->loaded() > 0) {
            $msg = 'Usuario encontrado';
            $items = $this->m_user->cast();
        } else {
            $msg = 'Usuario no encontrado';
        }
        echo json_encode([
            'mensaje' => $msg,
            'info' => ['items' => $items]
        ]);
    }

    public function eliminar($f3)
    {
        $this->validarToken($f3);
        $user_id = $f3->get('POST.user_id');
        $this->m_user->load(['id = ?', $user_id]);
        $msg = '';
        if ($this->m_user->loaded() > 0) {
            $msg = 'Usuario eliminado';
            $this->m_user->erase();
        } else {
            $msg = 'Usuario no encontrado';
        }
        echo json_encode(['mensaje' => $msg, 'info' => []]);
    }

    public function actualizar($f3)
    {
        $this->validarToken($f3);
        $user_id = $f3->get('PARAMS.user_id');
        $this->m_user->load(['id = ?', $user_id]);
        $msg = '';
        if ($this->m_user->loaded() > 0) {
            $_user = new m_usuarios();
            $_user->load(['email = ? AND id <> ?', $f3->get('POST.email'), $user_id]);
            if ($_user->loaded() > 0) {
                $msg = 'El correo ya está en uso por otro usuario';
            } else {
                $this->m_user->set('nombre', $f3->get('POST.nombre'));
                $this->m_user->set('apellidos', $f3->get('POST.apellidos'));
                $this->m_user->set('email', $f3->get('POST.email'));
                $this->m_user->set('password', $f3->get('POST.password')); // Recomendado encriptar si es nuevo
                $this->m_user->set('fecha_registro', date('Y-m-d H:i:s'));
                $this->m_user->save();
                $msg = 'Usuario actualizado';
            }
        } else {
            $msg = 'Usuario no encontrado';
        }
        echo json_encode(['mensaje' => $msg, 'info' => []]);
    }

    public function listado($f3)
    {
        $this->validarToken($f3);
        $result = $this->m_user->find();
        $items = [];
        foreach ($result as $user) {
            $items[] = $user->cast();
        }
        echo json_encode([
            'mensaje' => count($items) > 0 ? '' : 'Aún no hay registros que mostrar',
            'info' => [
                'items' => $items,
                'Total' => count($items),
            ]
        ]);
    }
}
