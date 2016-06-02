<?php

namespace Swissup\Core\Model\Module\UpgradeCommands;

class CmsBlock extends \Swissup\Core\Model\Module\UpgradeCommands\AbstractCommand
{
    /**
     * Backup and create new cms blocks
     *
     * @param  array $data Array of block data arrays
     * <pre>
     * [
     *     'header_cms_links' => [
     *         'title' => 'Argento Header Cms Links',
     *         'identifier' => 'header_cms_links',
     *         'is_active' => '1',
     *         'content' => ''
     *     ]
     * ]
     * </pre>
     * @return void
     */
    public function execute($data)
    {
        $isSingleStoreMode = $this->storeManager->isSingleStoreMode();
        foreach ($data as $itemData) {
            // 1. backup existing blocks
            $collection = $this->objectManager
                ->create('Magento\Cms\Model\ResourceModel\Block\Collection')
                ->addStoreFilter($this->getStoreIds())
                ->addFieldToFilter('identifier', $itemData['identifier']);

            foreach ($collection as $block) {
                $block->load($block->getId()); // load stores
                $storesToLeave = array_diff($block->getStoreId(), $this->getStoreIds());
                if (count($storesToLeave) && !$isSingleStoreMode) {
                    // unassign block from stores that will have new blocks
                    $block->setStores($storesToLeave);
                } else {
                    // disable block, because it has not store to assign to
                    $block->setIsActive(0)
                        ->setIdentifier($this->getBackupIdentifier($block->getIdentifier()));
                }

                try {
                    $block->save();
                } catch (\Exception $e) {
                    $this->fault('cmsblock_backup', $e);
                }
            }

            // 2. create new block
            try {
                $this->objectManager->create('Magento\Cms\Model\Block')
                    ->setData($itemData)
                    ->setStores($this->getStoreIds()) // see Magento\Cms\Model\ResourceModel\Block::_afterSave
                    ->save();
            } catch (\Exception $e) {
                $this->fault('cmsblock_save', $e);
            }
        }
    }
}
