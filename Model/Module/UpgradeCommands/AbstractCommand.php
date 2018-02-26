<?php

namespace Swissup\Core\Model\Module\UpgradeCommands;

abstract class AbstractCommand
{
    /**
     * @var \Swissup\Core\Model\Module\MessageLogger
     */
    protected $messageLogger;

    /**
     * @var array Store ids, where the module will be installed
     */
    protected $storeIds = array();

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    protected $configWriter;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
    ) {
        $this->objectManager = $objectManager;
        $this->localeDate = $localeDate;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
    }

    /**
     * Set store ids to run the upgrade on
     *
     * @return $this
     */
    public function setStoreIds(array $ids)
    {
        if ($this->storeManager->isSingleStoreMode()) {
            $ids = [\Magento\Store\Model\Store::DEFAULT_STORE_ID];
        }
        $this->storeIds = $ids;
        return $this;
    }

    /**
     * Retrieve store ids
     *
     * @return array
     */
    public function getStoreIds()
    {
        return $this->storeIds;
    }

    /**
     * @return $this
     */
    public function setMessageLogger($messageLogger)
    {
        $this->messageLogger = $messageLogger;
        return $this;
    }

    /**
     * @return \Swissup\Core\Model\Module\MessageLogger
     */
    public function getMessageLogger()
    {
        return $this->messageLogger;
    }

    /**
     * Log installation errors
     *
     * @param string $type
     * @param $e
     */
    protected function fault($type, $e)
    {
        $this->getMessageLogger()->addError($type, array(
            'message' => $e->getMessage(),
            'trace'   => $e->getTraceAsString()
        ));
    }

    /**
     * Returns unique string. Used to backup existing pages, blocks, etc
     * This method is not 100% bullet proof, but there is very low chance to
     * receive duplicate string.
     *
     * @param string $identifier
     * @return string
     */
    protected function getBackupIdentifier($identifier)
    {
        return $identifier
            . '_backup_'
            . rand(10, 99)
            . '_'
            . $this->localeDate->date()->format('Y-m-d-H-i-s');
    }

    /**
     * Save single config section
     *
     * @param  string $path
     * @param  mixed  $value
     * @param  array  $storeIds
     */
    protected function saveConfig($path, $value, $storeIds = array())
    {
        if (!$storeIds) {
            $storeIds = $this->getStoreIds();
        }

        foreach ($storeIds as $storeId) {
            if (!$storeId) { // all stores selected
                $writeScope = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            } else {
                $writeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
            }
            $this->configWriter->save($path, $value, $writeScope, $storeId);
        }
    }
}
