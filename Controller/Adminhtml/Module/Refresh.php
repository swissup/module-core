<?php

namespace Swissup\Core\Controller\Adminhtml\Module;

use Magento\Framework\App\Action\HttpPostActionInterface;

class Refresh extends AbstractList implements HttpPostActionInterface
{
    public function execute()
    {
        $this->loader->refresh();

        return $this->getModulesJson();
    }
}
