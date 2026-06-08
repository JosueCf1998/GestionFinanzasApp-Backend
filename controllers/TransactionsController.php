<?php

namespace Controllers;

require_once __DIR__ . '/BaseController.php';

class TransactionsController extends BaseController
{
    protected $transactionModel;

    public function __construct()
    {
        parent::__construct();
        $this->transactionModel = new \m_transacciones();
    }
    
    // Wrapper to match routes.ini (POST /transactions/register)
    public function register($f3)
    {
        return $this->create($f3);
    }
    
    public function create($f3)
    {
        $decoded = $this->requireAuth($f3);
        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return;
        }

        $this->transactionModel->set('usuario_id', $decoded->data->user_id);
        $this->transactionModel->set('categoria_id', $body['categoria_id']);
        $this->transactionModel->set('cuenta_id', $body['cuenta_id']);
        $this->transactionModel->set('fecha', $body['fecha']);
        $this->transactionModel->set('monto', $body['monto']);
        $this->transactionModel->set('tipo', $body['tipo']);
        $this->transactionModel->set('descripcion', $body['descripcion']);

        if ($this->transactionModel->save()) {
            $this->successResponse([
                'mensaje' => 'Transacción creada correctamente',
                'info' => [
                    'id' => $this->transactionModel->get('id')
                ]
            ]);
        } else {
            $this->errorResponse('No se pudo crear la transacción', 500);
        }
    }

    public function update($f3)
    {
        
        $decoded = $this->requireAuth($f3);
        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return;
        }

        $transac_id = $body['transac_id'];
        $this->transactionModel->load(['id = ? AND usuario_id = ?', $transac_id, $decoded->data->user_id]);

        if (!$this->transactionModel->loaded()) {
            $this->errorResponse('No tienes permiso para actualizar esta transacción o no existe', 403);
            return;
        }
        $this->transactionModel->set('categoria_id', $body['categoria_id']);
        $this->transactionModel->set('cuenta_id', $body['cuenta_id']);
        $this->transactionModel->set('fecha', $body['fecha']);
        $this->transactionModel->set('monto', $body['monto']);
        $this->transactionModel->set('tipo', $body['tipo']);
        $this->transactionModel->set('descripcion', $body['descripcion']);

        $this->transactionModel->save();

        $this->successResponse([
            'mensaje' => 'Transacción actualizada',
            'info' => ['id' => $this->transactionModel->get('id')]
        ]);
    }

    public function delete($f3)
    {
        
        $decoded = $this->requireAuth($f3);
        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return;
        }
        
        $transac_id = $body['transac_id'];

        $this->transactionModel->load(['id = ? AND usuario_id = ?', $transac_id, $decoded->data->user_id]);

        if ($this->transactionModel->loaded()) {
            $this->transactionModel->erase();
            $this->successResponse([
                'mensaje' => 'Transacción eliminada',
                'info' => ['id' => $transac_id]
            ]);
        } else {
            $this->errorResponse('No tienes permiso para eliminar esta transacción o no existe', 403);
        }
    }

    public function list($f3)
    {
        $decoded = $this->requireAuth($f3);

        $result = $this->transactionModel->find(['usuario_id = ?', $decoded->data->user_id]);
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
