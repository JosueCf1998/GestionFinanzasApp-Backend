<?php

require_once 'BaseController.php';

class transacciones_controllers extends BaseController
{
    public $m_transaccion = null;
    public function __construct()
    {
        $this->m_transaccion = new m_transacciones();
    }
    
    public function crear($f3)
      {
         $this->validarToken($f3); 
      
          
          $body = json_decode($f3->get('BODY'), true);
      
          
          $this->m_transaccion->set('categoria_id', $body['categoria_id']);
          $this->m_transaccion->set('cuenta_id', $body['cuenta_id']);
          $this->m_transaccion->set('monto', $body['monto']);
          $this->m_transaccion->set('tipo', $body['tipo']);
          $this->m_transaccion->set('descripcion', $body['descripcion']);
          $this->m_transaccion->set('fecha_registro', $body['fecha_registro']);
      
          
          if ($this->m_transaccion->save()) {
              $this->successResponse([
                  'mensaje' => 'Transacción creada correctamente',
                  'info' => [
                      'id' => $this->m_transaccion->get('id')
                  ]
              ]);
          } else {
              $this->errorResponse('No se pudo crear la transacción', 500);
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
                 $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key('$#Gre1410#$', 'HS256'));
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

    public function actualizar($f3)
      {
          $transac_id = $f3->get('PARAMS.transac_id');
          $this->m_transaccion->load(['id = ?', $transac_id]);
      
          if (!$this->m_transaccion->loaded()) {
              $this->errorResponse('Transacción no encontrada', 404);
              return;
          }
      
          
          $body = json_decode($f3->get('BODY'), true);
      
          
          $_transac = new m_transacciones();
          $_transac->load(['tipo = ? AND id <> ?', $body['tipo'], $transac_id]);
      
          if ($_transac->loaded()) {
              $this->errorResponse(
                  'Registro no se pudo modificar debido a que el tipo se encuentra en uso por otra transacción',
                  409
              );
              return;
          }
      
          
          $this->m_transaccion->set('categoria_id', $body['categoria_id']);
          $this->m_transaccion->set('cuenta_id', $body['cuenta_id']);
          $this->m_transaccion->set('monto', $body['monto']);
          $this->m_transaccion->set('tipo', $body['tipo']);
          $this->m_transaccion->set('descripcion', $body['descripcion']);
          $this->m_transaccion->set('fecha_registro', $body['fecha_registro']);
      
          
          $this->m_transaccion->save();
      
          $this->successResponse([
              'mensaje' => 'Transacción actualizada',
              'info' => ['id' => $this->m_transaccion->get('id')]
          ]);
      }



    public function consultar($f3)
     {
         $transac_id = $f3->get('PARAMS.transac_id');
         $this->m_transaccion->load(['id = ?', $transac_id]);
     
         if ($this->m_transaccion->loaded() > 0) {
             $this->successResponse([
                 'mensaje' => 'Transacción encontrada',
                 'info' => [
                     'items' => $this->m_transaccion->cast()
                 ]
             ]);
         } else {
             $this->errorResponse(
                 'Transacción no encontrada',
                 404,
                 ['items' => []]
             );
         }
     }

    public function eliminar($f3)
      {
          $this->validarToken($f3);
          $body = json_decode($f3->get('BODY'), true);
          $transac_id = $body['transac_id'];
      
          
          $this->m_transaccion->load(['id = ?', $transac_id]);
      
          if ($this->m_transaccion->loaded()) {
              $this->m_transaccion->erase();
              $this->successResponse([
                  'mensaje' => 'Transacción eliminada',
                  'info' => ['id' => $transac_id]
              ]);
          } else {
              $this->errorResponse('Transacción no encontrada', 404);
          }
      }


    public function listado($f3)
    {
        $this->validarToken($f3);
        $result = $this->m_transaccion->find();
        $items = [];
        foreach ($result as $transaccion) {
            $items[] = $transaccion->cast();
        }
        if (count($items) > 0) {
         $this->successResponse(['items' => $items, 'Total' => count($items)]);
     } else {
         $this->errorResponse('Aún no hay registros que mostrar', 404, ['items' => [], 'Total' => 0]);
     }
    }
    
}
