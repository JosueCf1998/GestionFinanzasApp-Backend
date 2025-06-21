<?php

require_once 'BaseController.php';

class transferencias_controllers extends BaseController
{
    public $m_transferencia = null;
    public function __construct()
    {
        $this->m_transferencia = new m_transferencias();
    }
    public function crear($f3)
      {
          // $this->validarToken($f3); // Descomenta si estás usando autenticación
      
          // Leer cuerpo JSON
          $body = json_decode($f3->get('BODY'), true);
      
          // Asignar valores al modelo
          $this->m_transferencia->set('cuenta_id', $body['cuenta_id']);
          $this->m_transferencia->set('tipo', $body['tipo']);
          $this->m_transferencia->set('cuenta_origen', $body['cuenta_origen']);
          $this->m_transferencia->set('cuenta_destino', $body['cuenta_destino']);
      
          // Guardar y responder
          if ($this->m_transferencia->save()) {
              $this->successResponse([
                  'mensaje' => 'Transferencia creada correctamente',
                  'info' => [
                      'id' => $this->m_transferencia->get('id')
                  ]
              ]);
          } else {
              $this->errorResponse('No se pudo crear la transferencia', 500);
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


    public function consultar($f3)
     {
         $transf_id = $f3->get('PARAMS.transf_id');
         $this->m_transferencia->load(['id = ?', $transf_id]);
     
         if ($this->m_transferencia->loaded() > 0) {
             $this->successResponse([
                 'mensaje' => 'Transferencia encontrada',
                 'info' => [
                     'items' => $this->m_transferencia->cast()
                 ]
             ]);
         } else {
             $this->errorResponse(
                 'Transferencia no encontrada',
                 404,
                 ['items' => []]
             );
         }
     }

    public function eliminar($f3)
      {
          // Leer cuerpo como JSON
          $body = json_decode($f3->get('BODY'), true);
          $transf_id = $body['transf_id'];
      
          // Buscar la transferencia
          $this->m_transferencia->load(['id = ?', $transf_id]);
      
          if ($this->m_transferencia->loaded()) {
              $this->m_transferencia->erase();
              $this->successResponse([
                  'mensaje' => 'Transferencia eliminada',
                  'info' => ['id' => $transf_id]
              ]);
          } else {
              $this->errorResponse('Transferencia no encontrada', 404);
          }
      }


    public function actualizar($f3)
      {
          $transf_id = $f3->get('PARAMS.transf_id');
          $this->m_transferencia->load(['id = ?', $transf_id]);
      
          if (!$this->m_transferencia->loaded()) {
              $this->errorResponse('Transferencia no encontrada', 404);
              return;
          }
      
          // Leer cuerpo JSON
          $body = json_decode($f3->get('BODY'), true);
      
          // Validar si existe otra transferencia con el mismo tipo
          $_transferencia = new m_transferencias();
          $_transferencia->load(['tipo = ? AND id <> ?', $body['tipo'], $transf_id]);
      
          if ($_transferencia->loaded()) {
              $this->errorResponse(
                  'Registro no se pudo modificar debido a que el tipo se encuentra en uso por otra transferencia',
                  409
              );
              return;
          }
      
          // Asignar nuevos valores
          $this->m_transferencia->set('cuenta_id', $body['cuenta_id']);
          $this->m_transferencia->set('tipo', $body['tipo']);
          $this->m_transferencia->set('cuenta_origen', $body['cuenta_origen']);
          $this->m_transferencia->set('cuenta_destino', $body['cuenta_destino']);
      
          $this->m_transferencia->save();
      
          $this->successResponse([
              'mensaje' => 'Transferencia actualizada',
              'info' => ['id' => $this->m_transferencia->get('id')]
          ]);
      }


    public function listado($f3)
    {
        $result = $this->m_transferencia->find();
        $items = [];
        foreach ($result as $transferencia) {
            $items[] = $transferencia->cast();
        }
        if (count($items) > 0) {
         $this->successResponse(['items' => $items, 'Total' => count($items)]);
     } else {
         $this->errorResponse('Aún no hay registros que mostrar', 404, ['items' => [], 'Total' => 0]);
     }
    }
}
