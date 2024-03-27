<?php

namespace Swissup\Core\Plugin;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\ButtonLockManager;

class SetButtonLockManager
{
    public function beforeToHtml(\Magento\Framework\View\Element\AbstractBlock $block)
    {
        if (!$block->getButtonLockManager() && class_exists(ButtonLockManager::class)) {
            $block->setButtonLockManager(ObjectManager::getInstance()->get(ButtonLockManager::class));
        }
    }
}
