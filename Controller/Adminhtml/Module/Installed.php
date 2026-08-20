<?php

namespace Swissup\Core\Controller\Adminhtml\Module;

use Magento\Framework\App\Action\HttpGetActionInterface;

class Installed extends AbstractList implements HttpGetActionInterface
{
    public function execute()
    {
        return $this->getModulesJson();
    }
}
