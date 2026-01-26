<?php

class m_cuentas extends \DB\SQL\Mapper
{
    public function __construct()
    {
        parent::__construct(\Base::instance()->get('DB'), 'cuentas');
    }

    // Recalcula el saldo de una cuenta a partir de transacciones y transferencias
    public function recalcularSaldo($cuenta_id)
    {
        $db = \Base::instance()->get('DB');

        // Sumar transacciones: los tipos 'gasto' se consideran negativos
        $sqlTrans = "SELECT COALESCE(SUM(CASE WHEN tipo = 'gasto' THEN -monto ELSE monto END),0) as val FROM transacciones WHERE cuenta_id = ?";
        $resTrans = $db->exec($sqlTrans, [$cuenta_id]);
        $transSum = isset($resTrans[0]['val']) ? $resTrans[0]['val'] : 0;

        // Transferencias entrantes
        $sqlIn = "SELECT COALESCE(SUM(monto),0) as in_sum FROM transferencias WHERE cuenta_id_destino = ?";
        $resIn = $db->exec($sqlIn, [$cuenta_id]);
        $inSum = isset($resIn[0]['in_sum']) ? $resIn[0]['in_sum'] : 0;

        // Transferencias salientes
        $sqlOut = "SELECT COALESCE(SUM(monto),0) as out_sum FROM transferencias WHERE cuenta_id_origen = ?";
        $resOut = $db->exec($sqlOut, [$cuenta_id]);
        $outSum = isset($resOut[0]['out_sum']) ? $resOut[0]['out_sum'] : 0;

        $nuevoSaldo = floatval($transSum) + floatval($inSum) - floatval($outSum);

        // Actualizar el campo saldo en la tabla cuentas
        $cuenta = new self();
        $cuenta->load(['id = ?', $cuenta_id]);
        if ($cuenta->loaded()) {
            $cuenta->set('saldo', $nuevoSaldo);
            $cuenta->save();
        }

        return $nuevoSaldo;
    }
}
