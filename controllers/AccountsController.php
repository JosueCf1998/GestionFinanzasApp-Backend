<?php

namespace Controllers;

require_once __DIR__ . '/BaseController.php';

class AccountsController extends BaseController
{
    protected $accountModel;

    public function __construct()
    {
        parent::__construct();
        $this->accountModel = new \m_cuentas();
    }

    // Wrapper to match routes.ini (POST /accounts/register)
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

        $_account = new \m_cuentas();
        $_account->load(['nombre = ? AND usuario_id = ?', $body['nombre'], $decoded->data->user_id]);
        if ($_account->loaded()) {
            $this->errorResponse('Ya existe una cuenta con ese nombre para este usuario', 409);
            return;
        }

        $this->accountModel->set('usuario_id', $decoded->data->user_id);
        $this->accountModel->set('nombre', $body['nombre']);
        $this->accountModel->set('saldo', $body['saldo']);
        $this->accountModel->set('icon', $body['icon']);
        $this->accountModel->set('color', $body['color']);

        if ($this->accountModel->save()) {

            if (floatval($body['saldo']) > 0) {
                $transferModel = new \m_transferencias();
                $transferModel->set('usuario_id', $decoded->data->user_id);
                $transferModel->set('fecha', date('Y-m-d'));
                $transferModel->set('cuenta_id_destino', $this->accountModel->get('id'));
                $transferModel->set('cuenta_id_origen', null);
                $transferModel->set('monto', $body['saldo']);
                $transferModel->set('comentario', 'Saldo inicial de la cuenta');
                $transferModel->set('tipo_transferencia', 'Inicial');
                $transferModel->save();
            }

            $this->successResponse([
                'mensaje' => 'Cuenta creada correctamente',
                'info' => [
                    'id' => $this->accountModel->get('id')
                ]
            ]);
        } else {
            $this->errorResponse('No se pudo crear la cuenta', 500);
        }
    }

    public function update($f3)
    {   
        $decoded = $this->requireAuth($f3);
        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return;
        }

        $cuenta_id = $body['cuenta_id'] ?? $body['id'] ?? null;
        
        if (!$cuenta_id) {
            $this->errorResponse('ID de cuenta no proporcionado', 400);
            return;
        }

        $this->accountModel->load(['id = ? AND usuario_id = ?', $cuenta_id, $decoded->data->user_id]);

        if (!$this->accountModel->loaded()) {
            $this->errorResponse('No tienes permiso para actualizar esta cuenta o no existe', 403);
            return;
        }

        $nombre = $body['nombre'] ?? $body['name'] ?? null;
        $saldo = $body['saldo'] ?? $body['amount'] ?? null;
        $icon = $body['icon'] ?? null;
        $color = $body['color'] ?? null;

        if (!$nombre || $saldo === null || !$icon || !$color) {
            $this->errorResponse('Faltan datos requeridos', 400);
            return;
        }

        $_account = new \m_cuentas();
        $_account->load(['nombre = ? AND id <> ? AND usuario_id = ?', $nombre, $cuenta_id, $decoded->data->user_id]);

        if ($_account->loaded()) {
            $this->errorResponse(
                'Registro no se pudo modificar debido a que el nombre se encuentra en uso por otra cuenta tuya',
                409
            );
            return;
        }

        $saldo_actual = floatval($this->accountModel->get('saldo'));
        $saldo_nuevo = floatval($saldo);
        $diferencia = $saldo_nuevo - $saldo_actual;

        $this->accountModel->set('nombre', $nombre);
        $this->accountModel->set('saldo', $saldo);
        $this->accountModel->set('icon', $icon);
        $this->accountModel->set('color', $color);
        $this->accountModel->save();

        if ($diferencia != 0) {
            $transferModel = new \m_transferencias();
            $transferModel->set('usuario_id', $decoded->data->user_id);
            $transferModel->set('fecha', date('Y-m-d'));
            
            if ($diferencia > 0) {
                $transferModel->set('cuenta_id_destino', $cuenta_id);
                $transferModel->set('cuenta_id_origen', null);
                $transferModel->set('monto', abs($diferencia));
                $transferModel->set('comentario', 'Ajuste de saldo (incremento)');
            } else {
                $transferModel->set('cuenta_id_destino', null);
                $transferModel->set('cuenta_id_origen', $cuenta_id);
                $transferModel->set('monto', abs($diferencia));
                $transferModel->set('comentario', 'Ajuste de saldo (decremento)');
            }
            
            $transferModel->set('tipo_transferencia', 'Ajuste');
            $transferModel->save();
        }

        $this->successResponse([
            'mensaje' => 'Cuenta actualizada',
            'info' => ['id' => $this->accountModel->get('id')]
        ]);
    }

    public function delete($f3)
    {
        $decoded = $this->requireAuth($f3);
        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return;
        }

        $cuenta_id = $body['cuenta_id'];
        $this->accountModel->load(['id = ? AND usuario_id = ?', $cuenta_id, $decoded->data->user_id]);

        if ($this->accountModel->loaded()) {
            $this->accountModel->erase();
            $this->successResponse([
                'mensaje' => 'Cuenta eliminada',
                'info' => ['id' => $cuenta_id]
            ]);
        } else {
            $this->errorResponse('No tienes permiso para eliminar esta cuenta o no existe', 403);
        }
    }

    public function list($f3)
    {
        $decoded = $this->requireAuth($f3);
        $result = $this->accountModel->find(['usuario_id = ?', $decoded->data->user_id]);
        $items = [];
        foreach ($result as $cuenta) {
            $items[] = [
                'id' => $cuenta->id,
                'name' => $cuenta->nombre,
                'amount' => $cuenta->saldo,
                'icon' => $cuenta->icon,
                'color' => $cuenta->color
            ];
        }
        if (count($items) > 0) {
            $this->successResponse(['items' => $items, 'Total' => count($items)]);
        } else {
            $this->successResponse('Aún no hay registros que mostrar', 404, ['items' => [], 'Total' => 0]);
        }
    }
}
