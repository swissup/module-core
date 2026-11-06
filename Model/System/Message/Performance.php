<?php

declare(strict_types=1);

namespace Swissup\Core\Model\System\Message;

use Magento\Framework\Notification\MessageInterface;
use Swissup\Core\Model\PageSpeedStatus;

class Performance implements MessageInterface
{
    private const XML_PATH_SHOW_MESSAGE = 'swissup_core/notification/performance_warning';

    public function __construct(
        private \Magento\Framework\FlagManager $flagManager,
        private \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        private \Magento\Backend\Model\UrlInterface $urlBuilder,
        private PageSpeedStatus $pageSpeedStatus
    ) {
    }

    /**
     * @return string
     */
    public function getIdentity()
    {
        return 'swissup_breeze_performance';
    }

    /**
     * @return bool
     */
    public function isDisplayed()
    {
        // the same check as the PageSpeed page and its menu badge
        return $this->isEnabled() && $this->isDismissed() && $this->pageSpeedStatus->getCount() > 0;
    }

    /**
     * @return bool
     */
    private function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_SHOW_MESSAGE);
    }

    /**
     * @return bool
     */
    private function isDismissed()
    {
        $flag = 'swissup_breeze_dismissed_messages';
        $dismissedMessages = $this->flagManager->getFlagData($flag) ?? [];
        return !in_array($this->getIdentity(), $dismissedMessages);
    }

    /**
     * @return string
     */
    public function getText()
    {
        return __(
            "<b>Swissup PageSpeed Analytics:</b> Some of your store's PageSpeed settings are not optimized. <a href=\"%1\">Review our recommendations</a> to improve performance.",
            $this->urlBuilder->getUrl('swissup/pagespeed/index')
        );
    }

    /**
     * @return int
     */
    public function getSeverity()
    {
        return MessageInterface::SEVERITY_MAJOR;
    }
}
