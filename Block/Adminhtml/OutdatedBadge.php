<?php

namespace Swissup\Core\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Serialize\Serializer\Json;
use Swissup\Core\Model\ComponentList\Loader;

class OutdatedBadge extends \Magento\Backend\Block\Template
{
    const ADMIN_RESOURCE = 'Swissup_Core::swissup';

    protected $_template = 'Swissup_Core::outdated-badge.phtml';

    private Loader $loader;

    private Json $json;

    public function __construct(
        Context $context,
        Loader $loader,
        Json $json,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->loader = $loader;
        $this->json = $json;
    }

    public function getCount()
    {
        return count($this->loader->setOfflineMode()->getOutdatedItems());
    }

    public function getModulesUrl()
    {
        return $this->getUrl('swissup/module/installed');
    }

    public function getModuleListUrl()
    {
        return $this->getUrl('adminhtml/system_config/edit', [
            'section' => 'swissup_core',
            '_fragment' => 'swissup_core_modules-link',
        ]);
    }

    public function getJsonConfig()
    {
        return $this->json->serialize([
            'url' => $this->getModulesUrl(),
            'moduleListUrl' => $this->getModuleListUrl(),
            'count' => $this->getCount(),
            // the stored data is refreshed once an hour at most, so there is
            // nothing to ask the server for until the check is due again
            'checkRequired' => $this->loader->isVersionCheckRequired(),
        ]);
    }

    protected function _toHtml()
    {
        if (!$this->_authorization->isAllowed(self::ADMIN_RESOURCE)) {
            return '';
        }

        return parent::_toHtml();
    }
}
