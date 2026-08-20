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

    protected $items = [];

    protected $isLoaded = false;

    private bool $offlineMode = false;

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
        if ($this->isLoaded()) {
            return $this->items;
        }

        $this->setIsLoaded(true);
        $this->items = array_replace_recursive(
            $this->localLoader->load(),
            $this->remoteLoader->load()
        );

        foreach ($this->items as &$item) {
            $item['is_installed'] = !empty($item['version']);
            $item['is_outdated'] = $item['is_installed']
                && !empty($item['latest_version'])
                && version_compare($item['version'], $item['latest_version'], '<');
        }
        unset($item);

        return $this->items;
    }

    /**
     * Discard the remote data staleness check, so that the next load re-fetches it
     *
     * @return $this
     */
    public function refresh()
    {
        $this->remoteLoader->refresh();
        $this->items = [];
        $this->setIsLoaded(false);

        return $this;
    }

    public function setOfflineMode($flag = true)
    {
        if ((bool) $flag !== $this->offlineMode) {
            $this->offlineMode = (bool) $flag;
            $this->remoteLoader->setOfflineMode($flag);
            $this->items = [];
            $this->setIsLoaded(false);
        }

        return $this;
    }

    public function getLastCheckTime()
    {
        return $this->remoteLoader->getLastCheckTime();
    }

    public function isVersionCheckRequired()
    {
        return $this->remoteLoader->isVersionCheckRequired();
    }

    public function getItems()
    {
        return $this->load();
    }

    public function getInstalledItems()
    {
        return array_filter($this->getItems(), function ($item) {
            return $item['is_installed'];
        });
    }

    public function getOutdatedItems()
    {
        return array_filter($this->getItems(), function ($item) {
            return $item['is_outdated'];
        });
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
