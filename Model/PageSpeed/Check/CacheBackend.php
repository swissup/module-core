<?php

declare(strict_types=1);

namespace Swissup\Core\Model\PageSpeed\Check;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Swissup\Core\Model\PageSpeed\CheckInterface;

class CacheBackend implements CheckInterface
{
    private const XML_PATH_CACHING_APPLICATION = 'system/full_page_cache/caching_application';
    private const BUILT_IN = 1;

    public function __construct(
        private DeploymentConfig $deploymentConfig,
        private ScopeConfigInterface $scopeConfig
    ) {
    }

    public function run(): ?array
    {
        $frontends = [
            'default' => [
                'label' => (string) __('Default cache'),
                'command' => 'bin/magento setup:config:set --cache-backend=redis --cache-backend-redis-server=127.0.0.1 --cache-backend-redis-db=0',
            ],
        ];

        // the page cache storage is not used with Varnish
        if ((int) $this->scopeConfig->getValue(self::XML_PATH_CACHING_APPLICATION) === self::BUILT_IN) {
            $frontends['page_cache'] = [
                'label' => (string) __('Page cache'),
                'command' => 'bin/magento setup:config:set --page-cache=redis --page-cache-redis-server=127.0.0.1 --page-cache-redis-db=1',
            ];
        }

        $current = [];
        $commands = [];
        foreach ($frontends as $id => $frontend) {
            $backend = (string) $this->deploymentConfig->get("cache/frontend/{$id}/backend");

            if (!preg_match('/redis|valkey/i', $backend)) {
                $commands[] = $frontend['command'];
            }

            $current[] = $frontend['label'] . ': ' . $this->getBackendLabel($backend);
        }

        return [
            'title' => (string) __('Cache Storage'),
            'description' => (string) __(
                'Redis or Valkey keeps the cache in memory, which is faster than the file system and works for multiple servers.'
            ),
            'passed' => !$commands,
            'type' => self::TYPE_SUGGESTION,
            'current' => implode('; ', $current),
            'recommended' => 'Redis / Valkey',
            'scope' => [],
            'commands' => $commands,
            'note' => $commands ? (string) __(
                'Install Redis or Valkey first, and change the server and database options in the commands.'
            ) : '',
            'url' => '',
        ];
    }

    private function getBackendLabel(string $backend): string
    {
        if ($backend === '' || preg_match('/file/i', $backend)) {
            return (string) __('File system');
        }

        if (preg_match('/redis|valkey|memcache/i', $backend, $matches)) {
            return ucfirst(strtolower($matches[0]));
        }

        return $backend;
    }
}
