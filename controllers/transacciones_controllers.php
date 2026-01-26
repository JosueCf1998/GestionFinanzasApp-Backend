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
        $token = JwtHelper::getBearerToken();
        $decoded = JwtHelper::validateToken($token, $this->jwtKey); 
        $body = json_decode($f3->get('BODY'), true);

        $this->m_transaccion->set('usuario_id', $decoded->data->user_id);
        $this->m_transaccion->set('categoria_id', $body['categoria_id']);
        $this->m_transaccion->set('cuenta_id', $body['cuenta_id']);
        $this->m_transaccion->set('fecha', $body['fecha']);
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
        $token = JwtHelper::getBearerToken();
        $decoded = JwtHelper::validateToken($token, $this->jwtKey);
        $body = json_decode($f3->get('BODY'), true);
        $transac_id = $body['transac_id'];
        // Solo puede actualizar si es dueño
        $this->m_transaccion->load(['id = ? AND usuario_id = ?', $transac_id, $decoded->data->user_id]);

        if (!$this->m_transaccion->loaded()) {
            $this->errorResponse('No tienes permiso para actualizar esta transacción o no existe', 403);
            return;
        }

        $this->m_transaccion->set('categoria_id', $body['categoria_id']);
        $this->m_transaccion->set('cuenta_id', $body['cuenta_id']);
        $this->m_transaccion->set('fecha', $body['fecha']);
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
        $token = JwtHelper::getBearerToken();
        $decoded = JwtHelper::validateToken($token, $this->jwtKey);
        $body = json_decode($f3->get('BODY'), true);
        $transac_id = $body['transac_id'];

        // Solo puede eliminar si es dueño
        $this->m_transaccion->load(['id = ? AND usuario_id = ?', $transac_id, $decoded->data->user_id]);

        if ($this->m_transaccion->loaded()) {
            $this->m_transaccion->erase();
            $this->successResponse([
                'mensaje' => 'Transacción eliminada',
                'info' => ['id' => $transac_id]
            ]);
        } else {
            $this->errorResponse('No tienes permiso para eliminar esta transacción o no existe', 403);
        }
    }

    public function listado($f3)
    {
        $token = JwtHelper::getBearerToken();
        $decoded = JwtHelper::validateToken($token, $this->jwtKey);
    
        $result = $this->m_transaccion->find(['usuario_id = ?', $decoded->data->user_id]);
        $items = [];
    
        foreach ($result as $transaccion) {
            $items[] = $transaccion->cast();
        }
    
        $this->successResponse([
            'items' => $items,
            'Total' => count($items),
            'mensaje' => count($items) > 0 
                ? 'Listado obtenido correctamente' 
                : 'No hay registros que mostrar'
        ]);
    }

}