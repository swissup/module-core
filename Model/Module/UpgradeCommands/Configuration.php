<?php

namespace Swissup\Core\Model\Module\UpgradeCommands;

class Configuration extends \Swissup\Core\Model\Module\UpgradeCommands\AbstractCommand
{
    /**
     * Backend Config Model Factory
     *
     * @var \Magento\Config\Model\Config\Factory
     */
    protected $configFactory;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Config\Model\Config\Factory $configFactory
    ) {
        parent::__construct($objectManager, $localeDate, $storeManager);
        $this->configFactory = $configFactory;
    }

    /**
     * Save confoguration values
     *
     * @param  array $data Array of config data
     * @return void
     */
    public function execute($data)
    {
        // 1. transform data into magento config sections format
        $sections = [];
        foreach ($data as $path => $value) {
            $pathParts = explode('/', $path);

            $section = array_shift($pathParts);
            if (!isset($sections[$section])) {
                $sections[$section] = [
                    'section' => $section,
                    'groups' => []
                ];
            }

            $limit = count($pathParts) - 1;
            $temp = [];
            for ($i = $limit; $i >= 0; $i--) {
                if ($i === $limit) {
                    $value = ['value' => $value];
                } elseif ($i === $limit - 1) {
                    $value = ['fields' => $temp];
                } else {
                    // when nesting level > 2 (prolabels)
                    $value = ['groups' => $temp];
                }
                $temp = [$pathParts[$i] => $value];
            }

            $sections[$section]['groups'] = array_merge_recursive(
                $sections[$section]['groups'],
                $temp
            );
        }

        // 2. save config
        foreach ($this->getStoreIds() as $storeId) {
            if (!$storeId) { // all stores selected
                $website = null;
                $store   = null;
            } else {
                if (!$this->storeManager->getStore($storeId)->getId()) {
                    continue;
                }
                $website = $this->storeManager->getStore($storeId)->getWebsite()->getCode();
                $store   = $this->storeManager->getStore($storeId)->getCode();
            }

            foreach ($sections as $section) {
                $configData = [
                    'section' => $section['section'],
                    'website' => $website,
                    'store'   => $store,
                    'groups'  => $section['groups'],
                ];
                try {
                    /** @var \Magento\Config\Model\Config $configModel  */
                    $configModel = $this->configFactory->create(['data' => $configData]);
                    $configModel->save();
                } catch (\Exception $e) {
                    $this->fault('configuration_save', $e);
                }
            }
        }
    }
}
