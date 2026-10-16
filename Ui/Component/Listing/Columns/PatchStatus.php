<?php

namespace Swissup\Core\Ui\Component\Listing\Columns;

class PatchStatus extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        $labels = [
            'missing' => __('Not Applied'),
            'unknown' => __('Unknown'),
            'applied' => __('Applied'),
        ];

        foreach ($dataSource['data']['items'] ?? [] as $i => $item) {
            $status = $item['status'];
            $severity = $status === 'applied' ? 'notice' : 'critical';
            $dataSource['data']['items'][$i]['status'] = '<span class="grid-severity-' . $severity . '"><span>'
                . $labels[$status]
                . '</span></span>';
        }

        return $dataSource;
    }
}
