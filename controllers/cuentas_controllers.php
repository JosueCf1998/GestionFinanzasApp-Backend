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

        $this->m_cuenta->set('usuario_id', $decoded->data->user_id);
        $this->m_cuenta->set('nombre', $body['nombre']);
        $this->m_cuenta->set('saldo', $body['saldo']);

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

    public function actualizar($f3)
    {   
        $token = JwtHelper::getBearerToken($f3);
        $decoded = JwtHelper::validateToken($token, $this->jwtKey);
        $cuenta_id = $f3->get('PARAMS.cuenta_id');
        // Solo puede actualizar si es dueño
        $this->m_cuenta->load(['id = ? AND usuario_id = ?', $cuenta_id, $decoded->data->user_id]);

        if (!$this->m_cuenta->loaded()) {
            $this->errorResponse('No tienes permiso para actualizar esta cuenta o no existe', 403);
            return;
        }

        $body = json_decode($f3->get('BODY'), true);

        $_cuenta = new m_cuentas();
        $_cuenta->load(['nombre = ? AND id <> ? AND usuario_id = ?', $body['nombre'], $cuenta_id, $decoded->data->user_id]);

        if ($_cuenta->loaded()) {
            $this->errorResponse(
                'Registro no se pudo modificar debido a que el nombre se encuentra en uso por otra cuenta tuya',
                409
            );
            return;
        }

        $this->m_cuenta->set('nombre', $body['nombre']);
        $this->m_cuenta->set('saldo', $body['saldo']);
        $this->m_cuenta->save();

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
            $items[] = $cuenta->cast();
        }
        if (count($items) > 0) {
            $this->successResponse(['items' => $items, 'Total' => count($items)]);
        } else {
            $this->errorResponse('Aún no hay registros que mostrar', 404, ['items' => [], 'Total' => 0]);
        }
    }
}