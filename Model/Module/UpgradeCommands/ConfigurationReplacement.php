<?php

namespace Swissup\Core\Model\Module\UpgradeCommands;

class ConfigurationReplacement extends \Swissup\Core\Model\Module\UpgradeCommands\AbstractCommand
{
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
        parent::__construct($objectManager, $localeDate, $storeManager);
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
    }

    /**
     * Replace suggested string with another one, keeping rest of config value
     *
     * Usefull to remove sample data config values and strings, keeping the rest
     * value
     *
     * @param  array $data Array of config data
     * @return void
     */
    public function execute($data)
    {
        foreach ($data as $path => $rules) {
            foreach ($this->getStoreIds() as $storeId) {
                if (!$storeId) { // all stores selected
                    $readScope = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
                    $writeScope = $readScope;
                } else {
                    $readScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
                    $writeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
                }

                $configValue = $this->scopeConfig->getValue($path, $readScope, $storeId);
                foreach ($rules as $search => $replace) {
                    $configValue = str_replace($search, $replace, $configValue);
                }
                $this->configWriter->save($path, $configValue, $writeScope, $storeId);
            }
        }
    }
}
