<?php

namespace Swissup\Core\Observer\Backend;

class AddDebugInfoBlock implements \Magento\Framework\Event\ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $blockName = \Swissup\Core\Helper\Debug::POPUP_NAME;
        if ($debug = $observer->getLayout()->getBlock($blockName)) {
            $observer->getLayout()->addBlock($debug, $blockName, 'content');
        }
    }
}
