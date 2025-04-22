<?php

class m_transferencias extends \DB\SQL\Mapper
{
    public function __construct()
    {
        parent::__construct(\Base::instance()->get('DB'), 'transferencias');
    }
}
