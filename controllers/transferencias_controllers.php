<?php

require_once 'BaseController.php';
require_once 'helpers/ResponseHelper.php';
require_once 'helpers/SecurityHelper.php';
require_once 'helpers/JwtHelper.php';
require_once 'helpers/SessionHelper.php';
require_once 'helpers/AesDecryptor.php';

class transferencias_controllers extends BaseController
{
    protected $m_transferencia;
    private $jwtKey;

    public function __construct()
    {
        $this->m_transferencia = new m_transferencias();
        $this->jwtKey = getenv('JWT_SECRET') ?: '$#Gre1410#$';
    }

    public function crear($f3)
    {
        $token = JwtHelper::getBearerToken($f3);
        $decoded = JwtHelper::validateToken($token, $this->jwtKey); 
        $body = json_decode($f3->get('BODY'), true);

    $this->m_transferencia->set('usuario_id', $decoded->data->user_id);
    $this->m_transferencia->set('fecha', $body['fecha']);
    $this->m_transferencia->set('cuenta_id_destino', $body['cuenta_id_destino']);
    $this->m_transferencia->set('cuenta_id_origen', $body['cuenta_id_origen']);
    $this->m_transferencia->set('monto', $body['monto']);
    $this->m_transferencia->set('comentario', $body['comentario']);

    

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

    public function actualizar($f3)
    {
        $token = JwtHelper::getBearerToken($f3);
        $decoded = JwtHelper::validateToken($token, $this->jwtKey);
        $body = json_decode($f3->get('BODY'), true);
        $transf_id = $body['transf_id'];
        // Solo puede actualizar si es dueño
        $this->m_transferencia->load(['id = ? AND usuario_id = ?', $transf_id, $decoded->data->user_id]);

        if (!$this->m_transferencia->loaded()) {
            $this->errorResponse('No tienes permiso para actualizar esta transferencia o no existe', 403);
            return;
        }
        $this->m_transferencia->set('fecha', $body['fecha']);
        $this->m_transferencia->set('cuenta_id_destino', $body['cuenta_id_destino']);
        $this->m_transferencia->set('cuenta_id_origen', $body['cuenta_id_origen']);
        $this->m_transferencia->set('monto', $body['monto']);
        $this->m_transferencia->set('comentario', $body['comentario']);


        $this->m_transferencia->save();

        $this->successResponse([
            'mensaje' => 'Transferencia actualizada',
            'info' => ['id' => $this->m_transferencia->get('id')]
        ]);
    }

    public function eliminar($f3)
    {
        $token = JwtHelper::getBearerToken($f3);
        $decoded = JwtHelper::validateToken($token, $this->jwtKey);
        $body = json_decode($f3->get('BODY'), true);
        $transf_id = $body['transf_id'];

        // Solo puede eliminar si es dueño
        $this->m_transferencia->load(['id = ? AND usuario_id = ?', $transf_id, $decoded->data->user_id]);

        if ($this->m_transferencia->loaded()) {
            $this->m_transferencia->erase();
            $this->successResponse([
                'mensaje' => 'Transferencia eliminada',
                'info' => ['id' => $transf_id]
            ]);
        } else {
            $this->errorResponse('No tienes permiso para eliminar esta transferencia o no existe', 403);
        }
    }

    public function listado($f3)
    {
        $token = JwtHelper::getBearerToken($f3);
        $decoded = JwtHelper::validateToken($token, $this->jwtKey);
        $result = $this->m_transferencia->find(['usuario_id = ?', $decoded->data->user_id]);
        $items = [];
    
        foreach ($result as $transferencia) {
            $items[] = $transferencia->cast();
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