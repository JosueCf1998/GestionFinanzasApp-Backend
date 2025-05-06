<?php

class transacciones_controllers
{
    public $m_transaccion = null;
    public function __construct()
    {
        $this->m_transaccion = new m_transacciones();
    }
    public function crear($f3)
    {
        $this->validarToken($f3);
        $this->m_transaccion->set('categoria_id', $f3->get('POST.categoria_id'));
        $this->m_transaccion->set('cuenta_id', $f3->get('POST.cuenta_id'));
        $this->m_transaccion->set('monto', $f3->get('POST.monto'));
        $this->m_transaccion->set('tipo', $f3->get('POST.tipo'));
        $this->m_transaccion->set('descripcion', $f3->get('POST.descripcion'));
        $this->m_transaccion->set('fecha_registro', $f3->get('POST.fecha_registro'));
        $this->m_transaccion->save();
        echo json_encode([
            'mensaje' => 'transacción creada',
            'info' => [
                'id' => $this->m_transaccion->get('id')
            ]
        ]);
    }

    private function validarToken($f3)
    {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            echo json_encode(['mensaje' => 'Token no proporcionado']);
            http_response_code(401);
            exit;
        }

        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            $token = $matches[1];
            try {
                $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key('$#Gre1410#$', 'HS256'));
                $f3->set('user_id', $decoded->data->user_id);
            } catch (Exception $e) {
                echo json_encode(['mensaje' => 'Token inválido o expirado']);
                http_response_code(403);
                exit;
            }
        } else {
            echo json_encode(['mensaje' => 'Formato de token inválido']);
            http_response_code(400);
            exit;
        }
    }

    public function actualizar($f3)
    {
        $transac_id = $f3->get('PARAMS.transac_id');
        $this->m_transaccion->load(['id = ?', $transac_id]);
        $msg = '';
        if ($this->m_transaccion->loaded() > 0) {
            $_transac = new m_transacciones();
            $_transac->load(['tipo = ? AND id <> ?', $f3->get('POST.tipo'), $transac_id]);
            if ($_transac->loaded() > 0) {
                $msg = 'Registro no se pudo modificar debido a que el tio se encuentra en uso por otra transaccion';
            } else {
                $this->m_transaccion->set('categoria_id', $f3->get('POST.categoria_id'));
                $this->m_transaccion->set('cuenta_id', $f3->get('POST.cuenta_id'));
                $this->m_transaccion->set('monto', $f3->get('POST.monto'));
                $this->m_transaccion->set('tipo', $f3->get('POST.tipo'));
                $this->m_transaccion->set('descripcion', $f3->get('POST.descripcion'));
                $this->m_transaccion->set('fecha_registro', $f3->get('POST.fecha_registro'));
                $this->m_transaccion->save();
                $msg = 'Transaccion actualizado';
            }
        } else {
            $msg = 'Transaccion no encontrada';
        }
        echo json_encode([
            'mensaje' => $msg,
            'info' => []
        ]);
    }

    public function consultar($f3)
    {
        $transac_id = $f3->get('PARAMS.transac_id');
        $this->m_transaccion->load(['id = ?', $transac_id]);
        $msg = '';
        $items = array();
        if ($this->m_transaccion->loaded() > 0) {
            $msg = 'transacción encontrada';
            $items = $this->m_transaccion->cast();
        } else {
            $msg = 'transacción no encontrada';
        }
        echo json_encode([
            'mensaje' => $msg,
            'info' => [
                'items' => $items
            ]
        ]);
    }
    public function eliminar($f3)
    {
        $transac_id = $f3->get('POST.transac_id');
        $this->m_transaccion->load(['id = ?', $transac_id]);
        $msg = '';
        if ($this->m_transaccion->loaded() > 0) {
            $msg = 'transacción eliminada';
            $this->m_transaccion->erase();
        } else {
            $msg = 'transacción no encontrada';
        }
        echo json_encode([
            'mensaje' => $msg,
            'info' => []
        ]);
    }
    public function listado($f3)
    {
        $result = $this->m_transaccion->find();
        foreach ($result as $transaccion) {
            $items[] = $transaccion->cast();
        }
        echo json_encode([
            'mensaje' => count($items) > 0 ? '' : 'aun no hay registros que mostrar',
            'info' => [
                'items' => $items,
                'Total' => count($items),
            ]
        ]);
    }
}
