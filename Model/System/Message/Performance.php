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
        private PageSpeedStatus $pageSpeedStatus,
        private \Magento\Framework\Escaper $escaper
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
        // the markup is kept out of translations, so they can't inject html
        $title = $this->escaper->escapeHtml(__('Swissup PageSpeed Analytics:'));
        $text = $this->escaper->escapeHtml(__(
            "Some of your store's PageSpeed settings are not optimized. %1Review our recommendations%2 to improve performance.",
            '[link]',
            '[/link]'
        ));
        $link = '<a href="' . $this->escaper->escapeUrl($this->urlBuilder->getUrl('swissup/pagespeed/index')) . '">';

        return '<b>' . $title . '</b> ' . str_replace(['[link]', '[/link]'], [$link, '</a>'], $text);
    }

    /**
     * @return int
     */
    public function getSeverity()
    {
        return MessageInterface::SEVERITY_MAJOR;
    }
}
