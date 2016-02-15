<?php

namespace Swissup\Core\Observer\Backend;

class AddPopupMessages implements \Magento\Framework\Event\ObserverInterface
{
    protected $popupMessageManager;

    public function __construct(\Swissup\Core\Helper\PopupMessageManager $popupMessageManager)
    {
        $this->popupMessageManager = $popupMessageManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->popupMessageManager->getPopups()) {
            $block = $observer->getLayout()->addBlock(
                'Magento\Framework\View\Element\Template',
                'swissup_popup_messages',
                'before.body.end'
            );
            $block->setTemplate('Swissup_Core::popup_messages.phtml')
                ->setData('popup_messenger', $this->popupMessageManager);
        }
    }
}
