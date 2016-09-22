<?php

namespace Swissup\Core\Model\Module\UpgradeCommands;

class Easytabs extends AbstractCommand
{
    /**
     * Create new tabs
     *
     * If duplicates are found - do nothing.
     *
     * @param  array $data Array of tabs
     * @return void
     */
    public function execute($data)
    {
        foreach ($data as $itemData) {
            $tab = $this->objectManager
                ->create('Swissup\Easytabs\Model\Entity')
                ->load($itemData['alias'], 'alias');

            if ($tab->getId()) {
                $storeIds = array_unique(
                    array_merge($tab->getStores(), $this->getStoreIds())
                );

                if (!array_diff($storeIds, $tab->getStores())) {
                    // tab is already assigned to requested store
                    continue;
                }
            } else {
                $tab->setData($itemData);
                $storeIds = $this->getStoreIds();
            }

            try {
                $tab->setStores($storeIds)->save();
            } catch (\Exception $e) {
                $this->fault('easytabs_tab_save', $e);
                continue;
            }
        }
    }
}
