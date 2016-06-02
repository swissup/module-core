<?php

namespace Swissup\Core\Model\Module\UpgradeCommands;

class CmsPage extends \Swissup\Core\Model\Module\UpgradeCommands\AbstractCommand
{
    /**
     * Backup and create new cms pages
     *
     * @param  array $data Array of page data arrays
     * <pre>
     * [
     *     'home' => [
     *         'title' => 'Argento Essence',
     *         'identifier' => 'home',
     *         'page_layout' => '1column'
     *         'content' => '',
     *         'is_active' => 1
     *     ]
     * ]
     * </pre>
     * @return void
     */
    public function execute($data)
    {
        $identifiers = array_map(
            function ($item) {
                return $item['identifier'];
            },
            $data
        );

        // bugfix: cleanup links in url_rewrite table before creating new pages.
        // Related to https://github.com/magento/magento2/issues/4113
        $this->cleanupUrlRewrites('cms-page', $identifiers);

        $isSingleStoreMode = $this->storeManager->isSingleStoreMode();

        // 1. Backup existing pages by:
        //    - creating page clone with backup identifier
        //    - or by unassign from store that will be used by new page
        $collection = $this->objectManager
            ->create('Magento\Cms\Model\ResourceModel\Page\Collection')
            ->addStoreFilter($this->getStoreIds())
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('identifier', ['in' => $identifiers]);

        foreach ($collection as $page) {
            $page->load($page->getId()); // load stores
            $storesToLeave = array_diff($page->getStoreId(), $this->getStoreIds());
            if (count($storesToLeave) && !$isSingleStoreMode) {
                // unassign page from stores that will have new pages
                $page->setStores($storesToLeave);
            } else {
                // duplicate page, because original page will be used for new content
                $page = $this->objectManager->create('Magento\Cms\Model\Page')
                    ->addData($page->getData())
                    ->unsPageId()
                    ->setIsActive(0)
                    ->setIdentifier($this->getBackupIdentifier($page->getIdentifier()));
            }

            try {
                $page->save();
            } catch (\Exception $e) {
                $this->fault('cmspage_backup', $e);
            }
        }

        // 2. create new page or write page content to the old page if
        //    identifier and stores are the same
        foreach ($data as $itemData) {
            $pages = $collection->getItemsByColumnValue(
                'identifier',
                $itemData['identifier']
            );

            $canUseExistingPage = false;
            foreach ($pages as $page) {
                $diff = array_diff($page->getStoreId(), $this->getStoreIds());
                if (!count($diff)) {
                    $canUseExistingPage = true;
                    break;
                }
            }

            if (!$canUseExistingPage) {
                $page = $this->objectManager->create('Magento\Cms\Model\Page');
            }

            try {
                $page->addData($itemData)
                    ->setStores($this->getStoreIds()) // see Magento\Cms\Model\ResourceModel\Page::_afterSave
                    ->save();
            } catch (\Exception $e) {
                $this->fault('cmspage_save', $e);
            }
        }
    }

    /**
     * Removes url rewrite entries, that match requested conditions
     *
     * @param  string $entityType
     * @param  array $requestPaths
     * @return void
     */
    protected function cleanupUrlRewrites($entityType, $requestPaths)
    {
        $urlCollection = $this->objectManager
            ->create('Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollection')
            ->addFieldToFilter('entity_type', $entityType)
            ->addFieldToFilter('request_path', ['in' => $requestPaths]);

        $storeIds = $this->getStoreIds();
        if (in_array(0, $storeIds)) {
            $storeIds = array_keys($this->storeManager->getStores(true));
        }
        $urlCollection->addFieldToFilter('store_id', ['in' => $storeIds]);

        foreach ($urlCollection as $item) {
            $item->delete();
        }
    }
}
