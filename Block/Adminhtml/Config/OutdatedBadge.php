<?php

namespace Swissup\Core\Block\Adminhtml\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Serialize\Serializer\Json;
use Swissup\Core\Model\ComponentList\Loader;

class OutdatedBadge extends \Magento\Backend\Block\Template
{
    const ADMIN_RESOURCE = 'Swissup_Core::core_config';

    protected $_template = 'Swissup_Core::config/outdated-badge.phtml';

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
        // The remote source is never queried here - it would slow down every
        // config page. An outdated counter is refreshed by the js component.
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
