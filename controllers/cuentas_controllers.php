<?php

require_once 'BaseController.php';
require_once 'helpers/ResponseHelper.php';
require_once 'helpers/SecurityHelper.php';
require_once 'helpers/JwtHelper.php';
require_once 'helpers/SessionHelper.php';
require_once 'helpers/AesDecryptor.php';

class cuentas_controllers extends BaseController
{
    protected $m_cuenta;
    private $jwtKey;

    public function __construct()
    {
        $this->m_cuenta = new m_cuentas();
        $this->jwtKey = getenv('JWT_SECRET') ?: '$#Gre1410#$';
    }

    public function crear($f3)
    {
        $token = JwtHelper::getBearerToken($f3);
        $decoded = JwtHelper::validateToken($token, $this->jwtKey); 
        $body = json_decode($f3->get('BODY'), true);

        // Validar que no exista otra cuenta con el mismo nombre para el usuario
        $_cuenta = new m_cuentas();
        $_cuenta->load(['nombre = ? AND usuario_id = ?', $body['nombre'], $decoded->data->user_id]);
        if ($_cuenta->loaded()) {
            $this->errorResponse('Ya existe una cuenta con ese nombre para este usuario', 409);
            return;
        }

        $this->m_cuenta->set('usuario_id', $decoded->data->user_id);
        $this->m_cuenta->set('nombre', $body['nombre']);
        $this->m_cuenta->set('saldo', $body['saldo']);
        $this->m_cuenta->set('icon', $body['icon']);
        $this->m_cuenta->set('color', $body['color']);

        if ($this->m_cuenta->save()) {
            $newId = $this->m_cuenta->get('id');
            // Si se crea con saldo inicial, registrar una transferencia tipo 'Inicial'
            if (!empty($body['saldo']) && floatval($body['saldo']) != 0) {
                $mtrans = new m_transferencias();
                $mtrans->set('usuario_id', $decoded->data->user_id);
                $mtrans->set('tipo', 'Inicial');
                $mtrans->set('fecha', date('Y-m-d'));
                $mtrans->set('cuenta_id_destino', $newId);
                $mtrans->set('cuenta_id_origen', null);
                $mtrans->set('monto', $body['saldo']);
                $mtrans->set('comentario', 'Saldo inicial');
                $mtrans->save();
            }

            // Recalcular saldo para la cuenta creada
            $mcuenta = new m_cuentas();
            $mcuenta->recalcularSaldo($newId);

            $this->successResponse([
                'mensaje' => 'Cuenta creada correctamente',
                'info' => [ 'id' => $newId ]
            ]);
        } else {
            $this->errorResponse('No se pudo crear la cuenta', 500);
        }
    }

    public function actualizar($f3)
    {   
        $token = JwtHelper::getBearerToken($f3);
        $decoded = JwtHelper::validateToken($token, $this->jwtKey);
        $body = json_decode($f3->get('BODY'), true);
        $cuenta_id = $body['cuenta_id'];
        // Solo puede actualizar si es dueño
        $this->m_cuenta->load(['id = ? AND usuario_id = ?', $cuenta_id, $decoded->data->user_id]);

        if (!$this->m_cuenta->loaded()) {
            $this->errorResponse('No tienes permiso para actualizar esta cuenta o no existe', 403);
            return;
        }

        $_cuenta = new m_cuentas();
        $_cuenta->load(['nombre = ? AND id <> ? AND usuario_id = ?', $body['nombre'], $cuenta_id, $decoded->data->user_id]);

        if ($_cuenta->loaded()) {
            $this->errorResponse(
                'Registro no se pudo modificar debido a que el nombre se encuentra en uso por otra cuenta tuya',
                409
            );
            return;
        }

        $oldSaldo = $this->m_cuenta->get('saldo');

        $this->m_cuenta->set('nombre', $body['nombre']);
        $this->m_cuenta->set('saldo', $body['saldo']);
        $this->m_cuenta->save();

        // Si hay diferencia en saldo, crear transferencia tipo 'Ajuste'
        $delta = floatval($body['saldo']) - floatval($oldSaldo);
        if (abs($delta) > 0.0001) {
            $mtrans = new m_transferencias();
            $mtrans->set('usuario_id', $decoded->data->user_id);
            $mtrans->set('tipo', 'Ajuste');
            $mtrans->set('fecha', date('Y-m-d'));
            if ($delta > 0) {
                $mtrans->set('cuenta_id_destino', $this->m_cuenta->get('id'));
                $mtrans->set('cuenta_id_origen', null);
                $mtrans->set('monto', $delta);
                $mtrans->set('comentario', 'Ajuste por incremento de saldo');
            } else {
                $mtrans->set('cuenta_id_origen', $this->m_cuenta->get('id'));
                $mtrans->set('cuenta_id_destino', null);
                $mtrans->set('monto', abs($delta));
                $mtrans->set('comentario', 'Ajuste por disminución de saldo');
            }
            $mtrans->save();

            // Recalcular saldo
            $mcuenta = new m_cuentas();
            $mcuenta->recalcularSaldo($this->m_cuenta->get('id'));
        }

        $this->successResponse([
            'mensaje' => 'Cuenta actualizada',
            'info' => ['id' => $this->m_cuenta->get('id')]
        ]);
    }

    public function eliminar($f3)
    {
        $token = JwtHelper::getBearerToken($f3);
        $decoded = JwtHelper::validateToken($token, $this->jwtKey);
        $body = json_decode($f3->get('BODY'), true);
        $cuenta_id = $body['cuenta_id'];

        // Solo puede eliminar si es dueño
        $this->m_cuenta->load(['id = ? AND usuario_id = ?', $cuenta_id, $decoded->data->user_id]);

        if ($this->m_cuenta->loaded()) {
            $this->m_cuenta->erase();
            $this->successResponse([
                'mensaje' => 'Cuenta eliminada',
                'info' => ['id' => $cuenta_id]
            ]);
        } else {
            $this->errorResponse('No tienes permiso para eliminar esta cuenta o no existe', 403);
        }
    }

    public function listado($f3)
    {
        $token = JwtHelper::getBearerToken($f3);
        $decoded = JwtHelper::validateToken($token, $this->jwtKey);
        // Solo listar cuentas del usuario autenticado
        $result = $this->m_cuenta->find(['usuario_id = ?', $decoded->data->user_id]);
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
            $this->errorResponse('Aún no hay registros que mostrar', 404, ['items' => [], 'Total' => 0]);
        }
    }
}