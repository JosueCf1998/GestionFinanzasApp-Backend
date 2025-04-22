<?php

class m_transacciones extends \DB\SQL\Mapper
{
    public function __construct()
    {
        parent::__construct(\Base::instance()->get('DB'), 'transacciones');
    }
}
