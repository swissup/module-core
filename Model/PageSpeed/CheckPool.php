<?php

declare(strict_types=1);

namespace Swissup\Core\Model\PageSpeed;

class CheckPool
{
    /**
     * @var array[]
     */
    private array $checks;

    /**
     * @param array $checks Items with 'check' (CheckInterface) and 'sortOrder' keys
     */
    public function __construct(array $checks = [])
    {
        $checks = array_filter($checks, function ($item) {
            return isset($item['check']) && $item['check'] instanceof CheckInterface;
        });

        // di.xml arrays are merged in the modules load order
        uasort($checks, function ($a, $b) {
            return ($a['sortOrder'] ?? 0) <=> ($b['sortOrder'] ?? 0);
        });

        $this->checks = $checks;
    }

    /**
     * @return CheckInterface[]
     */
    public function getChecks(): array
    {
        return array_map(function ($item) {
            return $item['check'];
        }, $this->checks);
    }
}
