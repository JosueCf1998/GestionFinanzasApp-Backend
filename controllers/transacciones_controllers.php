<?php

require_once 'BaseController.php';
require_once 'helpers/ResponseHelper.php';
require_once 'helpers/SecurityHelper.php';
require_once 'helpers/JwtHelper.php';
require_once 'helpers/SessionHelper.php';
require_once 'helpers/AesDecryptor.php';


class transacciones_controllers extends BaseController
{
    protected $m_transaccion;
    private $jwtKey;

    public function __construct()
    {
        $this->m_transaccion = new m_transacciones();
        $this->jwtKey = getenv('JWT_SECRET') ?: '$#Gre1410#$';
    }
    
    public function crear($f3)
      {
         $token = JwtHelper::getBearerToken($f3);
         $decoded = JwtHelper::validateToken($token, $this->jwtKey); 
      
          
          $body = json_decode($f3->get('BODY'), true);
      
          
          $this->m_transaccion->set('categoria_id', $body['categoria_id']);
          $this->m_transaccion->set('cuenta_id', $body['cuenta_id']);
          $this->m_transaccion->set('monto', $body['monto']);
          $this->m_transaccion->set('tipo', $body['tipo']);
          $this->m_transaccion->set('descripcion', $body['descripcion']);
          
      
          
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
      

    public function actualizar($f3)
      {
        $token = JwtHelper::getBearerToken($f3);
        $decoded = JwtHelper::validateToken($token, $this->jwtKey);
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
      
          
          $this->m_transaccion->save();
      
          $this->successResponse([
              'mensaje' => 'Transacción actualizada',
              'info' => ['id' => $this->m_transaccion->get('id')]
          ]);
      }




    public function eliminar($f3)
      {
          $token = JwtHelper::getBearerToken($f3);
          $decoded = JwtHelper::validateToken($token, $this->jwtKey);
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
        $token = JwtHelper::getBearerToken($f3);
        $decoded = JwtHelper::validateToken($token, $this->jwtKey);
        
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
