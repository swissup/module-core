<?php

namespace Swissup\Core\Model\Module\UpgradeCommands;

class Easybanner extends \Swissup\Core\Model\Module\UpgradeCommands\AbstractCommand
{
    /**
     * Create new banners and placeholders.
     *
     * If duplicates are found - do nothing.
     *
     * @param  array $data Array of placeholders/banner data arrays
     * @return void
     */
    public function execute($data)
    {
        // 1. prepare collection of existing placeholders
        $placeholders = $this->objectManager
            ->create('Swissup\Easybanner\Model\ResourceModel\Placeholder\Collection')
            ->addFieldToFilter(
                'name',
                [
                    'in' => array_map(
                        function ($item) {
                            return $item['name'];
                        },
                        $data
                    )
                ]
            );

        // 2. prepare collection of existing banners
        $bannerIdentifiers = [];
        foreach ($data as $item) {
            foreach ($item['banners'] as $banner) {
                $bannerIdentifiers[] = $banner['identifier'];
            }
        }
        $banners = $this->objectManager
            ->create('Swissup\Easybanner\Model\ResourceModel\Banner\Collection')
            ->addFieldToFilter('identifier', ['in' => $bannerIdentifiers])
            ->addStoreFilter($this->getStoreIds(), false);

        // 3. create new placeholders and banners
        $placeholderDefaults = [
            'status'        => 1,
            'limit'         => 1,
            'mode'          => 'rotator',
            'banner_offset' => 1,
            'sort_mode'     => 'sort_order'
        ];
        $bannerDefaults = [
            'type' => 1,
            'sort_order' => 10,
            'html'       => '',
            'status'     => 1,
            'mode'       => 'image',
            'target'     => 'popup',
            'hide_url'   => 0
        ];
        $isSingleStore = $this->storeManager->isSingleStoreMode();
        foreach ($data as $placeholderData) {
            $placeholder = $placeholders->getItemByColumnValue(
                'name',
                $placeholderData['name']
            );

            if (!$placeholder) {
                $placeholder = $this->objectManager
                    ->create('Swissup\Easybanner\Model\Placeholder');

                try {
                    $placeholder
                        ->setData(array_merge($placeholderDefaults, $placeholderData))
                        ->save();
                } catch (\Exception $e) {
                    $this->fault('easybanner_placeholder_save', $e);
                    continue;
                }
            }

            $bannerDefaults['sort_order'] = 10;
            foreach ($placeholderData['banners'] as $bannerData) {
                if (!empty($bannerData['sort_order'])) {
                    $bannerDefaults['sort_order'] = $bannerData['sort_order'];
                }

                // we will use existing banner, if it is linked to our placeholder
                $collection = $banners->getItemsByColumnValue(
                    'identifier',
                    $bannerData['identifier']
                );
                foreach ($collection as $banner) {
                    // load store ids and placeholders
                    $banner->load($banner->getId());
                    if (in_array($placeholder->getId(), $banner->getPlaceholders())) {
                        // skip to the next banner in placeholderData['banners']
                        continue 2;
                    }
                }

                // create banner if needed
                $banner = $this->objectManager
                    ->create('Swissup\Easybanner\Model\Banner')
                    ->setData(array_merge($bannerDefaults, $bannerData))
                    ->setStores($this->getStoreIds())
                    ->setPlaceholders([$placeholder->getId()]);

                try {
                    $banner->save();
                } catch (\Exception $e) {
                    $this->fault('easybanner_banner_save', $e);
                }
                $bannerDefaults['sort_order'] += 5;
            }
        }
    }
}
