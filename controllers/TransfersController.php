<?php

namespace Controllers;

require_once __DIR__ . '/BaseController.php';

class TransfersController extends BaseController
{
    protected $transferModel;

    public function __construct()
    {
        parent::__construct();
        $this->transferModel = new \m_transferencias();
    }

    public function create($f3)
    {
        $decoded = $this->requireAuth($f3);
        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return;
        }

    $this->transferModel->set('usuario_id', $decoded->data->user_id);
    $this->transferModel->set('fecha', $body['fecha']);
    $this->transferModel->set('cuenta_id_destino', $body['cuenta_id_destino']);
    $this->transferModel->set('cuenta_id_origen', $body['cuenta_id_origen']);
    $this->transferModel->set('monto', $body['monto']);
    $this->transferModel->set('comentario', $body['comentario']);

        if ($this->transferModel->save()) {
            $accountModel = new \m_cuentas();
            $accountModel->actualizarSaldo($body['cuenta_id_origen'], $body['monto'], 'restar');
            $accountModel->actualizarSaldo($body['cuenta_id_destino'], $body['monto'], 'sumar');

            $this->successResponse([
                'mensaje' => 'Transferencia creada correctamente',
                'info' => [
                    'id' => $this->transferModel->get('id')
                ]
            ]);
        } else {
            $this->errorResponse('No se pudo crear la transferencia', 500);
        }
    }

    public function update($f3)
    {
        $decoded = $this->requireAuth($f3);
        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return;
        }

        $transf_id = $body['transf_id'];
        $this->transferModel->load(['id = ? AND usuario_id = ?', $transf_id, $decoded->data->user_id]);

        if (!$this->transferModel->loaded()) {
            $this->errorResponse('No tienes permiso para actualizar esta transferencia o no existe', 403);
            return;
        }

        $monto_anterior = floatval($this->transferModel->get('monto'));
        $cuenta_origen_anterior = $this->transferModel->get('cuenta_id_origen');
        $cuenta_destino_anterior = $this->transferModel->get('cuenta_id_destino');

        $accountModel = new \m_cuentas();
        $accountModel->actualizarSaldo($cuenta_origen_anterior, $monto_anterior, 'sumar');
        $accountModel->actualizarSaldo($cuenta_destino_anterior, $monto_anterior, 'restar');

        $this->transferModel->set('fecha', $body['fecha']);
        $this->transferModel->set('cuenta_id_destino', $body['cuenta_id_destino']);
        $this->transferModel->set('cuenta_id_origen', $body['cuenta_id_origen']);
        $this->transferModel->set('monto', $body['monto']);
        $this->transferModel->set('comentario', $body['comentario']);

        $accountModel->actualizarSaldo($body['cuenta_id_origen'], $body['monto'], 'restar');
        $accountModel->actualizarSaldo($body['cuenta_id_destino'], $body['monto'], 'sumar');

        $this->transferModel->save();

        $this->successResponse([
            'mensaje' => 'Transferencia actualizada',
            'info' => ['id' => $this->transferModel->get('id')]
        ]);
    }

    public function delete($f3)
    {
        $decoded = $this->requireAuth($f3);
        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return;
        }

        $transf_id = $body['transf_id'];

        $this->transferModel->load(['id = ? AND usuario_id = ?', $transf_id, $decoded->data->user_id]);

        if ($this->transferModel->loaded()) {
            $monto = floatval($this->transferModel->get('monto'));
            $cuenta_origen = $this->transferModel->get('cuenta_id_origen');
            $cuenta_destino = $this->transferModel->get('cuenta_id_destino');

            $accountModel = new \m_cuentas();
            $accountModel->actualizarSaldo($cuenta_origen, $monto, 'sumar');
            $accountModel->actualizarSaldo($cuenta_destino, $monto, 'restar');

            $this->transferModel->erase();
            $this->successResponse([
                'mensaje' => 'Transferencia eliminada',
                'info' => ['id' => $transf_id]
            ]);
        } else {
            $this->errorResponse('No tienes permiso para eliminar esta transferencia o no existe', 403);
        }
    }

    public function list($f3)
    {
        $decoded = $this->requireAuth($f3);
        $result = $this->transferModel->find(['usuario_id = ?', $decoded->data->user_id]);
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
