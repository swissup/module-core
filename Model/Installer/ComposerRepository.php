<?php

namespace Swissup\Core\Model\Installer;

use Composer\Json\JsonFile;
use Composer\Json\JsonManipulator;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Composer\ComposerJsonFinder;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\HTTP\AsyncClient\Request;
use Magento\Framework\HTTP\AsyncClient\Response;
use Magento\Framework\HTTP\AsyncClientInterface;

class ComposerRepository
{
    const ID = 'swissuplabs';
    const TYPE = 'composer';
    const URL = 'https://ci.swissuplabs.com/api/packages.json';
    const HOSTNAME = 'ci.swissuplabs.com';

    private ?array $credentials = null;

    public function __construct(
        private ComposerJsonFinder $composerJsonFinder,
        private ScopeConfigInterface $scopeConfig,
        private AsyncClientInterface $asyncClient,
        private Composer $composer
    ) {
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->getKey() !== null;
    }

    /**
     * Add repository on top of the others (composer uses the first match)
     * keeping composer.json formatting.
     * Composer's JsonConfigSource is not used because since 2.9 it
     * converts "repositories" object into the list.
     *
     * @return void
     * @throws \RuntimeException
     */
    public function enable()
    {
        if ($this->isEnabled()) {
            return;
        }

        $path = $this->getComposerJsonPath();
        $json = (new JsonFile($path))->read();
        $manipulator = new JsonManipulator(file_get_contents($path));
        $config = [
            'type' => self::TYPE,
            'url' => self::URL,
        ];

        $repositories = $json['repositories'] ?? null;
        if ($repositories && array_keys($repositories) === range(0, count($repositories) - 1)) {
            // addListItem is not available in older composer versions
            $result = method_exists($manipulator, 'addListItem')
                && $manipulator->addListItem('repositories', $config, false);
        } else {
            $result = $manipulator->addSubNode('repositories', self::ID, $config, false);
        }

        if (!$result) {
            throw new \RuntimeException(sprintf('Unable to update %s file', $path));
        }

        file_put_contents($path, $manipulator->getContents());
    }

    /**
     * Remove repository keeping composer.json formatting
     *
     * @return void
     * @throws \RuntimeException
     */
    public function disable()
    {
        $key = $this->getKey();
        if ($key === null) {
            return;
        }

        $path = $this->getComposerJsonPath();
        $manipulator = new JsonManipulator(file_get_contents($path));

        if (is_int($key)) {
            // removeListItem is not available in older composer versions
            $result = method_exists($manipulator, 'removeListItem')
                && $manipulator->removeListItem('repositories', $key);
        } else {
            $result = $manipulator->removeSubNode('repositories', $key);
        }

        if (!$result) {
            throw new \RuntimeException(sprintf('Unable to update %s file', $path));
        }

        file_put_contents($path, $manipulator->getContents());
    }

    /**
     * Store domain is used as username for swissuplabs repository
     *
     * @return string
     */
    public function getDomain()
    {
        return (string) parse_url(
            (string) $this->scopeConfig->getValue('web/unsecure/base_url'),
            PHP_URL_HOST
        );
    }

    /**
     * Get credentials that composer uses: global auth.json,
     * project's auth.json and COMPOSER_AUTH merged together.
     *
     * "http-basic" is read as a whole because older composer versions
     * fail to parse "http-basic.ci.swissuplabs.com" key.
     *
     * @return array ['username' => string, 'password' => string]
     * @throws \RuntimeException
     */
    public function getCredentials()
    {
        if ($this->credentials === null) {
            $data = json_decode($this->composer->capture(['config', 'http-basic']), true);
            if (!is_array($data)) {
                throw new \RuntimeException('Unable to read http-basic credentials from composer config');
            }

            $this->credentials = [
                'username' => $data[self::HOSTNAME]['username'] ?? '',
                'password' => $data[self::HOSTNAME]['password'] ?? '',
            ];
        }

        return $this->credentials;
    }

    /**
     * Saved username or store domain for the new credentials
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->getCredentials()['username'] ?: $this->getDomain();
    }

    /**
     * Access keys are stored as space separated password
     *
     * @return string[]
     */
    public function getKeys()
    {
        $password = trim($this->getCredentials()['password']);

        return $password === '' ? [] : preg_split('/\s+/', $password);
    }

    /**
     * Get the site where the key was issued.
     * Key format: base64(domain):secret:
     *
     * @param string $key
     * @return string|null
     */
    public function getKeyDomain($key)
    {
        if (strpos($key, ':') === false) {
            return null;
        }

        $domain = base64_decode(strtok($key, ':'), true);
        if ($domain === false || !preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $domain)) {
            return null;
        }

