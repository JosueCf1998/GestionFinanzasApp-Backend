<?php

require_once 'BaseController.php';

class cuentas_controllers extends BaseController
{
    public $m_cuenta = null;
    public function __construct()
    {
        $this->m_cuenta = new m_cuentas();
    }
    public function crear($f3)
      {
          // $this->validarToken($f3); // Descomenta si estás usando autenticación
      
          // Leer cuerpo JSON
          $body = json_decode($f3->get('BODY'), true);
      
          // Asignar valores al modelo
          $this->m_cuenta->set('usuario_id', $body['usuario_id']);
          $this->m_cuenta->set('nombre', $body['nombre']);
          $this->m_cuenta->set('saldo', $body['saldo']);
      
          // Guardar y responder
          if ($this->m_cuenta->save()) {
              $this->successResponse([
                  'mensaje' => 'Cuenta creada correctamente',
                  'info' => [
                      'id' => $this->m_cuenta->get('id')
                  ]
              ]);
          } else {
              $this->errorResponse('No se pudo crear la cuenta', 500);
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
    //             $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key('$#Gre1410#$', 'HS256'));
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

    public function actualizar($f3)
      {
          $cuenta_id = $f3->get('PARAMS.cuenta_id');
          $this->m_cuenta->load(['id = ?', $cuenta_id]);
      
          if (!$this->m_cuenta->loaded()) {
              $this->errorResponse('Cuenta no encontrada', 404);
              return;
          }
      
          // Leer cuerpo como JSON
          $body = json_decode($f3->get('BODY'), true);
      
          // Verificar si hay otra cuenta con el mismo nombre
          $_cuenta = new m_cuentas();
          $_cuenta->load(['nombre = ? AND id <> ?', $body['nombre'], $cuenta_id]);
      
          if ($_cuenta->loaded()) {
              $this->errorResponse(
                  'Registro no se pudo modificar debido a que el nombre se encuentra en uso por otra cuenta',
                  409
              );
              return;
          }
      
          // Actualizar datos
          $this->m_cuenta->set('usuario_id', $body['usuario_id']);
          $this->m_cuenta->set('nombre', $body['nombre']);
          $this->m_cuenta->set('saldo', $body['saldo']);
          $this->m_cuenta->save();
      
          $this->successResponse([
              'mensaje' => 'Cuenta actualizada',
              'info' => ['id' => $this->m_cuenta->get('id')]
          ]);
      }



    public function consultar($f3)
     {
         $cuenta_id = $f3->get('PARAMS.cuenta_id');
         $this->m_cuenta->load(['id = ?', $cuenta_id]);
     
         if ($this->m_cuenta->loaded() > 0) {
             $this->successResponse([
                 'mensaje' => 'Cuenta encontrada',
                 'info' => [
                     'items' => $this->m_cuenta->cast()
                 ]
             ]);
         } else {
             $this->errorResponse(
                 'Cuenta no encontrada',
                 404,
                 ['items' => []]
             );
         }
     }

    public function eliminar($f3)
      {
          // Leer cuerpo como JSON
          $body = json_decode($f3->get('BODY'), true);
          $cuenta_id = $body['cuenta_id'];
      
          // Buscar la cuenta
          $this->m_cuenta->load(['id = ?', $cuenta_id]);
      
          if ($this->m_cuenta->loaded()) {
              $this->m_cuenta->erase();
              $this->successResponse([
                  'mensaje' => 'Cuenta eliminada',
                  'info' => ['id' => $cuenta_id]
              ]);
          } else {
              $this->errorResponse('Cuenta no encontrada', 404);
          }
      }


    public function listado($f3)
    {
        $result = $this->m_cuenta->find();
        $items = [];
        foreach ($result as $cuenta) {
            $items[] = $cuenta->cast();
        }
        if (count($items) > 0) {
         $this->successResponse(['items' => $items, 'Total' => count($items)]);
     } else {
         $this->errorResponse('Aún no hay registros que mostrar', 404, ['items' => [], 'Total' => 0]);
     }
    }
}
