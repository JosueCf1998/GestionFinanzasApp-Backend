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
