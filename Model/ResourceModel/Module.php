<?php

namespace Swissup\Core\Model\ResourceModel;

class Module extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Primery key auto increment flag
     *
     * @var bool
     */
    protected $_isPkAutoIncrement = false;

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('swissup_core_module', 'code');
    }
}
