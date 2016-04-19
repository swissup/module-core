<?php

namespace Swissup\Core\Model\ResourceModel\Module;

class RemoteCollection extends \Magento\Framework\Data\Collection
{
    const XML_USE_HTTPS_PATH = 'swissup_core/modules/use_https';
    const XML_FEED_URL_PATH  = 'swissup_core/modules/url';

    protected $collectedModules = array();

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
     * @var \Magento\Framework\HTTP\ZendClientFactory
     */
    protected $httpClientFactory;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory
    ) {
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->jsonHelper = $jsonHelper;
        $this->httpClientFactory = $httpClientFactory;
    }

    /**
     * Load data
     *
     * @param bool $printQuery
     * @param bool $logQuery
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function loadData($printQuery = false, $logQuery = false)
    {
        if ($this->isLoaded()) {
            return $this;
        }

        try {
            $modules = $this->fetchPackages($this->getFeedUrl());
            // do not use remote response
            // Need to rewrite Subscription_Checker logic
            $modules = array(
                'Swissup_Subscription' => array(
                    'code'          => 'Swissup_Subscription',
                    'version'       => '',
                    'link'          => 'https://swissuplabs.com',
                    'download_link' => 'https://swissuplabs.com/subscription/customer/products/',
                    'identity_key_link' => 'https://swissuplabs.com/license/customer/identity/'
                )
            );
        } catch (\Exception $e) {
            $modules = array(
                'Swissup_Subscription' => array(
                    'code'          => 'Swissup_Subscription',
                    'version'       => '',
                    'link'          => 'https://swissuplabs.com',
                    'download_link' => 'https://swissuplabs.com/subscription/customer/products/',
                    'identity_key_link' => 'https://swissuplabs.com/license/customer/identity/'
                )
            );
        }
        foreach ($modules as $moduleName => $values) {
            $values['id'] = $values['code'];
            $this->collectedModules[$values['code']] = $values;
        }
        // calculate totals
        $this->_totalRecords = count($this->collectedModules);
        $this->_setIsLoaded();

        // paginate and add items
        $from = ($this->getCurPage() - 1) * $this->getPageSize();
        $to = $from + $this->getPageSize() - 1;
        $isPaginated = $this->getPageSize() > 0;
        $cnt = 0;
        foreach ($this->collectedModules as $row) {
            $cnt++;
            if ($isPaginated && ($cnt < $from || $cnt > $to)) {
                continue;
            }
            $item = new $this->_itemObjectClass();
            $this->addItem($item->addData($row));
            if (!$item->hasId()) {
                $item->setId($cnt);
            }
        }

        return $this;
    }

    protected function fetchPackages($url)
    {
        $client = $this->httpClientFactory->create();
        $client->setUri($url);
        $client->setConfig(array('maxredirects'=>5, 'timeout'=>30));
        $client->setParameterGet('domain', $this->request->getHttpHost());
        $responseBody = $client->request()->getBody();

        $response = $this->jsonHelper->jsonDecode($responseBody);
        if (!is_array($response)) {
            throw new \Exception('Decoding failed');
        }

        if (!empty($response['packages'])) {
            $modules = [];
            foreach ($response['packages'] as $package => $info) {
                $versions = array_keys($info);
                $latestVersion = array_reduce(
                    $versions,
                    [$this, 'getNewerVersion']
                );

                $unset = [
                    'autoload',
                    'dist',
                    'license',
                    'require',
                    'source',
                    'support',
                    'version_normalized'
                ];
                foreach ($unset as $key) {
                    unset($info[$latestVersion][$key]);
                }

                $modules[$package] = $info[$latestVersion];
                $modules[$package]['code'] = $package;
            }
            return $modules;
        }

        if (!empty($response['includes'])) {
            // fetch for swissup.github.io/packages/include/all${sha1}.json
            // sha1 is taken from swissup.github.io/packages/packages.json
            $include = key($response['includes']);
            return $this->fetchPackages($this->getFeedUrl($include));
        }

        throw new \Exception('No packages found in response');
    }

    protected function getNewerVersion($carry, $item)
    {
        if (version_compare($carry, $item) === -1) {
            $carry = $item;
        }
        return $carry;
    }

    protected function getFeedUrl($suffix = 'packages.json')
    {
        $useHttps = $this->scopeConfig->getValue(
            self::XML_USE_HTTPS_PATH,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $url = $this->scopeConfig->getValue(
            self::XML_FEED_URL_PATH,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return ($useHttps ? 'https://' : 'http://') . $url . $suffix;
    }
}
