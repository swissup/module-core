<?php

namespace Swissup\Core\Installer\Command;

use Swissup\Core\Installer\Request;
use Swissup\Core\Installer\LoggerAware;

class CmsBlock
{
    use LoggerAware;

    public function __construct(
        private \Magento\Cms\Model\BlockFactory $blockFactory,
        private \Magento\Cms\Model\ResourceModel\Block\CollectionFactory $collectionFactory,
        private \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        private \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
    }

    public function execute(Request $request)
    {
        $this->logger->info('Cms Blocks: Backup existing and create new blocks');

        $idsToInstall = array_flip($request->getExtraOptions());
        $isSingleStoreMode = $this->storeManager->isSingleStoreMode();

        foreach ($request->getParams() as $data) {
            if ($idsToInstall && !isset($idsToInstall[$data['identifier']])) {
                continue;
            }

            $collection = $this->collectionFactory->create()
                ->addStoreFilter($request->getStoreIds())
                ->addFieldToFilter('identifier', $data['identifier']);

            foreach ($collection as $block) {
                $block->load($block->getId()); // load stores

                $storesToLeave = array_diff($block->getStoreId(), $request->getStoreIds());

                if (count($storesToLeave) && !$isSingleStoreMode) {
                    $block->setStores($storesToLeave);
                } else {
                    $block->setIsActive(0)
                        ->setIdentifier($this->getBackupIdentifier($block->getIdentifier()));
                }

                try {
                    $block->save();
                } catch (\Exception $e) {
                    $this->logger->warning($e->getMessage());
                }
            }

            $data = array_merge([
                'is_active' => 1,
            ], $data);

            try {
                $this->blockFactory->create()
                    ->setData($data)
                    ->setStores($request->getStoreIds()) // see Magento\Cms\Model\ResourceModel\Block::_afterSave
                    ->save();
            } catch (\Exception $e) {
                $this->logger->warning($e->getMessage());
            }
        }
    }

    /**
     * @param string $identifier
     * @return string
     */
    private function getBackupIdentifier($identifier)
    {
        return $identifier
            . '_backup_'
            . rand(10, 99)
            . '_'
            . $this->localeDate->date()->format('Y-m-d-H-i-s');
    }
}
