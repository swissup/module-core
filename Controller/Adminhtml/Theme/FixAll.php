<?php

namespace Swissup\Core\Controller\Adminhtml\Theme;

use Magento\Framework\Controller\ResultFactory;

class FixAll extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Swissup_Core::theme_fixall';

    public function execute()
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData(['message' => 'Virtual themes fixed. Please, clear the cache!']);
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        
        $connection = $objectManager->create('\Magento\Framework\App\ResourceConnection')->getConnection();
        $tableName = $connection->getTableName('theme');
        $connection->update($tableName, ['type' => 0]);

        return $resultJson;
    }
}
