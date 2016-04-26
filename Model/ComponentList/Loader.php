<?php

namespace Swissup\Core\Model\ComponentList;

class Loader
{
    /**
     * @var \Swissup\Core\Model\ComponentList\Loader\Local
     */
    protected $localLoader;

    /**
     * @var \Swissup\Core\Model\ComponentList\Loader\Remote
     */
    protected $remoteLoader;

    /**
     * @param \Swissup\Core\Model\ComponentList\Loader\Local  $localLoader
     * @param \Swissup\Core\Model\ComponentList\Loader\Remote $remoteLoader
     */
    public function __construct(
        \Swissup\Core\Model\ComponentList\Loader\Local $localLoader,
        \Swissup\Core\Model\ComponentList\Loader\Remote $remoteLoader
    ) {
        $this->localLoader = $localLoader;
        $this->remoteLoader = $remoteLoader;
    }

    /**
     * Load Swissup components information, using local and remote data
     *
     * @return array
     */
    public function load()
    {
        return array_replace_recursive(
            $this->localLoader->load(),
            $this->remoteLoader->load()
        );
    }
}
