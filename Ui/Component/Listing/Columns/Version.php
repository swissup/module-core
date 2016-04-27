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
        if (empty($item[$this->getData('name')])) {
            return __('N/A');
        }

        $currentVersion = $item[$this->getData('name')];
        $latestVersion = $item[$this->getData('config/compareWith')];
        $result = version_compare($currentVersion, $latestVersion, '>=');
        if ($result) {
            $severity = 'grid-severity-notice';
            $title = __('Module is up to date');
        } else {
            $severity = 'grid-severity-critical';
            $title = __("The latest version is %1", $latestVersion);
        }

        return '<span class="' . $severity . '" title="' . $title . '">'
            . $item[$this->getData('name')]
            . '</span>';
    }
}
