<?php

namespace Swissup\Core\Model\Config\Source\Store;

class All extends \Magento\Store\Model\System\Store
{
    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $this->setIsAdminScopeAllowed(true);
        return $this->getStoreValuesForForm(false, true);
    }
}
