<?php

class m_cuentas extends \DB\SQL\Mapper
{
    public function __construct()
    {
        parent::__construct(\Base::instance()->get('DB'), 'cuentas');
    }

    /**
     * Actualiza el saldo de una cuenta
     * @param int $cuenta_id ID de la cuenta
     * @param float $monto_ajuste Monto a sumar o restar
     * @param string $operacion 'sumar' o 'restar'
     * @return bool
     */
    public function actualizarSaldo($cuenta_id, $monto_ajuste, $operacion = 'sumar')
    {
        $this->load(['id = ?', $cuenta_id]);
        if (!$this->loaded()) {
            return false;
        }

        $saldo_actual = floatval($this->get('saldo'));
        
        if ($operacion === 'restar') {
            $nuevo_saldo = $saldo_actual - $monto_ajuste;
        } else {
            $nuevo_saldo = $saldo_actual + $monto_ajuste;
        }

        $this->set('saldo', $nuevo_saldo);
        $this->save();
        return true;
    }

    /**
     * Obtiene el saldo actual de una cuenta
     * @param int $cuenta_id
     * @return float|null
     */
    public function obtenerSaldo($cuenta_id)
    {
        $this->load(['id = ?', $cuenta_id]);
        if (!$this->loaded()) {
            return null;
        }
        return floatval($this->get('saldo'));
    }
}
