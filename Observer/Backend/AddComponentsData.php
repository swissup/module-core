<?php

namespace Swissup\Core\Observer\Backend;

class AddComponentsData implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Swissup\Core\Model\ComponentList\Loader
     */
    protected $loader;

    /**
     * @var \Swissup\Core\Model\ModuleFactory
     */
    protected $moduleFactory;

    /**
     * @param \Swissup\Core\Model\ComponentList\Loader  $loader
     * @param \Swissup\Core\Model\ModuleFactory         $moduleFactory
     */
    public function __construct(
        \Swissup\Core\Model\ComponentList\Loader $loader,
        \Swissup\Core\Model\ModuleFactory $moduleFactory
    ) {
        $this->loader = $loader;
        $this->moduleFactory = $moduleFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        foreach ($observer->getModuleCollection() as $module) {
            $component = $this->loader->getItemById($module->getCode());
            if (!$component) {
                $this->moduleFactory->create()->load($module->getCode())->delete();
                continue;
            }
            $module->addData($component);
        }
    }
}
