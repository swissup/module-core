<?php

namespace Swissup\Core\Model\Module;

class LicenseValidator
{
    const XML_USE_HTTPS_PATH    = 'swissup_core/license/use_https';
    const XML_VALIDATE_URL_PATH = 'swissup_core/license/url';

    /**
     * @var \Swissup\Core\Model\Module
     */
    protected $module;

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
     * @var \Magento\Framework\HTTP\Adapter\CurlFactory
     */
    protected $curlFactory;

    /**
     * Constructor
     *
     * @param \Swissup\Core\Model\Module $module
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
     */
    public function __construct(
        \Swissup\Core\Model\Module $module,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
    ) {
        $this->request = $request;
        $this->module = $module;
        $this->scopeConfig = $scopeConfig;
        $this->jsonHelper = $jsonHelper;
        $this->curlFactory = $curlFactory;
    }

    /**
     * Checks if module should be validated
     *
     * @return boolean
     */
    protected function canValidate()
    {
        return $this->module->getRemote() &&
            $this->module->getRemote()->getIdentityKeyLink();
    }

    /**
     * Validate module using curl request
     *
     * @return boolean|array
     */
    public function validate()
    {
        if (!$this->canValidate()) {
            return true;
        }

        $key = trim($this->module->getIdentityKey());
        if (empty($key)) {
            return ['error' => ['Identity key is required']];
        }

        // key format is: encoded_site:secret_key:optional_suffix
        $parts = explode(':', $key);
        if (count($parts) < 3) {
            return ['error' => ['Identity key is not valid']];
        }
        list($site, $secret, $suffix) = explode(':', $key);

        try {
            $client = $this->curlFactory->create();
            $client->setConfig(['maxredirects'=>5, 'timeout'=>30]);
            $client->write(
                \Zend_Http_Client::GET,
                $this->getUrl($site, [
                    'key' => $secret,
                    'suffix' => $suffix,
                ])
            );
            $responseBody = $client->read();
            $responseBody = \Zend_Http_Response::extractBody($responseBody);
            $client->close();
        } catch (\Exception $e) {
            return [
                'error' => [
                    'Response error: %1', $e->getMessage()
                ],
                'response' => $e->getTraceAsString()
            ];
        }

        return $this->parseResponse($responseBody);
    }

    /**
     * Parse server response
     *
     * @param string $response
     * <pre>
     * "{success: true}" or "{error: error_message}"
     * </pre>
     */
    protected function parseResponse($response)
    {
        try {
            $result = $this->jsonHelper->jsonDecode($response);
            if (!is_array($result)) {
                throw new \Exception('Decoding failed');
            }
            if (is_array($result) && isset($result['error'])) {
                $result['error'][0] = $this->convertMagento1xTranslation($result['error'][0]);
            }
        } catch (\Exception $e) {
            $result = [
                'error' => [
                    'Sorry, try again in five minutes. Validation response parsing error: %1',
                    $e->getMessage()
                ],
                'response' => $response
            ];
        }
        return $result;
    }

    /**
     * Convert Magento 1.x translation phrase into 2.x standard:
     *
     *     %s replaced with %1...%n
     *
     * @param  string $text
     * @return string
     */
    protected function convertMagento1xTranslation($text)
    {
        $parts = explode('%s', $text);
        $result = $parts[0];
        unset($parts[0]);
        foreach ($parts as $i => $part) {
            $result .= '%' . $i . $part;
        }
        return $result;
    }

    /**
     * Retrieve validation url according to the encoded $site
     *
     * @param string $site Base64 encoded site url
     * @param array $queryParams
     */
    protected function getUrl($site, array $queryParams = [])
    {
        $useHttps = $this->scopeConfig->getValue(
            self::XML_USE_HTTPS_PATH,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $url = $this->scopeConfig->getValue(
            self::XML_VALIDATE_URL_PATH,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $site = base64_decode($site);
        $url = ($useHttps ? 'https://' : 'http://') . rtrim($site, '/ ') . $url;
        $url .= '?' . http_build_query(array_merge($this->getQueryParams(), $queryParams));

        return $url;
    }

    /**
     * Prepare query parameters for the request
     *
     * @return array
     */
    protected function getQueryParams()
    {
        $purchaseCode = $this->module->getRemote()->getPurchaseCode();
        if (!$purchaseCode) {
            $purchaseCode = $this->module->getCode();
        }

        $domain = $this->module->getDomain();
        if (empty($domain)) {
            $domain = $this->request->getHttpHost();
        }

        $params = [
            'module' => $purchaseCode,
            'module_code' => $this->module->getCode(),
            'domain' => $domain,
        ];

        if ($this->module->getConfigSection()) {
            $params['config_section'] = $this->module->getConfigSection();
        }

        return $params;
    }
}
