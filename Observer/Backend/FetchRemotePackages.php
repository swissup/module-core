<?php

namespace Swissup\Core\Observer\Backend;

class FetchRemotePackages implements \Magento\Framework\Event\ObserverInterface
{
    protected $remoteCollection;

    public function __construct(
        \Swissup\Core\Model\ResourceModel\Module\RemoteCollection $remoteCollection
    ) {
        $this->remoteCollection = $remoteCollection;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        // @todo: use cache to minify requests qty
        foreach ($this->remoteCollection as $item) {
            $item->save();
        }
    }
}
