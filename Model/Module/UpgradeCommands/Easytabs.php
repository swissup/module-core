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
                continue;
            }

            try {
                $tab->setData($itemData)
                    ->setStores($this->getStoreIds())
                    ->save();
            } catch (\Exception $e) {
                $this->fault('easytabs_tab_save', $e);
                continue;
            }
        }
    }
}
