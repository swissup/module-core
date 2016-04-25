<?php

namespace Swissup\Core\Model\ResourceModel\Module;

use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem\DriverInterface;

class LocalCollection extends \Magento\Framework\Data\Collection
{
    /**
     * Component registrar
     *
     * @var ComponentRegistrarInterface
     */
    protected $registrar;

    /**
     * Component registrar
     *
     * @var DecoderInterface
     */
    protected $jsonDecoder;

    /**
     * Filesystem driver to allow reading of composer.json files
     *
     * @var DriverInterface
     */
    protected $filesystemDriver;

    /**
     * @var string
     */
    protected $_itemObjectClass = 'Swissup\Core\Model\Module';

    /**
     * Constructor
     *
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        ComponentRegistrarInterface $registrar,
        DecoderInterface $jsonDecoder,
        DriverInterface $filesystemDriver
    ) {
        parent::__construct($entityFactory);
        $this->registrar = $registrar;
        $this->jsonDecoder = $jsonDecoder;
        $this->filesystemDriver = $filesystemDriver;
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

        $modules = [];
        foreach ($this->fetchPackages() as list($path, $config)) {
            $config = $this->jsonDecoder->decode($config);
            $code = $this->convertPackageNameToModuleName($config['name']);
            $modules[$code] = [
                'code' => $code,
                'name' => $config['name'],
                'path' => $path,
                'local_version' => $config['version'],
            ];
        }

        // calculate totals
        $this->_totalRecords = count($modules);
        $this->_setIsLoaded();

        // paginate and add items
        $from = ($this->getCurPage() - 1) * $this->getPageSize();
        $to = $from + $this->getPageSize() - 1;
        $isPaginated = $this->getPageSize() > 0;
        $cnt = 0;
        foreach ($modules as $row) {
            $cnt++;
            if ($isPaginated && ($cnt < $from || $cnt > $to)) {
                continue;
            }
            $item = $this->_entityFactory->create($this->_itemObjectClass);
            $this->addItem($item->addData($row));
        }

        return $this;
    }

    protected function convertPackageNameToModuleName($packageName)
    {
        list($vendor, $name) = explode('/', $packageName, 2);
        $name = str_replace('-', ' ', $name);
        $name = str_replace(' ', '', ucwords($name));
        return ucfirst($vendor) . '_' . $name;
    }

    protected function fetchPackages()
    {
        $components = [
            ComponentRegistrar::THEME,
            ComponentRegistrar::MODULE
        ];

        foreach ($components as $component) {
            $paths = $this->registrar->getPaths($component);
            foreach ($paths as $name => $path) {
                if (!strstr($name, 'Swissup')) {
                    continue;
                }
                $filePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, "$path/composer.json");
                yield [$path, $this->filesystemDriver->fileGetContents($filePath)];
            }
        }
    }
}
