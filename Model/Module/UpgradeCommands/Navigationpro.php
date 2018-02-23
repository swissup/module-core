<?php

namespace Swissup\Core\Model\Module\UpgradeCommands;

class Navigationpro extends AbstractCommand
{
    /**
     * Create new menu
     *
     * If duplicate is found - do nothing.
     *
     * @param  array $data Array of menu's
     * @return void
     */
    public function execute($data)
    {
        // group stores by root_category_id for easier menu creation
        $collection = $this->objectManager
            ->create('Magento\Store\Model\ResourceModel\Store\Collection')
            ->addFieldToFilter('store_id', ['in' => $this->getStoreIds()])
            ->addRootCategoryIdAttribute();

        $rootCategoryIds = [];
        foreach ($collection as $store) {
            if (!isset($rootCategoryIds[$store->getRootCategoryId()])) {
                $rootCategoryIds[$store->getRootCategoryId()] = [];
            }
            $rootCategoryIds[$store->getRootCategoryId()][] = $store->getId();
        }

        foreach ($data as $menuData) {
            if (!isset($menuData['settings']['identifier']) || !isset($menuData['type'])) {
                continue;
            }

            $menu = $this->objectManager->create('Swissup\Navigationpro\Model\MenuFactory')
                ->create()
                ->load($menuData['settings']['identifier'], 'identifier');

            // don't do anything if menu with the same id is already exists
            if ($menu->getId()) {
                continue;
            }

            foreach ($rootCategoryIds as $categoryId => $storeIds) {
                $builder = $this->objectManager
                    ->create('Swissup\Navigationpro\Model\Menu\BuilderFactory')
                    ->create($menuData['type'])
                    ->setStoreIds($storeIds);

                if (isset($menuData['theme_id'])) {
                    $builder->setThemeId($menuData['theme_id']);
                }

                if (isset($menuData['settings'])) {
                    $builder->updateSettings($menuData['settings']);
                }

                if (isset($menuData['items'])) {
                    $builder->updateItems($menuData['items']);
                }

                $builder->setRootCategoryId($categoryId);

                try {
                    $builder->save();
                } catch (\Exception $e) {
                    $this->fault('navigationpro_menu_save', $e);
                    continue;
                }
            }
        }
    }
}
