<?php

namespace Swissup\Core\Ui\DataProvider;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Swissup\Core\Model\PatchStatus;

class SecurityListingDataProvider extends DataProvider
{
    private PatchStatus $patchStatus;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param PatchStatus $patchStatus
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        PatchStatus $patchStatus,
        array $meta = [],
        array $data = []
    ) {
        $this->patchStatus = $patchStatus;
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
    }

    public function getData()
    {
        $criteria = $this->getSearchCriteria();
        $items = [];

        // the index page runs the check, grid requests reuse its stored result
        foreach ($this->patchStatus->getStored()['patches'] ?? [] as $id => $status) {
            $items[] = ['id' => $id, 'status' => $status];
        }

        foreach ($criteria->getFilterGroups() as $group) {
            foreach ($group->getFilters() as $filter) {
                if ($filter->getConditionType() !== 'fulltext') {
                    continue;
                }
                $items = array_filter($items, function ($item) use ($filter) {
                    return mb_stripos($item['id'], (string) $filter->getValue()) !== false;
                });
            }
        }

        return [
            'items' => array_values($items),
            'totalRecords' => count($items),
        ];
    }
}
