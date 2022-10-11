<?php

namespace Swissup\Core\Model\ComponentList\Loader;

abstract class AbstractLoader implements LoaderInterface
{
    /**
     * @var \Swissup\Core\Helper\Component
     */
    protected $componentHelper;

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var bool
     */
    protected $isLoaded = false;

    /**
     * @param \Swissup\Core\Helper\Component $componentHelper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Swissup\Core\Helper\Component $componentHelper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->componentHelper = $componentHelper;
        $this->logger = $logger;
    }

    /**
     * Load components and return them as array
     *
     * @return array
     */
    public function load()
    {
        if ($this->isLoaded()) {
            return $this->items;
        }

        $this->setIsLoaded(true);

        try {
            $components = $this->getComponentsInfo();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            return [];
        }

        foreach ($components as $name => $config) {
            $code = $this->componentHelper->convertPackageNameToModuleName(
                $config['name']
            );

            $this->items[$code]['code'] = $code;
            foreach ($this->getMapping() as $source => $destination) {
                $value = $config;
                foreach (explode('.', $source) as $key) {
                    if (!isset($value[$key])) {
                        continue 2;
                    }
                    $value = $value[$key];
                }

                if (is_array($value)) {
                    $value = implode(',', $value);
                }
                $this->items[$code][$destination] = $value;
            }
        }
        return $this->items;
    }

    public function getItems()
    {
        return $this->load();
    }

    /**
     * @return bool
     */
    public function isLoaded()
    {
        return $this->isLoaded;
    }

    /**
     * @param bool $flag
     * @return $this
     */
    protected function setIsLoaded($flag = true)
    {
        $this->isLoaded = $flag;
        return $this;
    }

    public function getItemById($id)
    {
        $this->load();

        if (!isset($this->items[$id])) {
            return false;
        }
        return $this->items[$id];
    }
}
