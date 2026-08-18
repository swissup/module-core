<?php

namespace Swissup\Core\Model\Notification;

use Magento\Framework\App\ObjectManager;
use Swissup\Core\Model\FileStorage;

class Feed extends \Magento\AdminNotification\Model\Feed
{
    const CONFIG_PATH_ENABLED = 'swissup_core/notification/enabled';

    const XML_USE_HTTPS_PATH = 'swissup_core/notification/use_https';

    const XML_FEED_URL_PATH = 'swissup_core/notification/feed_url';

    const LAST_UPDATE_STORAGE_KEY = 'notifications_lastcheck';

    private ?FileStorage $storage = null;

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
        $lastUpdate = (int) $this->getStorage()->load(self::LAST_UPDATE_STORAGE_KEY);

        if ($lastUpdate > time()) {
            return 0;
        }

        return $lastUpdate;
    }

    /**
     * Set last update time (now)
     *
     * @return $this
     */
    public function setLastUpdate()
    {
        $this->getStorage()->save((string) time(), self::LAST_UPDATE_STORAGE_KEY);
        return $this;
    }

    private function getStorage(): FileStorage
    {
        if (!$this->storage) {
            $this->storage = ObjectManager::getInstance()->get(FileStorage::class);
        }
        return $this->storage;
    }
}
