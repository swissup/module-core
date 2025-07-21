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
        foreach ($this->loader->getItems() as $component) {
            $module = $this->moduleFactory->create()->load($component['code']);
            $module->addData($component);

            try {
                $module->save();
            } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
                continue;
            }
        }
    }
}
