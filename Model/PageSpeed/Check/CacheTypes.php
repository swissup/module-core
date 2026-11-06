<?php

declare(strict_types=1);

namespace Swissup\Core\Model\PageSpeed\Check;

use Magento\Framework\App\Cache\TypeListInterface;
use Swissup\Core\Model\PageSpeed\CheckInterface;

class CacheTypes implements CheckInterface
{
    public function __construct(
        private TypeListInterface $cacheTypeList
    ) {
    }

    public function run(): ?array
    {
        $disabled = [];
        foreach ($this->cacheTypeList->getTypes() as $id => $type) {
            if (!$type->getStatus()) {
                $disabled[$id] = (string) $type->getCacheType();
            }
        }

        return [
            'title' => (string) __('Cache Types'),
            'description' => (string) __(
                'Every disabled cache type makes Magento rebuild the data on each request. The Page Cache is the most important one.'
            ),
            'passed' => !$disabled,
            'type' => self::TYPE_REQUIRED,
            'current' => $disabled
                ? (string) __('Disabled: %1', implode(', ', $disabled))
                : (string) __('All enabled'),
            'recommended' => (string) __('All enabled'),
            'scope' => [],
            'commands' => $disabled ? ['bin/magento cache:enable ' . implode(' ', array_keys($disabled))] : [],
            'note' => '',
            'url' => '',
        ];
    }
}
