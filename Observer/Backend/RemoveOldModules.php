<?php

namespace Swissup\Core\Observer\Backend;

class RemoveOldModules implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Swissup\Core\Model\ResourceModel\Module\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param \Swissup\Core\Model\ResourceModel\Module\CollectionFactory $collectionFactory
     */
    public function __construct(
        \Swissup\Core\Model\ResourceModel\Module\CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param  \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('code', ['like' => '%_Module%']);

        foreach ($collection->getItems() as $item) {
            // No need to run delete, because items are already removed in
            // \Swissup\Core\Observer\Backend\AddComponentsData observer
            // $item->delete();
        }
    }
}
