<?php

namespace Swissup\Core\Observer\Backend;

class LoadComponents implements \Magento\Framework\Event\ObserverInterface
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
        $components = $this->loader->load();
        foreach ($components as $component) {
            $module = $this->moduleFactory->create()->load($component['code']);
            $module->addData($component);
            $module->save();
        }
    }
}
