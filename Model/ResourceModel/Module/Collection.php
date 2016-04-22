<?php

namespace Swissup\Core\Model\ResourceModel\Module;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_eventPrefix = 'swissup_core_module_collection';

    protected $_eventObject = 'module_collection';

    /**
     * @var string
     */
    protected $_idFieldName = 'code';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Swissup\Core\Model\Module', 'Swissup\Core\Model\ResourceModel\Module');
    }
}
