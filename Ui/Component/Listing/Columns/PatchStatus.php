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
            'missing' => [__('Not Applied'), 'critical'],
            'unknown' => [__('Unknown'), 'minor'],
            'applied' => [__('Applied'), 'notice'],
        ];

        foreach ($dataSource['data']['items'] ?? [] as $i => $item) {
            [$label, $severity] = $labels[$item['status']];
            $dataSource['data']['items'][$i]['status'] = '<span class="grid-severity-' . $severity . '"><span>'
                . $label
                . '</span></span>';
        }

        return $dataSource;
    }
}
