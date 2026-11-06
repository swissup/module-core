<?php

namespace Swissup\Core\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Serialize\Serializer\Json;
use Swissup\Core\Model\ComponentList\Loader;
use Swissup\Core\Model\PageSpeedStatus;
use Swissup\Core\Model\PatchStatus;

class OutdatedBadge extends \Magento\Backend\Block\Template
{
    const ADMIN_RESOURCE = 'Swissup_Core::swissup';

    const SECURITY_RESOURCE = 'Swissup_Core::security';

    const PAGESPEED_RESOURCE = 'Swissup_Core::pagespeed';

    protected $_template = 'Swissup_Core::outdated-badge.phtml';

    private Loader $loader;

    private Json $json;

    private PatchStatus $patchStatus;

    private PageSpeedStatus $pageSpeedStatus;

    public function __construct(
        Context $context,
        Loader $loader,
        Json $json,
        PatchStatus $patchStatus,
        PageSpeedStatus $pageSpeedStatus,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->loader = $loader;
        $this->json = $json;
        $this->patchStatus = $patchStatus;
        $this->pageSpeedStatus = $pageSpeedStatus;
    }

    public function getCount()
    {
        return count($this->loader->setOfflineMode()->getOutdatedItems());
    }

    public function getSecurityCount()
    {
        if (!$this->isSecurityAllowed()) {
            return 0;
        }

        return $this->patchStatus->getCount($this->patchStatus->getStored());
    }

    public function getPageSpeedCount()
    {
        if (!$this->_authorization->isAllowed(self::PAGESPEED_RESOURCE)) {
            return 0;
        }

        return $this->pageSpeedStatus->getCount();
    }

    private function isSecurityAllowed()
    {
        return $this->_authorization->isAllowed(self::SECURITY_RESOURCE);
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
            'securityUrl' => $this->getUrl('swissup/security/status'),
            'securityCount' => $this->getSecurityCount(),
            'securityCheckRequired' => $this->isSecurityAllowed()
                && $this->patchStatus->isCheckRequired(),
            'pagespeedCount' => $this->getPageSpeedCount(),
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
