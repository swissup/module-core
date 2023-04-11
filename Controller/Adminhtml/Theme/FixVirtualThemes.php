<?php

namespace Swissup\Core\Controller\Adminhtml\Theme;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory;

class FixVirtualThemes extends \Magento\Backend\App\Action
{
    private CollectionFactory $collectionFactory;

    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->collectionFactory = $collectionFactory;
    }

    public function execute()
    {        
        $virtualThemes = $this->collectionFactory->create()->addFieldToFilter('type', 1);
        foreach ($virtualThemes as $theme) {
            $theme->setType(0)->save();
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData(['message' => 'Virtual themes fixed. Please, clear the cache!']);

        return $resultJson;
    }
}
