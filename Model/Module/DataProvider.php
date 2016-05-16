<?php

namespace Swissup\Core\Model\Module;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var \Swissup\Core\Model\ResourceModel\Module\Collection
     */
    protected $collection;

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $systemStore;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param \Swissup\Core\Model\ResourceModel\Module\CollectionFactory $collectionFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \Swissup\Core\Model\ResourceModel\Module\CollectionFactory $collectionFactory,
        \Magento\Store\Model\System\Store $systemStore,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->systemStore = $systemStore;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $stores = $this->systemStore->getStoreOptionHash();
        $stores[0] = __('All Store Views');

        $items = $this->collection->getItems();
        /** @var \Swissup\Core\Model\Module $module */
        foreach ($items as $module) {
            $result['general'] = $module->getData();

            $oldStores = implode(
                "\n",
                array_intersect_key(
                    $stores,
                    array_flip($module->getOldStores())
                )
            );
            if (!$oldStores) {
                $oldStores = __('None');
            }
            $result['general']['store_labels'] = $oldStores;

            $this->loadedData[$module->getCode()] = $result;
        }

        return $this->loadedData;
    }
}
