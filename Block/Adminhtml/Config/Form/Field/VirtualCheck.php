<?php

namespace Swissup\Core\Block\Adminhtml\Config\Form\Field;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Theme\Model\ResourceModel\Theme\Collection;

class VirtualCheck extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $_template = 'config/field/virtual_check.phtml';
    
    public function render(AbstractElement $element)
    {
        $this->assign('configElement', $element);
        $html = $this->toHtml();
        
        return $this->_decorateRowHtml($element, "<td class='themes-table' colspan=\"3\">$html</td>");
    }

    public function getVirtualThemes() 
    {
        return ObjectManager::getInstance()->create(Collection::class)
            ->addFieldToFilter('type', 1);
    }
}
