<?php

namespace Swissup\Core\Ui\Component\Listing\Columns;

class ModuleActions extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as & $item) {
            // add external links
            foreach ($this->getData('links') as $link) {
                if (empty($item[$link['key']])) {
                    continue;
                }

                $item[$this->getData('name')][$link['key']] = [
                    'href'  => $item[$link['key']],
                    'label' => __($link['label']),
                    'target' => '_blank',
                    'rel' => 'noreferrer noopener',
                ];
            }
        }
        return $dataSource;
    }
}
