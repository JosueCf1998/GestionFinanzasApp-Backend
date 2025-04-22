<?php

class transferencias_controllers
{
    public $m_transferencia = null;
    public function __construct()
    {
        $this->m_transferencia = new m_transferencias();
    }
    public function crear($f3)
    {
        $this->m_transferencia->set('cuenta_id', $f3->get('POST.cuenta_id'));
        $this->m_transferencia->set('tipo', $f3->get('POST.tipo'));
        $this->m_transferencia->set('cuenta_origen', $f3->get('POST.cuenta_origen'));
        $this->m_transferencia->set('cuenta_destino', $f3->get('POST.cuenta_destino'));
        $this->m_transferencia->save();
        echo json_encode([
            'mensaje' => 'Transferencia creada',
            'info' => [
                'id' => $this->m_transferencia->get('id')
            ]
        ]);
    }

    public function consultar($f3)
    {
        $transf_id = $f3->get('PARAMS.transf_id');
        $this->m_transferencia->load(['id = ?', $transf_id]);
        $msg = '';
        $items = array();
        if ($this->m_transferencia->loaded() > 0) {
            $msg = 'Transferencia encontrada';
            $items = $this->m_transferencia->cast();
        } else {
            $msg = 'Transferencia no encontrado';
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
        $transf_id = $f3->get('POST.transf_id');
        $this->m_transferencia->load(['id = ?', $transf_id]);
        $msg = '';
        if ($this->m_transferencia->loaded() > 0) {
            $msg = 'Transferencia eliminada';
            $this->m_transferencia->erase();
        } else {
            $msg = 'Transferencia no encontrada';
        }
        echo json_encode([
            'mensaje' => $msg,
            'info' => []
        ]);
    }
    public function actualizar($f3)
    {
        $transf_id = $f3->get('PARAMS.transf_id');
        $this->m_transferencia->load(['id = ?', $transf_id]);
        $msg = '';
        if ($this->m_transferencia->loaded() > 0) {
            $_transferencia = new m_usuarios();
            $_transferencia->load(['tipo = ? AND id <> ?', $f3->get('POST.tipo'), $transf_id]);
            if ($_transferencia->loaded() > 0) {
                $msg = 'Registro no se pudo modificar debido a que el tio se encuentra en uso por otra transferencia';
            } else {
                $this->m_transferencia->set('cuenta_id', $f3->get('POST.cuenta_id'));
                $this->m_transferencia->set('tipo', $f3->get('POST.tipo'));
                $this->m_transferencia->set('cuenta_origen', $f3->get('POST.cuenta_origen'));
                $this->m_transferencia->set('cuenta_destino', $f3->get('POST.cuenta_destino'));
                $this->m_transferencia->save();
                $msg = 'Transferencia actualizado';
            }
        } else {
            $msg = 'Transferencia no encontrada';
        }
        echo json_encode([
            'mensaje' => $msg,
            'info' => []
        ]);
    }
    public function listado($f3)
    {
        $result = $this->m_transferencia->find();
        foreach ($result as $transferencia) {
            $items[] = $transferencia->cast();
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
