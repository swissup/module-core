<?php

namespace Swissup\Core\Model\Module;

/**
 * Collect all SwissupUpgrades of requested module and run them one by one
 */
class Installer
{
    /**
     * @var array
     */
    protected $upgrades = [];

    /**
     * @var \Swissup\Core\Model\Module
     */
    protected $module;

    /**
     * @var \Swissup\Core\Model\ModuleFactory
     */
    protected $moduleFactory;

    /**
     * @var \Swissup\Core\Model\Module\MessageLogger
     */
    protected $messageLogger;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Constructor
     *
     * @param \Swissup\Core\Model\Module $module
     * @param \Swissup\Core\Model\ModuleFactory $moduleFactory
     * @param \Swissup\Core\Model\Module\MessageLogger $messageLogger
     */
    public function __construct(
        \Swissup\Core\Model\Module $module,
        \Swissup\Core\Model\ModuleFactory $moduleFactory,
        \Swissup\Core\Model\Module\MessageLogger $messageLogger,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->module = $module;
        $this->moduleFactory = $moduleFactory;
        $this->messageLogger = $messageLogger;
        $this->objectManager = $objectManager;
    }

    /**
     * 1. Run dependent modules upgrades
     * 2. Run module upgrades on installed stores
     * 3. Run module upgrades on new stores
     *
     * @return void
     */
    public function up()
    {
        $oldStores = $this->module->getOldStores();
        $newStores = $this->module->getNewStores();
        if (!count($oldStores) && !count($newStores)) {
            return;
        }

        foreach ($this->module->getDepends() as $moduleCode) {
            if (0 !== strpos($moduleCode, 'Swissup')) {
                continue;
            }
            $this->getModuleObject($moduleCode)->up();
        }

        $saved = false;
        // upgrade currently installed version to the latest data_version
        if (count($oldStores)) {
            foreach ($this->getUpgradesToRun() as $version => $filename) {
                $this->resolve($filename)
                    ->setStoreIds($oldStores)
                    ->upgrade();
                $this->module->setDataVersion($version)->save();
                $saved = true;
            }
        }

        // install module to the new stores
        if (count($newStores)) {
            foreach ($this->getUpgradesToRun(0) as $version => $filename) {
                $this->resolve($filename)
                    ->setStoreIds($newStores)
                    ->upgrade();
                $this->module->setDataVersion($version)->save();
                $saved = true;
            }
        }

        if (!$saved) {
            $this->module->save();
        }
    }

    /**
     * Retrieve singleton instance of error logger, used in upgrade file
     * to write errors and module controller to read them.
     *
     * @return \Swissup\Core\Model\Module\MessageLogger
     */
    public function getMessageLogger()
    {
        return $this->messageLogger;
    }

    /**
     * Checks is the upgrades directory is exists in the module
     *
     * @return boolean
     */
    public function hasUpgradesDir()
    {
        $dir = $this->getUpgradesDir();
        return $dir && is_readable($dir);
    }

    /**
     * Retrieve the list of not installed upgrade filenames sorted by version_compare.
     * The list could be filtered by optional 'from' parameter.
     * This parameter is usefull, when the module is installed previously
     *
     * @param string $from
     * @return array
     */
    public function getUpgradesToRun($from = null)
    {
        if (null === $from) {
            $from = $this->module->getDataVersion();
        }
        $upgrades = array();
        foreach ($this->getUpgradeFiles() as $version => $filename) {
            if (version_compare($from, $version) >= 0) {
                continue;
            }
            $upgrades[$version] = $filename;
        }
        return $upgrades;
    }

    /**
     * Retrive the list of all module upgrades
     * sorted by version_compare
     *
     * [
     *     1.0.0 => 1.0.0_filename,
     *     1.1.0 => 1.1.0_filename2
     * ]
     *
     * @return array
     */
    public function getUpgradeFiles()
    {
        if ($this->upgrades) {
            return $this->upgrades;
        }

        if (!$this->hasUpgradesDir()) {
            return [];
        }

        try {
            $dir = new \DirectoryIterator($this->getUpgradesDir());
        } catch (\Exception $e) {
            return [];
        }

        $upgrades = [];
        foreach ($dir as $file) {
            $file = $file->getFilename();
            if (false === strstr($file, '.php')) {
                continue;
            }
            list($version, $part) = explode('_', $file, 2);
            $upgrades[$version] = substr($file, 0, -4);
        }

        uksort($upgrades, 'version_compare');
        $this->upgrades = $upgrades;

        return $upgrades;
    }

    /**
     * Returns upgrade class instance by given file basename
     *
     * Resolving examples:
     * 1.0.0_initial_installation   [Vendor\Module]\Upgrades\InitialInstallation
     * 1.1.0_add_used_id_column     [Vendor\Module]\Upgrades\AddUserIdColumn
     *
     * @param string $filename
     * @return \Swissup\Core\Model\Module\Upgrade
     */
    public function resolve($filename)
    {
        require_once $this->getUpgradesDir() . "/{$filename}.php";

        $className = implode(' ', array_slice(explode('_', $filename), 1));
        $className = str_replace(' ', '', ucwords($className));
        $namespace = str_replace('_', '\\', $this->module->getCode()) . '\\Upgrades';
        $className = $namespace . '\\' . $className;

        $upgrade = $this->objectManager->create($className);
        $upgrade->setMessageLogger($this->getMessageLogger());

        return $upgrade;
    }

    /**
     * Retrieve module upgrade directory
     *
     * @return string
     */
    public function getUpgradesDir()
    {
        if (!$this->module->getLocal() || !$this->module->getLocal()->getPath()) {
            return null;
        }
        return $this->module->getLocal()->getPath() . '/Upgrades';
    }

    /**
     * Returns loded module object with copied new_store_ids and skip_upgrade
     * instructions into it
     *
     * @return Swissup\Core\Model\Module
     */
    protected function getModuleObject($code)
    {
        $module = $this->moduleFactory->create()
            ->load($code)
            ->setNewStores($this->module->getNewStores());

        if (!$module->getIdentityKey()) {
            $module->setIdentityKey($this->module->getIdentityKey());
        }

        return $module;
    }
}
