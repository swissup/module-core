<?php

namespace Swissup\Core\Installer\Command;

use Swissup\Core\Installer\Request;
use Psr\Log\LoggerAwareTrait;

class ProductAttribute
{
    use LoggerAwareTrait;

    public function __construct(
        private \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory $attributeFactory,
        private \Magento\Catalog\Helper\Product $productHelper,
        private \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        private \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attributeSetCollectionFactory
    ) {
    }

    public function execute(Request $request)
    {
        $this->logger->info('Product Attributes: Update attributes');

        $entityTypeId = $this->eavEntityFactory->create()
            ->setType(\Magento\Catalog\Model\Product::ENTITY)
            ->getTypeId();
        $attributeSets = $this->attributeSetCollectionFactory->create()
            ->setEntityTypeFilter($entityTypeId);

        foreach ($request->getParams() as $data) {
            /* @var $model \Magento\Catalog\Model\ResourceModel\Eav\Attribute */
            $model = $this->attributeFactory->create()
                ->load($data['attribute_code'], 'attribute_code');
            if ($model->getId()) {
                continue;
            }

            $data = array_merge([
                'is_global'=> 0,
                'frontend_input'=> 'boolean',
                'is_configurable'=> 0,
                'is_filterable'=> 0,
                'is_filterable_in_search' => 0,
                'sort_order' => 1,
            ], $data);

            $data['source_model'] = $this->productHelper->getAttributeSourceModelByInputType(
                $data['frontend_input']
            );
            $data['backend_model'] = $this->productHelper->getAttributeBackendModelByInputType(
                $data['frontend_input']
            );
            $data['backend_type'] = $model->getBackendTypeByInput($data['frontend_input']);

            $model->addData($data);
            $model->setEntityTypeId($entityTypeId);
            $model->setIsUserDefined(1);

            foreach ($attributeSets as $set) {
                $model->setAttributeSetId($set->getId());
                $model->setAttributeGroupId($set->getDefaultGroupId());
                try {
                    $model->save();
                } catch (\Exception $e) {
                    $this->logger->warning($e->getMessage());
                }
            }

            if (!$attributeSets->count()) {
                try {
                    $model->save();
                } catch (\Exception $e) {
                    $this->logger->warning($e->getMessage());
                }
            }
        }
    }
}
