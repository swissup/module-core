<?php

namespace Swissup\Core\Model\ComponentList\Loader;

class Local extends AbstractLoader
{
    /**
     * Component registrar
     *
     * @var \Magento\Framework\Component\ComponentRegistrarInterface
     */
    protected $registrar;

    /**
     * Component registrar
     *
     * @var \Magento\Framework\Json\DecoderInterface
     */
    protected $jsonDecoder;

    /**
     * Filesystem driver to allow reading of composer.json files
     *
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $filesystemDriver;

    /**
     * @param \Swissup\Core\Helper\Component                           $componentHelper
     * @param \Magento\Framework\Component\ComponentRegistrarInterface $registrar
     * @param \Magento\Framework\Json\DecoderInterface                 $jsonDecoder
     * @param \Magento\Framework\Filesystem\Driver\File                $filesystemDriver
     */
    public function __construct(
        \Swissup\Core\Helper\Component $componentHelper,
        \Magento\Framework\Component\ComponentRegistrarInterface $registrar,
        \Magento\Framework\Json\DecoderInterface $jsonDecoder,
        \Magento\Framework\Filesystem\Driver\File $filesystemDriver
    ) {
        parent::__construct($componentHelper);
        $this->registrar = $registrar;
        $this->jsonDecoder = $jsonDecoder;
        $this->filesystemDriver = $filesystemDriver;
    }

    public function getMapping()
    {
        return [
            'description' => 'description',
            'name' => 'name',
            'path' => 'path',
            'version' => 'version',
        ];
    }

    /**
     * Retrieve component paths and configs from composer.json files
     *
     * @return \Traversable
     */
    public function getComponentsInfo()
    {
        $components = [
            \Magento\Framework\Component\ComponentRegistrar::THEME,
            \Magento\Framework\Component\ComponentRegistrar::MODULE
        ];

        $modules = [];
        foreach ($components as $component) {
            $paths = $this->registrar->getPaths($component);
            foreach ($paths as $name => $path) {
                if (!strstr($name, 'Swissup')) {
                    continue;
                }

                $filePath = str_replace(
                    ['\\', '/'],
                    DIRECTORY_SEPARATOR,
                    "$path/composer.json"
                );
                try {
                    $config = $this->filesystemDriver->fileGetContents($filePath);
                    $config = $this->jsonDecoder->decode($config);
                    $config['path'] = $path;
                    $modules[$config['name']] = $config;
                } catch (\Exception $e) {
                    // skip module
                }
            }
        }
        return $modules;
    }
}
