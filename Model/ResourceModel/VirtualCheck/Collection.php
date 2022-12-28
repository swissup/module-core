<?php

namespace Swissup\Core\Model\ResourceModel\VirtualCheck;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'theme_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Swissup\Core\Model\VirtualCheck::class,
            \Swissup\Core\Model\ResourceModel\VirtualCheck::class
        );
    }
}
