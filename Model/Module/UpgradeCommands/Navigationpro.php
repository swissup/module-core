<?php

namespace Swissup\Core\Model\Module\UpgradeCommands;

class Navigationpro extends AbstractCommand
{
    /**
     * Create new menu and enable it in the config, if needed
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
            ->addRootCategoryIdAttribute()
            ->setLoadDefault(true);

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

                // use unique menu name if multiple root category ids are found
                $name = $builder->getSettings('identifier');
                if (count($rootCategoryIds) > 1) {
                    $name .= '_cat' . $categoryId;
                    $builder->updateSettings([
                        'identifier' => $name
                    ]);
                }

                $menu = $this->objectManager->create('Swissup\Navigationpro\Model\MenuFactory')
                    ->create()
                    ->load($name, 'identifier');

                if ($menu->getId()) {
                    $this->activate($menu, $storeIds);
                    continue;
                }

                if (isset($menuData['items'])) {
                    $builder->updateItems($menuData['items']);
                }

                $builder->setRootCategoryId($categoryId);

                try {
                    $menu = $builder->save();

                    if (!empty($menuData['activate'])) {
                        $this->activate($menu, $storeIds);
                    }
                } catch (\Exception $e) {
                    $this->fault('navigationpro_menu_save', $e);
                    continue;
                }
            }
        }
    }

    /**
     * Activate menu per store ids
     *
     * @param  \Swissup\Navigationpro\Model\Menu $menu
     * @param  array $storeIds
     */
    private function activate(\Swissup\Navigationpro\Model\Menu $menu, $storeIds)
    {
        $this->saveConfig(
            'navigationpro/top/identifier',
            $menu->getIdentifier(),
            $storeIds
        );
    }
}
