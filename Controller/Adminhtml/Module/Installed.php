<?php

namespace Swissup\Core\Controller\Adminhtml\Module;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Swissup\Core\Model\ComponentList\Loader;

class Installed extends Action
{
    const ADMIN_RESOURCE = 'Swissup_Core::core_config';

    /**
     * The rest of the merged record - the filesystem path, the download and
     * license urls, the purchase code - has no business in the browser.
     */
    const RESPONSE_FIELDS = [
        'code',
        'name',
        'version',
        'latest_version',
        'release_date',
        'is_outdated',
    ];

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
            'items' => array_map(
                [$this, 'exportItem'],
                array_values($this->loader->getInstalledItems())
            ),
            // the load above may have re-checked the remote source, or failed
            // to - either way this is the time the clients should display
            'last_check' => $this->loader->getLastCheckTime(),
        ]);
    }

    private function exportItem(array $item)
    {
        return array_intersect_key($item, array_flip(self::RESPONSE_FIELDS));
    }
}
