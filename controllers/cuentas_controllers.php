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
            // Si el saldo inicial es mayor que 0, crear una transferencia tipo 'Inicial'
            if (floatval($body['saldo']) > 0) {
                $m_transferencia = new m_transferencias();
                $m_transferencia->set('usuario_id', $decoded->data->user_id);
                $m_transferencia->set('fecha', date('Y-m-d'));
                $m_transferencia->set('cuenta_id_destino', $this->m_cuenta->get('id'));
                $m_transferencia->set('cuenta_id_origen', null);
                $m_transferencia->set('monto', $body['saldo']);
                $m_transferencia->set('comentario', 'Saldo inicial de la cuenta');
                $m_transferencia->set('tipo_transferencia', 'Inicial');
                $m_transferencia->save();
            }

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

        // Obtener cuenta_id del body (soportar 'id' o 'cuenta_id')
        $cuenta_id = $body['cuenta_id'] ?? $body['id'] ?? null;
        
        if (!$cuenta_id) {
            $this->errorResponse('ID de cuenta no proporcionado', 400);
            return;
        }

        // Solo puede actualizar si es dueño
        $this->m_cuenta->load(['id = ? AND usuario_id = ?', $cuenta_id, $decoded->data->user_id]);

        if (!$this->m_cuenta->loaded()) {
            $this->errorResponse('No tienes permiso para actualizar esta cuenta o no existe', 403);
            return;
        }

        // Soportar nombres de campos en inglés y español
        $nombre = $body['nombre'] ?? $body['name'] ?? null;
        $saldo = $body['saldo'] ?? $body['amount'] ?? null;
        $icon = $body['icon'] ?? null;
        $color = $body['color'] ?? null;

        if (!$nombre || $saldo === null || !$icon || !$color) {
            $this->errorResponse('Faltan datos requeridos', 400);
            return;
        }

        $_cuenta = new m_cuentas();
        $_cuenta->load(['nombre = ? AND id <> ? AND usuario_id = ?', $nombre, $cuenta_id, $decoded->data->user_id]);

        if ($_cuenta->loaded()) {
            $this->errorResponse(
                'Registro no se pudo modificar debido a que el nombre se encuentra en uso por otra cuenta tuya',
                409
            );
            return;
        }

        // Calcular la diferencia entre el saldo actual y el nuevo
        $saldo_actual = floatval($this->m_cuenta->get('saldo'));
        $saldo_nuevo = floatval($saldo);
        $diferencia = $saldo_nuevo - $saldo_actual;

        $this->m_cuenta->set('nombre', $nombre);
        $this->m_cuenta->set('saldo', $saldo);
        $this->m_cuenta->set('icon', $icon);
        $this->m_cuenta->set('color', $color);
        $this->m_cuenta->save();

        // Si hay diferencia en el saldo, crear una transferencia tipo 'Ajuste'
        if ($diferencia != 0) {
            $m_transferencia = new m_transferencias();
            $m_transferencia->set('usuario_id', $decoded->data->user_id);
            $m_transferencia->set('fecha', date('Y-m-d'));
            
            if ($diferencia > 0) {
                // Incremento: cuenta destino recibe el ajuste
                $m_transferencia->set('cuenta_id_destino', $cuenta_id);
                $m_transferencia->set('cuenta_id_origen', null);
                $m_transferencia->set('monto', abs($diferencia));
                $m_transferencia->set('comentario', 'Ajuste de saldo (incremento)');
            } else {
                // Decremento: cuenta origen pierde el ajuste
                $m_transferencia->set('cuenta_id_destino', null);
                $m_transferencia->set('cuenta_id_origen', $cuenta_id);
                $m_transferencia->set('monto', abs($diferencia));
                $m_transferencia->set('comentario', 'Ajuste de saldo (decremento)');
            }
            
            $m_transferencia->set('tipo_transferencia', 'Ajuste');
            $m_transferencia->save();
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
        if (count($items) > 0) {
            $this->successResponse(['items' => $items, 'Total' => count($items)]);
        } else {
            $this->errorResponse('Aún no hay registros que mostrar', 404, ['items' => [], 'Total' => 0]);
        }
    }
}