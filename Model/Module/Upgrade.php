<?php

namespace Swissup\Core\Model\Module;

use Swissup\Core\Api\Data\ModuleUpgradeInterface;

abstract class Upgrade implements ModuleUpgradeInterface
{
    /**
     * @var array
     */
    protected $themeIds = [];

    /**
     * @var \Swissup\Core\Model\Module\MessageLogger
     */
    protected $messageLogger;

    /**
     * @var array Store ids, where the module will be installed
     */
    protected $storeIds = array();

    protected $allStoresList = array();

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * Additional operations could be done from this method
     */
    public function up()
    {
    }

    /**
     * Retrieve the list of commands to run
     *
     * @see Swissup\Core\Model\Module\UpgradeCommands for built-in commands
     *
     * @return array Key => Value pairs with command name and data
     */
    public function getCommands()
    {
        return [];
    }

    public function upgrade()
    {
        foreach ($this->getCommands() as $command => $data) {
            $className = 'Swissup\\Core\\Model\\Module\\UpgradeCommands\\' . $command;
            $this->objectManager->create($className)
                ->setStoreIds($this->getStoreIds())
                ->setMessageLogger($this->getMessageLogger())
                ->execute($data);
        }
        $this->up();
    }

    public function getThemeId($themePath)
    {
        if (!isset($this->themeIds[$themePath])) {
            $this->themeIds[$themePath] = $this->objectManager
                ->create('Magento\Theme\Model\ResourceModel\Theme\Collection')
                ->getThemeByFullPath($themePath)
                ->getThemeId();
        }
        return $this->themeIds[$themePath];
    }

    /**
     * Set store ids to run the upgrade on
     *
     * @return $this
     */
    public function setStoreIds(array $ids)
    {
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

    public function getAllStores()
    {
        if (empty($this->allStoresList)) {
            $storeManager = $this->objectManager->create('Magento\Store\Model\StoreManager');
            foreach ($storeManager->getStores() as $store) {
                $storeId = $store["store_id"];
                $storeName = $store["name"];
                $this->allStoresList[] = $storeId;
            }
        }
        return $this->allStoresList;
    }

    public function unsetEasytab($type, $storeIdsToRemove = [], $alias = null)
    {
        $storeManager = $this->objectManager->create('Magento\Store\Model\StoreManager');

        $storeIdsToRemove[] = 0;
        $storesToKeep = $this->getAllStores();
        $storesToKeep = array_diff($storesToKeep, $storeIdsToRemove);

        $collection = $this->objectManager
            ->create('Swissup\Easytabs\Model\Entity')
            ->getCollection();

        if (isset($type)) {
            $collection->addFieldToFilter('block', $type);
        }
        if (isset($alias)) {
            $collection->addFieldToFilter('alias', $alias);
        }
        $collection->walk('afterLoad');

        foreach ($collection as $tab) {
            if ($storeManager->isSingleStoreMode()) {
                $tab->setStatus(0);
            } else {
                $stores = $tab->getStores();
                if (!is_array($stores)) {
                    $stores = (array) $stores;
                }
                $stores = array_diff($stores, array(0));
                if (!$stores) { // tab was assigned to all stores
                    $tab->setStores($storesToKeep);
                } else {
                    if (!array_diff($stores, $storesToKeep)) {
                        // tab is not assigned to storesToRemove
                        continue;
                    }
                    $keep = array_intersect($stores, $storesToKeep);
                    if ($keep) {
                        $tab->setStores($keep);
                    } else {
                        $tab->setStatus(0);
                    }
                }
            }
            $tab->save();
        }
    }
}
