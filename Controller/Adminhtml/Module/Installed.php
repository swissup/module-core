<?php

namespace Swissup\Core\Controller\Adminhtml\Module;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Swissup\Core\Model\ComponentList\Loader;

class Installed extends Action
{
    const ADMIN_RESOURCE = 'Swissup_Core::core_config';

    private Loader $loader;

    public function __construct(
        Action\Context $context,
        Loader $loader
    ) {
        parent::__construct($context);
        $this->loader = $loader;
    }

    public function execute()
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        if ($this->getRequest()->getParam('refresh')) {
            // drop the version check throttle, so that the load below
            // re-reads the remote source
            $this->loader->refresh();
        }

        return $resultJson->setData([
            'items' => array_values($this->loader->getInstalledItems()),
            // the load above may have re-checked the remote source, or failed
            // to - either way this is the time the clients should display
            'last_check' => $this->loader->getLastCheckTime(),
        ]);
    }
}
