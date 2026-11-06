<?php

declare(strict_types=1);

namespace Swissup\Core\Model\PageSpeed\Check;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Swissup\Core\Model\PageSpeed\CheckInterface;

class FullPageCache implements CheckInterface
{
    // Magento\PageCache\Model\Config constants, the module is not required
    private const XML_PATH_CACHING_APPLICATION = 'system/full_page_cache/caching_application';
    private const BUILT_IN = 1;
    private const VARNISH = 2;

    public function __construct(
        private ScopeConfigInterface $scopeConfig
    ) {
    }

    public function run(): ?array
    {
        $application = (int) $this->scopeConfig->getValue(self::XML_PATH_CACHING_APPLICATION);
        // Varnish or another caching service, e.g. Fastly
        $passed = $application !== self::BUILT_IN;

        return [
            'title' => (string) __('Full Page Cache Application'),
            'description' => (string) __(
                'Varnish serves cached pages without running Magento, so the server responds much faster than with the built-in cache. It must be installed on the server.'
            ),
            'passed' => $passed,
            'type' => self::TYPE_SUGGESTION,
            'current' => $passed ? (string) __('Varnish or external service') : (string) __('Built-in'),
            'recommended' => (string) __('Varnish'),
            'scope' => [],
            'commands' => $passed ? [] : [
                'bin/magento config:set ' . self::XML_PATH_CACHING_APPLICATION . ' ' . self::VARNISH,
            ],
            'note' => $passed ? '' : (string) __(
                'Install and configure Varnish first. You can export the Varnish configuration for your store in Stores > Configuration > Advanced > System > Full Page Cache.'
            ),
            'url' => '',
        ];
    }
}
