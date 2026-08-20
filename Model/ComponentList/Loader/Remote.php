<?php

namespace Swissup\Core\Model\ComponentList\Loader;

use Swissup\Core\Model\FileStorage;

class Remote extends AbstractLoader
{
    const XML_USE_HTTPS_PATH = 'swissup_core/modules/use_https';
    const XML_FEED_URL_PATH  = 'swissup_core/modules/url';
    const RESPONSE_STORAGE_KEY = 'packages';
    const VERSION_STORAGE_KEY = 'packages_version';
    const LASTCHECK_STORAGE_KEY = 'packages_lastcheck';

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @var \Magento\Framework\HTTP\ClientFactory
     */
    protected $httpClientFactory;

    private FileStorage $storage;

    private ?string $packagesVersion = null;

    private bool $offlineMode = false;

    public function __construct(
        \Swissup\Core\Helper\Component $componentHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\HTTP\ClientFactory $httpClientFactory,
        FileStorage $storage
    ) {
        parent::__construct($componentHelper, $logger);
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->jsonHelper = $jsonHelper;
        $this->httpClientFactory = $httpClientFactory;
        $this->storage = $storage;
    }

    public function getMapping()
    {
        return [
            'description' => 'description',
            'keywords' => 'keywords',
            'name' => 'name',
            'version' => 'latest_version',
            'type' => 'type',
            'time' => 'release_date',
            'extra.swissup.links.store' => 'link',
            'extra.swissup.links.docs' => 'docs_link',
            'extra.swissup.links.download' => 'download_link',
            'extra.swissup.links.changelog' => 'changelog_link',
            'extra.swissup.links.marketplace' => 'marketplace_link',
            'extra.swissup.links.identity_key' => 'identity_key_link',
            'extra.swissup.purchase_code' => 'purchase_code',
        ];
    }

    /**
     * Retrieve component names and configs from remote satis repository
     *
     * @return \Traversable
     */
    public function getComponentsInfo()
    {
        $modules = [];
        $response = $this->loadPackagesData();

        if (!empty($response['packages'])) {
            foreach ($response['packages'] as $packageName => $info) {
                $versions = array_keys($info);
                $latestVersion = array_reduce($versions, function ($carry, $item) {
                    if (version_compare($carry, $item) === -1) {
                        $carry = $item;
                    }
                    return $carry;
                }, $versions[0] ?? 0);

                if (!empty($info[$latestVersion]['type']) &&
                    $info[$latestVersion]['type'] === 'metapackage'
                ) {
                    continue;
                }

                $modules[$packageName] = $info[$latestVersion];

                if (isset($info['dev-master']['extra']['swissup'])) {
                    $modules[$packageName]['extra']['swissup'] =
                        $info['dev-master']['extra']['swissup'];
                }
            }
        }

        $modules['swissup/subscription'] = [
            'name'          => 'swissup/subscription',
            'type'          => 'subscription-plan',
            'description'   => 'SwissUpLabs Modules Subscription',
            'version'       => '',
            'extra' => [
                'swissup' => [
                    'links' => [
                        'store' => 'https://swissuplabs.com',
                        'download' => 'https://swissuplabs.com/subscription/customer/products/',
                        'identity_key' => 'https://swissuplabs.com/license/customer/identity/'
                    ]
                ]
            ]
        ];

        return $modules;
    }

    /**
     * Forget when the remote version was checked last time, so that the next
     * load re-checks the feed instead of waiting for the throttle to expire.
     *
     * @return $this
     */
    public function refresh()
    {
        $this->storage->remove(self::LASTCHECK_STORAGE_KEY);
        $this->setIsLoaded(false);

        return $this;
    }

    /**
     * Use the previously stored packages only, without querying the remote source
     */
    public function setOfflineMode($flag = true)
    {
        if ((bool) $flag !== $this->offlineMode) {
            $this->offlineMode = (bool) $flag;
            $this->items = [];
            $this->setIsLoaded(false);
        }

        return $this;
    }

    /**
     * When the remote source was checked last time, if it was checked recently
     *
     * @return int|null
     */
    public function getLastCheckTime()
    {
        $time = $this->storage->load(self::LASTCHECK_STORAGE_KEY);

        return $time ? (int) $time : null;
    }

