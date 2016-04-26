<?php

namespace Swissup\Core\Model\ComponentList\Loader;

abstract class AbstractLoader implements LoaderInterface
{
    /**
     * @var \Swissup\Core\Helper\Component
     */
    protected $componentHelper;

    protected $items = [];

    protected $isLoaded = false;

    public function __construct(
        \Swissup\Core\Helper\Component $componentHelper
    ) {
        $this->componentHelper = $componentHelper;
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
        } catch (Exception $e) {
            return [];
        }

        foreach ($components as list($name, $config)) {
            $code = $this->componentHelper->convertPackageNameToModuleName(
                $config['name']
            );

            $this->items[$code]['code'] = $code;
            foreach ($this->getMapping() as $source => $destination) {
                if (!isset($config[$source])) {
                    continue;
                }
                $this->items[$code][$destination] = $config[$source];
            }
        }
        return $this->items;
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
