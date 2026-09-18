<?php

namespace Swissup\Core\Installer\Command;

use Magento\Store\Model\Store;
use Swissup\Core\Installer\Request;
use Swissup\Core\Installer\LoggerAware;

class CategoryUpdate
{
    use LoggerAware;

    public function __construct(
        private \Swissup\Core\Installer\Helper\Collection $collectionHelper
    ) {
    }

    /**
     * @param Request $request
     * @return void
     */
    public function execute(Request $request)
    {
        $this->logger->info('Category Update: Prepare category data');

        foreach ($request->getParams() as $data) {
            $collection = $this->collectionHelper->getCollection(
                [],
                \Magento\Catalog\Model\ResourceModel\Category\Collection::class,
                $data['filters'] ?? []
            );

            $storeIds = $data['store_id'] ?? [Store::DEFAULT_STORE_ID];
            if (!is_array($storeIds)) {
                $storeIds = [$storeIds];
            }

            foreach ($collection as $category) {
                foreach ($data['data'] as $key => $value) {
                    $category
                        ->setData($key, $value)
                        ->setCustomAttribute($key, $value);
                }

                foreach ($storeIds as $storeId) {
                    try {
                        $category->setStoreId($storeId)->save();
                    } catch (\Exception $e) {
                        $this->logger->warning($e->getMessage());
                    }
                }
            }
        }
    }
}