        return $domain;
    }

    /**
     * Check the key alone (to not save invalid key along with valid ones)
     * and save it with all currently used keys.
     *
     * @param string $key
     * @return array Packages available with this key
     * @throws AuthenticationException
     * @throws \RuntimeException
     */
    public function addKey($key)
    {
        $key = trim($key);
        if ($key === '' || preg_match('/\s/', $key)) {
            throw new \RuntimeException('Access key cannot be empty or contain spaces');
        }

        $username = $this->getUsername();
        $packages = $this->getPackages($username, $key);

        $keys = $this->getKeys();
        if (!in_array($key, $keys, true)) {
            $keys[] = $key;
            $this->saveCredentials($username, implode(' ', $keys));
        }

        return $packages;
    }

    /**
     * Remove the key from the project's auth.json file
     *
     * @param string $key
     * @return boolean False if the key is still used from global auth.json
     * @throws \RuntimeException
     */
    public function removeKey($key)
    {
        $keys = $this->getKeys();
        if (!in_array($key, $keys, true)) {
            throw new \RuntimeException('Access key is not found');
        }

        $keys = array_values(array_diff($keys, [$key]));
        if ($keys) {
            // project's entry overrides global one, so the key is not used anymore
            $this->saveCredentials($this->getUsername(), implode(' ', $keys));
            return true;
        }

        if ($this->composer->hasAuthJson()) {
            $this->composer->capture(['config', '--unset', 'http-basic.' . self::HOSTNAME]);
            $this->credentials = null;
        }

        return !in_array($key, $this->getKeys(), true);
    }

    /**
     * Save credentials to the project's auth.json file.
     * Password must contain all keys, including the ones from global auth.json,
     * because project's entry overrides the global one.
     *
     * @param string $username
     * @param string $password
     * @return void
     * @throws \RuntimeException
     */
    public function saveCredentials($username, $password)
    {
        $this->composer->capture(['config', 'http-basic.' . self::HOSTNAME, $username, $password]);
        $this->credentials = null;
    }

    /**
     * Fetch packages available for the given credentials
     *
     * @param string $username
     * @param string $password
     * @return array
     * @throws AuthenticationException
     * @throws \RuntimeException
     */
    public function getPackages($username, $password)
    {
        $packages = $this->getPackagesBatch($username, [$password])[$password];

        if ($packages instanceof \Exception) {
            throw $packages;
        }

        return $packages;
    }

    /**
     * @param string $username
     * @param string[] $passwords
     * @return array [password => array|\Exception]
     */
    public function getPackagesBatch($username, array $passwords)
    {
        $requests = [];
        foreach ($passwords as $password) {
            $requests[$password] = ['url' => self::URL, 'password' => $password];
        }

        $responses = $this->fetchAll($username, $requests);
        $packages = [];
        $includeRequests = [];

        foreach ($responses as $password => $response) {
            if ($response instanceof \Exception) {
                $packages[$password] = $response;
                continue;
            }

            $packages[$password] = $response['packages'] ?? [];
            foreach (array_keys($response['includes'] ?? []) as $include) {
                $includeRequests[] = [
                    'url' => dirname(self::URL) . '/' . ltrim($include, '/'),
                    'password' => $password,
                ];
            }
        }

        foreach ($this->fetchAll($username, $includeRequests) as $i => $response) {
            $password = $includeRequests[$i]['password'];
            $packages[$password] = $response instanceof \Exception
                ? $response
                : array_merge($packages[$password], $response['packages'] ?? []);
        }

        return $packages;
    }

    /**
     * Send all the requests before reading any response, so that they are
     * performed in parallel.
     *
     * @param string $username
     * @param array $requests [id => ['url' => string, 'password' => string]]
     * @return array [id => array|\Exception]
     */
    private function fetchAll($username, array $requests)
    {
        $deferred = [];
        foreach ($requests as $id => $request) {
            $deferred[$id] = $this->asyncClient->request(new Request(
                $request['url'],
                Request::METHOD_GET,
                ['Authorization' => 'Basic ' . base64_encode($username . ':' . $request['password'])],
                null
            ));
        }

        $responses = [];
        foreach ($deferred as $id => $response) {
            try {
                $responses[$id] = $this->parse($username, $requests[$id]['url'], $response->get());
            } catch (\Exception $e) {
                $responses[$id] = $e;
            }
        }

        return $responses;
    }

    /**
     * @param string $username
     * @param string $url
     * @param Response $response
     * @return array
     * @throws AuthenticationException
     * @throws \RuntimeException
     */
    private function parse($username, $url, Response $response)
    {
        $status = $response->getStatusCode();
        if ($status === 401 || $status === 403) {
            throw new AuthenticationException(__(
                'Access denied for "%1". Make sure the domain is activated and the key is correct.',
                $username
            ));
        }

        if ($status !== 200) {
            throw new \RuntimeException(sprintf('%s returned %s response code', $url, $status));
        }

        $data = json_decode($response->getBody(), true);
        if (!is_array($data)) {
            throw new \RuntimeException(sprintf('%s returned malformed response', $url));
        }

        return $data;
    }

    /**
     * Find repository in composer.json by its hostname.
     * The name may differ from self::ID if repo was added manually.
     *
     * @return string|int|null Object key or list index
     */
    private function getKey()
    {
        $json = (new JsonFile($this->getComposerJsonPath()))->read();

        foreach ($json['repositories'] ?? [] as $key => $repo) {
            if (!is_array($repo) || empty($repo['url'])) {
                continue;
            }

            if (parse_url($repo['url'], PHP_URL_HOST) === self::HOSTNAME) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @return string
     */
    private function getComposerJsonPath()
    {
        return $this->composerJsonFinder->findComposerJson();
    }
}
