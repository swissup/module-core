<?php

namespace Swissup\Core\Model\Notification;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Filesystem\Io\File as FileIo;

class Feed extends \Magento\AdminNotification\Model\Feed
{
    const CONFIG_PATH_ENABLED = 'swissup_core/notification/enabled';

    const XML_USE_HTTPS_PATH = 'swissup_core/notification/use_https';

    const XML_FEED_URL_PATH = 'swissup_core/notification/feed_url';

    private ?FileIo $fileIo = null;
    private ?DirectoryList $directoryList = null;

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
        $path = $this->getFilePath();

        if (!$this->getFileIo()->fileExists($path)) {
            return null;
        }

        try {
            $lastUpdate = $this->getFileIo()->read($path);
        } catch (\Exception $e) {
            $this->getFileIo()->rm($path);
            return null;
        }

        if ($lastUpdate > time()) {
            return null;
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
        $this->getFileIo()->write($this->getFilePath(), (string) time());
        return $this;
    }

    private function getFilePath()
    {
        $path = $this->getDirectoryList()->getPath(DirectoryList::VAR_DIR) . '/swissup/core';

        $this->getFileIo()->checkAndCreateFolder($path);

        return $path . '/notifications_lastcheck';
    }

    private function getFileIo()
    {
        if (!$this->fileIo) {
            $this->fileIo = ObjectManager::getInstance()->get(FileIo::class);
        }
        return $this->fileIo;
    }

    private function getDirectoryList()
    {
        if (!$this->directoryList) {
            $this->directoryList = ObjectManager::getInstance()->get(DirectoryList::class);
        }
        return $this->directoryList;
    }
}
