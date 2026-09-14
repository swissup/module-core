<?php

namespace Swissup\Core\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

class Version extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item[$this->getData('name')] = $this->prepareItem($item);
            }
        }
        return $dataSource;
    }

    protected function prepareItem(array $item)
    {
        $value = $item[$this->getData('name')] ?? '';
        if (empty($value)) {
            return __('N/A');
        }

        if (empty($item['version'])) {
            return $value;
        }

        if (empty($item['is_outdated'])) {
            $severity = 'grid-severity-notice';
            $title = __('Module is up to date');
        } else {
            $severity = 'grid-severity-critical';
            $title = __('Module is outdated');
        }

        return '<span class="' . $severity . '" title="' . $title . '">'
            . $value
            . '</span>';
    }
}
