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
        $bodyEncrypted = json_decode($f3->get('BODY'), true);
        $bodyDecrypted = AesDecryptor::decrypt(
            $bodyEncrypted['data'], 
            getenv('ENCRYPTION_JSON') ?: "TuClaveSuperSecreta@2024"
        );
        $body = json_decode($bodyDecrypted, true);
        if (!$body || !is_array($body)) {
            $this->errorResponse('Error al procesar los datos', 400);
            return;
        }
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
            $this->successResponse([
                'mensaje' => 'Cuenta creada correctamente',
                'info' => [
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
        
        $bodyEncrypted = json_decode($f3->get('BODY'), true);
        $bodyDecrypted = AesDecryptor::decrypt(
            $bodyEncrypted['data'], 
            getenv('ENCRYPTION_JSON') ?: "TuClaveSuperSecreta@2024"
        );
        
        $body = json_decode($bodyDecrypted, true);

        if (!$body || !is_array($body)) {
            $this->errorResponse('Error al procesar los datos', 400);
            return;
        }

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

        $this->m_cuenta->set('nombre', $body['nombre']);
        $this->m_cuenta->set('saldo', $body['saldo']);
        $this->m_cuenta->set('icon', $body['icon']);
        $this->m_cuenta->set('color', $body['color']);
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
        
        $bodyEncrypted = json_decode($f3->get('BODY'), true);
        $bodyDecrypted = AesDecryptor::decrypt(
            $bodyEncrypted['data'], 
            getenv('ENCRYPTION_JSON') ?: "TuClaveSuperSecreta@2024"
        );
        
        $body = json_decode($bodyDecrypted, true);

        if (!$body || !is_array($body)) {
            $this->errorResponse('Error al procesar los datos', 400);
            return;
        }

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
        $this->successResponse(['items' => $items, 'Total' => count($items)]);
    }
}