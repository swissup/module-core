<?php

namespace Swissup\Core\Model\Module\UpgradeCommands;

class Products extends \Swissup\Core\Model\Module\UpgradeCommands\AbstractCommand
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    protected $attributeCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $catalogProductVisibility;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility
    ) {
        parent::__construct(
            $objectManager,
            $localeDate,
            $storeManager,
            $scopeConfig,
            $configWriter
        );
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->catalogProductVisibility = $catalogProductVisibility;
    }

    /**
     * Enable requested attribute for products, if no products are using it
     *
     * Date attributes is supported for attribute_from values only.
     *
     * @param  array $data Key => Value pairs of attribute_code and count
     * @return void
     */
    public function execute($data)
    {
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
                // customer already has some products with specified attribute
                continue;
            }

            foreach ($this->getStoreIds() as $storeId) {
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
                        (int)in_array(0, $this->getStoreIds()), // value
                        0  // storeId
                    );

                    $product->setStoreId($storeId)
                        ->setData($attribute->getAttributeCode(), $value)
                        ->save();
                }
            }
        }
    }
}
