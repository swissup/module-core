<?php

namespace Swissup\Core\Model\Notification;

class Feed extends \Magento\AdminNotification\Model\Feed
{
    const CONFIG_PATH_ENABLED = 'swissup_core/notification/enabled';

    const XML_USE_HTTPS_PATH = 'swissup_core/notification/use_https';

    const XML_FEED_URL_PATH = 'swissup_core/notification/feed_url';

    /**
     * Copied from parent class becasue of `self` usage
     *
     * @return string
     */
    public function getFeedUrl()
    {
        $httpPath = $this->_backendConfig->isSetFlag(self::XML_USE_HTTPS_PATH) ? 'https://' : 'http://';
        if ($this->_feedUrl === null) {
            $this->_feedUrl = $httpPath . $this->_backendConfig->getValue(self::XML_FEED_URL_PATH);
        }
        return $this->_feedUrl;
    }

    /**
     * Overriden to check config status before fetching data
     *
     * @return $this
     */
    public function checkUpdate()
    {
        if (!$this->_backendConfig->isSetFlag(self::CONFIG_PATH_ENABLED)) {
            return $this;
        }
        return parent::checkUpdate();
    }

    /**
     * Retrieve Last update time
     *
     * @return int
     */
    public function getLastUpdate()
    {
        return $this->_cacheManager->load('swissup_core_notifications_lastcheck');
    }

    /**
     * Set last update time (now)
     *
     * @return $this
     */
    public function setLastUpdate()
    {
        $this->_cacheManager->save(time(), 'swissup_core_notifications_lastcheck');
        return $this;
    }
}
