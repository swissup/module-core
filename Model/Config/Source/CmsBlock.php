<?php

namespace Swissup\Core\Model\Config\Source;

use Magento\Cms\Model\ResourceModel\Block\CollectionFactory;

class CmsBlock implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Block collection factory
     *
     * @var CollectionFactory
     */
    protected $blockCollectionFactory;

    /**
     * Construct
     *
     * @param CollectionFactory $blockCollectionFactory
     */
    public function __construct(CollectionFactory $blockCollectionFactory)
    {
        $this->blockCollectionFactory = $blockCollectionFactory;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $collection = $this->blockCollectionFactory->create()
            ->setOrder('title', 'ASC');

        $options = [];
        foreach ($collection as $block) {
            $entry = [
                'value' => $block->getId(),
                'label' => $block->getTitle()
            ];

            if (is_array($block->getStoreId()) && !in_array(0, $block->getStoreId())) {
                $entry['label'] .= ' (' . implode(', ', $block->getStoreId()) . ')';
            }

            $options[] = $entry;
        }

        array_unshift($options, ['value' => '0', 'label' => __('No')]);

        return $options;
    }
}
