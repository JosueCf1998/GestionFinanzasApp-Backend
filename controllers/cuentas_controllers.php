<?php

class cuentas_controllers
{
    public $m_cuenta = null;
    public function __construct()
    {
        $this->m_cuenta = new m_cuentas();
    }
    public function crear($f3)
    {
        $this->validarToken($f3);
        $this->m_cuenta->set('usuario_id', $f3->get('POST.usuario_id'));
        $this->m_cuenta->set('nombre', $f3->get('POST.nombre'));
        $this->m_cuenta->set('saldo', $f3->get('POST.saldo'));
        $this->m_cuenta->save();
        echo json_encode([
            'mensaje' => 'Cuenta creada',
            'info' => [
                'id' => $this->m_cuenta->get('id')
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
        $cuenta_id = $f3->get('PARAMS.cuenta_id');
        $this->m_cuenta->load(['id = ?', $cuenta_id]);
        $msg = '';
        if ($this->m_cuenta->loaded() > 0) {
            $_cuenta = new m_cuentas();
            $_cuenta->load(['nombre = ? AND id <> ?', $f3->get('POST.nombre'), $cuenta_id]);
            if ($_cuenta->loaded() > 0) {
                $msg = 'Registro no se pudo modificar debido a que el nombre se encuentra en uso por otra cuenta';
            } else {
                $this->m_cuenta->set('usuario_id', $f3->get('POST.usuario_id'));
                $this->m_cuenta->set('nombre', $f3->get('POST.nombre'));
                $this->m_cuenta->set('saldo', $f3->get('POST.saldo'));
                $this->m_cuenta->save();
                $msg = 'Cuenta actualizada';
            }
        } else {
            $msg = 'Cuenta no encontrada';
        }
        echo json_encode([
            'mensaje' => $msg,
            'info' => []
        ]);
    }

    public function consultar($f3)
    {
        $cuenta_id = $f3->get('PARAMS.cuenta_id');
        $this->m_cuenta->load(['id = ?', $cuenta_id]);
        $msg = '';
        $items = array();
        if ($this->m_cuenta->loaded() > 0) {
            $msg = 'Cuenta encontrada';
            $items = $this->m_cuenta->cast();
        } else {
            $msg = 'Cuenta no encontrada';
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
        $cuenta_id = $f3->get('POST.cuenta_id');
        $this->m_cuenta->load(['id = ?', $cuenta_id]);
        $msg = '';
        if ($this->m_cuenta->loaded() > 0) {
            $msg = 'Cuenta eliminada';
            $this->m_cuenta->erase();
        } else {
            $msg = 'Cuemta no encontrada';
        }
        echo json_encode([
            'mensaje' => $msg,
            'info' => []
        ]);
    }
    public function listado($f3)
    {
        $result = $this->m_cuenta->find();
        foreach ($result as $cuenta) {
            $items[] = $cuenta->cast();
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
