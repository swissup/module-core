<?php

namespace Swissup\Core\Observer\Backend;

use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem\DriverInterface;

class FetchLocalPackages implements \Magento\Framework\Event\ObserverInterface
{
    protected $localCollection;

    public function __construct(
        \Swissup\Core\Model\ResourceModel\Module\LocalCollection $localCollection
    ) {
        $this->localCollection = $localCollection;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $modules = $observer->getModuleCollection();
        foreach ($modules as $module) {
            if ($item = $this->localCollection->getItemById($module->getId())) {
                $module->addData($item->getData());
            } else {
                $module->addData([
                    'local_version' => 'N/A'
                ]);
            }
        }
    }
}
