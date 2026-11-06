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
                $url = $item[$link['key']] ?? null;
                if (!is_string($url) || !str_starts_with($url, 'https://')) {
                    continue;
                }

                $item[$this->getData('name')][$link['key']] = [
                    'href'  => $url,
                    'label' => __($link['label']),
                    'target' => '_blank',
                    'rel' => 'noreferrer noopener',
                    // the grid evaluates ${...} in action properties as js templates
                    '__disableTmpl' => true,
                ];
            }
        }
        return $dataSource;
    }
}
