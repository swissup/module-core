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
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility
    ) {
        parent::__construct($objectManager, $localeDate, $storeManager);
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
                $visibleProducts = $this->productCollectionFactory->create()
                    ->setStoreId($storeId)
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
