<?php

namespace Swissup\Core\Installer\Command;

use Swissup\Core\Installer\Request;
use Swissup\Core\Installer\LoggerAware;

class ProductCollection
{
    use LoggerAware;

    public function __construct(
        private \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeCollectionFactory,
        private \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        private \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        private \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        private \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @param Request $request
     * @return void
     */
    public function execute(Request $request)
    {
        $this->logger->info('Product Collection: Prepare collections');

        $data = $request->getParams();
        $visibility = $this->catalogProductVisibility->getVisibleInCatalogIds();
        $attributes = $this->attributeCollectionFactory->create()
            ->addFieldToFilter('attribute_code', ['in' => array_keys($data)]);

        foreach ($attributes as $attribute) {
            $collection = $this->productCollectionFactory->create()
                ->setPageSize(1)
                ->setCurPage(1);

            switch ($attribute->getFrontendInput()) {
                case 'boolean':
                    $value = 1;
                    $collection->addAttributeToFilter($attribute, 1);
                    break;
                case 'date':
                    $value = $this->localeDate->date()->format('Y-m-d H:i:s');
                    $collection->addAttributeToFilter(
                        $attribute,
                        [
                            [
                                'date' => true,
                                'to' => $value
                            ]
                        ]
                    );
                    break;
            }

            if ($collection->getSize()) {
                // store has products with specified attribute
                continue;
            }

            foreach ($request->getStoreIds() as $storeId) {
                $collectionStoreId = $storeId;

                if ($storeId == \Magento\Store\Model\Store::DEFAULT_STORE_ID) {
                    // compatibility with M2.2.5 when install on 'All Store Views'
                    $collectionStoreId = $this->storeManager->getDefaultStoreView()->getId();
                }

                $visibleProducts = $this->productCollectionFactory->create()
                    ->setStoreId($collectionStoreId)
                    ->setVisibility($visibility)
                    ->addStoreFilter($storeId)
                    ->setPageSize($data[$attribute->getAttributeCode()])
                    ->setCurPage(1);

                if (!$visibleProducts->getSize()) {
                    continue;
                }

                foreach ($visibleProducts as $product) {
                    $product->addAttributeUpdate(
                        $attribute->getAttributeCode(),
                        (int) in_array(0, $request->getStoreIds()), // value
                        \Magento\Store\Model\Store::DEFAULT_STORE_ID
                    );

                    $product->setStoreId($storeId)
                        ->setData($attribute->getAttributeCode(), $value)
                        ->save();
                }
            }
        }
    }
}
