<?php

namespace Swissup\Core\Controller\Adminhtml\Security;

use Magento\Framework\Controller\ResultFactory;

class Status extends Index
{
    public function execute()
    {
        // the check can take minutes, the admin's other requests shouldn't wait for it
        $this->_session->writeClose();

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData([
            'count' => $this->patchStatus->getCount($this->patchStatus->getResult()),
        ]);
    }
}
