<?php

namespace Swissup\Core\Ui\DataProvider;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Swissup\Core\Model\ComponentList\Loader;

/**
 * Swissup modules grid data, taken from the component list instead of the database
 */
class ModuleListingDataProvider extends DataProvider
{
    const SEARCH_FIELDS = ['code', 'name'];

    const DATE_FIELDS = ['release_date'];

    private Loader $loader;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param Loader $loader
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
        Loader $loader,
        array $meta = [],
        array $data = []
    ) {
        $this->loader = $loader;
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
        $items = $this->loader->getItems();

        foreach ($criteria->getFilterGroups() as $group) {
            foreach ($group->getFilters() as $filter) {
                if ($filter->getConditionType() !== 'fulltext') {
                    continue;
                }
                $items = array_filter($items, function ($item) use ($filter) {
                    return $this->isSearchMatch($item, (string) $filter->getValue());
                });
            }
        }

        $sortOrders = $criteria->getSortOrders() ?: [];
        usort($items, function ($a, $b) use ($sortOrders) {
            // installed and outdated modules are always at the top
            $result = [!$a['is_installed'], !$a['is_outdated']]
                <=> [!$b['is_installed'], !$b['is_outdated']];

            foreach ($sortOrders as $sortOrder) {
                if ($result) {
                    break;
                }

                $field = (string) $sortOrder->getField();
                $result = $this->normalize($field, $a[$field] ?? null)
                    <=> $this->normalize($field, $b[$field] ?? null);

                if ($sortOrder->getDirection() === SortOrder::SORT_DESC) {
                    $result = -$result;
                }
            }

            return $result;
        });

        $totalRecords = count($items);
        if ($pageSize = (int) $criteria->getPageSize()) {
            $page = max(1, (int) $criteria->getCurrentPage());
            $items = array_slice($items, ($page - 1) * $pageSize, $pageSize);
        }

        return [
            'items' => $items,
            'totalRecords' => $totalRecords,
        ];
    }

    private function isSearchMatch(array $item, $value)
    {
        foreach (self::SEARCH_FIELDS as $key) {
            if (isset($item[$key]) && mb_stripos($item[$key], $value) !== false) {
                return true;
            }
        }

        return false;
    }

    private function normalize($field, $value)
    {
        if (in_array($field, self::DATE_FIELDS)) {
            return $value ? (int) strtotime($value) : 0;
        }

        return is_string($value) ? mb_strtolower($value) : $value;
    }
}