    protected function loadPackagesData()
    {
        if ($this->offlineMode) {
            return $this->loadStoredPackages();
        }

        $storedVersion = $this->storage->load(self::VERSION_STORAGE_KEY);
        if ($storedVersion && !$this->isVersionCheckRequired()) {
            if ($packages = $this->loadStoredPackages()) {
                return $packages;
            }
        }

        if (!$version = $this->fetchPackagesVersion()) {
            return $this->loadStoredPackages();
        }

        $this->updateVersionCheckTime();
        if ($version === $storedVersion && $packages = $this->loadStoredPackages()) {
            return $packages;
        }

        // Prevent parallel downloads caused by multiple admin accounts. The
        // lock is held by $lock variable, and released once this method returns.
        $lock = $this->storage->lock(self::RESPONSE_STORAGE_KEY);

        if (!$lock) {
            if ($packages = $this->loadStoredPackages()) {
                return $packages;
            }

            for ($i = 0; $i < 5; $i++) {
                sleep(1);
                if ($this->storage->lock(self::RESPONSE_STORAGE_KEY)) {
                    break;
                }
            }

            return $this->loadStoredPackages();
        }

        // The version was fetched before the lock was taken, so another process
        // could have stored the very list we are about to download
        if ($version === $this->storage->load(self::VERSION_STORAGE_KEY)
            && $packages = $this->loadStoredPackages()
        ) {
            return $packages;
        }

        $responseBody = $this->fetch($this->getPackagesUrl());
        $response = $this->decodeResponse($responseBody);

        if (empty($response['packages'])) {
            return $this->loadStoredPackages();
        }

        try {
            $this->storage->save($responseBody, self::RESPONSE_STORAGE_KEY);
            $this->storage->save($version, self::VERSION_STORAGE_KEY);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        return $response;
    }

    protected function loadStoredPackages(): array
    {
        $response = $this->decodeResponse($this->storage->load(self::RESPONSE_STORAGE_KEY));
        return empty($response['packages']) ? [] : $response;
    }

    protected function decodeResponse($responseBody): array
    {
        if (!$responseBody) {
            return [];
        }

        try {
            $response = $this->jsonHelper->jsonDecode($responseBody);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            return [];
        }

        return is_array($response) ? $response : [];
    }

    /**
     * Whether the remote source was not checked within the last hour
     *
     * @return bool
     */
    public function isVersionCheckRequired()
    {
        return !$this->storage->load(self::LASTCHECK_STORAGE_KEY);
    }

    private function updateVersionCheckTime()
    {
        try {
            $this->storage->save((string) time(), self::LASTCHECK_STORAGE_KEY, 3600 * 1);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        return $this;
    }

    /**
     * Make a http request and return response body
     *
     * @param  string $url
     * @return string
     */
    protected function fetch($url)
    {
        $client = $this->httpClientFactory->create();
        $client->setOption(CURLOPT_FOLLOWLOCATION, true);
        $client->setOption(CURLOPT_MAXREDIRS, 5);
        $client->setTimeout(30);

        try {
            $client->get($url);
        } catch (\Exception $e) {
            // Connection errors are thrown by the client. Swallow them, so
            // that the caller can fall back to the previously stored data.
            $this->logger->critical($e->getMessage());
            return '';
        }

        // Only the very first status line is recorded by the client, so a
        // followed redirect keeps reporting 3xx here. Hence 4xx and 5xx only -
        // the response contents is validated by the caller anyway.
        $status = $client->getStatus();
        if ($status >= 400) {
            $this->logger->critical(
                sprintf('Request to %s failed with status %s', $url, $status)
            );
            return '';
        }

        return $client->getBody();
    }

    /**
     * Get packages url from satis repository.
     *
     * To do that we send a request to http://docs.swissuplabs.com/packages/packages.json,
     * which returns actual packages list url: http://docs.swissuplabs.com/packages/include/all${sha1}.json
     *
     * @return mixed
     */
    protected function getPackagesUrl()
    {
        if (!$version = $this->fetchPackagesVersion()) {
            return false;
        }
        return $this->getPackagesUrlPrefix() . '/' . $version;
    }

    protected function fetchPackagesVersion()
    {
        if ($this->packagesVersion === null) {
            $response = $this->decodeResponse(
                $this->fetch($this->getPackagesUrlPrefix() . '/packages.json')
            );
            if (empty($response['includes'])) {
                return false;
            }
            // include/all${sha1}.json
            $this->packagesVersion = key($response['includes']);
        }
        return $this->packagesVersion;
    }

    protected function getPackagesUrlPrefix()
    {
        $useHttps = $this->scopeConfig->getValue(
            self::XML_USE_HTTPS_PATH,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $url = $this->scopeConfig->getValue(
            self::XML_FEED_URL_PATH,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        // docs.swissuplabs.com/packages
        return ($useHttps ? 'https://' : 'http://') . $url;
    }
}
