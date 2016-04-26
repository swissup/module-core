<?php

namespace Swissup\Core\Model\ComponentList\Loader;

interface LoaderInterface
{
    /**
     * Retrieve mapping rules, to use while filling resulting array
     *
     * @return array
     */
    public function getMapping();

    /**
     * Retrieve array of swissup components:
     * [
     *     [name, [config]]
     *     ...
     * ]
     *
     * @return \Traversable
     */
    public function getComponentsInfo();
}
