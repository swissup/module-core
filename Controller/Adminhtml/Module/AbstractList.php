<?php

namespace Swissup\Core\Controller\Adminhtml\Module;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Swissup\Core\Model\ComponentList\Loader;

abstract class AbstractList extends Action
{
    const ADMIN_RESOURCE = 'Swissup_Core::core_config';

    const RESPONSE_FIELDS = [
        'code',
        'name',
        'version',
        'latest_version',
        'release_date',
        'is_outdated',
    ];

    protected Loader $loader;

    public function __construct(
        Action\Context $context,
        Loader $loader
    ) {
        parent::__construct($context);
        $this->loader = $loader;
    }

    protected function getModulesJson()
    {
        $fields = array_flip(self::RESPONSE_FIELDS);

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData([
            'items' => array_map(function ($item) use ($fields) {
                return array_intersect_key($item, $fields);
            }, array_values($this->loader->getInstalledItems())),
            'last_check' => $this->loader->getLastCheckTime(),
        ]);
    }
}
