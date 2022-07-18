<?php

namespace Swissup\Core\Plugin;

use Magento\Framework\ObjectManagerInterface;
use Magento\Store\ViewModel\SwitcherUrlProvider as ViewModel;

class StoreSwitcherBlock
{
    /**
     * @var ViewModel|null
     */
    private $viewModel = null;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        if (class_exists(ViewModel::class)) {
            $this->viewModel = $objectManager->create(ViewModel::class);
        }
    }

    /**
     * @param \Magento\Store\Block\Switcher $block
     */
    public function beforeToHtml(\Magento\Store\Block\Switcher $block)
    {
        if (!$block->getViewModel() && $this->viewModel) {
            $block->setViewModel($this->viewModel);
            $block->assign('viewModel', $this->viewModel);
        }
    }
}
