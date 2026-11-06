<?php

declare(strict_types=1);

namespace Swissup\Core\Model;

use Magento\Framework\App\Cache\Type\Config as ConfigCache;
use Magento\Framework\App\CacheInterface;
use Psr\Log\LoggerInterface;
use Swissup\Core\Model\PageSpeed\CheckInterface;
use Swissup\Core\Model\PageSpeed\CheckPool;

class PageSpeedStatus
{
    public const CACHE_ID = 'swissup_core_pagespeed_status';
    public const CACHE_LIFETIME = 3600;

    private ?array $result = null;

    public function __construct(
        private CheckPool $checkPool,
        private CacheInterface $cache,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @return array[] Results of the applicable checks
     */
    public function run(): array
    {
        if ($this->result !== null) {
            return $this->result;
        }

        $this->result = [];
        foreach ($this->checkPool->getChecks() as $id => $check) {
            try {
                $result = $check->run();
            } catch (\Throwable $e) {
                // a broken check from a third-party module shouldn't break the admin
                $this->logger->error($e->getMessage(), ['exception' => $e]);
                continue;
            }

            if ($result !== null) {
                $this->result[$id] = $result;
            }
        }

        // config changes clean the stored counter, see getCount
        $this->cache->save(
            (string) $this->getIssuesCount($this->result),
            self::CACHE_ID,
            [ConfigCache::CACHE_TAG],
            self::CACHE_LIFETIME
        );

        return $this->result;
    }

    /**
     * @return int The menu badge counter: 1 when something can be improved
     */
    public function getCount(): int
    {
        // the badge is rendered on every admin page, so the stored value is used
        $count = $this->cache->load(self::CACHE_ID);
        if ($count === false) {
            $count = $this->getIssuesCount($this->run());
        }

        return (int) $count > 0 ? 1 : 0;
    }

    /**
     * @param array[] $results
     * @return int Not optimal required settings
     */
    public function getIssuesCount(array $results): int
    {
        return $this->countFailed($results, CheckInterface::TYPE_REQUIRED);
    }

    /**
     * @param array[] $results
     * @param string $type
     * @return int
     */
    public function countFailed(array $results, string $type): int
    {
        return count(array_filter($results, function ($result) use ($type) {
            return !$result['passed'] && $result['type'] === $type;
        }));
    }
}
