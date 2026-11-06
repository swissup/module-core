<?php

declare(strict_types=1);

namespace Swissup\Core\Model\PageSpeed\Check;

use Magento\Config\Model\Config\Reader\Source\Deployed\SettingChecker;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Swissup\Core\Model\PageSpeed\CheckInterface;

/**
 * Compares a config value with the recommended one in every active store view.
 * Declare checks as virtual types of this class in di.xml.
 */
class ConfigValue implements CheckInterface
{
    /**
     * @var StoreInterface[]|null
     */
    private ?array $stores = null;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $configCollectionFactory
     * @param SettingChecker $settingChecker
     * @param string $path Config path
     * @param string $value Recommended value
     * @param array $options Value labels, Yes/No flag when empty
     * @param string $title
     * @param string $description
     * @param string $type One of CheckInterface::TYPE_* constants
     * @param bool $global Whether the setting has the default scope only
     * @param string $url
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private StoreManagerInterface $storeManager,
        private CollectionFactory $configCollectionFactory,
        private SettingChecker $settingChecker,
        private string $path = '',
        private string $value = '1',
        private array $options = [],
        private string $title = '',
        private string $description = '',
        private string $type = self::TYPE_REQUIRED,
        private bool $global = false,
        private string $url = ''
    ) {
    }

    public function run(): ?array
    {
        $result = [
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'recommended' => $this->getLabel($this->value),
            'scope' => [],
            'commands' => [],
            'note' => '',
            'url' => $this->url,
        ];

        if ($this->global) {
            $current = $this->getValue(ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
            $result['passed'] = $current === $this->value;
            $result['current'] = $this->getLabel($current);

            return $this->addFix($result, $result['passed'] ? [] : [[ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null]]);
        }

        $stores = $this->getStores();
        if (!$stores) {
            return null;
        }

        $values = [];
        $failed = [];
        foreach ($stores as $store) {
            $value = $this->getValue(ScopeInterface::SCOPE_STORE, (int) $store->getId());
            $values[$value] = $this->getLabel($value);
            if ($value !== $this->value) {
                $failed[] = $store;
            }
        }

        $result['passed'] = !$failed;
        $result['current'] = implode(', ', $values);

        // the list of affected stores is useful when some of them are fine
        if ($failed && count($failed) < count($stores)) {
            $result['scope'] = array_map(function (StoreInterface $store) {
                return $store->getName();
            }, $failed);
        }

        return $this->addFix($result, $this->getSourceScopes($failed));
    }

    /**
     * Add CLI commands that apply the recommended value.
     *
     * @param array $result
     * @param array $scopes [scope, scope code] pairs to fix
     * @return array
     */
    private function addFix(array $result, array $scopes): array
    {
        foreach ($scopes as [$scope, $code]) {
            if ($this->settingChecker->isReadOnly($this->path, $scope, $code)) {
                $result['note'] = (string) __(
                    'The value is locked in app/etc/env.php, app/etc/config.php or an environment variable. Change it there.'
                );
                continue;
            }

            $result['commands'][] = sprintf(
                'bin/magento config:set%s %s %s',
                $code === null ? '' : " --scope={$scope} --scope-code={$code}",
                $this->path,
                $this->value
            );
        }

        return $result;
    }

    /**
     * Find the scopes the other values come from.
     *
     * @param StoreInterface[] $stores
     * @return array [scope, scope code] pairs, the default scope goes first
     */
    private function getSourceScopes(array $stores): array
    {
        if (!$stores) {
            return [];
        }

        $rows = [];
        $collection = $this->configCollectionFactory->create()
            ->addFieldToFilter('path', $this->path);
        foreach ($collection as $row) {
            $rows[$row->getScope() . ':' . $row->getScopeId()] = true;
        }

        $scopes = [
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT => [],
            ScopeInterface::SCOPE_WEBSITES => [],
            ScopeInterface::SCOPE_STORES => [],
        ];
        foreach ($stores as $store) {
            if (isset($rows[ScopeInterface::SCOPE_STORES . ':' . $store->getId()])) {
                $scopes[ScopeInterface::SCOPE_STORES][$store->getCode()] = [
                    ScopeInterface::SCOPE_STORES,
                    $store->getCode(),
                ];
            } elseif (isset($rows[ScopeInterface::SCOPE_WEBSITES . ':' . $store->getWebsiteId()])) {
                $code = $this->storeManager->getWebsite($store->getWebsiteId())->getCode();
                $scopes[ScopeInterface::SCOPE_WEBSITES][$code] = [ScopeInterface::SCOPE_WEBSITES, $code];
            } else {
                $scopes[ScopeConfigInterface::SCOPE_TYPE_DEFAULT][''] = [
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    null,
                ];
            }
        }

        return array_merge(...array_map('array_values', array_values($scopes)));
    }

    /**
     * @return StoreInterface[]
     */
    private function getStores(): array
    {
        if ($this->stores === null) {
            $this->stores = array_filter($this->storeManager->getStores(), function (StoreInterface $store) {
                return (bool) $store->getIsActive();
            });
        }

        return $this->stores;
    }

    private function getValue(string $scope, ?int $scopeId = null): string
    {
        if (!$this->options) {
            return $this->scopeConfig->isSetFlag($this->path, $scope, $scopeId) ? '1' : '0';
        }

        return (string) $this->scopeConfig->getValue($this->path, $scope, $scopeId);
    }

    private function getLabel(string $value): string
    {
        if (!$this->options) {
            return (string) ($value === '1' ? __('Yes') : __('No'));
        }

        return (string) ($this->options[$value] ?? $value);
    }
}
