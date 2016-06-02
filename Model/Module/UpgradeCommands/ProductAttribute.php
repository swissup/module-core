<?php

namespace Swissup\Core\Model\Module\UpgradeCommands;

class ProductAttribute extends \Swissup\Core\Model\Module\UpgradeCommands\AbstractCommand
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory
     */
    protected $attributeFactory;

    /**
     * @var \Magento\Catalog\Helper\Product
     */
    protected $productHelper;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory $attributeFactory,
        \Magento\Catalog\Helper\Product $productHelper
    ) {
        parent::__construct($objectManager, $localeDate, $storeManager);
        $this->attributeFactory = $attributeFactory;
        $this->productHelper = $productHelper;
    }

    /**
     * Add new product attrubute into all attribute sets.
     * If attribute is already exists - skip.
     *
     * @param  array $data Array of slider data arrays
     * <pre>
     * [
     *     attribute_code
     *     is_global
     *     frontend_input [text|boolean|textarea|select|price|media_image|etc]
     *     default_value_text
     *     is_searchable
     *     is_visible_in_advanced_search
     *     is_comparable
     *     frontend_label array
     *     sort_order Set 0 to use MaxSortOrder
     * ]
     * </pre>
     * @return void
     */
    public function execute($data)
    {
        $defaults = array(
            'is_global'               => 0,
            'frontend_input'          => 'boolean',
            'is_configurable'         => 0,
            'is_filterable'           => 0,
            'is_filterable_in_search' => 0,
            'sort_order'              => 1
        );

        $entityTypeId = $this->objectManager
            ->create('Magento\Eav\Model\Entity')
            ->setType(\Magento\Catalog\Model\Product::ENTITY)
            ->getTypeId();
        $attributeSets = $this->objectManager
            ->create('Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection')
            ->setEntityTypeFilter($entityTypeId);

        foreach ($data as $itemData) {
            /* @var $model \Magento\Catalog\Model\ResourceModel\Eav\Attribute */
            $model = $this->attributeFactory->create()
                ->load($itemData['attribute_code'], 'attribute_code');
            if ($model->getId()) {
                continue;
            }

            $itemData = array_merge($itemData, $defaults);

            $itemData['source_model'] = $this->productHelper->getAttributeSourceModelByInputType(
                $itemData['frontend_input']
            );
            $itemData['backend_model'] = $this->productHelper->getAttributeBackendModelByInputType(
                $itemData['frontend_input']
            );
            $itemData['backend_type'] = $model->getBackendTypeByInput($itemData['frontend_input']);

            $model->addData($itemData);
            $model->setEntityTypeId($entityTypeId);
            $model->setIsUserDefined(1);

            foreach ($attributeSets as $set) {
                $model->setAttributeSetId($set->getId());
                $model->setAttributeGroupId($set->getDefaultGroupId());
                try {
                    $model->save();
                } catch (\Exception $e) {
                    $this->fault('product_attribute_save', $e);
                }
            }

            if (!$attributeSets->count()) {
                try {
                    $model->save();
                } catch (\Exception $e) {
                    $this->fault('product_attribute_save', $e);
                }
            }
        }
    }
}
