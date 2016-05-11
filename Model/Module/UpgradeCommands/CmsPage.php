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
        // bugfix: cleanup links in url_rewrite table before creating new pages.
        // Related to https://github.com/magento/magento2/issues/4113
        $this->cleanupUrlRewrites(
            'cms-page',
            array_map(
                function ($item) {
                    return $item['identifier'];
                },
                $data
            )
        );

        $isSingleStoreMode = $this->storeManager->isSingleStoreMode();
        foreach ($data as $itemData) {
            // 1. backup existing pages
            $collection = $this->objectManager
                ->create('Magento\Cms\Model\ResourceModel\Page\Collection')
                ->addStoreFilter($this->getStoreIds())
                ->addFieldToFilter('identifier', $itemData['identifier']);

            foreach ($collection as $page) {
                $page->load($page->getId()); // load stores
                $storesToLeave = array_diff($page->getStoreId(), $this->getStoreIds());
                if (count($storesToLeave) && !$isSingleStoreMode) {
                    // unassign page from stores that will have new pages
                    $page->setStores($storesToLeave);
                } else {
                    // disable page, because it has not store to assign to
                    $page->setIsActive(0)
                        ->setIdentifier($this->getBackupIdentifier($page->getIdentifier()));
                }

                try {
                    $page->save();
                } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
                    // $this->_fault('cmspage_backup', $e);
                } catch (Exception $e) {
                    // $this->_fault('cmspage_backup', $e);
                }
            }

            // 2. create new page
            try {
                $this->objectManager->create('Magento\Cms\Model\Page')
                    ->setData($itemData)
                    ->setStores($this->getStoreIds()) // see Magento\Cms\Model\ResourceModel\Page::_afterSave
                    ->save();
            } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
                // $this->_fault('cmspage_save', $e);
            } catch (Exception $e) {
                // $this->_fault('cmspage_save', $e);
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
            ->addFieldToFilter('request_path', ['in' => $requestPaths])
            ->addFieldToFilter('store_id', ['in' => $this->getStoreIds()]);

        foreach ($urlCollection as $item) {
            $item->delete();
        }
    }
}
