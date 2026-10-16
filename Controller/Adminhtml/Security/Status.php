<?php

namespace Swissup\Core\Controller\Adminhtml\Security;

use Magento\Framework\Controller\ResultFactory;

class Status extends Index
{
    public function execute()
    {
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData([
            'count' => $this->patchStatus->getCount($this->patchStatus->run()),
        ]);
    }
}
